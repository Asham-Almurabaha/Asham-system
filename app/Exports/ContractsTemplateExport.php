<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ContractsTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
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

            // خيارات موحدة قديمة/مرنة:
            'investors', // id:pct|id:pct
            'payments',  // date:amount|date:amount (#note اختياري)
        ];

        // أعمدة المستثمرين المنفصلة حتى 6
        for ($i=1; $i<=6; $i++) {
            $base[] = "investor{$i}_id";
            $base[] = "investor{$i}_name";
            $base[] = "investor{$i}_pct";
        }

        // السدادات حتى 18: amount + date
        for ($n=1; $n<=18; $n++) {
            $base[] = "payment{$n}_amount";
            $base[] = "payment{$n}_date";
        }

        return $base;
    }

    public function array(): array
    {
        return [];
    }
}
