<?php

namespace Modules\Contracts\Exports;

use App\Support\ExcelHeadingLocalizer;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ContractsPaymentsTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        $base = [
            'contract_number',
            'previous_cumulative',
            'payments',
            'down_payment',
            'down_payment_date',
            'first_payment_amount',
            'first_payment_date',
        ];

        for ($n = 1; $n <= 18; $n++) {
            $base[] = "payment{$n}_amount";
            $base[] = "payment{$n}_date";
            $base[] = "payment{$n}_notes";
        }

        return ExcelHeadingLocalizer::translateMany($base);
    }

    public function array(): array
    {
        return [
            $this->buildRow(
                'CNT-001',
                '2024-01-05:1500|2024-02-05:1500#دفعة نقدية',
                0.0,
                500.0,
                '2023-12-20',
                null,
                null,
                [
                    ['amount' => 1500, 'date' => '2024-01-05', 'notes' => 'تحويل بنكي'],
                    ['amount' => 1500, 'date' => '2024-02-05', 'notes' => 'نقدي'],
                ]
            ),
            $this->buildRow(
                'CNT-002',
                '',
                1200.0,
                null,
                null,
                1200.0,
                '2023-11-15',
                [
                    ['amount' => 1200, 'date' => '2023-11-15', 'notes' => 'دفعة أولى'],
                ]
            ),
        ];
    }

    /**
     * @param  array<int, array{amount:float|null,date:string|null,notes:string|null}>  $installments
     */
    private function buildRow(
        string $contractNumber,
        string $paymentsColumn,
        ?float $previousCumulative,
        ?float $downPayment,
        ?string $downPaymentDate,
        ?float $firstPaymentAmount,
        ?string $firstPaymentDate,
        array $installments
    ): array {
        $row = [
            $contractNumber,
            $previousCumulative ?? '',
            $paymentsColumn,
            $downPayment ?? '',
            $downPaymentDate ?? '',
            $firstPaymentAmount ?? '',
            $firstPaymentDate ?? '',
        ];

        for ($n = 1; $n <= 18; $n++) {
            $entry = $installments[$n - 1] ?? null;
            $row[] = $entry['amount'] ?? '';
            $row[] = $entry['date'] ?? '';
            $row[] = $entry['notes'] ?? '';
        }

        return $row;
    }
}
