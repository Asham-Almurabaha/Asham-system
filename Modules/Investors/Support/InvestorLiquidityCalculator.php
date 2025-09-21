<?php

namespace Modules\Investors\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvestorLiquidityCalculator
{
    /**
     * Cache for transaction type buckets keyed by direction.
     *
     * @var array{in: array<int>, out: array<int>}|null
     */
    protected static ?array $typeBuckets = null;

    /**
     * Get transaction type IDs grouped by logical direction (deposit / withdraw).
     *
     * @return array{in: array<int>, out: array<int>}
     */
    public static function transactionTypeBuckets(): array
    {
        if (self::$typeBuckets !== null) {
            return self::$typeBuckets;
        }

        $types = DB::table('transaction_types')
            ->select(['id', 'name'])
            ->get();

        $depositKeywords = ['إيداع', 'ايداع', 'deposit', 'deposits', 'cash in', 'cash-in'];
        $withdrawKeywords = ['سحب', 'withdraw', 'withdrawal', 'withdrawals', 'cash out', 'cash-out'];

        $in  = [];
        $out = [];

        foreach ($types as $type) {
            $name = self::normalizeName((string) ($type->name ?? ''));

            if ($name === '') {
                continue;
            }

            if (self::containsKeyword($name, $depositKeywords)) {
                $in[] = (int) $type->id;
                continue;
            }

            if (self::containsKeyword($name, $withdrawKeywords)) {
                $out[] = (int) $type->id;
            }
        }

        return self::$typeBuckets = [
            'in'  => array_values(array_unique($in)),
            'out' => array_values(array_unique($out)),
        ];
    }

    /**
     * Aggregate investor liquidity totals grouped by investor.
     *
     * @param  callable(Builder):void|null  $scope  Optional callback to further constrain the base query.
     * @param  iterable<int>|null           $investorIds  Optional investor IDs to limit the aggregation.
     * @return Collection<int, array{in: float, out: float, net: float}>
     */
    public static function aggregateTotals(?callable $scope = null, $investorIds = null): Collection
    {
        $buckets = self::transactionTypeBuckets();
        $inTypeIds = $buckets['in'];
        $outTypeIds = $buckets['out'];

        $query = DB::table('investor_transactions as it')
            ->join('transaction_statuses as ts', 'ts.id', '=', 'it.status_id')
            ->select('it.investor_id')
            ->groupBy('it.investor_id');

        if (!is_null($investorIds)) {
            $ids = Collection::wrap($investorIds)
                ->filter(fn ($value) => !is_null($value))
                ->map(fn ($value) => (int) $value)
                ->unique()
                ->values();

            if ($ids->isEmpty()) {
                return collect();
            }

            $query->whereIn('it.investor_id', $ids);
        }

        if ($scope) {
            $scope($query);
        }

        if (!empty($inTypeIds)) {
            $placeholders = implode(',', array_fill(0, count($inTypeIds), '?'));
            $query->selectRaw(
                "SUM(CASE WHEN ts.transaction_type_id IN ($placeholders) THEN it.amount ELSE 0 END) AS total_in",
                $inTypeIds
            );
        } else {
            $query->selectRaw('0 AS total_in');
        }

        if (!empty($outTypeIds)) {
            $placeholders = implode(',', array_fill(0, count($outTypeIds), '?'));
            $query->selectRaw(
                "SUM(CASE WHEN ts.transaction_type_id IN ($placeholders) THEN it.amount ELSE 0 END) AS total_out",
                $outTypeIds
            );
        } else {
            $query->selectRaw('0 AS total_out');
        }

        return collect($query->get())->mapWithKeys(function ($row) {
            $totalIn = round((float) ($row->total_in ?? 0), 2);
            $totalOut = round((float) ($row->total_out ?? 0), 2);
            $investorId = (int) ($row->investor_id ?? 0);

            return [
                $investorId => [
                    'in'  => $totalIn,
                    'out' => $totalOut,
                    'net' => round($totalIn - $totalOut, 2),
                ],
            ];
        });
    }

    /**
     * Summarize liquidity for a single investor.
     *
     * @param  int                             $investorId
     * @param  callable(Builder):void|null     $scope
     * @return array{in: float, out: float, net: float}
     */
    public static function summarizeForInvestor(int $investorId, ?callable $scope = null): array
    {
        $rows = self::aggregateTotals($scope, [$investorId]);

        return $rows->get($investorId, [
            'in'  => 0.0,
            'out' => 0.0,
            'net' => 0.0,
        ]);
    }

    protected static function normalizeName(string $value): string
    {
        $value = trim($value);

        return $value === '' ? '' : mb_strtolower($value, 'UTF-8');
    }

    /**
     * Check whether the provided text contains any of the supplied keywords (case-insensitive).
     */
    protected static function containsKeyword(string $haystack, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            $keyword = self::normalizeName((string) $keyword);
            if ($keyword === '') {
                continue;
            }

            if (str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
