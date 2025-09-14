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

            foreach ($contract->investors as $investor) {
                $rows->push([
                    'contract_number'       => $contract->contract_number,
                    'investor_id'           => $investor->id,
                    'investor_name'         => $investor->name,
                    'share_percentage'      => $investor->pivot->share_percentage,
                    'total_share_percentage'=> $total,
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'contract_number',
            'investor_id',
            'investor_name',
            'share_percentage',
            'total_share_percentage',
        ];
    }
}
