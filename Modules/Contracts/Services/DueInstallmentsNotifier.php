<?php

namespace Modules\Contracts\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DueInstallmentsNotifier
{
    /**
     * Build the notification payload for installments due today that are not fully paid.
     *
     * @param  int  $limit
     * @return array{count:int,items:array<int,array<string,mixed>>}
     */
    public function today(int $limit = 5): array
    {
        if (!Schema::hasTable('contract_installments') || !Schema::hasTable('contracts')) {
            return ['count' => 0, 'items' => []];
        }

        $today = Carbon::today();

        $query = DB::table('contract_installments as ci')
            ->join('contracts as c', 'c.id', '=', 'ci.contract_id');

        $hasCustomers = Schema::hasTable('customers');
        if ($hasCustomers) {
            $query->leftJoin('customers as cust', 'cust.id', '=', 'c.customer_id');
        }

        $hasStatuses = Schema::hasTable('installment_statuses');
        if ($hasStatuses) {
            $query->leftJoin('installment_statuses as st', 'st.id', '=', 'ci.installment_status_id');
        }

        $query->whereDate('ci.due_date', $today->toDateString())
            ->where(function ($due) {
                $due->whereNull('ci.payment_amount')
                    ->orWhereRaw('ci.payment_amount < ci.due_amount');
            });

        if ($hasStatuses) {
            $query->where(function ($status) {
                $status->whereNull('st.name')
                    ->orWhereNotIn('st.name', ['مؤجل', 'معتذر']);
            });
        }

        $count = (clone $query)->count();

        if ($count === 0) {
            return ['count' => 0, 'items' => []];
        }

        $limit = max($limit, 1);
        $currency = config('app.currency_symbol', 'ر.س');

        $itemsQuery = (clone $query)->select([
            'ci.id as installment_id',
            'ci.contract_id',
            'ci.installment_number',
            'ci.due_date',
            'ci.due_amount',
            DB::raw('COALESCE(ci.payment_amount, 0) as paid_amount'),
            'c.contract_number',
        ]);

        if ($hasCustomers) {
            $itemsQuery->addSelect('cust.name as customer_name');
        }

        $items = $itemsQuery
            ->orderBy('c.contract_number')
            ->orderBy('ci.installment_number')
            ->limit($limit)
            ->get()
            ->map(function ($row) use ($currency) {
                $dueAmount = (float) ($row->due_amount ?? 0);
                $paidAmount = (float) ($row->paid_amount ?? 0);
                $remaining = max($dueAmount - $paidAmount, 0.0);

                return [
                    'installment_id'    => (int) $row->installment_id,
                    'contract_id'       => (int) $row->contract_id,
                    'contract_number'   => $row->contract_number,
                    'installment_number'=> (int) ($row->installment_number ?? 0),
                    'customer_name'     => property_exists($row, 'customer_name') ? $row->customer_name : null,
                    'due_amount'        => $dueAmount,
                    'paid_amount'       => $paidAmount,
                    'remaining_amount'  => $remaining,
                    'due_date'          => Carbon::parse($row->due_date)->toDateString(),
                    'currency'          => $currency,
                ];
            })
            ->all();

        return [
            'count' => $count,
            'items' => $items,
        ];
    }
}
