<?php

namespace Modules\Contracts\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContractsInvestorsSkippedExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(private array $rows) {}

    public function title(): string
    {
        return 'Skipped';
    }

    public function headings(): array
    {
        $base = [
            'contract_number',
            'investors',
        ];

        for ($i = 1; $i <= 6; $i++) {
            $base[] = "investor{$i}_id";
            $base[] = "investor{$i}_pct";
        }

        $base[] = 'reason';
        $base[] = 'source_row';

        return $base;
    }

    public function array(): array
    {
        $out = [];
        foreach ($this->rows as $r) {
            $vals = (array)($r['values'] ?? []);
            $reason = $r['reason'] ?? ($r['messages'] ?? '');

            $row = [
                $vals['contract_number'] ?? '',
                $vals['investors'] ?? '',
            ];

            for ($i = 1; $i <= 6; $i++) {
                $row[] = $vals["investor{$i}_id"] ?? '';
                $row[] = $vals["investor{$i}_pct"] ?? '';
            }

            $row[] = is_array($reason) ? implode(' | ', $reason) : (string)$reason;
            $row[] = (int)($r['row'] ?? 0);

            $out[] = $row;
        }

        return $out;
    }

    public function styles(Worksheet $sheet)
    {
        $highestCol = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$highestCol}1")->getFont()->setBold(true);

        $lastColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
        $reasonColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIndex - 1);
        $sheet->getStyle("{$reasonColLetter}:{$reasonColLetter}")->getAlignment()->setWrapText(true);

        $sheet->freezePane('A2');
        return [];
    }
}
