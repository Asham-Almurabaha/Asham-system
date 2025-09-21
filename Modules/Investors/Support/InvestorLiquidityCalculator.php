<?php

namespace Modules\Investors\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Contracts\Support\TransactionDirection;

class InvestorLiquidityCalculator
{
    /**
     * Arabic/English keywords used as a fallback when direction inference fails.
     */
    protected const DEPOSIT_KEYWORDS = [
        'إيداع',
        'ايداع',
        'توريد',
        'تحصيل',
        'deposit',
        'deposits',
        'cash in',
        'cash-in',
    ];

    protected const WITHDRAW_KEYWORDS = [
        'سحب',
        'سحوبات',
        'صرف',
        'توزيع',
        'استرداد',
        'withdraw',
        'withdrawal',
        'withdrawals',
        'cash out',
        'cash-out',
    ];

    /**
     * Cache for transaction type buckets keyed by direction.
     *
     * @var array{in: array<int>, out: array<int>}|null
     */
    protected static ?array $typeBuckets = null;

    /**
     * Cache for transaction status buckets keyed by direction.
     *
     * @var array{in: array<int>, out: array<int>}|null
     */
    protected static ?array $statusBuckets = null;

    /**
     * Cached direction lookup for transaction types.
     *
     * @var array<int, 'in'|'out'>|null
     */
    protected static ?array $typeDirections = null;

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

        $directionByType = [];
        $in  = [];
        $out = [];

        foreach ($types as $type) {
            $typeId = (int) ($type->id ?? 0);
            if ($typeId <= 0) {
                continue;
            }

            $direction = self::resolveDirection($type->name ?? null);
            if ($direction === null) {
                continue;
            }

            $directionByType[$typeId] = $direction;

            if ($direction === 'in') {
                $in[] = $typeId;
            } elseif ($direction === 'out') {
                $out[] = $typeId;
            }
        }

        self::$typeDirections = $directionByType;

        return self::$typeBuckets = [
            'in'  => array_values(array_unique($in)),
            'out' => array_values(array_unique($out)),
        ];
    }

    /**
     * Get transaction status IDs grouped by logical direction (deposit / withdraw).
     *
     * @return array{in: array<int>, out: array<int>}
     */
    public static function transactionStatusBuckets(): array
    {
        if (self::$statusBuckets !== null) {
            return self::$statusBuckets;
        }

        $typeBuckets = self::transactionTypeBuckets();
        $directionByType = self::$typeDirections ?? [];

        foreach ($typeBuckets['in'] as $typeId) {
            $directionByType[$typeId] = 'in';
        }

        foreach ($typeBuckets['out'] as $typeId) {
            $directionByType[$typeId] = 'out';
        }

        $statuses = DB::table('transaction_statuses')
            ->select(['id', 'name', 'transaction_type_id'])
            ->get();

        $in  = [];
        $out = [];

        foreach ($statuses as $status) {
            $statusId = (int) ($status->id ?? 0);
            if ($statusId <= 0) {
                continue;
            }

            $typeId = (int) ($status->transaction_type_id ?? 0);
            $direction = $directionByType[$typeId] ?? null;

            if ($direction === null) {
                $direction = self::resolveDirection($status->name ?? null);
            }

            if ($direction === null) {
                $normalized = self::normalizeName((string) ($status->name ?? ''));

                if ($normalized !== '') {
                    if (self::containsKeyword($normalized, self::DEPOSIT_KEYWORDS)) {
                        $direction = 'in';
                    } elseif (self::containsKeyword($normalized, self::WITHDRAW_KEYWORDS)) {
                        $direction = 'out';
                    }
                }
            }

            if ($direction === 'in') {
                $in[] = $statusId;
            } elseif ($direction === 'out') {
                $out[] = $statusId;
            }
        }

        return self::$statusBuckets = [
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
        $typeBuckets = self::transactionTypeBuckets();
        $statusBuckets = self::transactionStatusBuckets();

        $inTypeIds = $typeBuckets['in'];
        $outTypeIds = $typeBuckets['out'];
        $inStatusIds = $statusBuckets['in'];
        $outStatusIds = $statusBuckets['out'];

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

        $inCondition = self::buildCaseCondition($inStatusIds, $inTypeIds);
        if ($inCondition) {
            [$conditionSql, $bindings] = $inCondition;
            $query->selectRaw(
                "SUM(CASE WHEN $conditionSql THEN it.amount ELSE 0 END) AS total_in",
                $bindings
            );
        } else {
            $query->selectRaw('0 AS total_in');
        }

        $outCondition = self::buildCaseCondition($outStatusIds, $outTypeIds);
        if ($outCondition) {
            [$conditionSql, $bindings] = $outCondition;
            $query->selectRaw(
                "SUM(CASE WHEN $conditionSql THEN it.amount ELSE 0 END) AS total_out",
                $bindings
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

    /**
     * Clear cached lookups for transaction directions.
     */
    public static function clearCachedBuckets(): void
    {
        self::$typeBuckets = null;
        self::$statusBuckets = null;
        self::$typeDirections = null;
    }

    protected static function normalizeName(string $value): string
    {
        $normalized = TransactionDirection::arNormalize($value);

        return $normalized === '' ? '' : $normalized;
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

    protected static function resolveDirection(?string $name): ?string
    {
        $direction = TransactionDirection::directionFromTypeName($name);

        if ($direction !== null) {
            return $direction;
        }

        $normalized = self::normalizeName((string) ($name ?? ''));

        if ($normalized === '') {
            return null;
        }

        if (self::containsKeyword($normalized, self::DEPOSIT_KEYWORDS)) {
            return 'in';
        }

        if (self::containsKeyword($normalized, self::WITHDRAW_KEYWORDS)) {
            return 'out';
        }

        return null;
    }

    /**
     * Build a CASE condition for aggregating amounts by direction.
     *
     * @param  array<int>  $statusIds
     * @param  array<int>  $typeIds
     * @return array{0: string, 1: array<int>}|null
     */
    protected static function buildCaseCondition(array $statusIds, array $typeIds): ?array
    {
        $fragments = [];
        $bindings = [];

        if (!empty($statusIds)) {
            $placeholders = implode(',', array_fill(0, count($statusIds), '?'));
            $fragments[] = "ts.id IN ($placeholders)";
            $bindings = array_merge($bindings, $statusIds);
        }

        if (!empty($typeIds)) {
            $placeholders = implode(',', array_fill(0, count($typeIds), '?'));
            $fragments[] = "ts.transaction_type_id IN ($placeholders)";
            $bindings = array_merge($bindings, $typeIds);
        }

        if (empty($fragments)) {
            return null;
        }

        return [implode(' OR ', $fragments), $bindings];
    }
}
