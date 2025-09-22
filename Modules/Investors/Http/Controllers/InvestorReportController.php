<?php

namespace Modules\Investors\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Support\TransactionDirection;
use Modules\Lookups\Entities\Category;
use Modules\Lookups\Entities\ContractStatus;
use Modules\Lookups\Entities\TransactionStatus;
use Modules\Lookups\Entities\TransactionType;
use Modules\Investors\Entities\Investor;
use Modules\Investors\Entities\InvestorTransaction;
use Modules\Investors\Http\Controllers\Concerns\InvestorLiquiditySummaries;
use Modules\Investors\Services\InvestorDataService;
use Modules\Investors\Support\InvestorLiquidityCalculator;
use Modules\Investors\Support\InvestorContractPaymentAggregator;

class InvestorReportController extends Controller
{
    use InvestorLiquiditySummaries;

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

    public function depositsWithdrawalsReport(Request $request)
    {
        $investorId = $request->query('investor_id');
        $filters = [
            'investor_id' => $investorId !== null && $investorId !== ''
                ? (int) $investorId
                : null,
        ];

        $investorOptions = Investor::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $rows = Investor::query()
            ->select(['id', 'name'])
            ->when($filters['investor_id'], fn ($query, $id) => $query->whereKey($id))
            ->orderBy('name')
            ->get();

        $pageIds = $rows->pluck('id');
        $pageLiquidity = $this->summarizeInvestorLiquidity($pageIds);
        $pageByInvestor = $pageLiquidity['perInvestor'];
        $pageTotals = $pageLiquidity['totals'];

        $rows = $rows->map(function ($investor) use ($pageByInvestor) {
            $stats = $pageByInvestor->get($investor->id, ['in' => 0.0, 'out' => 0.0, 'net' => 0.0]);
            $investor->total_in = $stats['in'] ?? 0.0;
            $investor->total_out = $stats['out'] ?? 0.0;
            $investor->net_liquidity = $stats['net'] ?? 0.0;

            return $investor;
        })->values();

        $overallData = InvestorLiquidityCalculator::aggregateTotals(
            null,
            $filters['investor_id'] ? [$filters['investor_id']] : null
        );

        $overallTotals = [
            'in'  => round((float) $overallData->sum(fn ($row) => (float) ($row['in'] ?? 0)), 2),
            'out' => round((float) $overallData->sum(fn ($row) => (float) ($row['out'] ?? 0)), 2),
        ];
        $overallTotals['net'] = round($overallTotals['in'] - $overallTotals['out'], 2);

        return view('investors::reports.deposits-withdrawals-investors', [
            'rows'           => $rows,
            'filters'        => $filters,
            'currencySymbol' => 'ر.س',
            'pageTotals'     => $pageTotals,
            'overallTotals'  => $overallTotals,
            'investors'      => $investorOptions,
        ]);
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
        $transactions = InvestorTransaction::query()
            ->with([
                'status:id,name,transaction_type_id',
                'status.transactionType:id,name',
            ])
            ->where('investor_id', $investor->id)
            ->latest('transaction_date')
            ->get();

        $typeBuckets = InvestorLiquidityCalculator::transactionTypeBuckets();
        $directionByType = collect($typeBuckets['in'] ?? [])
            ->mapWithKeys(fn ($typeId) => [(int) $typeId => 'in'])
            ->merge(collect($typeBuckets['out'] ?? [])
                ->mapWithKeys(fn ($typeId) => [(int) $typeId => 'out']))
            ->all();

        $transactions = $transactions->map(function ($transaction) use ($directionByType) {
            $status = $transaction->status;
            $typeId = $status ? (int) ($status->transaction_type_id ?? 0) : 0;
            $direction = $directionByType[$typeId] ?? null;

            if ($direction === null) {
                $typeName = $status?->transactionType?->name;
                $direction = TransactionDirection::directionFromTypeName($typeName);
            }

            $transaction->setAttribute('cash_direction', $direction);

            return $transaction;
        });

        $transactionsCount = $transactions->count();
        $depositsTotal = $transactions
            ->filter(fn ($transaction) => $transaction->getAttribute('cash_direction') === 'in')
            ->sum('amount');
        $withdrawalsTotal = $transactions
            ->filter(fn ($transaction) => $transaction->getAttribute('cash_direction') === 'out')
            ->sum('amount');

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
        $investorId = $request->query('investor_id');
        $filters = [
            'investor_id' => $investorId !== null && $investorId !== ''
                ? (int) $investorId
                : null,
        ];

        $investorOptions = Investor::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $rows = Investor::query()
            ->select(['id', 'name', 'office_share_percentage'])
            ->when($filters['investor_id'], fn ($query, $id) => $query->whereKey($id))
            ->orderBy('name')
            ->get();

        $currencySymbol = 'ر.س';
        $officeStatusId = TransactionStatus::where('name', 'ربح المكتب')->value('id');
        $endedNames = [
            'مكتمل',
            'منتهي',
            'سداد مبكر',
            'إلغاء',
            'Closed',
            'Completed',
            'Early Settlement',
            'Inactive',
            'منتهي بمطالبة',
            'منتهى بمطالبة',
            'مُنتهي بمطالبة',
            'مُنتهى بمطالبة',
        ];
        $endedIds = ContractStatus::whereIn('name', $endedNames)->pluck('id')->all();

        $statusRows = ContractStatus::query()
            ->select(['id', 'name'])
            ->get();

        $claimStatusIds = $statusRows
            ->filter(function ($status) {
                $normalized = $this->normalizeStatusName($status->name ?? null);

                return in_array($normalized, $this->claimStatusNames(), true);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $paymentStatusBuckets = InvestorContractPaymentAggregator::transactionStatusBuckets();
        $paymentStatusIds = array_values(array_unique(array_merge(
            $paymentStatusBuckets['installment'] ?? [],
            $paymentStatusBuckets['claim'] ?? []
        )));

        $paidSub = DB::table('investor_transactions as it')
            ->selectRaw('it.investor_id, it.contract_id, SUM(it.amount) AS paid_in')
            ->groupBy('it.investor_id', 'it.contract_id');

        if (!empty($paymentStatusIds)) {
            $paidSub->whereIn('it.status_id', $paymentStatusIds);
        } else {
            $paidSub->whereRaw('0 = 1');
        }

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
        $officePctExpr = "CASE WHEN COALESCE(ci.office_share_percentage, 0) > 0 THEN COALESCE(ci.office_share_percentage, 0) ELSE COALESCE(inv.office_share_percentage, 0) END";
        $officeCutExpr = "ROUND($profitGrossExpr * $officePctExpr / 100, 2)";
        $paidExpr = 'COALESCE(paid.paid_in, 0)';
        $profitNetExpr = "GREATEST(ROUND($profitGrossExpr - $officeCutExpr, 2), 0)";
        $profitPaidExpr = "LEAST(GREATEST($paidExpr - $capitalExpr, 0), $profitNetExpr)";
        $unpaidProfitExpr = "GREATEST($profitNetExpr - $profitPaidExpr, 0)";
        $expectedWithExpr = "($capitalExpr + $profitGrossExpr)";
        $remainingWithBaseExpr = "GREATEST($expectedWithExpr - $paidExpr, 0)";
        $officePaidExpr = "LEAST(COALESCE(op.office_paid, 0), $officeCutExpr)";
        $remainingOfficeExpr = "GREATEST($officeCutExpr - $officePaidExpr, 0)";
        $claimAdjustmentExpr = '0';
        if (!empty($claimStatusIds)) {
            $claimIdList = implode(',', array_map('intval', $claimStatusIds));
            $claimAdjustmentExpr = "CASE WHEN c.contract_status_id IN ($claimIdList) THEN $unpaidProfitExpr ELSE 0 END";
        }
        $remainingWithExpr = "GREATEST($remainingWithBaseExpr - $claimAdjustmentExpr, 0)";
        $remainingWithoutExpr = "GREATEST($remainingWithBaseExpr - $remainingOfficeExpr - $claimAdjustmentExpr, 0)";

        $totalsRow = DB::table('contract_investor as ci')
            ->join('contracts as c', 'ci.contract_id', '=', 'c.id')
            ->join('investors as inv', 'ci.investor_id', '=', 'inv.id')
            ->leftJoinSub($paidSub, 'paid', function ($join) {
                $join->on('paid.contract_id', '=', 'ci.contract_id')
                    ->on('paid.investor_id', '=', 'ci.investor_id');
            })
            ->leftJoinSub($officePaidSub, 'op', function ($join) {
                $join->on('op.contract_id', '=', 'ci.contract_id')
                    ->on('op.investor_id', '=', 'ci.investor_id');
            })
            ->when(!empty($endedIds), function ($q) use ($endedIds) {
                $q->whereNotIn('c.contract_status_id', $endedIds);
            })
            ->when($filters['investor_id'], fn ($q, $id) => $q->where('inv.id', $id))
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

        $ids = $rows->pluck('id');

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
                    'c.contract_status_id',
                ])
                ->get();

            $contractsByInvestor = $contractRows->groupBy('investor_id');
            $contractIds = $contractRows->pluck('contract_id')->filter()->unique();

            $paidByInvestor = collect();
            $officePaidByInvestor = collect();
            if ($contractIds->isNotEmpty()) {
                $paidByInvestor = InvestorContractPaymentAggregator::sumForInvestors($ids, $contractIds);

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

            $rows = $rows->map(function ($investor) use (
                $contractsByInvestor,
                $paidByInvestor,
                $officePaidByInvestor,
                $claimStatusIds
            ) {
                $investorId = (int) ($investor->id ?? 0);
                $pctOffice = (float) ($investor->office_share_percentage ?? 0);
                $contracts = $contractsByInvestor->get($investorId, collect());
                $contracts = $contracts instanceof \Illuminate\Support\Collection
                    ? $contracts
                    : collect($contracts);
                $paymentsMap = $paidByInvestor->get($investorId, collect());
                $officePaidMap = $officePaidByInvestor->get($investorId, collect());

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

                    $paymentRow = [];
                    if ($paymentsMap instanceof \Illuminate\Support\Collection) {
                        $paymentRow = $paymentsMap->get($contract->contract_id, []);
                    } else {
                        $paymentRow = $paymentsMap[$contract->contract_id] ?? [];
                    }

                    if ($paymentRow instanceof \Illuminate\Support\Collection) {
                        $paymentRow = $paymentRow->toArray();
                    }

                    $paid = (float) ($paymentRow['total']
                        ?? ((float) ($paymentRow['installments'] ?? 0.0)
                            + (float) ($paymentRow['claims'] ?? 0.0)));

                    if ($officePaidMap instanceof \Illuminate\Support\Collection) {
                        $officePaid = (float) $officePaidMap->get($contract->contract_id, 0.0);
                    } else {
                        $officePaid = (float) ($officePaidMap[$contract->contract_id] ?? 0.0);
                    }

                    $officePaid = min($officeCut, round($officePaid, 2));
                    $remainingOffice = max(0.0, round($officeCut - $officePaid, 2));

                    $remainingWithBase = max(0.0, round($expectedWith - $paid, 2));
                    $remainingWithoutBase = max(0.0, round($remainingWithBase - $remainingOffice, 2));

                    $profitNet = max(0.0, round($profitGross - $officeCut, 2));
                    $profitPaidRaw = max(0.0, $paid - $shareVal);
                    $profitPaid = min($profitPaidRaw, $profitNet);
                    $profitPaid = round($profitPaid, 2);
                    $unpaidProfit = max(0.0, round($profitNet - $profitPaid, 2));

                    $remainingWith = $remainingWithBase;
                    $remainingWithout = $remainingWithoutBase;

                    if (!empty($claimStatusIds) && in_array((int) ($contract->contract_status_id ?? 0), $claimStatusIds, true)) {
                        $remainingWith = max(0.0, round($remainingWith - $unpaidProfit, 2));
                        $remainingWithout = max(0.0, round($remainingWithout - $unpaidProfit, 2));
                    }

                    $withOffice += $remainingWith;
                    $withoutOffice += $remainingWithout;
                    $officeShare += $remainingOffice;
                }

                $investor->remaining_with_office = round($withOffice, 2);
                $investor->remaining_without_office = round($withoutOffice, 2);
                $investor->remaining_office_share = round($officeShare, 2);

                return $investor;
            })->values();
        } else {
            $rows = collect();
        }

        $pageTotals = [
            'with_office'    => round((float) $rows->sum(fn ($investor) => (float) ($investor->remaining_with_office ?? 0)), 2),
            'without_office' => round((float) $rows->sum(fn ($investor) => (float) ($investor->remaining_without_office ?? 0)), 2),
            'office_share'   => round((float) $rows->sum(fn ($investor) => max(0.0, (float) ($investor->remaining_office_share ?? 0))), 2),
        ];

        return view('investors::reports.outstanding', [
            'rows'           => $rows,
            'filters'        => $filters,
            'currencySymbol' => $currencySymbol,
            'grandTotals'    => $grandTotals,
            'pageTotals'     => $pageTotals,
            'investors'      => $investorOptions,
        ]);
    }

    private function normalizeStatusName(?string $statusName): string
    {
        $label = trim((string) ($statusName ?? ''));
        if ($label === '') {
            return 'غير محدد';
        }

        $normalize = static fn ($value) => mb_strtolower(trim((string) $value), 'UTF-8');

        static $canonicalMap = null;
        static $aliasMap = null;

        if ($canonicalMap === null) {
            $canonicalNames = [
                'بدون مستثمر',
                'معلق',
                'جديد',
                'منتهي',
                'سداد مبكر',
                'مطلوب',
                'منتظم',
                'غير منتظم',
                'متأخر',
                'متعثر',
                'مرفوع فيه',
                'منتهي بمطالبة',
            ];

            $canonicalMap = [];
            foreach ($canonicalNames as $name) {
                $canonicalMap[$normalize($name)] = $name;
            }

            $aliasPairs = [
                'without investor'   => 'بدون مستثمر',
                'no investor'        => 'بدون مستثمر',
                'pending'            => 'معلق',
                'on hold'            => 'معلق',
                'waiting'            => 'معلق',
                'new'                => 'جديد',
                'fresh'              => 'جديد',
                'ended'              => 'منتهي',
                'closed'             => 'منتهي',
                'complete'           => 'منتهي',
                'completed'          => 'منتهي',
                'early settlement'   => 'سداد مبكر',
                'paid off'           => 'سداد مبكر',
                'required'           => 'مطلوب',
                'demand'             => 'مطلوب',
                'active'             => 'منتظم',
                'regular'            => 'منتظم',
                'irregular'          => 'غير منتظم',
                'non-regular'        => 'غير منتظم',
                'late'               => 'متأخر',
                'overdue'            => 'متأخر',
                'delayed'            => 'متأخر',
                'delinquent'         => 'متعثر',
                'defaulted'          => 'متعثر',
                'raised'             => 'مرفوع فيه',
                'raised status'      => 'مرفوع فيه',
                'ended with claim'   => 'منتهي بمطالبة',
                'under claim'        => 'منتهي بمطالبة',
                'claim closed'       => 'منتهي بمطالبة',
            ];

            $aliasMap = [];
            foreach ($aliasPairs as $alias => $canonical) {
                $aliasMap[$normalize($alias)] = $canonical;
            }
        }

        $normalizedInput = $normalize($label);

        if (isset($canonicalMap[$normalizedInput])) {
            return $canonicalMap[$normalizedInput];
        }

        if (isset($aliasMap[$normalizedInput])) {
            return $aliasMap[$normalizedInput];
        }

        return $label;
    }

    private function claimStatusNames(): array
    {
        return ['مطلوب', 'مرفوع فيه'];
    }

    public function allliquidity(Request $request)
    {
        $investorId = $request->query('investor_id');
        $filters = [
            'investor_id' => $investorId !== null && $investorId !== ''
                ? (int) $investorId
                : null,
        ];

        $investorOptions = Investor::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $rows = Investor::query()
            ->select(['id', 'name'])
            ->when($filters['investor_id'], fn ($query, $id) => $query->whereKey($id))
            ->orderBy('name')
            ->get();

        $currencySymbol = 'ر.س';
        $ids = $rows->pluck('id');

        $liquidityByInvestor = collect();
        $contractStats = collect();
        $activeContractNumbers = collect();
        if ($ids->isNotEmpty()) {
            $liquidityByInvestor = InvestorLiquidityCalculator::aggregateTotals(null, $ids)
                ->map(fn ($row) => (float) ($row['net'] ?? 0.0));

            $endedIds = ContractStatus::whereIn('name', [
                'مكتمل',
                'منتهي',
                'سداد مبكر',
                'إلغاء',
                'Closed',
                'Completed',
                'Early Settlement',
                'Inactive',
                'منتهي بمطالبة',
                'منتهى بمطالبة',
                'مُنتهي بمطالبة',
                'مُنتهى بمطالبة',
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

        $rows = $rows->map(function ($r) use ($liquidityByInvestor, $contractStats, $activeContractNumbers) {
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
        })->values();

        $grandTotal = (float) $rows->sum(fn ($row) => (float) ($row->liquidity ?? 0));

        return view('investors::reports.allliquidity', [
            'rows'           => $rows,
            'grandTotal'     => $grandTotal,
            'filters'        => $filters,
            'currencySymbol' => $currencySymbol,
            'investors'      => $investorOptions,
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
