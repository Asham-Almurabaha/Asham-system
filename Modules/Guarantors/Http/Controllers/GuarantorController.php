<?php

namespace Modules\Guarantors\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Guarantors\Entities\Guarantor;
use Modules\Lookups\Entities\GuarantorStatus;
use Modules\Lookups\Entities\Nationality;
use Modules\Lookups\Entities\Title;
use Modules\Contracts\Entities\ContractInstallment;
use App\Support\InstallmentPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class GuarantorController extends Controller
{
    public function index(Request $request)
    {
        [$statusIdCol, $statusNameCol] = $this->detectContractStatusColumns();
        $endedStatusNames = $this->endedContractStatusNames();
        $endedStatusIds   = $this->resolveContractStatusIds($endedStatusNames);

        $periodContext = $this->resolveInstallmentPeriodContext($request);
        $periodStart   = $periodContext['start']->copy();
        $periodEnd     = $periodContext['end']->copy();
        $periodMonths  = $this->periodMonthOptions();
        $periodYears   = $this->periodYearOptions();

        $report = trim((string) $request->input('report', '')) ?: null;

        $now        = Carbon::now();
        $today      = $now->copy()->startOfDay();
        $weekStart  = $now->copy()->startOfWeek();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd   = $now->copy()->endOfMonth();

        $query = Guarantor::query()
            ->select('guarantors.*')
            ->with([
                'nationality:id,name',
                'title:id,name',
                'guarantorStatus:id,name',
            ])
            ->withCount([
                'contracts',
                'contracts as customers_count' => function ($q) {
                    $q->select(DB::raw('COUNT(DISTINCT customer_id)'));
                },
            ]);

        // ===== بحث باسم الكفيل فقط =====
        $nameQ = trim((string) $request->input('guarantor_q', ''));
        if ($nameQ !== '') {
            $query->where('guarantors.name', 'like', '%' . $nameQ . '%');
        } else {
            // فلاتر إضافية اختيارية
            $query->when($request->filled('national_id') && Schema::hasColumn('guarantors', 'national_id'),
                    fn($q) => $q->where('national_id', 'like', '%'.trim($request->national_id).'%'))
                ->when($request->filled('phone'),
                    fn($q) => $q->where('phone', 'like', '%'.trim($request->phone).'%'));
        }

        $customOrderApplied = false;

        $aggregationReports = ['overdue', 'due-this-month'];
        if ($report && in_array($report, $aggregationReports, true)) {
            $aggregationBuilder = $this->buildGuarantorInstallmentAggregation(
                $today,
                $periodStart,
                $periodEnd,
                $statusIdCol,
                $statusNameCol,
                $endedStatusIds,
                $endedStatusNames
            );

            $query->leftJoinSub($aggregationBuilder, 'inst', function ($join) {
                $join->on('guarantors.id', '=', 'inst.guarantor_id');
            });

            $query->addSelect([
                'unpaid_total'         => DB::raw('COALESCE(inst.unpaid_total, 0)'),
                'overdue_total'        => DB::raw('COALESCE(inst.overdue_total, 0)'),
                'due_this_month_total' => DB::raw('COALESCE(inst.due_this_month_total, 0)'),
            ]);

            if ($report === 'overdue') {
                $query->whereRaw('COALESCE(inst.overdue_total, 0) > 0');
                $query->orderByDesc(DB::raw('COALESCE(inst.overdue_total, 0)'));
                $customOrderApplied = true;
            } elseif ($report === 'due-this-month') {
                $query->whereRaw('COALESCE(inst.due_this_month_total, 0) > 0');
                $query->orderByDesc(DB::raw('COALESCE(inst.due_this_month_total, 0)'));
                $customOrderApplied = true;
            }
        }

        if ($report === 'without-contracts') {
            $query->whereDoesntHave('contracts');
            $query->orderBy('guarantors.name');
            $customOrderApplied = true;
        }

        if (!$customOrderApplied) {
            $query->latest('guarantors.created_at');
        }

        // 20 صف في الصفحة
        $guarantors = $query->paginate(20)->withQueryString();

        // ===== كروت عامة (غير متأثرة بالفلاتر) =====
        $guarantorsTotalAll = Guarantor::count();

        $activeGuarantorsTotalAll = $this->countActiveGuarantors(
            $endedStatusIds,
            $endedStatusNames,
            $statusIdCol,
            $statusNameCol
        );

        $newGuarantorsThisMonthAll = Guarantor::whereBetween('created_at', [$monthStart, $now])->count();
        $newGuarantorsThisWeekAll  = Guarantor::whereBetween('created_at', [$weekStart,  $now])->count();

        return view('guarantors::index', compact(
            'guarantors',
            'guarantorsTotalAll',
            'activeGuarantorsTotalAll',
            'newGuarantorsThisMonthAll',
            'newGuarantorsThisWeekAll',
            'report',
            'periodContext',
            'periodMonths',
            'periodYears'
        ));
    }

    public function dashboard(Request $request)
    {
        [$statusIdCol, $statusNameCol] = $this->detectContractStatusColumns();
        $endedStatusNames = $this->endedContractStatusNames();
        $endedStatusIds   = $this->resolveContractStatusIds($endedStatusNames);

        $periodContext = $this->resolveInstallmentPeriodContext($request);
        $periodStart   = $periodContext['start']->copy();
        $periodEnd     = $periodContext['end']->copy();
        $periodMonths  = $this->periodMonthOptions();
        $periodYears   = $this->periodYearOptions();

        $totalGuarantors = Guarantor::count();

        $activeGuarantors = $this->countActiveGuarantors(
            $endedStatusIds,
            $endedStatusNames,
            $statusIdCol,
            $statusNameCol
        );

        $guarantorsWithContracts = Guarantor::has('contracts')->count();
        $guarantorsWithoutContracts = max($totalGuarantors - $guarantorsWithContracts, 0);
        $inactiveGuarantors = max($totalGuarantors - $activeGuarantors, 0);

        $now        = Carbon::now();
        $today      = $now->copy()->startOfDay();
        $weekStart  = $now->copy()->startOfWeek();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd   = $now->copy()->endOfMonth();

        $newThisMonth = Guarantor::whereBetween('created_at', [$monthStart, $now])->count();
        $newThisWeek  = Guarantor::whereBetween('created_at', [$weekStart, $now])->count();

        $aggregationBuilder = fn () => $this->buildGuarantorInstallmentAggregation(
            $today,
            $periodStart,
            $periodEnd,
            $statusIdCol,
            $statusNameCol,
            $endedStatusIds,
            $endedStatusNames
        );

        $overdueGuarantorsCount = Guarantor::query()
            ->joinSub($aggregationBuilder(), 'inst', function ($join) {
                $join->on('guarantors.id', '=', 'inst.guarantor_id');
            })
            ->where('inst.overdue_total', '>', 0)
            ->count();

        $dueThisMonthGuarantorsCount = Guarantor::query()
            ->joinSub($aggregationBuilder(), 'inst', function ($join) {
                $join->on('guarantors.id', '=', 'inst.guarantor_id');
            })
            ->where('inst.due_this_month_total', '>', 0)
            ->count();

        $pct = static function (int $total, int $value): float {
            if ($total <= 0) {
                return 0.0;
            }

            return round(($value / $total) * 100, 1);
        };

        $totals = [
            'total'            => $totalGuarantors,
            'active'           => $activeGuarantors,
            'inactive'         => $inactiveGuarantors,
            'withContracts'    => $guarantorsWithContracts,
            'withoutContracts' => $guarantorsWithoutContracts,
            'newMonth'         => $newThisMonth,
            'newWeek'          => $newThisWeek,
            'overdue'          => $overdueGuarantorsCount,
            'dueThisMonth'     => $dueThisMonthGuarantorsCount,
        ];

        $percentages = [
            'active'           => $pct($totalGuarantors, $activeGuarantors),
            'inactive'         => $pct($totalGuarantors, $inactiveGuarantors),
            'withContracts'    => $pct($totalGuarantors, $guarantorsWithContracts),
            'withoutContracts' => $pct($totalGuarantors, $guarantorsWithoutContracts),
            'overdue'          => $pct($totalGuarantors, $overdueGuarantorsCount),
        ];

        $statusCounts = Guarantor::selectRaw('guarantor_status_id, COUNT(*) as total')
            ->groupBy('guarantor_status_id')
            ->get();

        $statusNames = class_exists(GuarantorStatus::class)
            ? GuarantorStatus::whereIn('id', $statusCounts->pluck('guarantor_status_id')->filter()->all())
                ->pluck('name', 'id')
            : collect();

        $statusBreakdown = $statusCounts->map(function ($row) use ($statusNames, $totalGuarantors) {
            $id = $row->guarantor_status_id;
            $count = (int) ($row->total ?? 0);

            $name = $id
                ? ($statusNames[$id] ?? __('guarantors::messages.Undefined'))
                : __('guarantors::messages.Undefined');

            return [
                'id'    => $id ? (int) $id : null,
                'name'  => $name,
                'count' => $count,
                'pct'   => $totalGuarantors > 0 ? round(($count / $totalGuarantors) * 100, 1) : 0.0,
            ];
        })->sortByDesc('count')->values();

        $statusChartLabels = $statusBreakdown->pluck('name')->all();
        $statusChartData   = $statusBreakdown->pluck('count')->all();

        $monthsBack = 11;
        $rangeStart = $monthStart->copy()->subMonths($monthsBack);

        $monthlyRaw = Guarantor::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as ym, COUNT(*) as total')
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

        $topContractGuarantors = Guarantor::query()
            ->with('guarantorStatus:id,name')
            ->withCount('contracts')
            ->withCount(['contracts as active_contracts_count' => function ($query) use ($endedStatusIds, $endedStatusNames, $statusIdCol, $statusNameCol) {
                $this->applyActiveContractFilter($query, $endedStatusIds, $endedStatusNames, $statusIdCol, $statusNameCol);
            }])
            ->withCount(['contracts as customers_count' => function ($query) {
                $query->select(DB::raw('COUNT(DISTINCT customer_id)'));
            }])
            ->orderByDesc('active_contracts_count')
            ->orderByDesc('contracts_count')
            ->orderBy('name')
            ->take(5)
            ->get()
            ->filter(fn (Guarantor $guarantor) => (int) $guarantor->active_contracts_count > 0)
            ->map(function (Guarantor $guarantor) {
                return [
                    'id'                => (int) $guarantor->id,
                    'name'              => $guarantor->name,
                    'status'            => optional($guarantor->guarantorStatus)->name,
                    'active_contracts'  => (int) $guarantor->active_contracts_count,
                    'total_contracts'   => (int) $guarantor->contracts_count,
                    'customers_count'   => (int) $guarantor->customers_count,
                ];
            })
            ->values();

        $topOutstanding = Guarantor::query()
            ->select('guarantors.id', 'guarantors.name', 'inst.unpaid_total', 'inst.overdue_total', 'inst.due_this_month_total')
            ->joinSub($aggregationBuilder(), 'inst', function ($join) {
                $join->on('guarantors.id', '=', 'inst.guarantor_id');
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

        $topNationalitiesRaw = Guarantor::selectRaw('nationality_id, COUNT(*) as total')
            ->whereNotNull('nationality_id')
            ->groupBy('nationality_id')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        $nationalityNames = class_exists(Nationality::class)
            ? Nationality::whereIn('id', $topNationalitiesRaw->pluck('nationality_id')->all())->pluck('name', 'id')
            : collect();

        $topNationalities = $topNationalitiesRaw->map(function ($row) use ($nationalityNames, $totalGuarantors) {
            $id = (int) $row->nationality_id;
            $count = (int) ($row->total ?? 0);

            return [
                'id'    => $id,
                'name'  => $nationalityNames[$id] ?? __('guarantors::messages.Undefined'),
                'count' => $count,
                'pct'   => $totalGuarantors > 0 ? round(($count / $totalGuarantors) * 100, 1) : 0.0,
            ];
        })->values();

        $recentGuarantors = Guarantor::query()
            ->with('guarantorStatus:id,name')
            ->latest()
            ->take(8)
            ->get()
            ->map(function (Guarantor $guarantor) {
                return [
                    'id'        => (int) $guarantor->id,
                    'name'      => $guarantor->name,
                    'status'    => optional($guarantor->guarantorStatus)->name,
                    'created_at'=> optional($guarantor->created_at)->format('Y-m-d'),
                ];
            })
            ->values();

        return view('guarantors::dashboard', [
            'totals'               => $totals,
            'percentages'          => $percentages,
            'statusBreakdown'      => $statusBreakdownArray,
            'statusChartLabels'    => $statusChartLabels,
            'statusChartData'      => $statusChartData,
            'monthlyRegistrations' => $monthlyRegistrations,
            'topContractGuarantors'=> $topContractGuarantors,
            'topOutstanding'       => $topOutstanding,
            'topNationalities'     => $topNationalities,
            'recentGuarantors'     => $recentGuarantors,
            'periodContext'        => $periodContext,
            'periodMonths'         => $periodMonths,
            'periodYears'          => $periodYears,
        ]);
    }

    public function create()
    {
        $nationalities = Nationality::all();
        $titles = Title::all();
        $guarantorStatuses = GuarantorStatus::select('id', 'name')->orderBy('name')->get();
        return view('guarantors::create', compact('nationalities', 'titles', 'guarantorStatuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:guarantors,name,',
            'national_id' => 'nullable|digits:10|regex:/^[12]\d{9}$/|unique:guarantors,national_id,',
            'phone' => 'nullable|regex:/^(?:05\d{8}|\+?9665\d{8}|009665\d{8})$/|unique:guarantors,phone,',
            'email' => 'nullable|email|max:255',
            'title_id' => 'nullable|exists:titles,id',
            'address' => 'nullable|string',
            'nationality_id' => 'nullable|exists:nationalities,id',
            'guarantor_status_id' => 'nullable|exists:guarantor_statuses,id',
            'id_card_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('id_card_image')) {
            $validated['id_card_image'] = $request->file('id_card_image')->store('guarantor_id_cards', 'public');
        }

        Guarantor::create($validated);

        return redirect()->route('guarantors.index')->with('success', 'تم إضافة الكفيل بنجاح');
    }

    public function show(Guarantor $guarantor)
    {
        $guarantor->loadMissing(['guarantorStatus:id,name', 'title:id,name', 'nationality:id,name']);

        return view('guarantors::show', compact('guarantor'));
    }

    public function edit(Guarantor $guarantor)
    {
        $nationalities = Nationality::all();
        $titles = Title::all();
        $guarantorStatuses = GuarantorStatus::select('id', 'name')->orderBy('name')->get();
        return view('guarantors::edit', compact('guarantor', 'nationalities', 'titles', 'guarantorStatuses'));
    }

    public function update(Request $request, Guarantor $guarantor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:guarantors,name,' . $guarantor->id,
            'national_id' => 'nullable|digits:10|regex:/^[12]\d{9}$/|unique:guarantors,national_id,' . $guarantor->id,
            'phone' => 'nullable|regex:/^(?:05\d{8}|\+?9665\d{8}|009665\d{8})$/|unique:guarantors,phone,' . $guarantor->id,
            'email' => 'nullable|email|max:255',
            'title_id' => 'nullable|exists:titles,id',
            'address' => 'nullable|string',
            'nationality_id' => 'nullable|exists:nationalities,id',
            'guarantor_status_id' => 'nullable|exists:guarantor_statuses,id',
            'id_card_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('id_card_image')) {
            // حذف الصورة القديمة إذا موجودة
            if ($guarantor->id_card_image) {
                Storage::disk('public')->delete($guarantor->id_card_image);
            }
            $validated['id_card_image'] = $request->file('id_card_image')->store('guarantor_id_cards', 'public');
        }

        $guarantor->update($validated);

        return redirect()->route('guarantors.index')->with('success', 'تم تحديث بيانات الكفيل بنجاح');
    }

    public function destroy(Guarantor $guarantor)
    {
        if ($guarantor->id_card_image) {
            Storage::disk('public')->delete($guarantor->id_card_image);
        }
        $guarantor->delete();

        return redirect()->route('guarantors.index')->with('success', 'تم حذف الكفيل بنجاح');
    }

    private function buildGuarantorInstallmentAggregation(
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
                'contracts.guarantor_id as guarantor_id,
                SUM(CASE WHEN payment_date IS NULL OR payment_amount < due_amount THEN (due_amount - COALESCE(payment_amount,0)) ELSE 0 END) as unpaid_total,
                SUM(CASE WHEN (payment_date IS NULL OR payment_amount < due_amount) AND due_date < ? THEN (due_amount - COALESCE(payment_amount,0)) ELSE 0 END) as overdue_total,
                SUM(CASE WHEN (payment_date IS NULL OR payment_amount < due_amount) AND due_date BETWEEN ? AND ? THEN (due_amount - COALESCE(payment_amount,0)) ELSE 0 END) as due_this_month_total'
            , [
                $today->toDateString(),
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->join('contracts', 'contracts.id', '=', 'contract_installments.contract_id')
            ->whereNotNull('contracts.guarantor_id');

        $this->applyActiveContractFilter($query, $endedStatusIds, $endedStatusNames, $statusIdCol, $statusNameCol);

        return $query->groupBy('contracts.guarantor_id');
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

    private function countActiveGuarantors(array $endedStatusIds, array $endedStatusNames, ?string $statusIdCol, ?string $statusNameCol): int
    {
        if (method_exists(Guarantor::class, 'contracts')) {
            return Guarantor::whereHas('contracts', function ($query) use ($endedStatusIds, $endedStatusNames, $statusIdCol, $statusNameCol) {
                $this->applyActiveContractFilter($query, $endedStatusIds, $endedStatusNames, $statusIdCol, $statusNameCol);
            })->count();
        }

        if (Schema::hasColumn('contracts', 'guarantor_id')) {
            return Guarantor::whereExists(function ($query) use ($endedStatusIds, $endedStatusNames, $statusIdCol, $statusNameCol) {
                $query->select(DB::raw(1))
                    ->from('contracts')
                    ->whereColumn('contracts.guarantor_id', 'guarantors.id');
                $this->applyActiveContractFilter($query, $endedStatusIds, $endedStatusNames, $statusIdCol, $statusNameCol);
            })->count();
        }

        if (Schema::hasTable('contract_guarantors') || Schema::hasTable('contract_guarantor')) {
            $pivot = Schema::hasTable('contract_guarantors') ? 'contract_guarantors' : 'contract_guarantor';

            return Guarantor::whereExists(function ($query) use ($pivot, $endedStatusIds, $endedStatusNames, $statusIdCol, $statusNameCol) {
                $query->select(DB::raw(1))
                    ->from("{$pivot} as cg")
                    ->whereColumn('cg.guarantor_id', 'guarantors.id')
                    ->whereExists(function ($sub) use ($endedStatusIds, $endedStatusNames, $statusIdCol, $statusNameCol) {
                        $sub->select(DB::raw(1))
                            ->from('contracts')
                            ->whereColumn('contracts.id', 'cg.contract_id');
                        $this->applyActiveContractFilter($sub, $endedStatusIds, $endedStatusNames, $statusIdCol, $statusNameCol);
                    });
            })->count();
        }

        return 0;
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
