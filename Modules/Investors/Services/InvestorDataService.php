<?php

namespace Modules\Investors\Services;

use App\Models\LedgerEntry;
use Modules\Contracts\Entities\ContractStatus;
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
            $sharePctAll = (float) ($cAll->pivot->share_percentage ?? 0);
            $shareValAll = (float) ($cAll->pivot->share_value ?? 0);

            if ($shareValAll <= 0 && isset($cAll->contract_value)) {
                $shareValAll = round(((float)$cAll->contract_value) * $sharePctAll / 100, 2);
            }

            $profitGrossAll = isset($cAll->investor_profit)
                ? round(((float)$cAll->investor_profit) * $sharePctAll / 100, 2)
                : 0.0;

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
            $sharePct = (float) ($c->pivot->share_percentage ?? 0);
            $shareVal = (float) ($c->pivot->share_value ?? 0);

            if ($shareVal <= 0 && isset($c->contract_value)) {
                $shareVal = round(((float)$c->contract_value) * $sharePct / 100, 2);
            }

            $profitGross = isset($c->investor_profit)
                ? round(((float)$c->investor_profit) * $sharePct / 100, 2)
                : 0.0;

            $officeCut = round($profitGross * $pctOffice / 100, 2);
            $profitNet = $profitGross - $officeCut;

            $paidIn = (float) ($paidToInvestorByContract[$c->id] ?? 0);

            $totalCapitalShare += $shareVal;
            $totalProfitGross  += $profitGross;
            $totalOfficeCut    += $officeCut;
            $totalProfitNet    += $profitNet;
            $totalPaidPortionToInvestor += $paidIn;

            $contractBreakdown[] = [
                'contract_id'              => $c->id,
                'contract_number'          => $c->contract_number,
                'share_percentage'         => $sharePct,
                'share_value'              => $shareVal,
                'profit_gross'             => $profitGross,
                'office_cut'               => $officeCut,
                'profit_net'               => $profitNet,
                'paid_to_investor'         => $paidIn,
                'total_contract_value'     => $c->contract_value,
                'total_contract_profit'    => $c->investor_profit,
                'customer_name'            => $c->customer->name ?? null,
                'contract_status_id'       => $c->contract_status_id,
                'status_name'              => $c->contractStatus->name ?? null,
            ];
        }

        return [
            'totals' => [
                'capital_share_all' => round($totalCapitalShareAll, 2),
                'profit_gross_all'  => round($totalProfitGrossAll, 2),
                'office_cut_all'    => round($totalOfficeCutAll, 2),
                'profit_net_all'    => round($totalProfitNetAll, 2),
                'capital_share'     => round($totalCapitalShare, 2),
                'profit_gross'      => round($totalProfitGross, 2),
                'office_cut'        => round($totalOfficeCut, 2),
                'profit_net'        => round($totalProfitNet, 2),
                'paid_to_investor'  => round($totalPaidPortionToInvestor, 2),
            ],
            'contractBreakdown' => $contractBreakdown,
            'currencySymbol'    => $currencySymbol,
        ];
    }

    private function endedNames(): array
    {
        return ['مكتمل','منتهي','سداد مبكر','إلغاء','Closed','Completed','Early Settlement','Inactive'];
    }
}
