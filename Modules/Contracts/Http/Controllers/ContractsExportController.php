<?php

namespace Modules\Contracts\Http\Controllers;

use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Contracts\Exports\ContractsBasicExport;
use Modules\Contracts\Exports\ContractsInvestorsExport;
use Modules\Contracts\Exports\ContractsPaymentsExport;

class ContractsExportController extends Controller
{
    public function create()
    {
        return view('contracts::export');
    }

    public function basic()
    {
        return Excel::download(new ContractsBasicExport(), 'contracts_basic_example.xlsx');
    }

    public function investors()
    {
        return Excel::download(new ContractsInvestorsExport(), 'contracts_investors_mismatch.xlsx');
    }

    public function payments()
    {
        return Excel::download(new ContractsPaymentsExport(), 'contracts_installments_status.xlsx');
    }
}
