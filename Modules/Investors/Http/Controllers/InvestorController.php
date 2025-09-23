<?php

namespace Modules\Investors\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OfficeTransaction;
use Modules\Lookups\Entities\Nationality;
use Modules\Lookups\Entities\Title;
use App\Services\InstallmentsMonthlyService;
use App\Support\InstallmentPeriod;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractInvestor;
use Modules\Lookups\Entities\ContractStatus;
use Modules\Lookups\Entities\TransactionStatus;
use Modules\Investors\Entities\Investor;
use Modules\Investors\Http\Controllers\Concerns\InvestorLiquiditySummaries;
use Modules\Investors\Services\InvestorDataService;
use Modules\Investors\Support\InvestorLiquidityCalculator;
use Modules\Investors\Support\InvestorContractPaymentAggregator;

class InvestorController extends Controller
{
    use InvestorLiquiditySummaries;

    public function index(Request $request)
    {
        // استعلام أساسي
        $query = Investor::query();

        // حقل واحد للبحث بالاسم فقط (زي العملاء)
        $name = trim((string) $request->input('investor_q', ''));
        if ($name !== '') {
            $query->where('name', 'like', '%' . $name . '%');
        }

        // 20 نتيجة لكل صفحة
        $investors = $query->latest()->paginate(20)->withQueryString();

        // كروت عامة (غير متأثرة بالفلاتر)
        $investorsTotalAll = Investor::count();
        $activeInvestorsTotalAll = $this->countActiveInvestors();
        $newInvestorsThisMonthAll = Investor::whereBetween('created_at', [now()->startOfMonth(), now()])->count();
        $newInvestorsThisWeekAll  = Investor::whereBetween('created_at', [now()->startOfWeek(), now()])->count();

        // Per-page aggregates for table columns
        $ids = $investors->pluck('id')->all();
        $liquidityByInvestor = collect();
        $activeCountByInvestor = collect();
        $remainingByInvestor = collect();

        if (!empty($ids)) {
            // Liquidity: sum(in) - sum(out) for non-office entries
            $liquidityByInvestor = InvestorLiquidityCalculator::aggregateTotals(null, $ids)
                ->map(fn ($row) => (float) ($row['net'] ?? 0.0));

            // Active contracts per investor + remaining amount
            $endedStatusIds = $this->endedContractStatusIds();

            $rows = DB::table('contract_investor as ci')
                ->join('contracts as c', 'c.id', '=', 'ci.contract_id')
                ->whereIn('ci.investor_id', $ids)
                ->when(!empty($endedStatusIds), function($q) use ($endedStatusIds) {
                    $q->whereNotIn('c.contract_status_id', $endedStatusIds);
                })
                ->select(
                    'ci.investor_id',
                    'ci.contract_id',
                    'ci.share_percentage',
                    'ci.share_value',
                    'ci.office_share_percentage',
                    'c.contract_value',
                    'c.investor_profit'
                )
                ->get();

            $contractIds = $rows->pluck('contract_id')->filter()->unique()->all();
            $paidByInvestor = collect();

            if (!empty($contractIds)) {
                $paidByInvestor = InvestorContractPaymentAggregator::sumForInvestors($ids, $contractIds);
            }

            // Count active contracts per investor
            $activeCountByInvestor = $rows->groupBy('investor_id')->map(function($g){
                return $g->pluck('contract_id')->unique()->count();
            });

            // Remaining amount per investor
            $remainingByInvestor = $rows->groupBy('investor_id')->map(function($g, $investorId) use ($paidByInvestor) {
                $paymentsByContract = $paidByInvestor->get($investorId);
                if (!$paymentsByContract instanceof Collection) {
                    $paymentsByContract = Collection::make();
                }

                $totalRemaining = $g->reduce(function($carry, $item) use ($paymentsByContract) {
                    $contractId    = (int) ($item->contract_id ?? 0);
                    $contractValue = (float) ($item->contract_value ?? 0.0);
                    $sharePct      = (float) ($item->share_percentage ?? 0.0);
                    $shareVal      = (float) ($item->share_value ?? 0.0);
                    $officePct     = (float) ($item->office_share_percentage ?? 0.0);

                    $shareRatio = 0.0;
                    if ($sharePct > 0) {
                        $shareRatio = $sharePct / 100;
                        if ($shareVal <= 0 && $contractValue > 0) {
                            $shareVal = round($contractValue * $shareRatio, 2);
                        }
                    } elseif ($shareVal > 0 && $contractValue > 0) {
                        $shareRatio = $shareVal / $contractValue;
                    }

                    $shareVal = round($shareVal, 2);

                    $profitGross = 0.0;
                    if ($shareRatio > 0 && isset($item->investor_profit)) {
                        $profitGross = round(((float)$item->investor_profit) * $shareRatio, 2);
                    }

                    $officeCut = round($profitGross * $officePct / 100, 2);

                    $paymentRow = $paymentsByContract->get($contractId, []);
                    $paidInstallments = (float) ($paymentRow['installments'] ?? 0.0);
                    $paidClaims = (float) ($paymentRow['claims'] ?? 0.0);

                    $remaining = round($shareVal + $profitGross - $officeCut - $paidInstallments - $paidClaims, 2);
                    if (abs($remaining) < 0.005) {
                        $remaining = 0.0;
                    }

                    return $carry + $remaining;
                }, 0.0);

                $totalRemaining = round($totalRemaining, 2);
                if (abs($totalRemaining) < 0.005) {
                    $totalRemaining = 0.0;
                }

                return $totalRemaining;
            });
        }

        $nationalities = Nationality::all();
        $titles = Title::all();
        return view('investors::index', compact('investors', 'nationalities', 'titles', 'investorsTotalAll', 'activeInvestorsTotalAll', 'newInvestorsThisMonthAll', 'newInvestorsThisWeekAll', 'liquidityByInvestor', 'activeCountByInvestor', 'remainingByInvestor'));
    }

    public function dashboard(Request $request)
    {
        $investorsTotalAll = Investor::count();
        $activeInvestorsTotalAll = $this->countActiveInvestors();
        $inactiveInvestorsTotalAll = max($investorsTotalAll - $activeInvestorsTotalAll, 0);

        $newInvestorsThisMonthAll = Investor::whereBetween('created_at', [now()->startOfMonth(), now()])->count();
        $newInvestorsThisWeekAll  = Investor::whereBetween('created_at', [now()->startOfWeek(), now()])->count();

        $percentages = [
            'active'   => $investorsTotalAll > 0 ? round(($activeInvestorsTotalAll / $investorsTotalAll) * 100, 1) : 0.0,
            'inactive' => $investorsTotalAll > 0 ? round(($inactiveInvestorsTotalAll / $investorsTotalAll) * 100, 1) : 0.0,
        ];

        $liquidityData = $this->summarizeInvestorLiquidity();
        $liquidityTotals = $liquidityData['totals'];
        $liquidityByInvestor = $liquidityData['perInvestor'];

        $investorLookup = $liquidityByInvestor->isNotEmpty()
            ? Investor::whereIn('id', $liquidityByInvestor->keys()->all())
                ->get(['id', 'name'])
                ->keyBy('id')
            : collect();

        $liquidityReport = $liquidityByInvestor
            ->map(function (array $row, int $investorId) use ($investorLookup) {
                return [
                    'id'   => $investorId,
                    'name' => $investorLookup[$investorId]->name ?? ('#' . $investorId),
                    'in'   => $row['in'],
                    'out'  => $row['out'],
                    'net'  => $row['net'],
                ];
            })
            ->sortByDesc('net')
            ->values();

        $topLiquidity = $liquidityReport
            ->filter(fn ($row) => $row['net'] > 0)
            ->take(10)
            ->values();

        $topLiquidityTotalNet = round((float) $topLiquidity->sum('net'), 2);

        $recentInvestors = Investor::query()
            ->latest()
            ->withCount('contracts')
            ->take(5)
            ->get(['id', 'name', 'created_at', 'investment_start_date', 'office_share_percentage']);

        $investorsWithoutContracts = Investor::doesntHave('contracts')->count();
        $investorsWithContracts = Investor::has('contracts')->count();

        $avgOfficeShare = (float) Investor::avg('office_share_percentage');
        $avgOfficeShare = round($avgOfficeShare ?: 0, 2);

        $endedContractStatusIds = $this->endedContractStatusIds();
        $contractsQuery = DB::table('contract_investor as ci')
            ->join('contracts as c', 'c.id', '=', 'ci.contract_id');

        if (!empty($endedContractStatusIds)) {
            $placeholders = implode(',', array_fill(0, count($endedContractStatusIds), '?'));
            $contractsRow = $contractsQuery
                ->selectRaw('COUNT(DISTINCT c.id) AS total_contracts')
                ->selectRaw("COUNT(DISTINCT CASE WHEN c.contract_status_id NOT IN ($placeholders) THEN c.id END) AS active_contracts", $endedContractStatusIds)
                ->first();
        } else {
            $contractsRow = $contractsQuery
                ->selectRaw('COUNT(DISTINCT c.id) AS total_contracts')
                ->selectRaw('COUNT(DISTINCT c.id) AS active_contracts')
                ->first();
        }

        $contractStats = [
            'total'   => (int) ($contractsRow->total_contracts ?? 0),
            'active'  => (int) ($contractsRow->active_contracts ?? 0),
        ];
        $contractStats['coverage_pct'] = $contractStats['total'] > 0
            ? round(($contractStats['active'] / $contractStats['total']) * 100, 1)
            : 0.0;

        $currencySymbol = config('app.currency_symbol', 'ر.س');
        $outstandingTotals = $this->summarizeOutstandingForInvestors();

        return view('investors::dashboard', [
            'investorsTotalAll'         => $investorsTotalAll,
            'activeInvestorsTotalAll'   => $activeInvestorsTotalAll,
            'inactiveInvestorsTotalAll' => $inactiveInvestorsTotalAll,
            'newInvestorsThisMonthAll'  => $newInvestorsThisMonthAll,
            'newInvestorsThisWeekAll'   => $newInvestorsThisWeekAll,
            'percentages'               => $percentages,
            'liquidityTotals'           => $liquidityTotals,
            'liquidityReport'           => $liquidityReport,
            'topLiquidity'              => $topLiquidity,
            'topLiquidityTotalNet'      => $topLiquidityTotalNet,
            'recentInvestors'           => $recentInvestors,
            'investorsWithoutContracts' => $investorsWithoutContracts,
            'investorsWithContracts'    => $investorsWithContracts,
            'avgOfficeShare'            => $avgOfficeShare,
            'contractStats'             => $contractStats,
            'currencySymbol'            => $currencySymbol,
            'totalRemainingOnCustomersAll'     => $outstandingTotals['remaining_on_customers'],
            'officeProfitRemainingActiveAll'   => $outstandingTotals['office_profit_remaining'],
            'totalRemainingIncludingOfficeAll' => $outstandingTotals['total_remaining_including_office'],
        ]);
    }

    public function create()
    {
        $nationalities = Nationality::all();
        $titles = Title::all();
        return view('investors::create', compact('nationalities', 'titles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('investors', 'name')],
            'national_id' => ['required', 'digits:10', 'regex:/^[12]\d{9}$/', Rule::unique('investors', 'national_id')],
            'phone' => ['required', 'regex:/^(?:05\d{8}|\+?9665\d{8}|009665\d{8})$/', Rule::unique('investors', 'phone')],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'nationality_id' => ['nullable', 'exists:nationalities,id'],
            'title_id' => ['nullable', 'exists:titles,id'],
            'id_card_image' => ['nullable', 'image', 'max:2048'],
            'contract_image' => ['nullable', 'image', 'max:2048'],
            'office_share_percentage' => ['required', 'numeric', 'between:0,100'],
            'investment_start_date' => ['required', 'date'],
        ]);

        if ($request->hasFile('id_card_image')) {
            $validated['id_card_image'] = $request->file('id_card_image')->store('investor/investor_id_cards', 'public');
        }

        if ($request->hasFile('contract_image')) {
            $validated['contract_image'] = $request->file('contract_image')->store('investor/investor_contracts', 'public');
        }

        Investor::create($validated);

        return redirect()->route('investors.index')->with('success', __('investors::investors.Investor created successfully'));
    }

    public function show(Request $request, Investor $investor, InvestorDataService $service, InstallmentsMonthlyService $installmentsSvc)
    {
        // بيانات العرض الأساسية (توافق مع نسخ PHP لا تدعم named args)
        try {
            $data = $service->build($investor, currencySymbol: 'ر.س');
        } catch (\Throwable $e) {
            $data = $service->build($investor, 'ر.س');
        }

        // باراميترات شهر/سنة + حالات مستثناة
        $periodContext = $this->resolveInstallmentPeriodContext($request);
        $periodMonths  = $this->periodMonthOptions();
        $periodYears   = $this->periodYearOptions();
        $requestedMonth = $periodContext['requested_month'] ?? null;
        $requestedYear  = $periodContext['requested_year'] ?? null;
        $selectedPeriodMonth = $periodContext['month'] ?? null;
        $selectedPeriodYear  = $periodContext['year'] ?? null;

        $periodMonthForService = $requestedMonth ?? $selectedPeriodMonth;
        $periodYearForService  = $requestedYear ?? $selectedPeriodYear;
        $excluded = ['مؤجل', 'معتذر'];

        // ملخص الأقساط — أولوية لاستخدام نسخة المستثمر فقط، مع fallback آمن
        try {
            if (method_exists($installmentsSvc, 'buildForInvestor')) {
                // الإصدار الجديد من السيرفيس
                $installmentsMonthly = $installmentsSvc->buildForInvestor($investor, $periodMonthForService, $periodYearForService, $excluded);
            } else {
                // محاولة استخدام توقيع build الجديد (4 معاملات)
                $installmentsMonthly = $installmentsSvc->build($periodMonthForService, $periodYearForService, $excluded, $investor->id);
            }
        } catch (\ArgumentCountError $e) {
            // fallback للإصدار القديم (3 معاملات) — إجمالي النظام
            $installmentsMonthly = $installmentsSvc->build($periodMonthForService, $periodYearForService, $excluded);
        }

        return view('investors::show', [
            'investor'            => $investor,
            'installmentsMonthly' => $installmentsMonthly,
            'periodLabel'         => $periodContext['label'] ?? null,
            'periodMonths'        => $periodMonths,
            'periodYears'         => $periodYears,
            'selectedPeriodMonth' => $selectedPeriodMonth,
            'selectedPeriodYear'  => $selectedPeriodYear,
            'periodContext'       => $periodContext,
        ] + $data);
    }

    public function edit(Investor $investor)
    {
        $nationalities = Nationality::all();
        $titles = Title::all();
        return view('investors::edit', compact('investor', 'nationalities', 'titles'));
    }

    public function update(Request $request, Investor $investor)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('investors', 'name')->ignore($investor->id),
            ],
            'national_id' => [
                'required',
                'digits:10',
                'regex:/^[12]\d{9}$/',
                Rule::unique('investors', 'national_id')->ignore($investor->id),
            ],
            'phone' => [
                'required',
                'regex:/^(?:05\d{8}|\+?9665\d{8}|009665\d{8})$/',
                Rule::unique('investors', 'phone')->ignore($investor->id),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'nationality_id' => ['nullable', 'exists:nationalities,id'],
            'title_id' => ['nullable', 'exists:titles,id'],
            'id_card_image' => ['nullable', 'image', 'max:2048'],
            'contract_image' => ['nullable', 'image', 'max:2048'],
            'office_share_percentage' => ['required', 'numeric', 'between:0,100'],
            'investment_start_date' => ['nullable', 'date'],
        ]);

        if ($request->hasFile('id_card_image')) {
            if ($investor->id_card_image) {
                Storage::disk('public')->delete($investor->id_card_image);
            }
            $validated['id_card_image'] = $request->file('id_card_image')->store('investor/investor_id_cards', 'public');
        }

        if ($request->hasFile('contract_image')) {
            if ($investor->contract_image) {
                Storage::disk('public')->delete($investor->contract_image);
            }
            $validated['contract_image'] = $request->file('contract_image')->store('investor/investor_contracts', 'public');
        }

        $investor->update($validated);

        return redirect()->route('investors.index')->with('success', __('investors::investors.Investor updated successfully'));
    }

    public function destroy(Investor $investor)
    {
        if ($investor->id_card_image) {
            Storage::disk('public')->delete($investor->id_card_image);
        }
        if ($investor->contract_image) {
            Storage::disk('public')->delete($investor->contract_image);
        }

        $investor->delete();

        return redirect()->route('investors.index')->with('success', __('investors::investors.Investor deleted successfully'));
    }

    protected function countActiveInvestors(): int
    {
        $contractInvestorTable = (new ContractInvestor())->getTable();
        $contractsTable = (new Contract())->getTable();
        $investorsTable = (new Investor())->getTable();

        if (Schema::hasTable($contractInvestorTable) && Schema::hasTable($contractsTable)) {
            $query = DB::table($contractInvestorTable . ' as ci')
                ->join($contractsTable . ' as c', 'c.id', '=', 'ci.contract_id');

            if (Schema::hasTable($investorsTable)) {
                $query->join($investorsTable . ' as i', 'i.id', '=', 'ci.investor_id');
            }

            if (Schema::hasColumn($contractInvestorTable, 'deleted_at')) {
                $query->whereNull('ci.deleted_at');
            }

            if (Schema::hasColumn($contractsTable, 'deleted_at')) {
                $query->whereNull('c.deleted_at');
            }

            $statusIdColumn = Schema::hasColumn($contractsTable, 'contract_status_id') ? 'contract_status_id' : null;

            if ($statusIdColumn) {
                $endedStatusIds = $this->endedContractStatusIds();
                if (!empty($endedStatusIds)) {
                    $query->whereNotIn('c.' . $statusIdColumn, $endedStatusIds);
                }
            } else {
                $statusNameColumn = null;
                foreach (['status', 'state'] as $column) {
                    if (Schema::hasColumn($contractsTable, $column)) {
                        $statusNameColumn = $column;
                        break;
                    }
                }

                if ($statusNameColumn) {
                    $query->whereNotIn('c.' . $statusNameColumn, $this->endedContractStatusNames());
                }
            }

            $aggregate = $query
                ->selectRaw('COUNT(DISTINCT ci.investor_id) AS aggregate')
                ->value('aggregate');

            return (int) ($aggregate ?? 0);
        }

        $endedStatusNames = $this->endedInvestmentStatusNames();
        $endedStatusIds = $this->resolveEndedInvestmentStatusIds();

        if (Schema::hasTable('investments') && Schema::hasColumn('investments', 'investor_id')) {
            $statusIdCol = null;
            foreach (['status_id', 'investment_status_id', 'state_id'] as $column) {
                if (Schema::hasColumn('investments', $column)) {
                    $statusIdCol = $column;
                    break;
                }
            }

            $statusNameCol = null;
            foreach (['status', 'state'] as $column) {
                if (Schema::hasColumn('investments', $column)) {
                    $statusNameCol = $column;
                    break;
                }
            }

            return Investor::whereExists(function ($sub) use ($statusIdCol, $statusNameCol, $endedStatusIds, $endedStatusNames) {
                $sub->from('investments')
                    ->selectRaw('1')
                    ->whereColumn('investors.id', 'investments.investor_id');

                if ($statusIdCol && !empty($endedStatusIds)) {
                    $sub->whereNotIn($statusIdCol, $endedStatusIds);
                } elseif ($statusNameCol) {
                    $sub->whereNotIn($statusNameCol, $endedStatusNames);
                } elseif (Schema::hasColumn('investments', 'is_closed')) {
                    $sub->where('is_closed', 0);
                } elseif (Schema::hasColumn('investments', 'closed_at')) {
                    $sub->whereNull('closed_at');
                }
            })->count();
        }

        return Investor::query()
            ->where(function ($q) {
                $added = false;
                if (Schema::hasColumn('investors', 'contract_image')) {
                    $q->whereNotNull('contract_image')->where('contract_image', '!=', '');
                    $added = true;
                }
                if (Schema::hasColumn('investors', 'office_share_percentage')) {
                    $added ? $q->orWhere('office_share_percentage', '>', 0)
                        : $q->where('office_share_percentage', '>', 0);
                }
            })
            ->count();
    }

    protected function summarizeOutstandingForInvestors(): array
    {
        $defaults = [
            'remaining_on_customers'           => 0.0,
            'office_profit_total'              => 0.0,
            'office_profit_collected'          => 0.0,
            'office_profit_remaining'          => 0.0,
            'total_remaining_including_office' => 0.0,
        ];

        $contractInvestorTable = (new ContractInvestor())->getTable();
        $contractsTable = (new Contract())->getTable();
        $investorsTable = (new Investor())->getTable();

        if (
            !Schema::hasTable($contractInvestorTable)
            || !Schema::hasTable($contractsTable)
            || !Schema::hasTable($investorsTable)
        ) {
            return $defaults;
        }

        $query = DB::table($contractInvestorTable . ' as ci')
            ->join($contractsTable . ' as c', 'c.id', '=', 'ci.contract_id')
            ->join($investorsTable . ' as i', 'i.id', '=', 'ci.investor_id')
            ->select([
                'ci.contract_id',
                'ci.investor_id',
                'ci.share_percentage',
                'ci.share_value',
                'ci.office_share_percentage',
                'c.contract_value',
                'c.investor_profit',
            ]);

        $endedStatusIds = $this->endedContractStatusIds();

        $statusIdColumn = null;
        foreach (['contract_status_id', 'status_id', 'state_id'] as $column) {
            if (Schema::hasColumn($contractsTable, $column)) {
                $statusIdColumn = $column;
                break;
            }
        }

        if ($statusIdColumn && !empty($endedStatusIds)) {
            $query->whereNotIn('c.' . $statusIdColumn, $endedStatusIds);
        } else {
            $statusNameColumn = null;
            foreach (['status', 'state', 'contract_status'] as $column) {
                if (Schema::hasColumn($contractsTable, $column)) {
                    $statusNameColumn = $column;
                    break;
                }
            }

            if ($statusNameColumn) {
                $query->whereNotIn('c.' . $statusNameColumn, $this->endedContractStatusNames());
            }
        }

        if (Schema::hasColumn($contractInvestorTable, 'deleted_at')) {
            $query->whereNull('ci.deleted_at');
        }

        if (Schema::hasColumn($contractsTable, 'deleted_at')) {
            $query->whereNull('c.deleted_at');
        }

        if (Schema::hasColumn($investorsTable, 'deleted_at')) {
            $query->whereNull('i.deleted_at');
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            return $defaults;
        }

        $investorIds = $rows->pluck('investor_id')
            ->filter(fn ($value) => !is_null($value))
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values();

        $contractIds = $rows->pluck('contract_id')
            ->filter(fn ($value) => !is_null($value))
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values();

        $paymentsByInvestor = collect();
        if ($investorIds->isNotEmpty() && $contractIds->isNotEmpty()) {
            $paymentsByInvestor = InvestorContractPaymentAggregator::sumForInvestors($investorIds, $contractIds);
        }

        $officePaidLookup = [];
        if ($investorIds->isNotEmpty() && $contractIds->isNotEmpty()) {
            $officeStatusIds = $this->officeProfitStatusIds();

            $officeQuery = OfficeTransaction::query()
                ->from('office_transactions as ot')
                ->whereIn('ot.contract_id', $contractIds)
                ->whereIn('ot.investor_id', $investorIds);

            if (!empty($officeStatusIds)) {
                $officeQuery->whereIn('ot.status_id', $officeStatusIds);
            }

            $officeRows = $officeQuery
                ->groupBy('ot.investor_id', 'ot.contract_id')
                ->selectRaw('ot.investor_id as investor_id, ot.contract_id as contract_id, SUM(ot.amount) as amount')
                ->get();

            foreach ($officeRows as $row) {
                $invId = (int) ($row->investor_id ?? 0);
                $contractId = (int) ($row->contract_id ?? 0);

                if ($invId <= 0 || $contractId <= 0) {
                    continue;
                }

                $amount = round((float) ($row->amount ?? 0.0), 2);

                if ($amount === 0.0) {
                    continue;
                }

                if (!isset($officePaidLookup[$invId])) {
                    $officePaidLookup[$invId] = [];
                }

                $officePaidLookup[$invId][$contractId] = $amount;
            }
        }

        $totalRemainingOnCustomers = 0.0;
        $totalOfficeCut = 0.0;
        $totalOfficePaid = 0.0;

        foreach ($rows as $row) {
            $investorId = (int) ($row->investor_id ?? 0);
            $contractId = (int) ($row->contract_id ?? 0);

            if ($investorId <= 0 || $contractId <= 0) {
                continue;
            }

            $contractValue = (float) ($row->contract_value ?? 0.0);
            $sharePct = (float) ($row->share_percentage ?? 0.0);
            $shareVal = (float) ($row->share_value ?? 0.0);
            $officePct = (float) ($row->office_share_percentage ?? 0.0);
            $investorProfit = (float) ($row->investor_profit ?? 0.0);

            $shareRatio = 0.0;
            if ($sharePct > 0) {
                $shareRatio = $sharePct / 100;

                if ($shareVal <= 0 && $contractValue > 0) {
                    $shareVal = round($contractValue * $shareRatio, 2);
                }
            } elseif ($shareVal > 0 && $contractValue > 0) {
                $shareRatio = $shareVal / $contractValue;
            }

            $shareVal = round($shareVal, 2);

            $profitGross = 0.0;
            if ($shareRatio > 0 && $investorProfit != 0.0) {
                $profitGross = round($investorProfit * $shareRatio, 2);
            }

            $officeCut = round($profitGross * $officePct / 100, 2);
            $profitNet = round($profitGross - $officeCut, 2);

            $paidTotal = 0.0;
            $paymentsForInvestor = $paymentsByInvestor->get($investorId);
            if ($paymentsForInvestor instanceof Collection) {
                $paymentRow = $paymentsForInvestor->get($contractId);
                if (is_array($paymentRow)) {
                    $paidTotal = (float) ($paymentRow['total'] ?? 0.0);
                }
            }
            $paidTotal = round($paidTotal, 2);

            $remaining = round($shareVal + $profitNet - $paidTotal, 2);
            if (abs($remaining) < 0.005) {
                $remaining = 0.0;
            }

            $totalRemainingOnCustomers += $remaining;
            $totalOfficeCut += $officeCut;

            $officePaid = (float) ($officePaidLookup[$investorId][$contractId] ?? 0.0);
            $totalOfficePaid += $officePaid;
        }

        $totalRemainingOnCustomers = round($totalRemainingOnCustomers, 2);
        if (abs($totalRemainingOnCustomers) < 0.005) {
            $totalRemainingOnCustomers = 0.0;
        }

        $totalOfficeCut = round($totalOfficeCut, 2);
        $totalOfficePaid = round($totalOfficePaid, 2);

        $officeProfitRemaining = round($totalOfficeCut - $totalOfficePaid, 2);
        if ($officeProfitRemaining < 0) {
            $officeProfitRemaining = 0.0;
        }

        if (abs($officeProfitRemaining) < 0.005) {
            $officeProfitRemaining = 0.0;
        }

        $totalIncludingOffice = round($totalRemainingOnCustomers + $officeProfitRemaining, 2);
        if (abs($totalIncludingOffice) < 0.005) {
            $totalIncludingOffice = 0.0;
        }

        return [
            'remaining_on_customers'           => $totalRemainingOnCustomers,
            'office_profit_total'              => $totalOfficeCut,
            'office_profit_collected'          => $totalOfficePaid,
            'office_profit_remaining'          => $officeProfitRemaining,
            'total_remaining_including_office' => $totalIncludingOffice,
        ];
    }

    protected function officeProfitStatusIds(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $names = ['ربح المكتب', 'office profit', 'office share'];
        $normalize = static fn ($value) => mb_strtolower(trim((string) $value), 'UTF-8');
        $targets = array_unique(array_map($normalize, $names));

        if (empty($targets)) {
            return $cache = [];
        }

        $ids = TransactionStatus::query()
            ->select(['id', 'name'])
            ->get()
            ->reduce(function (array $carry, $status) use ($normalize, $targets) {
                $name = $normalize($status->name ?? '');

                if ($name !== '' && in_array($name, $targets, true)) {
                    $carry[] = (int) $status->id;
                }

                return $carry;
            }, []);

        return $cache = array_values(array_unique($ids));
    }

    protected function endedInvestmentStatusNames(): array
    {
        return [
            'منتهي',
            'منتهى',
            'منتهي بمطالبة',
            'منتهى بمطالبة',
            'سداد مبكر',
            'سداد مُبكر',
            'سداد مبكّر',
            'Completed',
            'Early Settlement',
            'Closed',
            'Inactive',
            'Ended with claim',
            'Ended With Claim',
            'Claim closed',
        ];
    }

    protected function resolveEndedInvestmentStatusIds(): array
    {
        $names = $this->endedInvestmentStatusNames();
        $investmentStatusTable = 'investment_statuses';
        $investmentStatusClass = '\\Modules\\Lookups\\Entities\\InvestmentStatus';

        if (class_exists($investmentStatusClass) && Schema::hasTable($investmentStatusTable)) {
            return $investmentStatusClass::whereIn('name', $names)->pluck('id')->all();
        }

        $contractStatusTable = (new ContractStatus())->getTable();
        if (Schema::hasTable($contractStatusTable)) {
            return ContractStatus::whereIn('name', $names)->pluck('id')->all();
        }

        return [];
    }

    protected function endedContractStatusIds(): array
    {
        $names = $this->endedContractStatusNames();
        $contractStatusTable = (new ContractStatus())->getTable();

        if (!Schema::hasTable($contractStatusTable)) {
            return [];
        }

        return ContractStatus::whereIn('name', $names)->pluck('id')->all();
    }

    protected function endedContractStatusNames(): array
    {
        return [
            'مكتمل',
            'منتهي',
            'منتهى',
            'منتهي بمطالبة',
            'منتهى بمطالبة',
            'سداد مبكر',
            'سداد مُبكر',
            'سداد مبكّر',
            'إلغاء',
            'Closed',
            'Completed',
            'Early Settlement',
            'Inactive',
            'Ended with claim',
            'Ended With Claim',
            'Claim closed',
        ];
    }

    protected function resolveInstallmentPeriodContext(Request $request): array
    {
        $month = $this->normalizeMonth($request->input('period_month'));
        if ($month === null) {
            $month = $this->normalizeMonth($request->input('m'));
        }

        $year = $this->normalizeYear($request->input('period_year'));
        if ($year === null) {
            $year = $this->normalizeYear($request->input('y'));
        }

        $resolved = InstallmentPeriod::resolve($month, $year, Carbon::now());

        $start = $resolved['start']->copy();
        $end   = $resolved['end']->copy();

        return [
            'start'           => $start,
            'end'             => $end,
            'month'           => (int) $start->month,
            'year'            => (int) $start->year,
            'label'           => $start->format('Y-m-d') . ' — ' . $end->format('Y-m-d'),
            'requested_month' => $month,
            'requested_year'  => $year,
        ];
    }

    protected function normalizeMonth($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (int) $value;

        return $value >= 1 && $value <= 12 ? $value : null;
    }

    protected function normalizeYear($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (int) $value;

        return $value >= 1900 && $value <= 2100 ? $value : null;
    }

    protected function periodMonthOptions(): array
    {
        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $months[$month] = Carbon::create(null, $month, 1)
                ->locale(app()->getLocale())
                ->translatedFormat('F');
        }

        return $months;
    }

    protected function periodYearOptions(): array
    {
        $currentYear = Carbon::now()->year;
        $years = [];

        for ($year = $currentYear - 5; $year <= $currentYear + 1; $year++) {
            $years[$year] = (string) $year;
        }

        return $years;
    }
}
