<?php

namespace Modules\Investors\Http\Controllers\Concerns;

use App\Models\LedgerEntry;
use Illuminate\Support\Collection;

trait InvestorLiquiditySummaries
{
    /**
     * Summarize deposits, withdrawals, and net liquidity grouped by investor.
     *
     * @param  iterable<int>|null  $investorIds
     * @return array{perInvestor: \Illuminate\Support\Collection, totals: array{in: float, out: float, net: float}}
     */
    protected function summarizeInvestorLiquidity($investorIds = null): array
    {
        $query = LedgerEntry::query()
            ->whereNotNull('investor_id')
            ->where('is_office', false)
            ->groupBy('investor_id')
            ->selectRaw(<<<SQL
                investor_id,
                COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END), 0) AS total_in,
                COALESCE(SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END), 0) AS total_out
            SQL);

        if (!is_null($investorIds)) {
            $ids = Collection::wrap($investorIds)
                ->filter(fn ($value) => !is_null($value))
                ->map(fn ($value) => (int) $value)
                ->unique()
                ->values();

            if ($ids->isEmpty()) {
                return [
                    'perInvestor' => collect(),
                    'totals'      => [
                        'in'  => 0.0,
                        'out' => 0.0,
                        'net' => 0.0,
                    ],
                ];
            }

            $query->whereIn('investor_id', $ids);
        }

        $rows = $query->get();

        $perInvestor = $rows->mapWithKeys(function ($row) {
            $totalIn = round((float) ($row->total_in ?? 0), 2);
            $totalOut = round((float) ($row->total_out ?? 0), 2);

            return [
                (int) $row->investor_id => [
                    'in'  => $totalIn,
                    'out' => $totalOut,
                    'net' => round($totalIn - $totalOut, 2),
                ],
            ];
        });

        $totalsIn = $rows->sum(fn ($row) => (float) ($row->total_in ?? 0));
        $totalsOut = $rows->sum(fn ($row) => (float) ($row->total_out ?? 0));

        $totals = [
            'in'  => round($totalsIn, 2),
            'out' => round($totalsOut, 2),
        ];
        $totals['net'] = round($totals['in'] - $totals['out'], 2);

        return [
            'perInvestor' => $perInvestor,
            'totals'      => $totals,
        ];
    }
}

