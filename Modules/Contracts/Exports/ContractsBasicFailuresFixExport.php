<?php

namespace Modules\Contracts\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContractsBasicFailuresFixExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private array $failures) {}

    public function headings(): array
    {
        return [
            'contract_number','customer_id','customer_name',
            'guarantor_id','guarantor_name',
            'product_type_id','product_type_name',
            'products_count','purchase_price','sale_price',
            'contract_value','investor_profit','total_value','discount_amount',
            'installment_type_id','installment_type_name',
            'installment_value','installments_count',
            'start_date','first_installment_date',
            '__errors','__row',
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->failures as $f) {
            $vals = (array)($f['values'] ?? []);
            $msgs = $f['messages'] ?? ($f['errors'] ?? '');

            $rows[] = [
                $vals['contract_number']        ?? '',
                $vals['customer_id']            ?? '',
                $vals['customer_name']          ?? '',
                $vals['guarantor_id']           ?? '',
                $vals['guarantor_name']         ?? '',
                $vals['product_type_id']        ?? '',
                $vals['product_type_name']      ?? '',
                $vals['products_count']         ?? '',
                $vals['purchase_price']         ?? '',
                $vals['sale_price']             ?? '',
                $vals['contract_value']         ?? '',
                $vals['investor_profit']        ?? '',
                $vals['total_value']            ?? '',
                $vals['discount_amount']        ?? '',
                $vals['installment_type_id']    ?? '',
                $vals['installment_type_name']  ?? '',
                $vals['installment_value']      ?? '',
                $vals['installments_count']     ?? '',
                $vals['start_date']             ?? '',
                $vals['first_installment_date'] ?? '',
                is_array($msgs) ? implode(' | ', $msgs) : (string)$msgs,
                (int)($f['row'] ?? 0),
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $highestCol = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$highestCol}1")->getFont()->setBold(true);

        $lastColIndex   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
        $errorsColIndex = $lastColIndex - 1;
        $errorsColLetter= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($errorsColIndex);
        $sheet->getStyle("{$errorsColLetter}:{$errorsColLetter}")->getAlignment()->setWrapText(true);

        $sheet->freezePane('A2');
        return [];
    }
}
