<?php

namespace Modules\Contracts\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Contracts\Entities\Contract;
use Modules\Lookups\Entities\ContractStatus;

class ContractStatusSummaryService
{
    /**
     * Build the contract status distribution along with remaining amounts aggregates.
     */
    public function buildDistribution(): array
    {
        $contractsTotal = Contract::count();

        $statusCounts = Contract::select('contract_status_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('contract_status_id')
            ->get();

        $statusNames = ContractStatus::pluck('name', 'id');

        $remainingByStatus = $this->calculateRemainingAmountsByStatus();

        $namesEnded   = ['منتهي', 'سداد مبكر'];
        $namesPending = ['معلق'];

        $endedIds = $statusNames
            ->filter(fn ($name) => in_array($name, $namesEnded, true))
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();

        $pendingIds = $statusNames
            ->filter(fn ($name) => in_array($name, $namesPending, true))
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();

        $statuses = $statusCounts->map(function ($row) use ($statusNames, $contractsTotal) {
            $name = $statusNames[$row->contract_status_id] ?? 'غير محدد';
            $cnt  = (int) $row->cnt;
            $pct  = $contractsTotal > 0 ? round(($cnt / $contractsTotal) * 100, 2) : 0.0;

            return [
                'id'    => (int) $row->contract_status_id,
                'name'  => $name,
                'count' => $cnt,
                'pct'   => $pct,
            ];
        })->map(function ($row) use ($remainingByStatus) {
            $statusId = (int) ($row['id'] ?? 0);
            $row['remaining'] = (float) ($remainingByStatus[$statusId] ?? 0.0);

            return $row;
        })->sortByDesc('count')->values();

        $chartLabels = $statuses->pluck('name')->values()->all();
        $chartData   = $statuses->pluck('count')->values()->all();

        $normalize = static function ($value): string {
            return mb_strtolower(trim((string) $value), 'UTF-8');
        };

        $raisedNames    = array_map($normalize, ['مرفوع فيه', 'مرفوع', 'raised', 'raised status']);
        $requiredNames  = array_map($normalize, ['مطلوب', 'required']);

        $classification = [];
        $raisedCount = 0;
        $requiredCount = 0;
        $raisedRemaining = 0.0;
        $requiredRemaining = 0.0;

        $statuses->each(function (&$status) use (
            &$classification,
            &$raisedCount,
            &$requiredCount,
            &$raisedRemaining,
            &$requiredRemaining,
            $normalize,
            $raisedNames,
            $requiredNames,
            $endedIds,
            $pendingIds
        ) {
            $statusId = (int) ($status['id'] ?? 0);
            $count = (int) ($status['count'] ?? 0);
            $remainingAmount = (float) ($status['remaining'] ?? 0.0);
            $nameNormalized = $normalize($status['name'] ?? '');

            if (in_array($statusId, $endedIds, true)) {
                $classification[$statusId] = 'ended';
            } elseif (in_array($statusId, $pendingIds, true)) {
                $classification[$statusId] = 'pending';
            } else {
                $classification[$statusId] = 'active';
            }

            if (in_array($nameNormalized, $raisedNames, true)) {
                $raisedCount += $count;
                $raisedRemaining += $remainingAmount;
                $classification[$statusId] = 'raised';
            }

            if (in_array($nameNormalized, $requiredNames, true)) {
                $requiredCount += $count;
                $requiredRemaining += $remainingAmount;
                $classification[$statusId] = 'required';
            }
        });

        $statuses = $statuses->map(function ($status) use ($classification) {
            $statusId = (int) ($status['id'] ?? 0);
            $status['classification'] = $classification[$statusId] ?? null;

            return $status;
        });

        $activeRemaining = 0.0;
        foreach ($remainingByStatus as $statusId => $remainingAmount) {
            $statusId = (int) $statusId;
            if (!in_array($statusId, $endedIds, true) && !in_array($statusId, $pendingIds, true)) {
                $activeRemaining += (float) $remainingAmount;
            }
        }

        $activeRemaining   = round($activeRemaining, 2);
        $raisedRemaining   = round($raisedRemaining, 2);
        $requiredRemaining = round($requiredRemaining, 2);

        $activeCount = $statuses->reduce(function (int $carry, array $status) {
            $classification = $status['classification'] ?? null;
            if (in_array($classification, ['ended', 'pending'], true)) {
                return $carry;
            }

            return $carry + (int) ($status['count'] ?? 0);
        }, 0);

        $labels = [
            'ended'   => $this->buildStatusLabel($statusNames, $endedIds),
            'pending' => $this->buildStatusLabel($statusNames, $pendingIds),
        ];

        return [
            'total'    => $contractsTotal,
            'statuses' => $statuses->values()->all(),
            'chart'    => [
                'labels' => $chartLabels,
                'data'   => $chartData,
            ],
            'raised'   => $raisedCount,
            'required' => $requiredCount,
            'active'   => $activeCount,
            'active_remaining'   => $activeRemaining,
            'raised_remaining'   => $raisedRemaining,
            'required_remaining' => $requiredRemaining,
            'remaining_summary'  => [
                'active'   => $activeRemaining,
                'raised'   => $raisedRemaining,
                'required' => $requiredRemaining,
            ],
            'remaining_total'   => round(array_sum($remainingByStatus), 2),
            'remaining_by_status' => $remainingByStatus,
            'classification'      => $classification,
            'ended_status_ids'    => $endedIds,
            'pending_status_ids'  => $pendingIds,
            'labels'              => $labels,
        ];
    }

    /**
     * Calculate the remaining amounts grouped by status id.
     *
     * @return array<int,float>
     */
    private function calculateRemainingAmountsByStatus(): array
    {
        $installmentsAgg = DB::table('contract_installments as ci')
            ->selectRaw('ci.contract_id, SUM(COALESCE(ci.due_amount, 0)) AS due_sum, SUM(COALESCE(ci.payment_amount, 0)) AS paid_sum')
            ->groupBy('ci.contract_id');

        $rows = DB::table('contracts as c')
            ->leftJoinSub($installmentsAgg, 'agg', function ($join) {
                $join->on('agg.contract_id', '=', 'c.id');
            })
            ->select('c.id', 'c.contract_status_id', 'c.total_value')
            ->selectRaw('COALESCE(agg.due_sum, 0) AS due_sum')
            ->selectRaw('COALESCE(agg.paid_sum, 0) AS paid_sum')
            ->orderBy('c.id')
            ->cursor();

        $remainingByStatus = [];

        foreach ($rows as $row) {
            $dueAmount   = (float) $row->due_sum;
            $paidAmount  = (float) $row->paid_sum;
            $totalValue  = $row->total_value !== null ? (float) $row->total_value : 0.0;

            if ($dueAmount <= 0.0 && $totalValue > 0.0) {
                $dueAmount = $totalValue;
            }

            $remaining = max(0.0, round($dueAmount - $paidAmount, 2));
            $statusId  = $row->contract_status_id !== null ? (int) $row->contract_status_id : 0;

            $remainingByStatus[$statusId] = ($remainingByStatus[$statusId] ?? 0.0) + $remaining;
        }

        foreach ($remainingByStatus as $key => $value) {
            $remainingByStatus[$key] = round((float) $value, 2);
        }

        return $remainingByStatus;
    }

    private function buildStatusLabel(Collection $statusNames, array $ids): string
    {
        if (empty($ids)) {
            return '—';
        }

        return $statusNames
            ->only($ids)
            ->filter()
            ->values()
            ->map(fn ($name) => (string) $name)
            ->implode('، ');
    }
}
