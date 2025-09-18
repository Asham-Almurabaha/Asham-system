<?php

namespace Modules\Investors\Exports;

use App\Support\ExcelHeadingLocalizer;
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
        return ExcelHeadingLocalizer::translateMany([
            'name','national_id','phone','email','address',
            'nationality','title','id_card_image','contract_image',
            'office_share_percentage','investment_start_date',
            'errors','source_row',
        ]);
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
                $vals['investment_start_date'] ?? ($vals['تاريخ_بدء_الاستثمار'] ?? ($vals['start_date'] ?? '')),
                is_array($msgs) ? implode(' | ', $msgs) : (string)$msgs,
                (int)($f['row'] ?? 0),
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);
        $sheet->getStyle('L:L')->getAlignment()->setWrapText(true);
        $sheet->freezePane('A2');
        return [];
    }
}
