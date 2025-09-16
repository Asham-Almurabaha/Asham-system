<?php

namespace Modules\Contracts\Exports;

use App\Support\ExcelHeadingLocalizer;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ContractsBasicExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        $base = [
            'contract_number','customer_id','customer_name',
            'guarantor_id','guarantor_name',
            'product_type_id','product_type_name',
            'products_count','purchase_price','sale_price',
            'contract_value','investor_profit','total_value','discount_amount',
            'installment_type_id','installment_type_name',
            'installment_value','installments_count',
            'start_date','first_installment_date',
        ];

        return ExcelHeadingLocalizer::translateMany($base);
    }

    public function array(): array
    {
        $row = [
            'CNT-001', 1, 'عميل تجريبي',
            2, 'كفيل تجريبي',
            3, 'نوع منتج',
            4, 1000, 1200,
            5000, 500, 5500, 100,
            1, 'شهري',
            300, 12,
            '2024-01-01', '2024-02-01',
        ];

        return [$row];
    }
}
