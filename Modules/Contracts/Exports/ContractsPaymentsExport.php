<?php

namespace Modules\Contracts\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Modules\Contracts\Entities\Contract;

class ContractsPaymentsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function collection(): Collection
    {
        $rows = collect();

        $contracts = Contract::with('installments')->get();

        foreach ($contracts as $contract) {
            $paidAmount = $contract->installments->sum('payment_amount');
            $remainingAmount = (float) $contract->total_value - (float) $paidAmount;
            $installmentValue = (float) $contract->installment_value;
            $remainingInstallments = $installmentValue > 0
                ? (int) ceil($remainingAmount / $installmentValue)
                : 0;

            $rows->push([
                'contract_number'        => $contract->contract_number,
                'remaining_amount'       => $remainingAmount,
                'installment_value'      => $installmentValue,
                'remaining_installments' => $remainingInstallments,
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'contract_number',
            'remaining_amount',
            'installment_value',
            'remaining_installments',
        ];
    }
}

