<?php

namespace Modules\Customers\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CustomersIssuesExport implements WithMultipleSheets
{
    public function __construct(private array $failures, private array $skipped)
    {
    }

    public function sheets(): array
    {
        $sheets = [];
        if (!empty($this->failures)) {
            $sheets[] = new CustomersFailuresFixExport($this->failures);
        }
        if (!empty($this->skipped)) {
            $sheets[] = new CustomersSkippedExport($this->skipped);
        }
        return $sheets;
    }
}
