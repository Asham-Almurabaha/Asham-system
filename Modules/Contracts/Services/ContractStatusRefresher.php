<?php

namespace Modules\Contracts\Services;

use Carbon\Carbon;
use Modules\Contracts\Entities\Contract;
use Modules\Lookups\Entities\ContractStatus;
use Modules\Lookups\Entities\InstallmentStatus;

class ContractStatusRefresher
{
    /**
     * Cache for installment status ids indexed by name.
     *
     * @var array<string,int>|null
     */
    private ?array $installmentStatuses = null;

    /**
     * Cache for contract status ids indexed by name.
     *
     * @var array<string,int>|null
     */
    private ?array $contractStatuses = null;

    /**
     * Refresh the status (and related installment statuses) for the provided
     * contract ids. If no ids are provided, all eligible contracts will be
     * processed in chunks.
     *
     * @param  array<int>|null  $onlyIds
     */
    public function refresh(?array $onlyIds = null): void
    {
        $ids = null;

        if ($onlyIds !== null) {
            $ids = array_values(array_filter(array_map('intval', $onlyIds)));

            if (empty($ids)) {
                return;
            }
        }

        $excludedNames = ['منتهي', 'سداد مبكر', 'مطلوب', 'مرفوع فيه'];
        $excludedIds   = ContractStatus::whereIn('name', $excludedNames)
            ->pluck('id')
            ->filter()
            ->all();

        $query = Contract::query()
            ->when(!empty($excludedIds), fn($q) => $q->whereNotIn('contract_status_id', $excludedIds))
            ->when($ids !== null, fn($q) => $q->whereIn('id', $ids))
            ->with(['investors', 'installments.installmentStatus', 'contractStatus']);

        if ($ids !== null) {
            $query->get()->each(fn($contract) => $this->refreshContract($contract));
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
            ->sum(fn($investor) => (float) ($investor->pivot->share_percentage ?? 0));

        if (round($sumPct, 2) !== 100.00) {
            return;
        }

        $excludedContractStatuses = ['منتهي', 'سداد مبكر', 'مطلوب', 'مرفوع فيه'];
        if (in_array($contract->contractStatus->name ?? '', $excludedContractStatuses, true)) {
            return;
        }

        $today = now();

        $statuses         = $this->getInstallmentStatuses();
        $contractStatuses = $this->getContractStatuses();

        $lateCount        = 0;
        $maatherCount     = 0;
        $allPaid          = true;
        $anyLate          = false;
        $allNotDueYet     = true;
        $contractRemaining = 0.0;

        foreach ($contract->installments as $installment) {
            $statusName = $installment->installmentStatus->name ?? null;

            if (!empty($installment->notes) && stripos($installment->notes, 'معتذر') !== false) {
                $maatherCount++;
            }

            $dueAmount = (float) ($installment->due_amount ?? 0);
            $paid      = (float) ($installment->payment_amount ?? 0);
            $remainingForInstallment = max(0.0, round($dueAmount - $paid, 2));
            $contractRemaining += $remainingForInstallment;

            if ($remainingForInstallment > 0) {
                $allPaid = false;
            }

            if (in_array($statusName, ['مدفوع كامل', 'مدفوع مبكر', 'مدفوع متأخر', 'مدفوع جزئي', 'مؤجل', 'معتذر'], true)) {
                $allNotDueYet = false;
                continue;
            }

            $dueDate = Carbon::parse($installment->due_date);

            if ($remainingForInstallment > 0) {
                if ($dueDate->between($today->copy()->subDays(7), $today->copy()->addDays(7))) {
                    $installment->installment_status_id = $statuses['مستحق'] ?? $installment->installment_status_id;
                    $allNotDueYet = false;
                } elseif ($dueDate->greaterThan($today->copy()->addDays(7))) {
                    $installment->installment_status_id = $statuses['لم يحل'] ?? $installment->installment_status_id;
                } elseif ($dueDate->lessThan($today->copy()->subDays(7))) {
                    $installment->installment_status_id = $statuses['متأخر'] ?? $installment->installment_status_id;
                    $lateCount++;
                    $anyLate = true;
                    $allNotDueYet = false;
                }
            }

            $installment->save();
        }

        $contractRemaining = round($contractRemaining, 2);

        if ($contractRemaining <= 0.0) {
            $contract->contract_status_id = $contractStatuses['منتهي'] ?? $contract->contract_status_id;
        } elseif ($allNotDueYet) {
            $contract->contract_status_id = $contractStatuses['جديد'] ?? $contract->contract_status_id;
        } elseif ($maatherCount > 2) {
            $contract->contract_status_id = $contractStatuses['غير منتظم'] ?? $contract->contract_status_id;
        } elseif ($lateCount >= 3) {
            $contract->contract_status_id = $contractStatuses['متعثر'] ?? $contract->contract_status_id;
        } elseif ($anyLate) {
            $contract->contract_status_id = $contractStatuses['متأخر'] ?? $contract->contract_status_id;
        } else {
            $contract->contract_status_id = $contractStatuses['منتظم'] ?? $contract->contract_status_id;
        }

        $contract->save();
    }

    /**
     * @return array<string,int>
     */
    private function getInstallmentStatuses(): array
    {
        if ($this->installmentStatuses === null) {
            $this->installmentStatuses = InstallmentStatus::pluck('id', 'name')->all();
        }

        return $this->installmentStatuses;
    }

    /**
     * @return array<string,int>
     */
    private function getContractStatuses(): array
    {
        if ($this->contractStatuses === null) {
            $this->contractStatuses = ContractStatus::pluck('id', 'name')->all();
        }

        return $this->contractStatuses;
    }
}
