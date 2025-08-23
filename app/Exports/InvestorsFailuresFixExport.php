<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvestorsFailuresFixExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private array $failures) {}

    public function headings(): array
    {
        return [
            'name','national_id','phone','email','address',
            'nationality','title','id_card_image','contract_image',
            'office_share_percentage',
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
                $vals['id_card_image']?? ($vals['صورة_الهوية'] ?? ''),
                $vals['contract_image']?? ($vals['صورة_العقد'] ?? ''),
                $vals['office_share_percentage'] ?? ($vals['نسبة_مشاركة_المكتب'] ?? ''),
                is_array($msgs) ? implode(' | ', $msgs) : (string)$msgs,
                (int)($f['row'] ?? 0),
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);
        $sheet->getStyle('K:K')->getAlignment()->setWrapText(true);
        $sheet->freezePane('A2');
        return [];
    }
}
