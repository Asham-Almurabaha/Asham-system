<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContractsFailuresFixExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private array $failures) {}

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

            'investors', // id:pct|id:pct
            'payments',  // date:amount|date:amount
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

        // أعمدة مساعدة
        $base[] = '__errors';
        $base[] = '__row';

        return $base;
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->failures as $f) {
            $vals = (array)($f['values'] ?? []);
            $msgs = $f['messages'] ?? ($f['errors'] ?? '');

            $row = [
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
                $vals['contract_number']        ?? '',

                $vals['investors']              ?? '',
                $vals['payments']               ?? '',
            ];

            for ($i=1; $i<=6; $i++) {
                $row[] = $vals["investor{$i}_id"]   ?? '';
                $row[] = $vals["investor{$i}_name"] ?? '';
                $row[] = $vals["investor{$i}_pct"]  ?? '';
            }

            for ($n=1; $n<=18; $n++) {
                $row[] = $vals["payment{$n}_amount"] ?? '';
                $row[] = $vals["payment{$n}_date"]   ?? '';
            }

            $row[] = is_array($msgs) ? implode(' | ', $msgs) : (string)$msgs;
            $row[] = (int)($f['row'] ?? 0);

            $rows[] = $row;
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Bold للعناوين
        $highestCol = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$highestCol}1")->getFont()->setBold(true);

        // لفّ النص في عمود الأخطاء (قبل الأخير)
        $lastColIndex   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
        $errorsColIndex = $lastColIndex - 1;
        $errorsColLetter= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($errorsColIndex);
        $sheet->getStyle("{$errorsColLetter}:{$errorsColLetter}")->getAlignment()->setWrapText(true);

        $sheet->freezePane('A2');
        return [];
    }
}
