<?php

namespace Modules\Investors\Http\Controllers\Concerns;

use Modules\Investors\Support\InvestorLiquidityCalculator;

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
        $rows = InvestorLiquidityCalculator::aggregateTotals(null, $investorIds);

        $perInvestor = $rows;

        $totalsIn = $perInvestor->sum(fn ($row) => (float) ($row['in'] ?? 0));
        $totalsOut = $perInvestor->sum(fn ($row) => (float) ($row['out'] ?? 0));

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

