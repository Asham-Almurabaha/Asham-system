<?php

namespace Modules\Investors\Services;

use App\Models\LedgerEntry;
use Modules\Lookups\Entities\ContractStatus;
use Modules\Investors\Entities\Investor;

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
        $paidToInvestorByContract = collect(); // [contract_id => sum(amount)]
        if ($activeIds->isNotEmpty()) {
            $paidToInvestorByContract = LedgerEntry::query()
                ->whereIn('contract_id', $activeIds)
                ->where('investor_id', $investor->id) // <<< أهم شرط
                ->where('direction', 'in')            // دفعات داخلة تخص المستثمر لهذا العقد
                ->groupBy('contract_id')
                ->selectRaw('contract_id, SUM(amount) as paid_in')
                ->pluck('paid_in', 'contract_id');
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
            $profitNet = $profitGross - $officeCut;

            $paidIn = (float) ($paidToInvestorByContract[$c->id] ?? 0);

            $expectedTotal = $shareVal + $profitNet;
            $remaining = $expectedTotal - $paidIn;
            $customerName = $c->customer->name ?? null;
            $customerId = $c->customer_id ?? ($c->customer->id ?? null);

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
                'profit_net'               => $profitNet,
                'paid_to_investor'         => $paidIn,
                'paid_to_investor_from_customer' => $paidIn,
                'remaining_on_customers'   => $remaining,
                'total_contract_value'     => $c->contract_value,
                'total_contract_profit'    => $c->investor_profit,
                'contract_status_id'       => $c->contract_status_id,
                'status_name'              => $c->contractStatus->name ?? null,
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
        $totalRemainingOnCustomers  = round(($totalCapitalShare + $totalProfitNet) - $totalPaidPortionToInvestor, 2);

        // صافي السيولة الحالية = إجمالي الداخل - إجمالي الخارج (باستثناء قيود المكتب)
        $liquidityRow = LedgerEntry::query()
            ->where('investor_id', $investor->id)
            ->where('is_office', false)
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END), 0) AS total_in, " .
                "COALESCE(SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END), 0) AS total_out"
            )
            ->first();

        $liquidity = 0.0;
        if ($liquidityRow) {
            $liquidity = (float) ($liquidityRow->total_in ?? 0) - (float) ($liquidityRow->total_out ?? 0);
            $liquidity = round($liquidity, 2);
        }

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

    private function endedNames(): array
    {
        return ['مكتمل','منتهي','سداد مبكر','إلغاء','Closed','Completed','Early Settlement','Inactive'];
    }
}
