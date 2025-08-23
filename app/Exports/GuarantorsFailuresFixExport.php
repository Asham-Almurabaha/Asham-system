<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GuarantorsFailuresFixExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private array $failures) {}

    public function headings(): array
    {
        return [
            'name','national_id','phone','email','address',
            'nationality','title','notes','id_card_image',
            'errors','source_row',
        ];
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->failures as $f) {
            $vals = (array)($f['values'] ?? []);
            $msgs = $f['messages'] ?? '';
            $rows[] = [
                $vals['name']         ?? ($vals['الاسم'] ?? ''),
                $vals['national_id']  ?? ($vals['الهوية'] ?? ''),
                $vals['phone']        ?? ($vals['الجوال'] ?? ''),
                $vals['email']        ?? '',
                $vals['address']      ?? ($vals['العنوان'] ?? ''),
                $vals['nationality']  ?? ($vals['الجنسية'] ?? ''),
                $vals['title']        ?? ($vals['الوظيفة'] ?? ''),
                $vals['notes']        ?? ($vals['ملاحظات'] ?? ''),
                $vals['id_card_image']?? '',
                is_array($msgs) ? implode(' | ', $msgs) : (string)$msgs,
                (int)($f['row'] ?? 0),
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('J:J')->getAlignment()->setWrapText(true);
        $sheet->freezePane('A2');
        return [];
    }
}
