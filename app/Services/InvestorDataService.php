<?php

namespace App\Services;

use App\Models\Investor;
use App\Models\ContractStatus;
use App\Models\LedgerEntry;

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
        $totalPaidPortionToInvestor = 0.0; // الآن = المدفوع فعليًا من العميل للمستثمر
        $totalRemainingOnCustomers  = 0.0;

        $contractBreakdown = [];

        foreach ($activeContracts as $c) {
            $sharePct = (float) ($c->pivot->share_percentage ?? 0);
            $shareVal = (float) ($c->pivot->share_value ?? 0);

            // لو share_value مش محفوظة، نحسبها من قيمة العقد
            if ($shareVal <= 0 && isset($c->contract_value)) {
                $shareVal = round(((float)$c->contract_value) * $sharePct / 100, 2);
            }

            // ربح المستثمر الإجمالي من العقد (قبل خصم المكتب)
            $profitGross = isset($c->investor_profit)
                ? round(((float)$c->investor_profit) * $sharePct / 100, 2)
                : 0.0;

            // نصيب المكتب من ربح المستثمر
            $officeCut = round($profitGross * $pctOffice / 100, 2);

            // ربح المستثمر الصافي
            $profitNet = $profitGross - $officeCut;

            // مستحق المستثمر من العقد حتى الإقفال = رأس المال + ربحه الصافي
            $investorDue = $shareVal + $profitNet;

            // >>> المبلغ المدفوع فعليًا من العميل لهذا المستثمر في هذا العقد
            $paidPortion = (float) ($paidToInvestorByContract[$c->id] ?? 0.0);

            // المتبقي على العملاء لصالح المستثمر
            $remainingOnCustomers = round($investorDue - $paidPortion, 2);

            // تجميع المجاميع (نشِط)
            $totalCapitalShare += $shareVal;
            $totalProfitGross  += $profitGross;
            $totalOfficeCut    += $officeCut;
            $totalProfitNet    += $profitNet;
            $totalPaidPortionToInvestor += $paidPortion;       // صار فعليًا من الدفتر
            $totalRemainingOnCustomers  += $remainingOnCustomers;

            $contractBreakdown[] = [
                'contract_id'   => (int) $c->id,
                'customer'      => $c->customer->name ?? '-',
                'share_pct'     => $sharePct,
                'share_value'   => $shareVal,
                'profit_gross'  => $profitGross,
                'office_cut'    => $officeCut,
                'profit_net'    => $profitNet,
                // لو محتاج تعرضه في جدولك:
                'paid_to_investor_from_customer' => $paidPortion,
                'remaining_on_customers'         => $remainingOnCustomers,
            ];
        }

        // سيولة المستثمر من الدفتر (داخل − خارج) تخص المستثمر نفسه
        $liquidity = (float) LedgerEntry::query()
            ->where('investor_id', $investor->id)
            ->where('is_office', false)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN direction = 'in'  THEN amount ELSE 0 END),0)
                -
                COALESCE(SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END),0) AS bal
            ")
            ->value('bal');

        return [
            // إجماليات العدّ
            'contractsTotal'   => $contractsTotal,
            'contractsEnded'   => $contractsEnded,
            'contractsActive'  => $contractsActive,

            // مجاميع نشِط
            'totalCapitalShare' => $totalCapitalShare,
            'totalProfitGross'  => $totalProfitGross,
            'totalOfficeCut'    => $totalOfficeCut,
            'totalProfitNet'    => $totalProfitNet,
            // الآن = مجموع ما دُفع فعليًا من العميل لهذا المستثمر على العقود النشطة
            'totalPaidPortionToInvestor' => $totalPaidPortionToInvestor,
            'totalRemainingOnCustomers'  => $totalRemainingOnCustomers,

            // مجاميع "كل العقود" (نشِط + منتهي)
            'totalCapitalShareAll' => $totalCapitalShareAll,
            'totalProfitGrossAll'  => $totalProfitGrossAll,
            'totalOfficeCutAll'    => $totalOfficeCutAll,
            'totalProfitNetAll'    => $totalProfitNetAll,

            // تفصيل
            'contractBreakdown'    => $contractBreakdown,

            // السيولة والعملة
            'liquidity'            => $liquidity,
            'currencySymbol'       => $currencySymbol,
        ];
    }

    /** الأسماء التي تعتبر "منتهي". */
    private function endedNames(): array
    {
        return ['منتهي', 'منتهى', 'سداد مبكر', 'سداد مُبكر', 'سداد مبكّر', 'Completed', 'Early Settlement'];
    }
}
