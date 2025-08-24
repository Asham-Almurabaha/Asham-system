<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomersSkippedExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(private array $rows) {}

    public function title(): string
    {
        return 'Skipped';
    }

    public function headings(): array
    {
        return [
            'name','national_id','phone','email','address',
            'nationality','title','id_card_image','contract_image',
            'reason','source_row',
        ];
    }

    public function array(): array
    {
        $out = [];
        foreach ($this->rows as $r) {
            $vals   = (array)($r['values'] ?? []);
            $reason = $r['reason'] ?? ($r['messages'] ?? '');
            $out[] = [
                $this->pick($vals, ['name','الاسم']),
                $this->pick($vals, ['national_id','الهوية','رقم_الهوية']),
                $this->pick($vals, ['phone','الجوال','رقم_الجوال']),
                $this->pick($vals, ['email','البريد','البريد_الإلكتروني']),
                $this->pick($vals, ['address','العنوان']),
                $this->pick($vals, ['nationality','الجنسية']),
                $this->pick($vals, ['title','الوظيفة']),
                $this->pick($vals, ['id_card_image','صورة_الهوية']),
                $this->pick($vals, ['contract_image','صورة_العقد']),
                $this->flatten($reason),
                (int)($r['row'] ?? 0),
            ];
        }
        return $out;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('J:J')->getAlignment()->setWrapText(true);
        $sheet->freezePane('A2');
        return [];
    }

    private function pick(array $values, array $keys): string
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $values) && $values[$k] !== null && $values[$k] !== '') {
                return is_scalar($values[$k]) ? (string)$values[$k] : json_encode($values[$k], JSON_UNESCAPED_UNICODE);
            }
        }
        return '';
    }

    private function flatten(mixed $msgs): string
    {
        if (is_array($msgs)) {
            $flat = [];
            array_walk_recursive($msgs, function ($v) use (&$flat) { if ($v !== null && $v !== '') $flat[] = (string)$v; });
            return implode(' | ', $flat);
        }
        return (string)$msgs;
    }
}
