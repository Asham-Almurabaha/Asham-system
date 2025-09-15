<?php

namespace Modules\Contracts\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ContractsInvestorsIssuesExport implements WithMultipleSheets
{
    public function __construct(private array $failures, private array $skipped) {}

    public function sheets(): array
    {
        $sheets = [];
        if (!empty($this->failures)) {
            $sheets[] = new ContractsInvestorsFailuresFixExport($this->failures);
        }
        if (!empty($this->skipped)) {
            $sheets[] = new ContractsInvestorsSkippedExport($this->skipped);
        }
        return $sheets;
    }
}
