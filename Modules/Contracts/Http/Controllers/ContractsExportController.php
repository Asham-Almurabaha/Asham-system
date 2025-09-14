<?php

namespace Modules\Contracts\Http\Controllers;

use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Contracts\Exports\ContractsBasicExport;
use Modules\Contracts\Exports\ContractsInvestorsExport;

class ContractsExportController extends Controller
{
    public function basic()
    {
        return Excel::download(new ContractsBasicExport(), 'contracts_basic_example.xlsx');
    }

    public function investors()
    {
        return Excel::download(new ContractsInvestorsExport(), 'contracts_investors_mismatch.xlsx');
    }
}
