<?php

namespace Modules\Guarantors\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GuarantorsIssuesExport implements WithMultipleSheets
{
    public function __construct(private array $failures, private array $skipped)
    {
    }

    public function sheets(): array
    {
        $sheets = [];

        if (!empty($this->failures)) {
            $sheets[] = new GuarantorsFailuresFixExport($this->failures);
        }

        if (!empty($this->skipped)) {
            $sheets[] = new GuarantorsSkippedExport($this->skipped);
        }

        return $sheets;
    }
}
