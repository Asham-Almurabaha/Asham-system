<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractStatus;
use Illuminate\Support\Facades\Schema;

class ContractReportController extends Controller
{
    public function status($status)
    {
        $statusIdCol = null;
        foreach (['contract_status_id','status_id','state_id'] as $col) {
            if (Schema::hasColumn('contracts', $col)) { $statusIdCol = $col; break; }
        }
        $statusNameCol = null;
        foreach (['status','state','contract_status'] as $col) {
            if (Schema::hasColumn('contracts', $col)) { $statusNameCol = $col; break; }
        }

        $statusName = null;
        $statusId = null;
        if (is_numeric($status)) {
            $statusId = (int)$status;
            if (class_exists(ContractStatus::class)) {
                $statusName = ContractStatus::find($statusId)?->name;
            }
        } else {
            $statusName = (string)$status;
            if (class_exists(ContractStatus::class)) {
                $statusId = ContractStatus::where('name', $statusName)->value('id');
            }
        }

        $rows = Contract::query()
            ->with(['customer','guarantor','productType','contractStatus'])
            ->when($statusIdCol && $statusId, fn($q) => $q->where($statusIdCol, $statusId))
            ->when(!$statusIdCol && $statusNameCol && $statusName, fn($q) => $q->where($statusNameCol, $statusName))
            ->orderBy('start_date')
            ->get();

        $title = __('Contracts Report').' - '.($statusName ?: __('Status'));
        return view('contracts.reports.status', compact('rows','title','statusName'));
    }

    public function withoutInvestor()
    {
        $rows = Contract::query()
            ->with(['customer','guarantor','productType','contractStatus'])
            ->doesntHave('investors')
            ->orderBy('start_date')
            ->get();

        $title = __('Contracts Report').' - '.__('Without Investor');
        return view('contracts.reports.status', compact('rows','title'));
    }
}

