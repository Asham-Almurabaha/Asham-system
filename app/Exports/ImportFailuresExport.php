<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportFailuresExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private array $rows) {}

    public function headings(): array
    {
        return ['row', 'attribute', 'messages', 'values'];
    }

    public function array(): array
    {
        // تأكد إن كل العناصر Scalars أو Arrays قابلة للعرض
        return array_map(function ($r) {
            return [
                $r['row']       ?? '',
                $r['attribute'] ?? '',
                $r['messages']  ?? '',
                $r['values']    ?? '',
            ];
        }, $this->rows);
    }

    public function styles(Worksheet $sheet)
    {
        // تغليف النص في عمود الرسائل والقيم، وتوسيط رؤوس الأعمدة
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('C:D')->getAlignment()->setWrapText(true);
        $sheet->freezePane('A2');

        return [];
    }
}
