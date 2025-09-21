<?php

namespace Modules\Investors\Services;

use Modules\Lookups\Entities\ContractStatus;
use Modules\Investors\Entities\Investor;
use App\Models\LedgerEntry;
use App\Models\OfficeTransaction;
use Modules\Investors\Support\InvestorLiquidityCalculator;
use Modules\Investors\Entities\InvestorTransaction;
use Modules\Lookups\Entities\TransactionStatus;

class InvestorDataService
{
    /**
     * يبني كل البيانات اللازمة لعرض المستثمر (KPIs + تفصيل العقود).
     */
    public function build(Investor $investor, string $currencySymbol = 'ر.س'): array
    {
        // 1) حالات "منتهي" -> IDs
        $endedStatusIds = ContractStatus::whereIn('name', $this->endedNames())->pluck('id')->all();

        // 2) كل عقود المستثمر (مع pivot)
        $contractsAll = $investor->contracts()
            ->with(['customer:id,name', 'contractStatus:id,name'])
            ->withPivot(['share_percentage', 'share_value'])
            ->get();

        $contractsTotal  = (int) $contractsAll->count();
        $contractsEnded  = (int) $contractsAll->whereIn('contract_status_id', $endedStatusIds)->count();
        $contractsActive = max($contractsTotal - $contractsEnded, 0);

        $statusCounts = [];
        foreach ($contractsAll as $contractRow) {
            $statusId = (int) ($contractRow->contract_status_id ?? 0);
            $statusName = (string) ($contractRow->contractStatus->name ?? 'غير محدد');

            if (!isset($statusCounts[$statusId])) {
                $statusCounts[$statusId] = [
                    'id'    => $statusId,
                    'name'  => $statusName,
                    'count' => 0,
                ];
            } elseif (
                isset($statusCounts[$statusId]['name'])
                && $statusCounts[$statusId]['name'] === 'غير محدد'
                && $statusName !== 'غير محدد'
                && $statusName !== ''
            ) {
                $statusCounts[$statusId]['name'] = $statusName;
            }

            $statusCounts[$statusId]['count']++;
        }

        $statusMetrics = array_map(static function (array $row) use ($contractsTotal) {
            $count = (int) ($row['count'] ?? 0);
            $pct = $contractsTotal > 0
                ? round(($count / $contractsTotal) * 100, 2)
                : 0.0;

            return [
                'id'    => (int) ($row['id'] ?? 0),
                'name'  => (string) ($row['name'] ?? 'غير محدد'),
                'count' => $count,
                'pct'   => $pct,
            ];
        }, array_values($statusCounts));

        usort($statusMetrics, static fn ($a, $b) => ($b['count'] ?? 0) <=> ($a['count'] ?? 0));

        // العقود النشطة فقط
        $activeContracts = $contractsAll->reject(
            fn($c) => in_array((int)($c->contract_status_id ?? 0), $endedStatusIds, true)
        );

        $pctOffice = (float) ($investor->office_share_percentage ?? 0);

        /* =========================
         * إجماليات على "كل العقود" (نشِط + منتهي)
         * ========================= */
        $totalCapitalShareAll = 0.0;
        $totalProfitGrossAll  = 0.0;
        $totalOfficeCutAll    = 0.0;
        $totalProfitNetAll    = 0.0;

        foreach ($contractsAll as $cAll) {
            $contractValueAll = (float) ($cAll->contract_value ?? 0);
            $sharePctAll = (float) ($cAll->pivot->share_percentage ?? 0);
            $shareValAll = (float) ($cAll->pivot->share_value ?? 0);

            $shareRatioAll = 0.0;
            if ($sharePctAll > 0) {
                $shareRatioAll = $sharePctAll / 100;
                if ($shareValAll <= 0 && $contractValueAll > 0) {
                    $shareValAll = round($contractValueAll * $shareRatioAll, 2);
                }
            } elseif ($shareValAll > 0 && $contractValueAll > 0) {
                $shareRatioAll = $shareValAll / $contractValueAll;
            }

            $shareValAll = round($shareValAll, 2);

            $profitGrossAll = 0.0;
            if ($shareRatioAll > 0 && isset($cAll->investor_profit)) {
                $profitGrossAll = round(((float)$cAll->investor_profit) * $shareRatioAll, 2);
            }

            $officeCutAll = round($profitGrossAll * $pctOffice / 100, 2);
            $profitNetAll = $profitGrossAll - $officeCutAll;

            $totalCapitalShareAll += $shareValAll;
            $totalProfitGrossAll  += $profitGrossAll;
            $totalOfficeCutAll    += $officeCutAll;
            $totalProfitNetAll    += $profitNetAll;
        }

        // ===== التحصيل الفعلي لكل عقد لهذا المستثمر (بدون Pro-Rata) =====
        $activeIds = $activeContracts->pluck('id')->filter()->values();

        $statusBuckets = $this->transactionStatusBuckets();
        $installmentStatusIds = $statusBuckets['installment'] ?? [];
        $claimStatusIds = $statusBuckets['claim'] ?? [];
        $officeStatusIds = $statusBuckets['office'] ?? [];

        $paidInstallmentsByContract = collect();
        if ($activeIds->isNotEmpty() && !empty($installmentStatusIds)) {
            $paidInstallmentsByContract = InvestorTransaction::query()
                ->from('investor_transactions as it')
                ->whereIn('it.contract_id', $activeIds)
                ->where('it.investor_id', $investor->id)
                ->whereIn('it.status_id', $installmentStatusIds)
                ->groupBy('it.contract_id')
                ->selectRaw('it.contract_id as contract_id, SUM(it.amount) as paid_installments')
                ->pluck('paid_installments', 'contract_id');
        }

        $paidClaimsByContract = collect();
        $raisedContractIds = $activeContracts
            ->filter(function ($contract) {
                $statusName = $this->normalizeStatusName($contract->contractStatus->name ?? null);

                return $statusName === 'مرفوع فيه';
            })
            ->pluck('id')
            ->filter()
            ->values();

        if ($raisedContractIds->isNotEmpty() && !empty($claimStatusIds)) {
            $paidClaimsByContract = InvestorTransaction::query()
                ->from('investor_transactions as it')
                ->whereIn('it.contract_id', $raisedContractIds)
                ->where('it.investor_id', $investor->id)
                ->whereIn('it.status_id', $claimStatusIds)
                ->groupBy('it.contract_id')
                ->selectRaw('it.contract_id as contract_id, SUM(it.amount) as paid_from_claims')
                ->pluck('paid_from_claims', 'contract_id');
        }

        $officeCutPaidByContract = collect();
        if ($activeIds->isNotEmpty()) {
            $officePaidQuery = OfficeTransaction::query()
                ->from('office_transactions as ot')
                ->whereIn('ot.contract_id', $activeIds)
                ->where('ot.investor_id', $investor->id);

            if (!empty($officeStatusIds)) {
                $officePaidQuery->whereIn('ot.status_id', $officeStatusIds);
            }

            $officeCutPaidByContract = $officePaidQuery
                ->groupBy('ot.contract_id')
                ->selectRaw('ot.contract_id as contract_id, SUM(ot.amount) as office_cut_paid')
                ->pluck('office_cut_paid', 'contract_id');
        }

        // مجاميع (للعقود النشطة فقط)
        $totalCapitalShare = 0.0;
        $totalProfitGross  = 0.0;
        $totalOfficeCut    = 0.0;
        $totalProfitNet    = 0.0;
        $totalPaidPortionToInvestor = 0.0;

        $contractBreakdown = [];
        foreach ($activeContracts as $c) {
            $contractValue = (float) ($c->contract_value ?? 0);
            $sharePct = (float) ($c->pivot->share_percentage ?? 0);
            $shareVal = (float) ($c->pivot->share_value ?? 0);

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
            if ($shareRatio > 0 && isset($c->investor_profit)) {
                $profitGross = round(((float)$c->investor_profit) * $shareRatio, 2);
            }

            $officeCut = round($profitGross * $pctOffice / 100, 2);
            $profitNet = round($profitGross - $officeCut, 2);

            $paidFromInstallments = (float) ($paidInstallmentsByContract[$c->id] ?? 0);
            $statusName = $this->normalizeStatusName($c->contractStatus->name ?? null);

            $paidFromClaims = 0.0;
            if ($statusName === 'مرفوع فيه') {
                $paidFromClaims = (float) ($paidClaimsByContract[$c->id] ?? 0);
            }

            $paidFromInstallments = round($paidFromInstallments, 2);
            $paidFromClaims = round($paidFromClaims, 2);
            $paidIn = round($paidFromInstallments + $paidFromClaims, 2);

            $customerName = $c->customer->name ?? null;
            $customerId = $c->customer_id ?? ($c->customer->id ?? null);

            $officeCutPaidRaw = (float) ($officeCutPaidByContract[$c->id] ?? 0.0);
            $officeCutPaid = round(max(0.0, $officeCutPaidRaw), 2);

            $remaining = round(($shareVal + $profitNet) - $paidIn, 2);
            if (abs($remaining) < 0.005) {
                $remaining = 0.0;
            }

            $totalCapitalShare += $shareVal;
            $totalProfitGross  += $profitGross;
            $totalOfficeCut    += $officeCut;
            $totalProfitNet    += $profitNet;
            $totalPaidPortionToInvestor += $paidIn;

            $contractBreakdown[] = [
                'contract_id'              => $c->id,
                'contract_number'          => $c->contract_number,
                'customer_id'              => $customerId,
                'customer'                 => $customerName,
                'customer_name'            => $customerName,
                'share_percentage'         => $sharePct,
                'share_pct'                => $sharePct,
                'share_value'              => $shareVal,
                'profit_gross'             => $profitGross,
                'office_cut'               => $officeCut,
                'office_cut_paid'          => $officeCutPaid,
                'profit_net'               => $profitNet,
                'paid_to_investor'         => $paidIn,
                'paid_to_investor_from_customer' => $paidIn,
                'paid_to_investor_from_installments' => $paidFromInstallments,
                'paid_to_investor_from_claims' => $paidFromClaims,
                'remaining_on_customers'   => $remaining,
                'total_contract_value'     => $c->contract_value,
                'total_contract_profit'    => $c->investor_profit,
                'contract_status_id'       => $c->contract_status_id,
                'status_name'              => $statusName,
            ];
        }

        $totalCapitalShareAll = round($totalCapitalShareAll, 2);
        $totalProfitGrossAll  = round($totalProfitGrossAll, 2);
        $totalOfficeCutAll    = round($totalOfficeCutAll, 2);
        $totalProfitNetAll    = round($totalProfitNetAll, 2);

        $totalCapitalShare    = round($totalCapitalShare, 2);
        $totalProfitGross     = round($totalProfitGross, 2);
        $totalOfficeCut       = round($totalOfficeCut, 2);
        $totalProfitNet       = round($totalProfitNet, 2);
        $totalPaidPortionToInvestor = round($totalPaidPortionToInvestor, 2);
        $totalRemainingOnCustomers  = round(array_sum(array_map(
            static fn ($row) => (float) ($row['remaining_on_customers'] ?? 0.0),
            $contractBreakdown
        )), 2);
        if (abs($totalRemainingOnCustomers) < 0.005) {
            $totalRemainingOnCustomers = 0.0;
        }

        // صافي السيولة الحالية = إجمالي الداخل - إجمالي الخارج (باستثناء قيود المكتب)
        $liquiditySummary = InvestorLiquidityCalculator::summarizeForInvestor($investor->id);
        $liquidity = round((float) ($liquiditySummary['net'] ?? 0), 2);

        // ===== زكاة المال =====
        $lastZakatEntry = LedgerEntry::query()
            ->where('investor_id', $investor->id)
            ->where('is_office', false)
            ->whereHas('status', fn ($q) => $q->where('name', 'زكاة المال'))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->first();

        $zakatStartDate = null;
        $zakatStartSource = null;

        if ($lastZakatEntry && $lastZakatEntry->entry_date) {
            $zakatStartDate = $lastZakatEntry->entry_date->copy();
            $zakatStartSource = 'ledger';
        } elseif ($investor->investment_start_date) {
            $zakatStartDate = $investor->investment_start_date->copy();
            $zakatStartSource = 'investment_start';
        } elseif ($investor->created_at) {
            $zakatStartDate = $investor->created_at->copy();
            $zakatStartSource = 'created_at';
        }

        $now = now();

        $zakatDaysSince = $zakatStartDate ? $zakatStartDate->copy()->diffInDays($now) : null;
        $zakatLastEntryDate = ($lastZakatEntry && $lastZakatEntry->entry_date) ? $lastZakatEntry->entry_date->copy() : null;

        $zakatCycleDays = 354; // طول الحول (سنة قمرية)
        $zakatDueDate = $zakatStartDate ? $zakatStartDate->copy()->addDays($zakatCycleDays) : null;
        $zakatDaysUntilDue = $zakatDueDate ? $now->diffInDays($zakatDueDate, false) : null;
        $zakatIsDue = $zakatDueDate ? $now->greaterThanOrEqualTo($zakatDueDate) : false;
        $zakatDaysOverdue = ($zakatIsDue && $zakatDueDate)
            ? $zakatDueDate->diffInDays($now)
            : null;

        $zakatBase = round($liquidity + $totalRemainingOnCustomers, 2);
        $zakatAmount = $zakatBase > 0 ? round($zakatBase * 0.025, 2) : 0.0;

        $zakatData = [
            'base' => $zakatBase,
            'amount' => $zakatAmount,
            'rate' => 0.025,
            'rate_pct' => 2.5,
            'base_breakdown' => [
                'liquidity' => $liquidity,
                'remaining' => $totalRemainingOnCustomers,
            ],
            'start_date' => $zakatStartDate ? $zakatStartDate->copy() : null,
            'start_source' => $zakatStartSource,
            'last_entry_date' => $zakatLastEntryDate,
            'last_entry_id' => $lastZakatEntry?->id,
            'days_since' => $zakatDaysSince,
            'cycle_days' => $zakatCycleDays,
            'due_date' => $zakatDueDate,
            'days_until_due' => $zakatDaysUntilDue,
            'days_overdue' => $zakatDaysOverdue,
            'is_due' => $zakatIsDue,
        ];

        return [
            'currencySymbol'            => $currencySymbol,
            'contractsTotal'            => $contractsTotal,
            'contractsEnded'            => $contractsEnded,
            'contractsActive'           => $contractsActive,
            'liquidity'                 => $liquidity,
            'initialCapital'            => $totalCapitalShare,
            'totalCapitalShareAll'      => $totalCapitalShareAll,
            'totalProfitGrossAll'       => $totalProfitGrossAll,
            'totalOfficeCutAll'         => $totalOfficeCutAll,
            'totalProfitNetAll'         => $totalProfitNetAll,
            'totalCapitalShare'         => $totalCapitalShare,
            'totalProfitGross'          => $totalProfitGross,
            'totalOfficeCut'            => $totalOfficeCut,
            'totalProfitNet'            => $totalProfitNet,
            'totalPaidPortionToInvestor'=> $totalPaidPortionToInvestor,
            'totalRemainingOnCustomers' => $totalRemainingOnCustomers,
            'contractStatusMetrics'     => $statusMetrics,
            'contractStatusTotal'       => $contractsTotal,
            'zakat'                     => $zakatData,
            'contractBreakdown'         => $contractBreakdown,
            'totals' => [
                'capital_share_all' => $totalCapitalShareAll,
                'profit_gross_all'  => $totalProfitGrossAll,
                'office_cut_all'    => $totalOfficeCutAll,
                'profit_net_all'    => $totalProfitNetAll,
                'capital_share'     => $totalCapitalShare,
                'profit_gross'      => $totalProfitGross,
                'office_cut'        => $totalOfficeCut,
                'profit_net'        => $totalProfitNet,
                'paid_to_investor'  => $totalPaidPortionToInvestor,
                'remaining_on_customers' => $totalRemainingOnCustomers,
            ],
        ];
    }

    private function transactionStatusBuckets(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $normalize = static fn ($value) => mb_strtolower(trim((string) $value), 'UTF-8');

        $installmentKeys = array_unique(array_map($normalize, $this->installmentStatusNames()));
        $claimKeys = array_unique(array_map($normalize, $this->claimStatusNames()));
        $officeKeys = array_unique(array_map($normalize, $this->officeStatusNames()));

        if (empty($installmentKeys) && empty($claimKeys) && empty($officeKeys)) {
            return $cache = ['installment' => [], 'claim' => [], 'office' => []];
        }

        $statuses = TransactionStatus::query()->select(['id', 'name'])->get();

        $installmentIds = [];
        $claimIds = [];
        $officeIds = [];

        foreach ($statuses as $status) {
            $key = $normalize($status->name ?? '');

            if (in_array($key, $installmentKeys, true)) {
                $installmentIds[] = (int) $status->id;
            }

            if (in_array($key, $claimKeys, true)) {
                $claimIds[] = (int) $status->id;
            }

            if (in_array($key, $officeKeys, true)) {
                $officeIds[] = (int) $status->id;
            }
        }

        return $cache = [
            'installment' => array_values(array_unique($installmentIds)),
            'claim'       => array_values(array_unique($claimIds)),
            'office'      => array_values(array_unique($officeIds)),
        ];
    }

    private function installmentStatusNames(): array
    {
        return [
            'سداد قسط',
            'تحصيل قسط',
            'تحصيل',
            'installment payment',
            'installment',
            'installment settlement',
        ];
    }

    private function claimStatusNames(): array
    {
        return [
            'سداد مطالبة',
            'سداد مطالبه',
            'سداد مطالبة للمستثمرين',
            'سداد مطالبه للمستثمرين',
            'claim payment',
            'claim settlement',
            'claim investor payment',
        ];
    }

    private function officeStatusNames(): array
    {
        return [
            'ربح المكتب',
            'office profit',
            'office share',
        ];
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

    private function endedNames(): array
    {
        return [
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
    }
}
