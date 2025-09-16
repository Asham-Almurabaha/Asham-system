<?php

namespace Modules\Contracts\Exports;

use App\Support\ExcelHeadingLocalizer;
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

            $row = [
                $contract->contract_number,
                $remainingAmount,
                $installmentValue,
            ];

            for ($n = 1; $n <= 18; $n++) {
                $installment = $contract->installments->firstWhere('installment_number', $n);
                $row[] = $installment?->payment_amount ?? '';
                $row[] = $installment?->payment_date?->toDateString() ?? '';
            }

            $rows->push($row);
        }

        return $rows;
    }

    public function headings(): array
    {
        $base = [
            'contract_number',
            'remaining_amount',
            'installment_value',
        ];

        for ($n = 1; $n <= 18; $n++) {
            $base[] = "payment{$n}_amount";
            $base[] = "payment{$n}_date";
        }

        return ExcelHeadingLocalizer::translateMany($base);
    }
}

