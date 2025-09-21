<?php

namespace Modules\Contracts\Exports;

use App\Support\ExcelHeadingLocalizer;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ContractsInvestorsTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        $base = [
            'contract_number',
            'investors',
        ];

        for ($i = 1; $i <= 6; $i++) {
            $base[] = "investor{$i}_id";
            $base[] = "investor{$i}_name";
            $base[] = "investor{$i}_pct";
        }

        return ExcelHeadingLocalizer::translateMany($base);
    }

    public function array(): array
    {
        return [
            $this->buildRow('CNT-001', '1:60|2:40', [
                [1, 'مستثمر أول', 60],
                [2, 'مستثمر ثاني', 40],
            ]),
            $this->buildRow('CNT-002', '', [
                [5, 'Investor Alpha', 100],
            ]),
        ];
    }

    /**
     * @param  array<int, array{0:int|null,1:string|null,2:float|int|null}>  $investors
     */
    private function buildRow(string $contractNumber, string $investorsColumn, array $investors): array
    {
        $row = [
            $contractNumber,
            $investorsColumn,
        ];

        for ($i = 1; $i <= 6; $i++) {
            $data = $investors[$i - 1] ?? [null, null, null];
            $row[] = $data[0] ?? '';
            $row[] = $data[1] ?? '';
            $row[] = $data[2] ?? '';
        }

        return $row;
    }
}
