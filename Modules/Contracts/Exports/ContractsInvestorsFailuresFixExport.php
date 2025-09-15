<?php

namespace Modules\Contracts\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContractsInvestorsFailuresFixExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(private array $rows) {}

    public function title(): string
    {
        return 'Failures';
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

        $base[] = '__errors';
        $base[] = '__row';

        return $base;
    }

    public function array(): array
    {
        $out = [];
        foreach ($this->rows as $f) {
            $vals = (array)($f['values'] ?? []);
            $msgs = $f['messages'] ?? ($f['errors'] ?? '');

            $row = [
                $vals['contract_number'] ?? '',
                $vals['investors'] ?? '',
            ];

            for ($i = 1; $i <= 6; $i++) {
                $row[] = $vals["investor{$i}_id"] ?? '';
                $row[] = $vals["investor{$i}_pct"] ?? '';
            }

            $row[] = is_array($msgs) ? implode(' | ', $msgs) : (string)$msgs;
            $row[] = (int)($f['row'] ?? 0);

            $out[] = $row;
        }

        return $out;
    }

    public function styles(Worksheet $sheet)
    {
        $highestCol = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$highestCol}1")->getFont()->setBold(true);

        $lastColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
        $errorsColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIndex - 1);
        $sheet->getStyle("{$errorsColLetter}:{$errorsColLetter}")->getAlignment()->setWrapText(true);

        $sheet->freezePane('A2');
        return [];
    }
}
