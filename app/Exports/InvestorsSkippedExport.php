<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvestorsSkippedExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    /**
     * توقع شكل العناصر في $rows:
     * [
     *   [
     *     'row'    => 12,
     *     'values' => ['name' => '...', 'national_id' => '...', ...] // أو مفاتيح عربية
     *     'reason' => 'Duplicate phone' // أو 'messages' كمصفوفة/نص
     *   ],
     *   ...
     * ]
     */
    public function __construct(private array $rows) {}

    public function title(): string
    {
        return 'Skipped';
    }

    public function headings(): array
    {
        return [
            'name',
            'national_id',
            'phone',
            'email',
            'address',
            'nationality',
            'title',
            'id_card_image',
            'contract_image',
            'office_share_percentage',
            'reason',
            'source_row',
        ];
    }

    public function array(): array
    {
        $out = [];

        foreach ($this->rows as $r) {
            $vals   = (array)($r['values'] ?? []);
            $reason = $r['reason'] ?? ($r['messages'] ?? '');

            $out[] = [
                $this->clean($this->pick($vals, ['name','الاسم'])),
                $this->clean($this->pick($vals, ['national_id','الهوية','رقم_الهوية'])),
                $this->clean($this->pick($vals, ['phone','الجوال','رقم_الجوال'])),
                $this->clean($this->pick($vals, ['email','البريد','البريد_الإلكتروني'])),
                $this->clean($this->pick($vals, ['address','العنوان'])),
                $this->clean($this->pick($vals, ['nationality','الجنسية'])),
                $this->clean($this->pick($vals, ['title','الوظيفة'])),
                $this->clean($this->pick($vals, ['id_card_image','صورة_الهوية'])),
                $this->clean($this->pick($vals, ['contract_image','صورة_العقد'])),
                $this->clean($this->pick($vals, ['office_share_percentage','نسبة_مشاركة_المكتب'])),
                $this->flattenMessages($reason),
                (int)($r['row'] ?? 0),
            ];
        }

        return $out;
    }

    public function styles(Worksheet $sheet)
    {
        // تمييز الهيدر
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);

        // تغليف نص عمود السبب (K)
        $sheet->getStyle('K:K')->getAlignment()->setWrapText(true);

        // تجميد الصف الأول
        $sheet->freezePane('A2');

        return [];
    }

    /* ===================== Helpers ===================== */

    private function pick(array $values, array $keys): mixed
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $values) && $values[$k] !== null && $values[$k] !== '') {
                return $values[$k];
            }
        }
        return '';
    }

    private function clean(mixed $v): string
    {
        if (is_array($v) || is_object($v)) {
            return trim(json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        return trim((string)$v);
    }

    private function flattenMessages(mixed $msgs): string
    {
        if (is_array($msgs)) {
            $flat = [];
            array_walk_recursive($msgs, function ($item) use (&$flat) {
                if ($item !== null && $item !== '') $flat[] = (string)$item;
            });
            return implode(' | ', $flat);
        }
        return (string)$msgs;
    }
}
