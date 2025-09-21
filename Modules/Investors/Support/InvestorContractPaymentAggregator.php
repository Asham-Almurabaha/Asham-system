<?php

namespace Modules\Investors\Support;

use Illuminate\Support\Collection;
use Modules\Investors\Entities\InvestorTransaction;
use Modules\Lookups\Entities\TransactionStatus;

/**
 * Helper for aggregating paid amounts (installments / claims) for investors per contract.
 */
class InvestorContractPaymentAggregator
{
    /**
     * Cached lookup for transaction status buckets keyed by logical group.
     *
     * @var array{installment: array<int>, claim: array<int>}|null
     */
    protected static ?array $statusBuckets = null;

    /**
     * Aggregate paid amounts for a single investor grouped by contract.
     *
     * @param  int                $investorId
     * @param  iterable<int>|null $contractIds
     * @return Collection<int, array{installments: float, claims: float, total: float}>
     */
    public static function sumForInvestor(int $investorId, iterable $contractIds = []): Collection
    {
        if ($investorId <= 0) {
            return collect();
        }

        $result = self::sumForInvestors([$investorId], $contractIds);

        $contracts = $result->get($investorId);

        return $contracts instanceof Collection ? $contracts : collect();
    }

    /**
     * Aggregate paid amounts for multiple investors grouped by contract.
     *
     * @param  iterable<int> $investorIds
     * @param  iterable<int> $contractIds
     * @return Collection<int, Collection<int, array{installments: float, claims: float, total: float}>>
     */
    public static function sumForInvestors(iterable $investorIds, iterable $contractIds): Collection
    {
        $investorIds = Collection::wrap($investorIds)
            ->filter(static fn ($value) => !is_null($value))
            ->map(static fn ($value) => (int) $value)
            ->filter(static fn ($value) => $value > 0)
            ->unique()
            ->values();

        $contractIds = Collection::wrap($contractIds)
            ->filter(static fn ($value) => !is_null($value))
            ->map(static fn ($value) => (int) $value)
            ->filter(static fn ($value) => $value > 0)
            ->unique()
            ->values();

        if ($investorIds->isEmpty() || $contractIds->isEmpty()) {
            return collect();
        }

        $statusBuckets = self::transactionStatusBuckets();
        $installmentStatusIds = $statusBuckets['installment'] ?? [];
        $claimStatusIds = $statusBuckets['claim'] ?? [];

        $installmentRows = collect();
        if (!empty($installmentStatusIds)) {
            $installmentRows = InvestorTransaction::query()
                ->from('investor_transactions as it')
                ->whereIn('it.investor_id', $investorIds)
                ->whereIn('it.contract_id', $contractIds)
                ->whereIn('it.status_id', $installmentStatusIds)
                ->groupBy('it.investor_id', 'it.contract_id')
                ->selectRaw('it.investor_id as investor_id, it.contract_id as contract_id, SUM(it.amount) as amount')
                ->get();
        }

        $claimRows = collect();
        if (!empty($claimStatusIds)) {
            $claimRows = InvestorTransaction::query()
                ->from('investor_transactions as it')
                ->whereIn('it.investor_id', $investorIds)
                ->whereIn('it.contract_id', $contractIds)
                ->whereIn('it.status_id', $claimStatusIds)
                ->groupBy('it.investor_id', 'it.contract_id')
                ->selectRaw('it.investor_id as investor_id, it.contract_id as contract_id, SUM(it.amount) as amount')
                ->get();
        }

        $aggregated = [];

        foreach ($installmentRows as $row) {
            $investorId = (int) ($row->investor_id ?? 0);
            $contractId = (int) ($row->contract_id ?? 0);
            $amount = (float) ($row->amount ?? 0.0);

            if ($investorId <= 0 || $contractId <= 0) {
                continue;
            }

            $aggregated[$investorId][$contractId]['installments'] = round($amount, 2);
        }

        foreach ($claimRows as $row) {
            $investorId = (int) ($row->investor_id ?? 0);
            $contractId = (int) ($row->contract_id ?? 0);
            $amount = (float) ($row->amount ?? 0.0);

            if ($investorId <= 0 || $contractId <= 0) {
                continue;
            }

            $aggregated[$investorId][$contractId]['claims'] = round($amount, 2);
        }

        return collect($aggregated)->map(function (array $contracts) {
            return collect($contracts)->map(function (array $amounts) {
                $installments = (float) ($amounts['installments'] ?? 0.0);
                $claims = (float) ($amounts['claims'] ?? 0.0);

                return [
                    'installments' => round($installments, 2),
                    'claims'       => round($claims, 2),
                    'total'        => round($installments + $claims, 2),
                ];
            });
        });
    }

    /**
     * Get transaction status IDs grouped by logical payment bucket.
     *
     * @return array{installment: array<int>, claim: array<int>}
     */
    public static function transactionStatusBuckets(): array
    {
        if (self::$statusBuckets !== null) {
            return self::$statusBuckets;
        }

        $normalize = static fn ($value) => mb_strtolower(trim((string) $value), 'UTF-8');

        $installmentKeys = array_unique(array_map($normalize, self::installmentStatusNames()));
        $claimKeys = array_unique(array_map($normalize, self::claimStatusNames()));

        if (empty($installmentKeys) && empty($claimKeys)) {
            return self::$statusBuckets = ['installment' => [], 'claim' => []];
        }

        $statuses = TransactionStatus::query()->select(['id', 'name'])->get();

        $installmentIds = [];
        $claimIds = [];

        foreach ($statuses as $status) {
            $key = $normalize($status->name ?? '');

            if (in_array($key, $installmentKeys, true)) {
                $installmentIds[] = (int) $status->id;
            }

            if (in_array($key, $claimKeys, true)) {
                $claimIds[] = (int) $status->id;
            }
        }

        return self::$statusBuckets = [
            'installment' => array_values(array_unique($installmentIds)),
            'claim'       => array_values(array_unique($claimIds)),
        ];
    }

    /**
     * Reset cached buckets - useful for testing.
     */
    public static function clearCachedBuckets(): void
    {
        self::$statusBuckets = null;
    }

    /**
     * Transaction status names that represent installment payments.
     *
     * @return array<int, string>
     */
    protected static function installmentStatusNames(): array
    {
        return [
            'سداد قسط',
            'تحصيل قسط',
            'تحصيل',
            'installment payment',
            'installment',
            'installment settlement',
        ];
    }

    /**
     * Transaction status names that represent claim payments.
     *
     * @return array<int, string>
     */
    protected static function claimStatusNames(): array
    {
        return [
            'سداد مطالبة',
            'سداد مطالبه',
            'سداد مطالبة للمستثمرين',
            'سداد مطالبه للمستثمرين',
            'claim payment',
            'claim settlement',
            'claim investor payment',
        ];
    }
}

