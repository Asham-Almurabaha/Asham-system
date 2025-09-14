<?php

namespace Modules\Contracts\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ContractsBasicExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        $base = [
            'customer_id','customer_name',
            'guarantor_id','guarantor_name',
            'product_type_id','product_type_name',
            'products_count','purchase_price','sale_price',
            'contract_value','investor_profit','total_value','discount_amount',
            'installment_type_id','installment_type_name',
            'installment_value','installments_count',
            'start_date','first_installment_date','contract_number',

            'investors',
            'payments',
        ];

        for ($i=1; $i<=6; $i++) {
            $base[] = "investor{$i}_id";
            $base[] = "investor{$i}_name";
            $base[] = "investor{$i}_pct";
        }

        for ($n=1; $n<=18; $n++) {
            $base[] = "payment{$n}_amount";
            $base[] = "payment{$n}_date";
        }

        return $base;
    }

    public function array(): array
    {
        $row = [
            1, 'عميل تجريبي',
            2, 'كفيل تجريبي',
            3, 'نوع منتج',
            4, 1000, 1200,
            5000, 500, 5500, 100,
            1, 'شهري',
            300, 12,
            '2024-01-01', '2024-02-01', 'CNT-001',
            '1:50|2:50',
            '2024-03-01:1000|2024-04-01:1000',
        ];

        for ($i=1; $i<=6; $i++) {
            $row[] = $i;                  // investor{$i}_id
            $row[] = "Investor {$i}";    // investor{$i}_name
            $row[] = 10 * $i;             // investor{$i}_pct
        }

        for ($n=1; $n<=18; $n++) {
            $row[] = 1000 * $n;                                       // payment{$n}_amount
            $row[] = '2024-' . str_pad((string)$n, 2, '0', STR_PAD_LEFT) . '-01'; // payment{$n}_date
        }

        return [$row];
    }
}
