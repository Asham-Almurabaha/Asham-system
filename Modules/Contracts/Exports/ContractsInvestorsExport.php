<?php

namespace Modules\Contracts\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\Contracts\Entities\Contract;

class ContractsInvestorsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function collection(): Collection
    {
        $rows = collect();

        $contracts = Contract::with('investors')->get();

        foreach ($contracts as $contract) {
            $total = $contract->investors->sum(function ($inv) {
                return (float) $inv->pivot->share_percentage;
            });

            if ((float) $total == 100.0) {
                continue;
            }

            $row = [
                'contract_number'        => $contract->contract_number,
                'total_share_percentage' => $total,
            ];

            for ($i = 1; $i <= 6; $i++) {
                $investor = $contract->investors[$i - 1] ?? null;
                $row["investor{$i}_id"]   = $investor->id ?? '';
                $row["investor{$i}_name"] = $investor->name ?? '';
                $row["investor{$i}_pct"]  = $investor->pivot->share_percentage ?? '';
            }

            $rows->push($row);
        }

        return $rows;
    }

    public function headings(): array
    {
        $base = [
            'contract_number',
            'total_share_percentage',
        ];

        for ($i = 1; $i <= 6; $i++) {
            $base[] = "investor{$i}_id";
            $base[] = "investor{$i}_name";
            $base[] = "investor{$i}_pct";
        }

        return $base;
    }
}
