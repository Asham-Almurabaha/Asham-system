<?php

namespace Modules\Contracts\Services;

use Carbon\Carbon;
use Modules\Contracts\Entities\Contract;
use Modules\Lookups\Entities\ContractStatus;
use Modules\Lookups\Entities\InstallmentStatus;

class ContractStatusUpdater
{
    private array $installmentStatuses = [];
    private array $contractStatuses = [];

    public function refresh(?array $onlyIds = null): void
    {
        if ($onlyIds !== null) {
            $onlyIds = array_values(array_filter(array_map('intval', $onlyIds)));

            if (empty($onlyIds)) {
                return;
            }
        }

        $excludedNames = ['منتهي', 'سداد مبكر', 'مطلوب'];
        $excludedIds   = ContractStatus::whereIn('name', $excludedNames)->pluck('id')->filter()->all();

        $query = Contract::query()
            ->when(!empty($excludedIds), fn ($q) => $q->whereNotIn('contract_status_id', $excludedIds))
            ->when($onlyIds !== null, fn ($q) => $q->whereIn('id', $onlyIds))
            ->with(['investors', 'installments.installmentStatus', 'contractStatus']);

        if ($onlyIds !== null) {
            $query->get()->each(fn (Contract $contract) => $this->refreshContract($contract));
            return;
        }

        $query->chunkById(100, function ($contracts) {
            foreach ($contracts as $contract) {
                $this->refreshContract($contract);
            }
        });
    }

    public function refreshContract(Contract $contract): void
    {
        $this->updateInstallmentsStatuses($contract);
    }

    private function updateInstallmentsStatuses(Contract $contract): void
    {
        $contract->loadMissing('investors', 'installments.installmentStatus', 'contractStatus');

        $sumPct = (float) $contract->investors
            ->sum(fn($i) => (float) ($i->pivot->share_percentage ?? 0));

        if (round($sumPct, 2) !== 100.00) {
            return;
        }

        $excludedContractStatuses = ['منتهي', 'سداد مبكر', 'مطلوب'];
        if (in_array($contract->contractStatus->name ?? '', $excludedContractStatuses, true)) {
            return;
        }

        $today = now();

        if (empty($this->installmentStatuses)) {
            $this->installmentStatuses = InstallmentStatus::pluck('id', 'name')->all();
        }

        if (empty($this->contractStatuses)) {
            $this->contractStatuses = ContractStatus::pluck('id', 'name')->all();
        }

        $lateCount     = 0;
        $maatherCount  = 0;
        $allPaid       = true;
        $anyLate       = false;
        $allNotDueYet  = true;

        foreach ($contract->installments as $installment) {
            $statusName = $installment->installmentStatus->name ?? null;

            if (!empty($installment->notes) && stripos($installment->notes, 'معتذر') !== false) {
                $maatherCount++;
            }

            if (in_array($statusName, ['مدفوع كامل', 'مدفوع مبكر', 'مدفوع متأخر', 'مدفوع جزئي', 'مؤجل', 'معتذر'], true)) {
                $allNotDueYet = false;
                continue;
            }

            $dueDate   = Carbon::parse($installment->due_date);
            $paid      = (float) ($installment->payment_amount ?? 0);
            $dueAmount = (float) ($installment->due_amount ?? 0);

            if ($paid < $dueAmount) {
                $allPaid = false;

                if ($dueDate->between($today->copy()->subDays(7), $today->copy()->addDays(7))) {
                    $installment->installment_status_id = $this->installmentStatuses['مستحق'] ?? $installment->installment_status_id;
                    $allNotDueYet = false;
                }
                elseif ($dueDate->greaterThan($today->copy()->addDays(7))) {
                    $installment->installment_status_id = $this->installmentStatuses['لم يحل'] ?? $installment->installment_status_id;
                }
                elseif ($dueDate->lessThan($today->copy()->subDays(7))) {
                    $installment->installment_status_id = $this->installmentStatuses['متأخر'] ?? $installment->installment_status_id;
                    $lateCount++;
                    $anyLate = true;
                    $allNotDueYet = false;
                }
            }

            $installment->save();
        }

        if ($allPaid) {
            $contract->contract_status_id = $this->contractStatuses['منتهي'] ?? $contract->contract_status_id;
        }
        elseif ($allNotDueYet) {
            $contract->contract_status_id = $this->contractStatuses['جديد'] ?? $contract->contract_status_id;
        }
        elseif ($maatherCount > 2) {
            $contract->contract_status_id = $this->contractStatuses['غير منتظم'] ?? $contract->contract_status_id;
        }
        elseif ($lateCount >= 3) {
            $contract->contract_status_id = $this->contractStatuses['متعثر'] ?? $contract->contract_status_id;
        }
        elseif ($anyLate) {
            $contract->contract_status_id = $this->contractStatuses['متأخر'] ?? $contract->contract_status_id;
        }
        else {
            $contract->contract_status_id = $this->contractStatuses['منتظم'] ?? $contract->contract_status_id;
        }

        $contract->save();
    }
}
