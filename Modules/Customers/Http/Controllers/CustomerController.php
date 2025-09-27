<?php

namespace Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Customers\Entities\Customer;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractClaim;
use Modules\Contracts\Entities\ContractInstallment;
use Modules\Lookups\Entities\ContractStatus;
use Modules\Lookups\Entities\CustomerStatus;
use Modules\Lookups\Entities\Nationality;
use Modules\Lookups\Entities\Title;
use Modules\Customers\Services\CustomerDetailsService;
use App\Support\InstallmentPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Customers\Http\Requests\StoreCustomerRequest;
use Modules\Customers\Http\Requests\UpdateCustomerRequest;
use App\Models\OfficeTransaction;
use Modules\Investors\Entities\InvestorTransaction;
use Modules\Investors\Support\InvestorContractPaymentAggregator;
use Modules\Lookups\Entities\TransactionStatus;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Customers\Exports\CustomersExport;


class CustomerController extends Controller
{
    // عرض كل العملاء
    public function index(Request $request)
    {
        $periodContext = $this->resolveInstallmentPeriodContext($request);
        $periodStart   = $periodContext['start']->copy();
        $periodEnd     = $periodContext['end']->copy();
        $periodMonths  = $this->periodMonthOptions();
        $periodYears   = $this->periodYearOptions();

        $query = Customer::query()->with(['customerStatus:id,name']);

        // ===== بحث باسم العميل فقط =====
        $nameQ = trim((string) $request->input('customer_q', ''));
        if ($nameQ !== '') {
            $query->where('customers.name', 'like', '%' . $nameQ . '%');
        } else {
            // فلاتر إضافية اختيارية
            $query->when($request->filled('national_id'),
                    fn($q) => $q->where('national_id', 'like', '%'.trim($request->national_id).'%'))
                  ->when($request->filled('phone'),
                    fn($q) => $q->where('phone', 'like', '%'.trim($request->phone).'%'));
        }

        // 20 صف في الصفحة
        $customers = $query->latest()->paginate(20)->withQueryString();

        $listMetrics = $this->buildCustomersListMetrics(
            $customers->getCollection()->pluck('id')->all(),
            $periodStart,
            $periodEnd
        );

        $customers->setCollection(
            $customers->getCollection()->map(function (Customer $customer) use ($listMetrics) {
                $metrics = $listMetrics[$customer->id] ?? [
                    'active_contracts'       => 0,
                    'remaining_sum'          => 0.0,
                    'unpaid_month_count'     => 0,
                    'unpaid_month_sum'       => 0.0,
                ];

                $customer->active_contracts_count = (int) ($metrics['active_contracts'] ?? 0);
                $customer->remaining_balance_total = (float) ($metrics['remaining_sum'] ?? 0.0);
                $customer->unpaid_installments_this_month = (int) ($metrics['unpaid_month_count'] ?? 0);
                $customer->unpaid_amount_this_month = (float) ($metrics['unpaid_month_sum'] ?? 0.0);

                return $customer;
            })
        );

        // كروت عامة (غير متأثرة بالفلاتر)
        $customersTotalAll = Customer::count();

        // تقدير "النشط" من جدول العقود
        $endedStatusNames = ['منتهي','منتهى','سداد مبكر','سداد مُبكر','سداد مبكّر','Completed','Early Settlement'];

        $endedStatusIds = [];
        if (class_exists(\Modules\Lookups\Entities\ContractStatus::class)) {
            $endedStatusIds = \Modules\Lookups\Entities\ContractStatus::query()
                ->whereIn('name', $endedStatusNames)
                ->pluck('id')->all();
        }

        // اكتشاف عمود الحالة
        $statusIdCol = null;
        foreach (['contract_status_id', 'status_id', 'state_id'] as $col) {
            if (Schema::hasColumn('contracts', $col)) { $statusIdCol = $col; break; }
        }
        $statusNameCol = null;
        foreach (['status', 'state', 'contract_status'] as $col) {
            if (Schema::hasColumn('contracts', $col)) { $statusNameCol = $col; break; }
        }

        $activeCustomersTotalAll = Customer::query()
            ->whereHas('contracts', function ($c) use ($statusIdCol, $statusNameCol, $endedStatusIds, $endedStatusNames) {
                if ($statusIdCol && !empty($endedStatusIds)) {
                    $c->whereNotIn($statusIdCol, $endedStatusIds);
                } elseif ($statusNameCol) {
                    $c->whereNotIn($statusNameCol, $endedStatusNames);
                } elseif (Schema::hasColumn('contracts', 'is_closed')) {
                    $c->where('is_closed', 0);
                } elseif (Schema::hasColumn('contracts', 'closed_at')) {
                    $c->whereNull('closed_at');
                } else {
                    $c->whereRaw('1=1');
                }
            })
            ->count();

        $newCustomersThisMonthAll = Customer::whereBetween('created_at', [now()->startOfMonth(), now()])->count();
        $newCustomersThisWeekAll  = Customer::whereBetween('created_at', [now()->startOfWeek(),  now()])->count();

        // (اختياري) لو بتستخدمهم في فلاتر إضافية
        $nationalities = class_exists(Nationality::class)
            ? Nationality::select('id','name')->orderBy('name')->get()
            : collect();
        $titles = class_exists(Title::class)
            ? Title::select('id','name')->orderBy('name')->get()
            : collect();

        return view('customers::index', compact(
            'customers',
            'customersTotalAll',
            'activeCustomersTotalAll',
            'newCustomersThisMonthAll',
            'newCustomersThisWeekAll',
            'nationalities',
            'titles',
            'periodContext',
            'periodMonths',
            'periodYears'
        ));
    }

    public function export()
    {
        $timestamp = now()->format('Y_m_d_His');

        return Excel::download(new CustomersExport(), "customers_export_{$timestamp}.xlsx");
    }

    public function refreshStatuses(Request $request)
    {
        $statusIds = $this->resolveCustomerStatusIds();

        if (!array_filter($statusIds)) {
            return redirect()
                ->route('customers.index')
                ->withErrors([
                    'customers-refresh' => __('customers::messages.Unable to refresh customer statuses because default statuses are missing.'),
                ]);
        }

        $contractStatusLookup = $this->contractStatusNameLookup();
        $contractStatusGroups = $this->contractStatusGroups();

        $updatedCount = 0;

        Customer::query()
            ->select(['id', 'customer_status_id'])
            ->with(['contracts:id,customer_id,contract_status_id'])
            ->chunkById(200, function ($customers) use (&$updatedCount, $statusIds, $contractStatusLookup, $contractStatusGroups) {
                foreach ($customers as $customer) {
                    $newStatusId = $this->determineCustomerStatusId($customer, $statusIds, $contractStatusLookup, $contractStatusGroups);

                    if ($newStatusId && $newStatusId !== (int) $customer->customer_status_id) {
                        $customer->customer_status_id = $newStatusId;
                        $customer->save();

                        $updatedCount++;
                    }
                }
            });

        $messageKey = $updatedCount > 0
            ? 'customers::messages.Customer statuses refreshed (:count updated).'
            : 'customers::messages.Customer statuses refreshed (no changes).';

        return redirect()
            ->route('customers.index')
            ->with('success', __($messageKey, ['count' => $updatedCount]));
    }

    public function dashboard(Request $request)
    {
        [$statusIdCol, $statusNameCol] = $this->detectContractStatusColumns();
        $endedStatusNames = $this->endedContractStatusNames();
        $endedStatusIds   = $this->resolveContractStatusIds($endedStatusNames);

        $periodContext = $this->resolveInstallmentPeriodContext($request);
        $periodStart   = $periodContext['start']->copy();
        $periodEnd     = $periodContext['end']->copy();

        $totalCustomers = Customer::count();

        $activeCustomers = Customer::query()
            ->whereHas('contracts', function ($query) use ($statusIdCol, $statusNameCol, $endedStatusIds, $endedStatusNames) {
                $this->applyActiveContractFilter($query, $endedStatusIds, $endedStatusNames, $statusIdCol, $statusNameCol);
            })
            ->count();

        $customersWithContracts = Customer::has('contracts')->count();
        $customersWithoutContracts = max($totalCustomers - $customersWithContracts, 0);
        $inactiveCustomers = max($totalCustomers - $activeCustomers, 0);

        $now        = Carbon::now();
        $today      = $now->copy()->startOfDay();
        $weekStart  = $now->copy()->startOfWeek();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd   = $now->copy()->endOfMonth();

        $newThisMonth = Customer::whereBetween('created_at', [$monthStart, $now])->count();
        $newThisWeek  = Customer::whereBetween('created_at', [$weekStart, $now])->count();

        $aggregationBuilder = fn () => $this->buildCustomerInstallmentAggregation(
            $today,
            $periodStart,
            $periodEnd,
            $statusIdCol,
            $statusNameCol,
            $endedStatusIds,
            $endedStatusNames
        );

        $overdueCustomersCount = Customer::query()
            ->joinSub($aggregationBuilder(), 'inst', function ($join) {
                $join->on('customers.id', '=', 'inst.customer_id');
            })
            ->where('inst.overdue_total', '>', 0)
            ->count();

        $dueThisMonthCustomersCount = Customer::query()
            ->joinSub($aggregationBuilder(), 'inst', function ($join) {
                $join->on('customers.id', '=', 'inst.customer_id');
            })
            ->where('inst.due_this_month_total', '>', 0)
            ->count();

        $financialRow = DB::query()
            ->fromSub($aggregationBuilder(), 'inst')
            ->selectRaw(
                'SUM(unpaid_total) as unpaid_sum, '
                . 'SUM(overdue_total) as overdue_sum, '
                . 'SUM(due_this_month_total) as due_this_month_sum'
            )
            ->first();

        $financialTotals = [
            'unpaid_total'        => round((float) ($financialRow->unpaid_sum ?? 0), 2),
            'overdue_total'       => round((float) ($financialRow->overdue_sum ?? 0), 2),
            'due_this_month_total'=> round((float) ($financialRow->due_this_month_sum ?? 0), 2),
        ];

        $pct = static function (int $total, int $value): float {
            if ($total <= 0) {
                return 0.0;
            }

            return round(($value / $total) * 100, 1);
        };

        $totals = [
            'total'            => $totalCustomers,
            'active'           => $activeCustomers,
            'inactive'         => $inactiveCustomers,
            'withContracts'    => $customersWithContracts,
            'withoutContracts' => $customersWithoutContracts,
            'newMonth'         => $newThisMonth,
            'newWeek'          => $newThisWeek,
            'overdue'          => $overdueCustomersCount,
            'dueThisMonth'     => $dueThisMonthCustomersCount,
        ];

        $percentages = [
            'active'           => $pct($totalCustomers, $activeCustomers),
            'inactive'         => $pct($totalCustomers, $inactiveCustomers),
            'withContracts'    => $pct($totalCustomers, $customersWithContracts),
            'withoutContracts' => $pct($totalCustomers, $customersWithoutContracts),
            'overdue'          => $pct($totalCustomers, $overdueCustomersCount),
        ];

        $statusCounts = Customer::selectRaw('customer_status_id, COUNT(*) as total')
            ->groupBy('customer_status_id')
            ->get();

        $statusNames = class_exists(CustomerStatus::class)
            ? CustomerStatus::whereIn('id', $statusCounts->pluck('customer_status_id')->filter()->all())
                ->pluck('name', 'id')
            : collect();

        $statusBreakdown = $statusCounts->map(function ($row) use ($statusNames, $totalCustomers) {
            $id = $row->customer_status_id;
            $count = (int) ($row->total ?? 0);

            $name = $id
                ? ($statusNames[$id] ?? __('customers::messages.Undefined'))
                : __('customers::messages.Undefined');

            return [
                'id'    => $id ? (int) $id : null,
                'name'  => $name,
                'count' => $count,
                'pct'   => $totalCustomers > 0 ? round(($count / $totalCustomers) * 100, 1) : 0.0,
            ];
        })->sortByDesc('count')->values();

        $statusChartLabels = $statusBreakdown->pluck('name')->all();
        $statusChartData   = $statusBreakdown->pluck('count')->all();

        $monthsBack = 11;
        $rangeStart = $monthStart->copy()->subMonths($monthsBack);

        $monthlyRaw = Customer::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as ym, COUNT(*) as total')
            ->where('created_at', '>=', $rangeStart)
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('total', 'ym');

        $monthlyLabels = [];
        $monthlyValues = [];

        for ($i = 0; $i <= $monthsBack; $i++) {
            $date = $rangeStart->copy()->addMonths($i);
            $key  = $date->format('Y-m');

            $monthlyLabels[] = $date->locale(app()->getLocale())->translatedFormat('M Y');
            $monthlyValues[] = (int) ($monthlyRaw[$key] ?? 0);
        }

        $monthlyRegistrations = [
            'labels' => $monthlyLabels,
            'values' => $monthlyValues,
            'range'  => [
                'from' => $rangeStart->format('Y-m-d'),
                'to'   => $monthEnd->format('Y-m-d'),
            ],
        ];

        $statusBreakdownArray = $statusBreakdown->all();

        $topContractCustomers = Customer::query()
            ->with('customerStatus:id,name')
            ->withCount('contracts')
            ->withCount(['contracts as active_contracts_count' => function ($query) use ($statusIdCol, $statusNameCol, $endedStatusIds, $endedStatusNames) {
                $this->applyActiveContractFilter($query, $endedStatusIds, $endedStatusNames, $statusIdCol, $statusNameCol);
            }])
            ->orderByDesc('active_contracts_count')
            ->orderByDesc('contracts_count')
            ->orderBy('name')
            ->take(5)
            ->get()
            ->filter(fn ($customer) => (int) $customer->active_contracts_count > 0)
            ->map(function (Customer $customer) {
                return [
                    'id'                => (int) $customer->id,
                    'name'              => $customer->name,
                    'status'            => optional($customer->customerStatus)->name,
                    'active_contracts'  => (int) $customer->active_contracts_count,
                    'total_contracts'   => (int) $customer->contracts_count,
                    'phone'             => $customer->phone,
                ];
            })
            ->values();

        $topOutstanding = Customer::query()
            ->select('customers.id', 'customers.name', 'inst.unpaid_total', 'inst.overdue_total', 'inst.due_this_month_total')
            ->joinSub($aggregationBuilder(), 'inst', function ($join) {
                $join->on('customers.id', '=', 'inst.customer_id');
            })
            ->where('inst.unpaid_total', '>', 0)
            ->orderByDesc('inst.unpaid_total')
            ->take(5)
            ->get()
            ->map(function ($row) {
                return [
                    'id'                  => (int) $row->id,
                    'name'                => $row->name,
                    'unpaid_total'        => (float) $row->unpaid_total,
                    'overdue_total'       => (float) $row->overdue_total,
                    'due_this_month_total'=> (float) $row->due_this_month_total,
                ];
            })
            ->values();

        $topNationalitiesRaw = Customer::selectRaw('nationality_id, COUNT(*) as total')
            ->whereNotNull('nationality_id')
            ->groupBy('nationality_id')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        $nationalityNames = class_exists(Nationality::class)
            ? Nationality::whereIn('id', $topNationalitiesRaw->pluck('nationality_id')->all())->pluck('name', 'id')
            : collect();

        $topNationalities = $topNationalitiesRaw->map(function ($row) use ($nationalityNames, $totalCustomers) {
            $id = (int) $row->nationality_id;
            $count = (int) ($row->total ?? 0);

            return [
                'id'    => $id,
                'name'  => $nationalityNames[$id] ?? __('customers::messages.Undefined'),
                'count' => $count,
                'pct'   => $totalCustomers > 0 ? round(($count / $totalCustomers) * 100, 1) : 0.0,
            ];
        })->values();

        $recentCustomers = Customer::query()
            ->with('customerStatus:id,name')
            ->latest()
            ->take(8)
            ->get()
            ->map(function (Customer $customer) {
                return [
                    'id'        => (int) $customer->id,
                    'name'      => $customer->name,
                    'status'    => optional($customer->customerStatus)->name,
                    'created_at'=> optional($customer->created_at)->format('Y-m-d'),
                    'phone'     => $customer->phone,
                ];
            })
            ->values();

        return view('customers::dashboard', [
            'totals'                => $totals,
            'percentages'           => $percentages,
            'statusBreakdown'       => $statusBreakdownArray,
            'statusChartLabels'     => $statusChartLabels,
            'statusChartData'       => $statusChartData,
            'monthlyRegistrations'  => $monthlyRegistrations,
            'topContractCustomers'  => $topContractCustomers,
            'topOutstanding'        => $topOutstanding,
            'topNationalities'      => $topNationalities,
            'recentCustomers'       => $recentCustomers,
            'financialTotals'       => $financialTotals,
            'periodContext'         => $periodContext,
        ]);
    }

    // صفحة إنشاء عميل جديد
    public function create()
    {
        $titles = Title::all();
        $nationalities = Nationality::all();
        $customerStatuses = CustomerStatus::select('id', 'name')->orderBy('name')->get();
        $statusIds = $this->resolveCustomerStatusIds();
        $defaultCustomerStatusId = $statusIds['new'] ?? null;

        return view('customers::create', compact(
            'titles',
            'nationalities',
            'customerStatuses',
            'defaultCustomerStatusId'
        ));
    }

    // حفظ عميل جديد
    public function store(StoreCustomerRequest $request)
    {
        $validated = $request->validated();

        if (blank($validated['customer_status_id'] ?? null)) {
            $statusIds = $this->resolveCustomerStatusIds();
            if (!blank($statusIds['new'] ?? null)) {
                $validated['customer_status_id'] = $statusIds['new'];
            }
        }

        // رفع صورة الهوية
        if ($request->hasFile('id_card_image')) {
            $validated['id_card_image'] = $request->file('id_card_image')->store('customer_id_cards', 'public');
        }

        Customer::create($validated);

        return redirect()->route('customers.index')->with('success', 'تم إضافة العميل بنجاح.');
    }

    // عرض تفاصيل عميل
    public function show(Customer $customer, Request $request, CustomerDetailsService $detailsSvc)
    {
        $customer->loadMissing(['customerStatus:id,name', 'title:id,name', 'nationality:id,name']);

        // helpers لتنظيف المُدخلات
        $parseIds = function ($value): array {
            if (is_string($value)) {
                $value = array_map('trim', explode(',', $value));
            }
            $value = is_array($value) ? $value : [];
            // أرقام صحيحة موجبة وفريدة
            $value = array_values(array_unique(array_filter(array_map(fn ($v) => (int)$v, $value), fn ($v) => $v > 0)));
            return $value;
        };

        $parseDate = function ($value): ?string {
            if (empty($value)) return null;
            try {
                // نقبل أي صيغة قابلة للبارس ونخرج Y-m-d
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        };

        // فلاتر مُنظَّفة من الكويري
        $filters = [
            'status_ids' => $parseIds($request->input('status_ids', [])),
            'from_start' => $parseDate($request->input('from_start')),
            'to_start'   => $parseDate($request->input('to_start')),
            'from_due'   => $parseDate($request->input('from_due')),
            'to_due'     => $parseDate($request->input('to_due')),
        ];

        $periodContext = $this->resolveInstallmentPeriodContext($request);
        $periodMonths  = $this->periodMonthOptions();
        $periodYears   = $this->periodYearOptions();

        $monthlyPaymentReport = $this->buildCustomerMonthlyPaymentReport(
            (int) $customer->id,
            $periodContext['start'],
            $periodContext['end']
        );

        // بناء تفاصيل العميل (DTOs)
        $details = $detailsSvc->build($customer->id, $filters);

        // JSON API (عند الطلب)
        if ($request->wantsJson()) {
            return response()->json($details->toArray());
        }

        $contractIdsForFinancials = [];
        $collectContractIds = function ($items) use (&$contractIdsForFinancials): void {
            if (!is_iterable($items)) {
                return;
            }

            foreach ($items as $item) {
                if (is_object($item) && isset($item->id)) {
                    $id = (int) $item->id;
                } elseif (is_array($item) && isset($item['id'])) {
                    $id = (int) $item['id'];
                } else {
                    $id = null;
                }

                if ($id && $id > 0) {
                    $contractIdsForFinancials[] = $id;
                }
            }
        };

        $collectContractIds($details->active   ?? []);
        $collectContractIds($details->finished ?? []);
        $collectContractIds($details->other    ?? []);

        $financialByContract = [];
        if (!empty($contractIdsForFinancials)) {
            $financialData = $this->computeContractFinancials($contractIdsForFinancials);
            $financialByContract = $financialData['per_contract'] ?? [];
        }

        if (!empty($financialByContract)) {
            $updateBriefs = function ($items) use ($financialByContract): void {
                if (!is_iterable($items)) {
                    return;
                }

                foreach ($items as $item) {
                    if (!is_object($item)) {
                        continue;
                    }

                    $contractId = (int) ($item->id ?? 0);

                    if ($contractId <= 0 || !isset($financialByContract[$contractId])) {
                        continue;
                    }

                    $financial = $financialByContract[$contractId];
                    $item->due_sum = round((float) ($financial['expected_total'] ?? 0.0), 2);
                    $item->paid_sum = round((float) ($financial['paid_total'] ?? 0.0), 2);
                    $item->unpaid_sum = round((float) ($financial['remaining'] ?? 0.0), 2);
                    $item->remaining_amount = $item->unpaid_sum;
                }
            };

            $updateBriefs($details->active   ?? []);
            $updateBriefs($details->finished ?? []);
            $updateBriefs($details->other    ?? []);
        }

        $claimCards = $this->buildCustomerClaimCards($customer->id);

        // بعض النِّسَب الجاهزة للعرض (اختياري للواجهة)
        $totalContracts = (int)$details->total_contracts;
        $percent = function (int $part) use ($totalContracts): float {
            return $totalContracts > 0 ? round(($part / $totalContracts) * 100, 1) : 0.0;
        };

        // تمرير البيانات للواجهة
        return view('customers::show', [
            'customer'          => $customer,
            'details'           => $details, // الكائن الكامل لو حاب تتعامل معه مباشرة

            // مختصرات جاهزة للبلِيد
            'activeContracts'   => $details->active,
            'finishedContracts' => $details->finished,
            'otherContracts'    => $details->other,

            'contractsSummary'  => [
                'total'       => $details->total_contracts,
                'active'      => $details->active_count,
                'finished'    => $details->finished_count,
                'other'       => $details->other_count,
                'pct_active'  => $percent($details->active_count),
                'pct_finished'=> $percent($details->finished_count),
                'pct_other'   => $percent($details->other_count),
            ],

            'statusesBreakdown' => $details->statuses_breakdown,
            'installments'      => $details->installments_summary,
            'claimCards'        => $claimCards,

            // الفلاتر بعد التنظيف لإعادة ملؤها في الواجهة
            'filters'           => $filters,
            'periodContext'     => $periodContext,
            'periodMonths'      => $periodMonths,
            'periodYears'       => $periodYears,
            'monthlyPaymentReport' => $monthlyPaymentReport,
        ]);
    }

    public function printMonthlyPayments(Customer $customer, Request $request)
    {
        $customer->loadMissing(['customerStatus:id,name', 'nationality:id,name', 'title:id,name']);

        $periodContext = $this->resolveInstallmentPeriodContext($request);
        $periodMonths  = $this->periodMonthOptions();
        $periodYears   = $this->periodYearOptions();

        $report = $this->buildCustomerMonthlyPaymentReport(
            (int) $customer->id,
            $periodContext['start'],
            $periodContext['end']
        );

        return view('customers::reports.monthly-payments-print', [
            'customer'      => $customer,
            'report'        => $report,
            'periodContext' => $periodContext,
            'periodMonths'  => $periodMonths,
            'periodYears'   => $periodYears,
        ]);
    }

    // صفحة تعديل عميل
    public function edit(Customer $customer)
    {
        $titles = Title::all();
        $nationalities = Nationality::all();
        $customerStatuses = CustomerStatus::select('id', 'name')->orderBy('name')->get();
        return view('customers::edit', compact('customer', 'titles', 'nationalities', 'customerStatuses'));
    }

    // تحديث بيانات عميل
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $validated = $request->validated();

        if (blank($validated['customer_status_id'] ?? null)) {
            $statusIds = $this->resolveCustomerStatusIds();
            if (!blank($statusIds['new'] ?? null)) {
                $validated['customer_status_id'] = $statusIds['new'];
            }
        }

        // رفع صورة الهوية الجديدة وحذف القديمة
        if ($request->hasFile('id_card_image')) {
            if ($customer->id_card_image && Storage::disk('public')->exists($customer->id_card_image)) {
                Storage::disk('public')->delete($customer->id_card_image);
            }
            $validated['id_card_image'] = $request->file('id_card_image')->store('customer_id_cards', 'public');
        }

        $customer->update($validated);

        return redirect()->route('customers.index')->with('success', 'تم تعديل بيانات العميل بنجاح.');
    }

    // حذف عميل
    public function destroy(Customer $customer)
    {
        if ($customer->id_card_image && Storage::disk('public')->exists($customer->id_card_image)) {
            Storage::disk('public')->delete($customer->id_card_image);
        }

        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'تم حذف العميل بنجاح.');
}

    private function buildCustomersListMetrics(array $customerIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        if (empty($customerIds)) {
            return [];
        }

        [$statusIdCol, $statusNameCol] = $this->detectContractStatusColumns();
        $endedStatusNames = $this->endedContractStatusNames();
        $endedStatusIds   = $this->resolveContractStatusIds($endedStatusNames);

        $activeContracts = Contract::query()
            ->select(['id', 'customer_id'])
            ->whereIn('customer_id', $customerIds);

        $this->applyActiveContractFilter($activeContracts, $endedStatusIds, $endedStatusNames, $statusIdCol, $statusNameCol);

        $activeGrouped = $activeContracts->get()->groupBy('customer_id');

        $activeContractIds = $activeGrouped
            ->flatMap(fn ($rows) => $rows->pluck('id'))
            ->unique()
            ->values()
            ->all();

        $installmentsAgg = collect();

        if (!empty($activeContractIds)) {
            $installmentsAgg = ContractInstallment::query()
                ->selectRaw(
                    'contracts.customer_id as customer_id,
                    SUM(CASE WHEN payment_date IS NULL OR payment_amount < due_amount THEN (due_amount - COALESCE(payment_amount,0)) ELSE 0 END) as remaining_sum,
                    SUM(CASE WHEN (payment_date IS NULL OR payment_amount < due_amount) AND due_date BETWEEN ? AND ? THEN (due_amount - COALESCE(payment_amount,0)) ELSE 0 END) as unpaid_month_sum,
                    SUM(CASE WHEN (payment_date IS NULL OR payment_amount < due_amount) AND due_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as unpaid_month_count'
                , [
                    $periodStart->toDateString(),
                    $periodEnd->toDateString(),
                    $periodStart->toDateString(),
                    $periodEnd->toDateString(),
                ])
                ->join('contracts', 'contracts.id', '=', 'contract_installments.contract_id')
                ->whereIn('contracts.customer_id', $customerIds)
                ->whereIn('contract_installments.contract_id', $activeContractIds)
                ->groupBy('contracts.customer_id')
                ->get()
                ->keyBy('customer_id');
        }

        $financialData = $this->computeContractFinancials($activeContractIds);
        $remainingByCustomer = $financialData['per_customer'] ?? [];

        $metrics = [];

        foreach ($customerIds as $customerId) {
            $activeCollection = $activeGrouped->get($customerId, collect());
            $aggRow = $installmentsAgg->get($customerId);

            $metrics[$customerId] = [
                'active_contracts'   => $activeCollection->count(),
                'remaining_sum'      => (float) ($remainingByCustomer[$customerId] ?? 0.0),
                'unpaid_month_count' => $aggRow ? (int) ($aggRow->unpaid_month_count ?? 0) : 0,
                'unpaid_month_sum'   => $aggRow ? (float) ($aggRow->unpaid_month_sum ?? 0.0) : 0.0,
            ];
        }

        return $metrics;
    }

    private function computeContractFinancials(array $contractIds): array
    {
        $contractIds = array_values(array_unique(array_filter(array_map('intval', $contractIds), fn ($id) => $id > 0)));

        if (empty($contractIds)) {
            return [
                'per_contract' => [],
                'per_customer' => [],
            ];
        }

        $contracts = Contract::query()
            ->select(['id', 'customer_id', 'total_value', 'contract_value', 'investor_profit', 'discount_amount'])
            ->whereIn('id', $contractIds)
            ->get();

        if ($contracts->isEmpty()) {
            return [
                'per_contract' => [],
                'per_customer' => [],
            ];
        }

        $expectedByContract = [];
        $customerByContract = [];

        foreach ($contracts as $contract) {
            $contractId = (int) ($contract->id ?? 0);
            $customerId = (int) ($contract->customer_id ?? 0);

            if ($contractId <= 0 || $customerId <= 0) {
                continue;
            }

            $totalValue = $this->resolveContractTotalValue($contract);

            if ($totalValue <= 0) {
                continue;
            }

            $expectedByContract[$contractId] = round($totalValue, 2);
            $customerByContract[$contractId] = $customerId;
        }

        if (empty($expectedByContract)) {
            return [
                'per_contract' => [],
                'per_customer' => [],
            ];
        }

        $paymentBuckets = InvestorContractPaymentAggregator::transactionStatusBuckets();
        $paymentStatusIds = array_values(array_unique(array_merge(
            $paymentBuckets['installment'] ?? [],
            $paymentBuckets['claim'] ?? []
        )));

        $investorPaidQuery = InvestorTransaction::query()
            ->selectRaw('contract_id, SUM(amount) as amount')
            ->whereIn('contract_id', array_keys($expectedByContract));

        if (!empty($paymentStatusIds)) {
            $investorPaidQuery->whereIn('status_id', $paymentStatusIds);
        } else {
            $investorPaidQuery->whereRaw('0 = 1');
        }

        $investorPaid = $investorPaidQuery
            ->groupBy('contract_id')
            ->pluck('amount', 'contract_id');

        $officeStatusIds = $this->officeProfitStatusIds();

        $officePaidQuery = OfficeTransaction::query()
            ->selectRaw('contract_id, SUM(amount) as amount')
            ->whereIn('contract_id', array_keys($expectedByContract));

        if (!empty($officeStatusIds)) {
            $officePaidQuery->whereIn('status_id', $officeStatusIds);
        } else {
            $officePaidQuery->whereRaw('0 = 1');
        }

        $officePaid = $officePaidQuery
            ->groupBy('contract_id')
            ->pluck('amount', 'contract_id');

        $perContract = [];
        $perCustomer = [];

        foreach ($expectedByContract as $contractId => $expected) {
            $paidInvestor = round((float) ($investorPaid[$contractId] ?? 0.0), 2);
            $paidOffice   = round((float) ($officePaid[$contractId]   ?? 0.0), 2);
            $paidTotal    = round($paidInvestor + $paidOffice, 2);
            $remaining    = round($expected - $paidTotal, 2);

            if ($remaining < 0) {
                $remaining = 0.0;
            }

            $perContract[$contractId] = [
                'expected_total' => $expected,
                'paid_investor'  => $paidInvestor,
                'paid_office'    => $paidOffice,
                'paid_total'     => $paidTotal,
                'remaining'      => $remaining,
            ];

            $customerId = $customerByContract[$contractId] ?? null;

            if ($customerId) {
                $perCustomer[$customerId] = round(($perCustomer[$customerId] ?? 0.0) + $remaining, 2);
            }
        }

        return [
            'per_contract' => $perContract,
            'per_customer' => $perCustomer,
        ];
    }

    private function resolveContractTotalValue($contract): float
    {
        $total = (float) ($contract->total_value ?? 0.0);

        if ($total > 0) {
            return round($total, 2);
        }

        $contractValue = (float) ($contract->contract_value ?? 0.0);
        $profit        = (float) ($contract->investor_profit ?? 0.0);
        $discount      = (float) ($contract->discount_amount ?? 0.0);

        $computed = $contractValue + $profit - $discount;

        return $computed > 0 ? round($computed, 2) : 0.0;
    }

    private function officeProfitStatusIds(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $names = ['ربح المكتب', 'office profit', 'office share'];
        $normalize = static fn ($value) => mb_strtolower(trim((string) $value), 'UTF-8');
        $target = array_map($normalize, $names);

        $ids = TransactionStatus::query()
            ->select(['id', 'name'])
            ->get()
            ->filter(function ($status) use ($normalize, $target) {
                $name = $normalize($status->name ?? '');

                return $name !== '' && in_array($name, $target, true);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $cache = $ids;
    }

    private function buildCustomerClaimCards(int $customerId): array
    {
        $statusIds = ContractStatus::query()
            ->whereIn('name', ['مطلوب', 'مرفوع فيه'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($statusIds)) {
            return [
                'show'      => false,
                'total'     => 0.0,
                'paid'      => 0.0,
                'remaining' => 0.0,
            ];
        }

        $contractIds = Contract::query()
            ->where('customer_id', $customerId)
            ->whereIn('contract_status_id', $statusIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($contractIds)) {
            return [
                'show'      => false,
                'total'     => 0.0,
                'paid'      => 0.0,
                'remaining' => 0.0,
            ];
        }

        $claimsNet = ContractClaim::query()
            ->whereIn('contract_id', $contractIds)
            ->selectRaw('SUM(COALESCE(claim_amount,0) - COALESCE(discount_amount,0)) as net_sum')
            ->value('net_sum');

        $totalClaims = round(max(0.0, (float) ($claimsNet ?? 0.0)), 2);

        $paymentBuckets = InvestorContractPaymentAggregator::transactionStatusBuckets();
        $claimStatusIds = $paymentBuckets['claim'] ?? [];

        $investorPaidQuery = InvestorTransaction::query()
            ->whereIn('contract_id', $contractIds)
            ->whereNotNull('contract_claim_id');

        if (!empty($claimStatusIds)) {
            $investorPaidQuery->whereIn('status_id', $claimStatusIds);
        } else {
            $investorPaidQuery->whereRaw('0 = 1');
        }

        $investorPaid = (float) $investorPaidQuery->sum('amount');

        $officeStatusIds = $this->officeProfitStatusIds();

        $officePaidQuery = OfficeTransaction::query()
            ->whereIn('contract_id', $contractIds)
            ->whereNotNull('contract_claim_id');

        if (!empty($officeStatusIds)) {
            $officePaidQuery->whereIn('status_id', $officeStatusIds);
        } else {
            $officePaidQuery->whereRaw('0 = 1');
        }

        $officePaid = (float) $officePaidQuery->sum('amount');

        $totalPaid = round($investorPaid + $officePaid, 2);
        $remaining = round(max(0.0, $totalClaims - $totalPaid), 2);

        return [
            'show'      => true,
            'total'     => $totalClaims,
            'paid'      => $totalPaid,
            'remaining' => $remaining,
        ];
    }

    private function buildCustomerInstallmentAggregation(
        Carbon $today,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?string $statusIdCol,
        ?string $statusNameCol,
        array $endedStatusIds,
        array $endedStatusNames
    ) {
        $query = ContractInstallment::query()
            ->selectRaw(
                'contracts.customer_id as customer_id,
                SUM(CASE WHEN payment_date IS NULL OR payment_amount < due_amount THEN (due_amount - COALESCE(payment_amount,0)) ELSE 0 END) as unpaid_total,
                SUM(CASE WHEN (payment_date IS NULL OR payment_amount < due_amount) AND due_date < ? THEN (due_amount - COALESCE(payment_amount,0)) ELSE 0 END) as overdue_total,
                SUM(CASE WHEN (payment_date IS NULL OR payment_amount < due_amount) AND due_date BETWEEN ? AND ? THEN (due_amount - COALESCE(payment_amount,0)) ELSE 0 END) as due_this_month_total'
            , [
                $today->toDateString(),
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->join('contracts', 'contracts.id', '=', 'contract_installments.contract_id');

        $this->applyActiveContractFilter($query, $endedStatusIds, $endedStatusNames, $statusIdCol, $statusNameCol);

        return $query->groupBy('contracts.customer_id');
    }

    private function detectContractStatusColumns(): array
    {
        $statusIdCol = null;
        foreach (['contract_status_id', 'status_id', 'state_id'] as $column) {
            if (Schema::hasColumn('contracts', $column)) {
                $statusIdCol = $column;
                break;
            }
        }

        $statusNameCol = null;
        foreach (['status', 'state', 'contract_status'] as $column) {
            if (Schema::hasColumn('contracts', $column)) {
                $statusNameCol = $column;
                break;
            }
        }

        return [$statusIdCol, $statusNameCol];
    }

    private function endedContractStatusNames(): array
    {
        $names = [
            'مغلق', 'مقفلة', 'مغلقة', 'منتهي', 'منتهى',
            'سداد مبكر', 'سداد مُبكر', 'سداد مبكّر',
            'Completed', 'completed', 'Finished', 'finished',
            'Early Settlement', 'early settlement', 'Closed', 'closed',
        ];

        return array_values(array_unique(array_filter($names)));
    }

    private function resolveContractStatusIds(array $names): array
    {
        if (empty($names) || !class_exists(\Modules\Lookups\Entities\ContractStatus::class)) {
            return [];
        }

        return \Modules\Lookups\Entities\ContractStatus::query()
            ->whereIn('name', $names)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function applyActiveContractFilter($query, array $endedStatusIds, array $endedStatusNames, ?string $statusIdCol, ?string $statusNameCol): void
    {
        if ($statusIdCol && !empty($endedStatusIds)) {
            $query->whereNotIn($statusIdCol, $endedStatusIds);
            return;
        }

        if ($statusNameCol && !empty($endedStatusNames)) {
            $query->whereNotIn($statusNameCol, $endedStatusNames);
            return;
        }

        if (Schema::hasColumn('contracts', 'is_closed')) {
            $query->where('is_closed', 0);
            return;
        }

        if (Schema::hasColumn('contracts', 'closed_at')) {
            $query->whereNull('closed_at');
        }
    }

    private function buildCustomerMonthlyPaymentReport(int $customerId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $start = $periodStart->copy()->startOfDay();
        $end   = $periodEnd->copy()->endOfDay();

        if ($customerId <= 0) {
            return [
                'period_start'        => $start->toDateString(),
                'period_end'          => $end->toDateString(),
                'period_label'        => $start->format('Y-m-d') . ' — ' . $end->format('Y-m-d'),
                'month'               => (int) $start->month,
                'year'                => (int) $start->year,
                'contracts_count'     => 0,
                'installments_count'  => 0,
                'total_due'           => 0.0,
                'total_paid'          => 0.0,
                'total_remaining'     => 0.0,
                'contracts'           => [],
            ];
        }

        $installments = ContractInstallment::query()
            ->with([
                'contract:id,customer_id,contract_number,start_date,contract_status_id',
                'contract.contractStatus:id,name',
                'installmentStatus:id,name',
            ])
            ->whereHas('contract', fn ($q) => $q->where('customer_id', $customerId))
            ->whereNotNull('payment_date')
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('payment_date')
            ->get();

        if ($installments->isEmpty()) {
            return [
                'period_start'        => $start->toDateString(),
                'period_end'          => $end->toDateString(),
                'period_label'        => $start->format('Y-m-d') . ' — ' . $end->format('Y-m-d'),
                'month'               => (int) $start->month,
                'year'                => (int) $start->year,
                'contracts_count'     => 0,
                'installments_count'  => 0,
                'total_due'           => 0.0,
                'total_paid'          => 0.0,
                'total_remaining'     => 0.0,
                'contracts'           => [],
            ];
        }

        $contracts = [];
        $totalDue = 0.0;
        $totalPaid = 0.0;
        $totalRemaining = 0.0;
        $installmentsCount = 0;

        foreach ($installments->groupBy('contract_id') as $contractId => $items) {
            $contractModel = $items->first()->contract;
            $contractNumber = $contractModel->contract_number ?? (string) $contractId;
            $statusName = optional($contractModel->contractStatus)->name;
            $startDate = $contractModel->start_date ?? null;
            $startDateFormatted = $startDate ? Carbon::parse($startDate)->format('Y-m-d') : null;

            $contractDue = 0.0;
            $contractPaid = 0.0;
            $contractRemaining = 0.0;
            $lastPayment = null;
            $lastPaymentAmount = null;

            foreach ($items as $installment) {
                $dueAmount = (float) ($installment->due_amount ?? 0.0);
                $rawPaid = (float) ($installment->payment_amount ?? 0.0);
                $paidEffective = $dueAmount > 0 ? min($dueAmount, max($rawPaid, 0.0)) : max($rawPaid, 0.0);
                $remaining = max($dueAmount - $paidEffective, 0.0);
                $paymentDate = $installment->payment_date ? Carbon::parse($installment->payment_date) : null;

                if ($paymentDate && (!$lastPayment || $paymentDate->gt($lastPayment) || ($paymentDate->eq($lastPayment) && ($paidEffective >= ($lastPaymentAmount ?? PHP_FLOAT_MIN))))) {
                    $lastPayment = $paymentDate;
                    $lastPaymentAmount = $paidEffective;
                }

                $contractDue += $dueAmount;
                $contractPaid += $paidEffective;
                $contractRemaining += $remaining;
                $installmentsCount++;
            }

            $totalDue += $contractDue;
            $totalPaid += $contractPaid;
            $totalRemaining += $contractRemaining;

            $contracts[] = [
                'contract_id'        => (int) $contractId,
                'contract_number'    => $contractNumber,
                'status_name'        => $statusName,
                'start_date'         => $startDateFormatted,
                'installment_count'  => count($items),
                'due_sum'            => round($contractDue, 2),
                'paid_sum'           => round($contractPaid, 2),
                'remaining_sum'      => round($contractRemaining, 2),
                'last_payment_date'  => $lastPayment?->format('Y-m-d'),
                'last_payment_amount' => $lastPaymentAmount !== null ? round($lastPaymentAmount, 2) : null,
            ];
        }

        usort($contracts, fn ($a, $b) => strnatcasecmp((string) ($a['contract_number'] ?? ''), (string) ($b['contract_number'] ?? '')));

        return [
            'period_start'        => $start->toDateString(),
            'period_end'          => $end->toDateString(),
            'period_label'        => $start->format('Y-m-d') . ' — ' . $end->format('Y-m-d'),
            'month'               => (int) $start->month,
            'year'                => (int) $start->year,
            'contracts_count'     => count($contracts),
            'installments_count'  => $installmentsCount,
            'total_due'           => round($totalDue, 2),
            'total_paid'          => round($totalPaid, 2),
            'total_remaining'     => round($totalRemaining, 2),
            'contracts'           => $contracts,
        ];
    }

    private function resolveInstallmentPeriodContext(Request $request): array
    {
        $month = $this->normalizeMonth($request->input('period_month'));
        $year  = $this->normalizeYear($request->input('period_year'));

        $resolved = InstallmentPeriod::resolve($month, $year, Carbon::now());

        $start = $resolved['start']->copy();
        $end   = $resolved['end']->copy();

        return [
            'start' => $start,
            'end'   => $end,
            'month' => $month ?? (int) $start->month,
            'year'  => $year ?? (int) $start->year,
            'label' => $start->format('Y-m-d') . ' — ' . $end->format('Y-m-d'),
        ];
    }

    private function resolveCustomerStatusIds(): array
    {
        $lookup = CustomerStatus::query()
            ->select(['id', 'name'])
            ->get()
            ->mapWithKeys(function ($status) {
                $normalized = $this->normalizeStatusName($status->name ?? '');

                if ($normalized === '') {
                    return [];
                }

                return [$normalized => (int) $status->id];
            })
            ->all();

        return [
            'new'        => $this->matchCustomerStatusId($lookup, ['جديد', 'new']),
            'committed'  => $this->matchCustomerStatusId($lookup, ['ملتزم', 'compliant', 'ملتزم بالكامل', 'ملتزم تمامًا']),
            'delinquent' => $this->matchCustomerStatusId($lookup, ['غير ملتزم', 'delinquent', 'non compliant', 'متخلف']),
            'inactive'   => $this->matchCustomerStatusId($lookup, ['غير نشط', 'inactive', 'inactive customer']),
            'raised'     => $this->matchCustomerStatusId($lookup, ['مرفوع فيه', 'raised']),
            'blacklist'  => $this->matchCustomerStatusId($lookup, ['قائمة سوداء', 'blacklist', 'black-listed']),
        ];
    }

    private function matchCustomerStatusId(array $lookup, array $candidates): ?int
    {
        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeStatusName($candidate);

            if ($normalized !== '' && isset($lookup[$normalized])) {
                return (int) $lookup[$normalized];
            }
        }

        return null;
    }

    private function contractStatusNameLookup(): array
    {
        return ContractStatus::query()
            ->select(['id', 'name'])
            ->get()
            ->mapWithKeys(function ($status) {
                $normalized = $this->normalizeStatusName($status->name ?? '');

                return [(int) $status->id => $normalized];
            })
            ->all();
    }

    private function contractStatusGroups(): array
    {
        return [
            'blacklist' => $this->normalizeStatusArray([
                'منتهي بمطالبة',
                'منتهى بمطالبة',
                'terminated with claim',
                'ended with claim',
                'claim terminated',
                'claim ended',
                'claim with lawsuit',
            ]),
            'raised' => $this->normalizeStatusArray([
                'مرفوع فيه',
                'مرفوع',
                'raised',
                'raised status',
                'case filed',
            ]),
            'delinquent' => $this->normalizeStatusArray([
                'مطلوب',
                'غير منتظم',
                'متأخر',
                'متعثر',
                'متأخر الدفع',
                'delinquent',
                'late',
                'overdue',
                'defaulted',
                'irregular',
                'required',
            ]),
            'finished' => $this->normalizeStatusArray([
                'منتهي',
                'منتهى',
                'منتهية',
                'سداد مبكر',
                'سداد مُبكر',
                'سداد مبكّر',
                'completed',
                'finished',
                'closed',
                'settled',
                'settled early',
                'early settlement',
                'paid off',
            ]),
        ];
    }

    private function normalizeStatusArray(array $values): array
    {
        $normalized = [];

        foreach ($values as $value) {
            $name = $this->normalizeStatusName($value);

            if ($name !== '') {
                $normalized[$name] = true;
            }
        }

        return array_keys($normalized);
    }

    private function normalizeStatusName(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', $value);

        return mb_strtolower($value, 'UTF-8');
    }

    private function determineCustomerStatusId(Customer $customer, array $statusIds, array $contractStatusLookup, array $groups): ?int
    {
        $contracts = $customer->relationLoaded('contracts')
            ? $customer->getRelation('contracts')
            : collect();

        if (! $contracts instanceof \Illuminate\Support\Collection) {
            $contracts = collect($contracts);
        }

        if ($contracts->isEmpty()) {
            return $statusIds['new'] ?? null;
        }

        $statusNames = [];

        foreach ($contracts as $contract) {
            $statusId = (int) ($contract->contract_status_id ?? 0);

            if ($statusId <= 0) {
                continue;
            }

            $name = $contractStatusLookup[$statusId] ?? '';

            if ($name !== '') {
                $statusNames[$name] = true;
            }
        }

        $statusNames = array_keys($statusNames);

        if (empty($statusNames)) {
            return $statusIds['committed'] ?? null;
        }

        if ($this->containsAnyNormalized($statusNames, $groups['blacklist'] ?? [])) {
            return $statusIds['blacklist']
                ?? $statusIds['raised']
                ?? $statusIds['delinquent']
                ?? null;
        }

        if ($this->containsAnyNormalized($statusNames, $groups['raised'] ?? [])) {
            return $statusIds['raised']
                ?? $statusIds['delinquent']
                ?? null;
        }

        if ($this->containsAnyNormalized($statusNames, $groups['delinquent'] ?? [])) {
            return $statusIds['delinquent'] ?? null;
        }

        if (!empty($groups['finished']) && $this->allInSet($statusNames, $groups['finished'])) {
            return $statusIds['inactive'] ?? null;
        }

        return $statusIds['committed'] ?? null;
    }

    private function containsAnyNormalized(array $haystack, array $needles): bool
    {
        if (empty($haystack) || empty($needles)) {
            return false;
        }

        $needles = array_values(array_unique($needles));

        foreach ($haystack as $value) {
            if (in_array($value, $needles, true)) {
                return true;
            }
        }

        return false;
    }

    private function allInSet(array $haystack, array $allowed): bool
    {
        if (empty($haystack)) {
            return false;
        }

        $allowed = array_values(array_unique($allowed));

        foreach ($haystack as $value) {
            if (!in_array($value, $allowed, true)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeMonth($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (int) $value;

        return $value >= 1 && $value <= 12 ? $value : null;
    }

    private function normalizeYear($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (int) $value;

        return $value >= 1900 && $value <= 2100 ? $value : null;
    }

    private function periodMonthOptions(): array
    {
        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $months[$month] = Carbon::create(null, $month, 1)
                ->locale(app()->getLocale())
                ->translatedFormat('F');
        }

        return $months;
    }

    private function periodYearOptions(): array
    {
        $currentYear = Carbon::now()->year;
        $years = [];

        for ($year = $currentYear - 5; $year <= $currentYear + 1; $year++) {
            $years[$year] = (string) $year;
        }

        return $years;
    }
}
