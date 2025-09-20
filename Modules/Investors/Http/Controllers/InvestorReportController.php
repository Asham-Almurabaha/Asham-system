<?php

namespace Modules\Investors\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Contracts\Entities\Contract;
use Modules\Lookups\Entities\Category;
use Modules\Lookups\Entities\ContractStatus;
use Modules\Lookups\Entities\TransactionStatus;
use Modules\Lookups\Entities\TransactionType;
use Modules\Investors\Entities\Investor;
use Modules\Investors\Services\InvestorDataService;

class InvestorReportController extends Controller
{
    public function statement(Investor $investor, InvestorDataService $service)
    {
        // ابني كل الأرقام من الخدمة
        $data = $service->build($investor, currencySymbol: 'ر.س');

        // (اختياري) حالات العقود لعرضها في الجدول
        $contractIds = collect($data['contractBreakdown'] ?? [])->pluck('contract_id')->filter()->values();
        $statusMap = collect();
        if ($contractIds->isNotEmpty()) {
            $statusMap = Contract::query()
            ->with('contractStatus:id,name')
            ->whereIn('id', $contractIds)
            ->get(['id','contract_status_id'])
            ->mapWithKeys(function ($c) {
                $name = $c->contractStatus->name ?? null;
                if (!$name) {
                    if (!is_null($c->is_closed) && (int)$c->is_closed === 1) $name = 'مغلق';
                    elseif (!empty($c->closed_at)) $name = 'مغلق';
                    else $name = 'ساري';
                }
                return [$c->id => $name];
            });
        }

        return view('investors::reports.statement', [
            'investor'  => $investor,
            'data'      => $data,
            'statusMap' => $statusMap,
        ]);
    }

    public function deposits(Investor $investor)
    {
        $deposits = LedgerEntry::query()
            ->with(['status:id,name', 'type:id,name'])
            ->where('investor_id', $investor->id)
            ->where('direction', 'in')
            ->latest('entry_date')
            ->get();

        $depositsTotal = $deposits->sum('amount');

        return view('investors::reports.deposits', compact('investor', 'deposits', 'depositsTotal'));
    }

    public function depositsInstallments(Investor $investor)
    {
        $status = TransactionStatus::query()
            ->where('name', 'سداد قسط')
            ->first(['id', 'name']);

        $deposits = LedgerEntry::query()
            ->with(['status:id,name', 'type:id,name'])
            ->where('investor_id', $investor->id)
            ->where('direction', 'in')
            ->when(
                $status?->id,
                fn($query, $statusId) => $query->where('transaction_status_id', $statusId)
            )
            ->latest('entry_date')
            ->get();

        $depositsTotal = $deposits->sum('amount');

        return view('investors::reports.deposits', [
            'investor'          => $investor,
            'deposits'          => $deposits,
            'depositsTotal'     => $depositsTotal,
            'depositsCount'     => $deposits->count(),
            'reportTitle'       => __('reports.Installment Deposits Summary'),
            'statusFilterName'  => $status?->name ?? 'سداد قسط',
        ]);
    }

    public function depositsLedger(Request $request, Investor $investor)
    {
        $statusData = $this->investorStatusesByTransactionType('إيداع', ['سداد قسط']);
        $deposits = $this->buildLedgerEntryCollection($request, $investor, 'in', $statusData['ids']);

        $depositsTotal = $deposits->sum('amount');

        return view('investors::reports.deposits', [
            'investor'          => $investor,
            'deposits'          => $deposits,
            'depositsTotal'     => $depositsTotal,
            'depositsCount'     => $deposits->count(),
            'reportTitle'       => __('reports.Investor Ledger Deposits Summary'),
            'statusFilterName'  => $this->formatStatusList($statusData['names']),
        ]);
    }

    public function withdrawals(Investor $investor)
    {
        $withdrawals = LedgerEntry::query()
            ->with(['status:id,name', 'type:id,name'])
            ->where('investor_id', $investor->id)
            ->where('direction', 'out')
            ->latest('entry_date')
            ->get();

        $withdrawalsTotal = $withdrawals->sum('amount');
        $withdrawalsCount = $withdrawals->count();

        return view(
            'investors::reports.withdrawals',
            compact('investor', 'withdrawals', 'withdrawalsTotal', 'withdrawalsCount')
        );
    }

    public function withdrawalsLedger(Request $request, Investor $investor)
    {
        $statusData = $this->investorStatusesByTransactionType('سحب', ['إضافة عقد']);
        $withdrawals = $this->buildLedgerEntryCollection($request, $investor, 'out', $statusData['ids']);

        $withdrawalsTotal = $withdrawals->sum('amount');
        $withdrawalsCount = $withdrawals->count();

        return view('investors::reports.withdrawals', [
            'investor'         => $investor,
            'withdrawals'      => $withdrawals,
            'withdrawalsTotal' => $withdrawalsTotal,
            'withdrawalsCount' => $withdrawalsCount,
            'reportTitle'      => __('reports.Investor Ledger Withdrawals Summary'),
            'statusFilterName' => $this->formatStatusList($statusData['names']),
        ]);
    }

    public function withdrawalsAddContract(Investor $investor)
    {
        $status = TransactionStatus::query()
            ->where('name', 'إضافة عقد')
            ->first(['id', 'name']);

        $withdrawals = LedgerEntry::query()
            ->with(['status:id,name', 'type:id,name'])
            ->where('investor_id', $investor->id)
            ->where('direction', 'out')
            ->when(
                $status?->id,
                fn($query, $statusId) => $query->where('transaction_status_id', $statusId)
            )
            ->latest('entry_date')
            ->get();

        $withdrawalsTotal = $withdrawals->sum('amount');
        $withdrawalsCount = $withdrawals->count();

        return view('investors::reports.withdrawals', [
            'investor'         => $investor,
            'withdrawals'      => $withdrawals,
            'withdrawalsTotal' => $withdrawalsTotal,
            'withdrawalsCount' => $withdrawalsCount,
            'reportTitle'      => __('reports.Add Contract Withdrawals Summary'),
            'statusFilterName' => $status?->name ?? 'إضافة عقد',
        ]);
    }

    public function transactions(Investor $investor)
    {
        $transactions = LedgerEntry::query()
            ->with(['status:id,name', 'type:id,name'])
            ->where('investor_id', $investor->id)
            ->latest('entry_date')
            ->get();

        $transactionsCount = $transactions->count();
        $depositsTotal    = $transactions->where('direction', 'in')->sum('amount');
        $withdrawalsTotal = $transactions->where('direction', 'out')->sum('amount');

        return view(
            'investors::reports.transactions',
            compact(
                'investor',
                'transactions',
                'transactionsCount',
                'depositsTotal',
                'withdrawalsTotal'
            )
        );
    }

    public function outstanding(Request $request)
    {
        $filters = [
            'q'        => $request->query('q'),
            'per_page' => (int) $request->query('per_page', 25),
        ];

        $query = Investor::query()
            ->select(['id', 'name', 'office_share_percentage'])
            ->when($filters['q'], function ($q, $v) {
                $q->where('name', 'like', "%{$v}%");
            })
            ->orderBy('name');

        $rows = $query
            ->paginate(max(1, $filters['per_page']))
            ->withQueryString();

        $currencySymbol = 'ر.س';
        $officeStatusId = TransactionStatus::where('name', 'ربح المكتب')->value('id');
        $endedNames = ['مكتمل', 'منتهي', 'سداد مبكر', 'إلغاء', 'Closed', 'Completed', 'Early Settlement', 'Inactive'];
        $endedIds = ContractStatus::whereIn('name', $endedNames)->pluck('id')->all();

        $ledgerSub = DB::table('ledger_entries')
            ->selectRaw('investor_id, contract_id, SUM(amount) AS paid_in')
            ->where('direction', 'in')
            ->whereNull('deleted_at')
            ->groupBy('investor_id', 'contract_id');

        $officePaidSub = DB::table('office_transactions as ot')
            ->selectRaw('ot.investor_id, ot.contract_id, SUM(ot.amount) AS office_paid')
            ->when($officeStatusId, function ($q) use ($officeStatusId) {
                $q->where('ot.status_id', $officeStatusId);
            })
            ->groupBy('ot.investor_id', 'ot.contract_id');

        $capitalExprRaw = "CASE WHEN COALESCE(ci.share_value, 0) <= 0 THEN COALESCE(c.contract_value, 0) * COALESCE(ci.share_percentage, 0) / 100 ELSE COALESCE(ci.share_value, 0) END";
        $capitalExpr = "ROUND($capitalExprRaw, 2)";
        $shareRatioExpr = "CASE WHEN COALESCE(ci.share_percentage, 0) > 0 THEN COALESCE(ci.share_percentage, 0) / 100 WHEN COALESCE(ci.share_value, 0) > 0 AND COALESCE(c.contract_value, 0) > 0 THEN COALESCE(ci.share_value, 0) / NULLIF(c.contract_value, 0) ELSE 0 END";
        $profitGrossExpr = "ROUND(COALESCE(c.investor_profit, 0) * ($shareRatioExpr), 2)";
        $officePctExpr = 'COALESCE(inv.office_share_percentage, 0)';
        $officeCutExpr = "ROUND($profitGrossExpr * $officePctExpr / 100, 2)";
        $paidExpr = 'COALESCE(le.paid_in, 0)';
        $expectedWithExpr = "($capitalExpr + $profitGrossExpr)";
        $remainingWithExpr = "GREATEST($expectedWithExpr - $paidExpr, 0)";
        $officePaidExpr = "LEAST(COALESCE(op.office_paid, 0), $officeCutExpr)";
        $remainingOfficeExpr = "GREATEST($officeCutExpr - $officePaidExpr, 0)";
        $remainingWithoutExpr = "GREATEST($remainingWithExpr - $remainingOfficeExpr, 0)";

        $totalsRow = DB::table('contract_investor as ci')
            ->join('contracts as c', 'ci.contract_id', '=', 'c.id')
            ->join('investors as inv', 'ci.investor_id', '=', 'inv.id')
            ->leftJoinSub($ledgerSub, 'le', function ($join) {
                $join->on('le.contract_id', '=', 'ci.contract_id')
                    ->on('le.investor_id', '=', 'ci.investor_id');
            })
            ->leftJoinSub($officePaidSub, 'op', function ($join) {
                $join->on('op.contract_id', '=', 'ci.contract_id')
                    ->on('op.investor_id', '=', 'ci.investor_id');
            })
            ->when(!empty($endedIds), function ($q) use ($endedIds) {
                $q->whereNotIn('c.contract_status_id', $endedIds);
            })
            ->when($filters['q'], function ($q, $v) {
                $q->where('inv.name', 'like', "%{$v}%");
            })
            ->selectRaw(
                "SUM(ROUND($remainingWithExpr, 2)) AS remaining_with_office, " .
                "SUM(ROUND($remainingWithoutExpr, 2)) AS remaining_without_office, " .
                "SUM(ROUND($remainingOfficeExpr, 2)) AS remaining_office_share"
            )
            ->first();

        $grandTotals = [
            'with_office'    => $totalsRow ? round((float) ($totalsRow->remaining_with_office ?? 0), 2) : 0.0,
            'without_office' => $totalsRow ? round((float) ($totalsRow->remaining_without_office ?? 0), 2) : 0.0,
        ];
        $grandTotals['office_share'] = $totalsRow ? round((float) ($totalsRow->remaining_office_share ?? 0), 2) : 0.0;

        $items = $rows->getCollection();
        $ids = $items->pluck('id');

        $pageTotals = [
            'with_office'    => 0.0,
            'without_office' => 0.0,
            'office_share'   => 0.0,
        ];

        if ($ids->isNotEmpty()) {
            $contractRows = DB::table('contract_investor as ci')
                ->join('contracts as c', 'ci.contract_id', '=', 'c.id')
                ->whereIn('ci.investor_id', $ids)
                ->when(!empty($endedIds), function ($q) use ($endedIds) {
                    $q->whereNotIn('c.contract_status_id', $endedIds);
                })
                ->select([
                    'ci.investor_id',
                    'ci.contract_id',
                    'ci.share_percentage',
                    'ci.share_value',
                    'c.contract_value',
                    'c.investor_profit',
                ])
                ->get();

            $contractsByInvestor = $contractRows->groupBy('investor_id');
            $contractIds = $contractRows->pluck('contract_id')->filter()->unique();

            $paidByInvestor = collect();
            $officePaidByInvestor = collect();
            if ($contractIds->isNotEmpty()) {
                $paidRows = DB::table('ledger_entries')
                    ->selectRaw('investor_id, contract_id, SUM(amount) AS paid_in')
                    ->whereIn('investor_id', $ids)
                    ->whereIn('contract_id', $contractIds)
                    ->where('direction', 'in')
                    ->whereNull('deleted_at')
                    ->groupBy('investor_id', 'contract_id')
                    ->get();

                $paidByInvestor = $paidRows->groupBy('investor_id')->map(function ($rows) {
                    return $rows->mapWithKeys(fn ($r) => [$r->contract_id => (float) ($r->paid_in ?? 0)]);
                });

                $officePaidRows = DB::table('office_transactions as ot')
                    ->selectRaw('ot.investor_id, ot.contract_id, SUM(ot.amount) AS office_paid')
                    ->whereIn('ot.investor_id', $ids)
                    ->whereIn('ot.contract_id', $contractIds)
                    ->when($officeStatusId, function ($q) use ($officeStatusId) {
                        $q->where('ot.status_id', $officeStatusId);
                    })
                    ->groupBy('ot.investor_id', 'ot.contract_id')
                    ->get();

                $officePaidByInvestor = $officePaidRows->groupBy('investor_id')->map(function ($rows) {
                    return $rows->mapWithKeys(fn ($r) => [$r->contract_id => (float) ($r->office_paid ?? 0)]);
                });
            }

            $items->transform(function ($investor) use ($contractsByInvestor, $paidByInvestor, $officePaidByInvestor, &$pageTotals) {
                $id = $investor->id;
                $pctOffice = (float) ($investor->office_share_percentage ?? 0);
                $contracts = $contractsByInvestor->get($id, collect());
                $paidMap = $paidByInvestor->get($id, collect());
                $officePaidMap = $officePaidByInvestor->get($id, collect());

                $withOffice = 0.0;
                $withoutOffice = 0.0;
                $officeShare = 0.0;

                foreach ($contracts as $contract) {
                    $contractValue = (float) ($contract->contract_value ?? 0);
                    $sharePct = (float) ($contract->share_percentage ?? 0);
                    $shareVal = (float) ($contract->share_value ?? 0);

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
                    if ($shareRatio > 0 && isset($contract->investor_profit)) {
                        $profitGross = round(((float) $contract->investor_profit) * $shareRatio, 2);
                    }

                    $officeCut = round($profitGross * $pctOffice / 100, 2);
                    $expectedWith = round($shareVal + $profitGross, 2);

                    if ($paidMap instanceof \Illuminate\Support\Collection) {
                        $paid = (float) $paidMap->get($contract->contract_id, 0.0);
                    } else {
                        $paid = (float) ($paidMap[$contract->contract_id] ?? 0.0);
                    }

                    if ($officePaidMap instanceof \Illuminate\Support\Collection) {
                        $officePaid = (float) $officePaidMap->get($contract->contract_id, 0.0);
                    } else {
                        $officePaid = (float) ($officePaidMap[$contract->contract_id] ?? 0.0);
                    }

                    $officePaid = min($officeCut, round($officePaid, 2));
                    $remainingOffice = max(0.0, round($officeCut - $officePaid, 2));

                    $remainingWith = max(0.0, round($expectedWith - $paid, 2));
                    $remainingWithout = max(0.0, round($remainingWith - $remainingOffice, 2));

                    $withOffice += $remainingWith;
                    $withoutOffice += $remainingWithout;
                    $officeShare += $remainingOffice;
                }

                $investor->remaining_with_office = round($withOffice, 2);
                $investor->remaining_without_office = round($withoutOffice, 2);
                $investor->remaining_office_share = round($officeShare, 2);

                $pageTotals['with_office'] += $investor->remaining_with_office;
                $pageTotals['without_office'] += $investor->remaining_without_office;
                $pageTotals['office_share'] += $investor->remaining_office_share;

                return $investor;
            });
        }

        $pageTotals = [
            'with_office'    => round($pageTotals['with_office'], 2),
            'without_office' => round($pageTotals['without_office'], 2),
            'office_share'   => round(max(0.0, $pageTotals['office_share']), 2),
        ];

        return view('investors::reports.outstanding', [
            'rows'           => $rows,
            'filters'        => $filters,
            'currencySymbol' => $currencySymbol,
            'grandTotals'    => $grandTotals,
            'pageTotals'     => $pageTotals,
        ]);
    }

    public function allliquidity(Request $request)
    {
        $filters = [
            'q'        => $request->query('q'),
            'per_page' => (int) $request->query('per_page', 25),
        ];

        $query = Investor::query()
            ->select(['id', 'name'])
            ->when($filters['q'], function ($q, $v) {
                $q->where('name', 'like', "%{$v}%");
            })
            ->orderBy('name');

        $rows = $query
            ->paginate(max(1, $filters['per_page']))
            ->withQueryString();

        $currencySymbol = 'ر.س';
        $ids = $rows->pluck('id');

        $liquidityByInvestor = collect();
        $contractStats = collect();
        $activeContractNumbers = collect();
        if ($ids->isNotEmpty()) {
            $liquidityByInvestor = LedgerEntry::query()
                ->whereIn('investor_id', $ids)
                ->where('is_office', false)
                ->groupBy('investor_id')
                ->selectRaw("investor_id, COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END),0) AS in_sum, COALESCE(SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END),0) AS out_sum")
                ->get()
                ->mapWithKeys(fn ($r) => [$r->investor_id => ((float) $r->in_sum - (float) $r->out_sum)]);

            $endedIds = ContractStatus::whereIn('name', [
                'مكتمل','منتهي','سداد مبكر','إلغاء','Closed','Completed','Early Settlement','Inactive'
            ])->pluck('id')->all();

            $notIn = empty($endedIds) ? '0' : implode(',', $endedIds);

            $contractStats = DB::table('contract_investor as ci')
                ->join('contracts as c', 'ci.contract_id', '=', 'c.id')
                ->whereIn('ci.investor_id', $ids)
                ->groupBy('ci.investor_id')
                ->selectRaw(
                    "ci.investor_id, " .
                    "SUM(CASE WHEN c.contract_status_id NOT IN ($notIn) THEN ci.share_value ELSE 0 END) AS initial_capital, " .
                    "SUM(CASE WHEN c.contract_status_id NOT IN ($notIn) THEN 1 ELSE 0 END) AS contracts_active, " .
                    "COUNT(*) AS contracts_total"
                )
                ->get()
                ->keyBy('investor_id');

            $activeContractNumbers = DB::table('contract_investor as ci')
                ->join('contracts as c', 'ci.contract_id', '=', 'c.id')
                ->whereIn('ci.investor_id', $ids)
                ->when(!empty($endedIds), fn ($query) => $query->whereNotIn('c.contract_status_id', $endedIds))
                ->orderBy('c.contract_number')
                ->select(['ci.investor_id', 'c.contract_number'])
                ->get()
                ->groupBy('investor_id')
                ->map(fn ($rows) => $rows->pluck('contract_number')->unique()->values());
        }

        $rows->getCollection()->transform(function ($r) use ($liquidityByInvestor, $contractStats, $activeContractNumbers) {
            $id = $r->id;
            $stats = $contractStats[$id] ?? null;
            $r->liquidity        = (float) ($liquidityByInvestor[$id] ?? 0);
            $r->contracts_active = $stats ? (int) ($stats->contracts_active ?? 0) : 0;
            $contracts = $activeContractNumbers[$id] ?? collect();
            $contracts = $contracts instanceof Collection ? $contracts : collect($contracts);
            $r->active_contract_numbers = $contracts->values()->all();
            if (!$r->contracts_active) {
                $r->contracts_active = $contracts->count();
            }
            return $r;
        });

        $grandTotal = (float) $rows->getCollection()->sum('liquidity');

        return view('investors::reports.allliquidity', [
            'rows'           => $rows,
            'grandTotal'     => $grandTotal,
            'filters'        => $filters,
            'currencySymbol' => $currencySymbol,
        ]);
    }

    private function buildLedgerEntryCollection(Request $request, Investor $investor, string $direction, array $statusIds = [])
    {
        return LedgerEntry::query()
            ->with(['status:id,name', 'type:id,name'])
            ->where('investor_id', $investor->id)
            ->where('direction', $direction)
            ->when(!empty($statusIds), fn ($query) => $query->whereIn('transaction_status_id', $statusIds))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('entry_date', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('entry_date', '<=', $request->query('to')))
            ->latest('entry_date')
            ->get();
    }

    private function investorStatusesByTransactionType(string $typeName, array $excludeNames = []): array
    {
        static $cache = [];

        $cacheKey = $typeName . '|' . implode('|', $excludeNames);
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $type = TransactionType::query()
            ->where('name', $typeName)
            ->first(['id']);

        if (!$type) {
            return $cache[$cacheKey] = ['ids' => [], 'names' => []];
        }

        $category = Category::query()
            ->where('name', 'المستثمرين')
            ->first(['id']);

        $statuses = TransactionStatus::query()
            ->select(['id', 'name'])
            ->where('transaction_type_id', $type->id)
            ->when($category, function ($query) use ($category) {
                $query->whereHas('categories', fn ($cat) => $cat->where('categories.id', $category->id));
            })
            ->when(!empty($excludeNames), fn ($query) => $query->whereNotIn('name', $excludeNames))
            ->orderBy('name')
            ->get();

        return $cache[$cacheKey] = [
            'ids'   => $statuses->pluck('id')->all(),
            'names' => $statuses->pluck('name')->all(),
        ];
    }

    private function formatStatusList(array $names): ?string
    {
        $filtered = array_values(array_filter($names, fn ($name) => !is_null($name) && $name !== ''));

        if (empty($filtered)) {
            return null;
        }

        $separator = app()->getLocale() === 'ar' ? '، ' : ', ';

        return implode($separator, $filtered);
    }
}
