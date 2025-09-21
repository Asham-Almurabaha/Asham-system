<?php

namespace Modules\Contracts\Exports;

use App\Support\ExcelHeadingLocalizer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Support\ContractStatusNames;
use Modules\Lookups\Entities\ContractStatus;

class ContractsInvestorsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function collection(): Collection
    {
        $statusId = ContractStatus::where('name', ContractStatusNames::NO_INVESTORS)->value('id');

        if (! $statusId) {
            return collect();
        }

        $contracts = Contract::with(['investors', 'contractStatus'])
            ->where('contract_status_id', $statusId)
            ->orderBy('contract_number')
            ->get();

        return $contracts->map(function (Contract $contract) {
            $total = $contract->investors->sum(function ($inv) {
                return (float) $inv->pivot->share_percentage;
            });

            $row = [
                'contract_number'        => $contract->contract_number,
                'contract_status'        => optional($contract->contractStatus)->name,
                'total_share_percentage' => $total,
            ];

            for ($i = 1; $i <= 6; $i++) {
                $investor = $contract->investors[$i - 1] ?? null;
                $row["investor{$i}_id"]   = $investor->id ?? '';
                $row["investor{$i}_name"] = $investor->name ?? '';
                $row["investor{$i}_pct"]  = $investor->pivot->share_percentage ?? '';
            }

            return $row;
        });
    }

    public function headings(): array
    {
        $base = [
            'contract_number',
            'contract_status',
            'total_share_percentage',
        ];

        for ($i = 1; $i <= 6; $i++) {
            $base[] = "investor{$i}_id";
            $base[] = "investor{$i}_name";
            $base[] = "investor{$i}_pct";
        }

        return ExcelHeadingLocalizer::translateMany($base);
    }
}
