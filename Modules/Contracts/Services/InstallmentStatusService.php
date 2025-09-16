<?php

namespace Modules\Contracts\Services;

use App\Models\Lookups\InstallmentStatus;
use Illuminate\Support\Carbon;
use Modules\Contracts\Entities\ContractInstallment;

class InstallmentStatusService
{
    /**
     * Recalculate the installment status using the same logic as manual payments.
     */
    public static function recalculate(ContractInstallment $installment): void
    {
        // Ensure we have the relations used in the calculations.
        $installment->loadMissing('contract.investors', 'installmentStatus');

        $sumPct = 0.0;
        if ($installment->contract && $installment->contract->investors) {
            $sumPct = (float) $installment->contract->investors
                ->sum(fn($i) => (float) ($i->pivot->share_percentage ?? 0));
        }

        // Only apply statuses if the investors' shares add up to 100% (same as manual flow).
        if (round($sumPct, 2) !== 100.00) {
            return;
        }

        $paid    = (float) ($installment->payment_amount ?? 0);
        $total   = (float) ($installment->due_amount ?? 0);
        $dueDate = Carbon::parse($installment->due_date)->startOfDay();
        $payDate = $installment->payment_date
            ? Carbon::parse($installment->payment_date)->startOfDay()
            : null;
        $today   = now()->startOfDay();

        $currentStatusName = optional($installment->installmentStatus)->name;
        $statusName = null;

        // 1) Fully paid
        if ($total > 0 && $paid >= $total) {
            $effectivePayDate = $payDate ?: $today;
            $diffDays = $effectivePayDate->diffInDays($dueDate, false); // negative = before due date

            if ($diffDays > 7) {
                $statusName = 'مدفوع مبكر';
            } elseif ($diffDays < -7) {
                $statusName = 'مدفوع متأخر';
            } else {
                $statusName = 'مدفوع كامل';
            }
        }
        // 2) Partially paid
        elseif ($paid > 0 && $paid < $total) {
            $statusName = 'مدفوع جزئي';
        }
        // 3) Not paid yet
        else {
            // Keep postponed/apologized statuses untouched if no payment.
            if (in_array($currentStatusName, ['مؤجل', 'معتذر'], true)) {
                return;
            }

            if ($today->lt($dueDate)) {
                $statusName = 'مطلوب';
            } else {
                $overdueDays = $dueDate->diffInDays($today);
                $statusName  = ($overdueDays > 15) ? 'متعثر' : 'متأخر';
            }
        }

        if ($statusName) {
            $statusId = InstallmentStatus::where('name', $statusName)->value('id');
            if ($statusId) {
                $installment->update(['installment_status_id' => $statusId]);
            }
        }
    }
}
