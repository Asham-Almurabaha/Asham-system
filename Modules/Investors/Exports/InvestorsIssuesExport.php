<?php

namespace Modules\Investors\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InvestorsIssuesExport implements WithMultipleSheets
{
    public function __construct(private array $failures, private array $skipped)
    {
    }

    public function sheets(): array
    {
        $sheets = [];

        if (!empty($this->failures)) {
            $sheets[] = new InvestorsFailuresFixExport($this->failures);
        }

        if (!empty($this->skipped)) {
            $sheets[] = new InvestorsSkippedExport($this->skipped);
        }

        return $sheets;
    }
}
