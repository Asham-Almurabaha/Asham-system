<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InstallmentStatus;
use Illuminate\Support\Facades\Schema;

class CustomerReportController extends Controller
{
    public function delinquent()
    {
        $statusId = InstallmentStatus::where('name', 'متأخر')->value('id');
        $rows = Customer::query()
            ->whereHas('contracts.installments', fn($q) => $q->where('installment_status_id', $statusId))
            ->orderBy('name')
            ->get();
        return view('customers.reports.delinquent', ['rows' => $rows]);
    }

    public function unpaid()
    {
        $start = now()->startOfMonth();
        $end   = now()->endOfMonth();
        $rows = Customer::query()
            ->whereHas('contracts.installments', function ($q) use ($start, $end) {
                $q->whereBetween('due_date', [$start, $end])
                  ->whereNull('payment_date');
            })
            ->orderBy('name')
            ->get();
        return view('customers.reports.unpaid', ['rows' => $rows]);
    }

    public function contracts()
    {
        $endedStatusNames = ['منتهي','منتهى','سداد مبكر','سداد مُبكر','سداد مبكّر','Completed','Early Settlement'];
        $endedStatusIds = [];
        if (class_exists(\App\Models\ContractStatus::class)) {
            $endedStatusIds = \App\Models\ContractStatus::query()
                ->whereIn('name', $endedStatusNames)
                ->pluck('id')
                ->all();
        }

        $statusIdCol = null;
        foreach (['contract_status_id','status_id','state_id'] as $col) {
            if (Schema::hasColumn('contracts', $col)) { $statusIdCol = $col; break; }
        }
        $statusNameCol = null;
        foreach (['status','state','contract_status'] as $col) {
            if (Schema::hasColumn('contracts', $col)) { $statusNameCol = $col; break; }
        }

        $rows = Customer::query()
            ->withCount('contracts')
            ->withCount(['contracts as active_contracts' => function ($c) use ($statusIdCol, $statusNameCol, $endedStatusIds, $endedStatusNames) {
                if ($statusIdCol && !empty($endedStatusIds)) {
                    $c->whereNotIn($statusIdCol, $endedStatusIds);
                } elseif ($statusNameCol) {
                    $c->whereNotIn($statusNameCol, $endedStatusNames);
                } elseif (Schema::hasColumn('contracts', 'is_closed')) {
                    $c->where('is_closed', 0);
                } elseif (Schema::hasColumn('contracts', 'closed_at')) {
                    $c->whereNull('closed_at');
                }
            }])
            ->orderBy('name')
            ->get();

        return view('customers.reports.contracts', ['rows' => $rows]);
    }
}
