<?php

namespace Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Customers\Entities\Customer;
use App\Models\Lookups\InstallmentStatus;
use Illuminate\Support\Facades\Schema;

class CustomerReportController extends Controller
{
    public function active()
    {
        $endedStatusNames = ['منتهي','منتهى','سداد مبكر','سداد مُبكر','سداد مبكّر','Completed','Early Settlement'];
        $endedStatusIds = [];
        if (class_exists(\App\Models\Lookups\ContractStatus::class)) {
            $endedStatusIds = \App\Models\Lookups\ContractStatus::query()
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
            ->with(['contracts' => function ($c) use ($statusIdCol, $statusNameCol, $endedStatusIds, $endedStatusNames) {
                if ($statusIdCol && !empty($endedStatusIds)) {
                    $c->whereNotIn($statusIdCol, $endedStatusIds);
                } elseif ($statusNameCol) {
                    $c->whereNotIn($statusNameCol, $endedStatusNames);
                } elseif (Schema::hasColumn('contracts', 'is_closed')) {
                    $c->where('is_closed', 0);
                } elseif (Schema::hasColumn('contracts', 'closed_at')) {
                    $c->whereNull('closed_at');
                }
                $c->with('installments');
            }])
            ->whereHas('contracts', function ($c) use ($statusIdCol, $statusNameCol, $endedStatusIds, $endedStatusNames) {
                if ($statusIdCol && !empty($endedStatusIds)) {
                    $c->whereNotIn($statusIdCol, $endedStatusIds);
                } elseif ($statusNameCol) {
                    $c->whereNotIn($statusNameCol, $endedStatusNames);
                } elseif (Schema::hasColumn('contracts', 'is_closed')) {
                    $c->where('is_closed', 0);
                } elseif (Schema::hasColumn('contracts', 'closed_at')) {
                    $c->whereNull('closed_at');
                }
            })
            ->orderBy('name')
            ->get()
            ->transform(function ($customer) {
                $activeContracts = $customer->contracts ?? collect();
                $totalRemaining = 0.0;
                foreach ($activeContracts as $contract) {
                    $items = $contract->installments ?? collect();
                    $contractTotal = (float) ($items->sum(fn($i) => (float) ($i->due_amount ?? 0)) ?: ($contract->total_value ?? 0));
                    $totalPaid     = (float) $items->sum(fn($i) => (float) ($i->payment_amount ?? 0));
                    $remaining     = max(0.0, $contractTotal - $totalPaid);
                    $totalRemaining += $remaining;
                }
                $customer->active_remaining_total = round($totalRemaining, 2);
                return $customer;
            });

        return view('customers::reports.active', ['rows' => $rows]);
    }
    public function delinquent()
    {
        $statusId = InstallmentStatus::where('name', 'متأخر')->value('id');
        $rows = Customer::query()
            ->whereHas('contracts.installments', fn($q) => $q->where('installment_status_id', $statusId))
            ->orderBy('name')
            ->get();
        return view('customers::reports.delinquent', ['rows' => $rows]);
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
            ->with(['contracts' => function ($q) use ($start, $end) {
                $q->with(['installments' => function ($iq) use ($start, $end) {
                    $iq->whereBetween('due_date', [$start, $end])
                       ->whereNull('payment_date');
                }]);
            }])
            ->orderBy('name')
            ->get()
            ->transform(function ($customer) {
                $contracts = $customer->contracts ?? collect();
                $count = 0; $total = 0.0;
                foreach ($contracts as $contract) {
                    $items = $contract->installments ?? collect();
                    $count += $items->count();
                    $total += (float) $items->sum(fn($i) => (float) ($i->due_amount ?? 0));
                }
                $customer->unpaid_month_count = $count;
                $customer->unpaid_month_total = round($total, 2);
                return $customer;
            });
        return view('customers::reports.unpaid', ['rows' => $rows]);
    }

    public function contracts()
    {
        $endedStatusNames = ['منتهي','منتهى','سداد مبكر','سداد مُبكر','سداد مبكّر','Completed','Early Settlement'];
        $endedStatusIds = [];
        if (class_exists(\App\Models\Lookups\ContractStatus::class)) {
            $endedStatusIds = \App\Models\Lookups\ContractStatus::query()
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

        $query = Customer::query()
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
            // Load only active contracts with installments to compute remaining
            ->with(['contracts' => function ($c) use ($statusIdCol, $statusNameCol, $endedStatusIds, $endedStatusNames) {
                if ($statusIdCol && !empty($endedStatusIds)) {
                    $c->whereNotIn($statusIdCol, $endedStatusIds);
                } elseif ($statusNameCol) {
                    $c->whereNotIn($statusNameCol, $endedStatusNames);
                } elseif (Schema::hasColumn('contracts', 'is_closed')) {
                    $c->where('is_closed', 0);
                } elseif (Schema::hasColumn('contracts', 'closed_at')) {
                    $c->whereNull('closed_at');
                }
                $c->with('installments');
            }]);

        // Optional filters:
        // - only customers with NO contracts at all: ?only_empty=1
        if (request()->boolean('only_empty')) {
            $query->doesntHave('contracts');
        }

        // - only customers with NO ACTIVE contracts: ?without_active=1
        if (request()->boolean('without_active')) {
            $query->whereDoesntHave('contracts', function ($c) use ($statusIdCol, $statusNameCol, $endedStatusIds, $endedStatusNames) {
                if ($statusIdCol && !empty($endedStatusIds)) {
                    $c->whereNotIn($statusIdCol, $endedStatusIds); // any active contract excludes the customer
                } elseif ($statusNameCol) {
                    $c->whereNotIn($statusNameCol, $endedStatusNames);
                } elseif (Schema::hasColumn('contracts', 'is_closed')) {
                    $c->where('is_closed', 0);
                } elseif (Schema::hasColumn('contracts', 'closed_at')) {
                    $c->whereNull('closed_at');
                }
            });
        }

        // Default behavior: show only customers who have any contracts
        if (!request()->boolean('only_empty') && !request()->boolean('without_active')) {
            $query->has('contracts');
        }

        $rows = $query->orderBy('name')->get()->transform(function ($customer) {
            $activeContracts = $customer->contracts ?? collect();
            $totalRemaining = 0.0;
            foreach ($activeContracts as $contract) {
                $items = $contract->installments ?? collect();
                $contractTotal = (float) ($items->sum(fn($i) => (float) ($i->due_amount ?? 0)) ?: ($contract->total_value ?? 0));
                $totalPaid     = (float) $items->sum(fn($i) => (float) ($i->payment_amount ?? 0));
                $remaining     = max(0.0, $contractTotal - $totalPaid);
                $totalRemaining += $remaining;
            }
            $customer->active_remaining_total = round($totalRemaining, 2);
            return $customer;
        });

        return view('customers::reports.contracts', ['rows' => $rows]);
    }
}
