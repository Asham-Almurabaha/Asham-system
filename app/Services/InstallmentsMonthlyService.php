<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InstallmentsMonthlyService
{
    /**
     * ملخص أقساط شهر/سنة (بدون ربح مكتب).
     *
     * @param int|null $month  1..12 (افتراضي: شهر الآن)
     * @param int|null $year   YYYY  (افتراضي: سنة الآن)
     * @param array    $excludeStatusNames حالات تُستثنى (مثلاً: مؤجل، معتذر)
     */
    public function build(
        ?int $month = null,
        ?int $year = null,
        array $excludeStatusNames = ['مؤجل','معتذر']
    ): array {
        $now   = Carbon::now();
        $m     = $month && $month >= 1 && $month <= 12 ? $month : (int)$now->month;
        $y     = $year  && $year >= 2000 && $year <= 2100 ? $year : (int)$now->year;
        $start = Carbon::create($y, $m, 1)->startOfDay();
        $end   = (clone $start)->endOfMonth();

        // نستخدم transaction_types لو موجودة للتمييز “إيداع”
        $TYPE_IN = null;
        if (Schema::hasTable('transaction_types')) {
            $TYPE_IN = (int) (DB::table('transaction_types')->where('name','إيداع')->value('id') ?? 0);
        }

        // Subquery: المبالغ المدفوعة من investor_transactions لكل قسط
        $invPaidSub = null;
        if (Schema::hasTable('investor_transactions') && Schema::hasColumn('investor_transactions','installment_id')) {
            $invPaid = DB::table('investor_transactions as it');

            $hasItStatus = Schema::hasColumn('investor_transactions','status_id');
            $hasTsType   = Schema::hasTable('transaction_statuses') && Schema::hasColumn('transaction_statuses','transaction_type_id');

            if ($TYPE_IN && $hasItStatus && $hasTsType) {
                $invPaid->leftJoin('transaction_statuses as ts','ts.id','=','it.status_id')
                        ->where('ts.transaction_type_id', $TYPE_IN);
            } else {
                // fallback: اعتبر الإيجابي “داخل”
                if (Schema::hasColumn('investor_transactions','amount')) {
                    $invPaid->where('it.amount','>',0);
                }
            }

            $invPaidSub = $invPaid
                ->selectRaw('it.installment_id, SUM(ABS(it.amount)) as paid')
                ->groupBy('it.installment_id');
        }

        // أقساط الشهر مع استثناء حالات معينة
        $q = DB::table('contract_installments as ci')
            ->leftJoin('installment_statuses as st','st.id','=','ci.installment_status_id')
            ->whereBetween('ci.due_date', [$start->toDateString(), $end->toDateString()]);

        if (!empty($excludeStatusNames)) {
            $q->where(function($w) use ($excludeStatusNames) {
                $w->whereNull('st.name')->orWhereNotIn('st.name', $excludeStatusNames);
            });
        }

        if ($invPaidSub) {
            $q->leftJoinSub($invPaidSub, 'invp', 'invp.installment_id','=','ci.id');
        }

        $rows = $q->selectRaw("
                ci.id,
                ci.contract_id,
                ci.due_date,
                st.name as status_name,
                ci.due_amount,
                ci.payment_amount,
                COALESCE(invp.paid, 0) as paid_inv
            ")
            ->orderBy('ci.due_date')
            ->get();

        // بناء النتائج
        $outRows = [];
        $totDue  = 0.0;
        $totPaid = 0.0;

        foreach ($rows as $r) {
            $due  = (float) ($r->due_amount ?? 0);
            $pInv = (float) ($r->paid_inv ?? 0);
            $pCol = (float) ($r->payment_amount ?? 0);

            // المدفوع الفعّال للقسط: نفضل investor_transactions، وإلا payment_amount
            $paidEff    = $pInv > 0 ? $pInv : $pCol;
            $paidCapped = min($paidEff, $due);
            $remaining  = max($due - $paidCapped, 0);

            $totDue  += $due;
            $totPaid += $paidCapped;

            $outRows[] = [
                'id'             => (int) $r->id,
                'contract_id'    => $r->contract_id ? (int)$r->contract_id : null,
                'due_date'       => (string) $r->due_date,
                'status'         => $r->status_name,
                'due_amount'     => $due,
                'paid_effective' => $paidCapped,
                'remaining'      => $remaining,
            ];
        }

        return [
            'month'                 => $m,
            'year'                  => $y,
            'month_label'           => sprintf('%04d-%02d', $y, $m),
            'excluded_status_names' => array_values($excludeStatusNames),

            'totals' => [
                'count'     => (int) count($outRows),
                'due'       => round($totDue, 2),
                'paid'      => round($totPaid, 2),
                'remaining' => round(max($totDue - $totPaid, 0), 2),
            ],
            'rows' => $outRows,
        ];
    }
}
