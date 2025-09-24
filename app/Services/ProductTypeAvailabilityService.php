<?php

namespace App\Services;

use Modules\Ledger\Entities\LedgerEntry;
use Modules\Ledger\Entities\ProductTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Modules\Lookups\Entities\ProductType;

class ProductTypeAvailabilityService
{
    /**
     * Compute availability details for a given product type.
     *
     * @param  ProductType  $productType
     * @param  array<string, mixed>  $filters
     * @return array{stock: array<string, mixed>, available: float|int, ledger: array<string, mixed>}
     */
    public function compute(ProductType $productType, array $filters = []): array
    {
        $from        = $filters['from'] ?? null;
        $to          = $filters['to'] ?? null;
        $accountType = $filters['account_type'] ?? null;
        $exclude     = $this->normalizeExclusions($filters['exclude_contract_id'] ?? $filters['exclude_contract_ids'] ?? []);

        $stockIncomingStatusIds = $this->transactionStatusIdsForType(2);
        $stockOutgoingStatusIds = $this->transactionStatusIdsForType(1);

        $txBase = ProductTransaction::query()
            ->from('product_transactions as pt')
            ->join('ledger_entries as le', 'le.id', '=', 'pt.ledger_entry_id')
            ->where('pt.product_type_id', $productType->id)
            ->when($from, fn($q) => $q->whereDate('le.entry_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('le.entry_date', '<=', $to))
            ->when($accountType, function ($q) use ($accountType) {
                if ($accountType === 'bank') {
                    $q->whereNotNull('le.bank_account_id')->whereNull('le.safe_id');
                } elseif ($accountType === 'safe') {
                    $q->whereNotNull('le.safe_id')->whereNull('le.bank_account_id');
                }
            })
            ->when($exclude, function ($q) use ($exclude) {
                $q->where(function ($q) use ($exclude) {
                    $q->whereNull('le.contract_id')->orWhereNotIn('le.contract_id', $exclude);
                });
            });

        $qtyIn = (clone $txBase)
            ->where(function ($q) use ($stockIncomingStatusIds) {
                $this->applyLedgerDirectionFilter($q, ['out', 'OUT'], 2, $stockIncomingStatusIds);
            })
            ->sum('pt.quantity');

        $qtyOut = (clone $txBase)
            ->where(function ($q) use ($stockOutgoingStatusIds) {
                $this->applyLedgerDirectionFilter($q, ['in', 'IN'], 1, $stockOutgoingStatusIds);
            })
            ->sum('pt.quantity');

        $qtyIn        = (int) round($qtyIn);
        $qtyOut       = (int) round($qtyOut);
        $availableQty = $qtyIn - $qtyOut;

        $ledgerBase = LedgerEntry::query()
            ->from('ledger_entries as le')
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->whereNull('le.deleted_at')
            ->whereExists(function ($sub) use ($productType) {
                $sub->select(DB::raw(1))
                    ->from('product_transactions as pt')
                    ->whereColumn('pt.ledger_entry_id', 'le.id')
                    ->where('pt.product_type_id', $productType->id);
            })
            ->when($from, fn($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('entry_date', '<=', $to))
            ->when($accountType, function ($q) use ($accountType) {
                if ($accountType === 'bank') {
                    $q->whereNotNull('bank_account_id')->whereNull('safe_id');
                } elseif ($accountType === 'safe') {
                    $q->whereNotNull('safe_id')->whereNull('bank_account_id');
                }
            })
            ->when($exclude, function ($q) use ($exclude) {
                $q->where(function ($q) use ($exclude) {
                    $q->whereNull('contract_id')->orWhereNotIn('contract_id', $exclude);
                });
            });

        $amountIn = (clone $ledgerBase)
            ->where(function ($q) use ($stockOutgoingStatusIds) {
                $this->applyLedgerDirectionFilter($q, ['in', 'IN'], 1, $stockOutgoingStatusIds);
            })
            ->sum('le.amount');

        $amountOut = (clone $ledgerBase)
            ->where(function ($q) use ($stockIncomingStatusIds) {
                $this->applyLedgerDirectionFilter($q, ['out', 'OUT'], 2, $stockIncomingStatusIds);
            })
            ->sum('le.amount');

        $amountIn  = round((float) $amountIn, 2);
        $amountOut = round((float) $amountOut, 2);
        $balance   = round($amountIn - $amountOut, 2);

        return [
            'stock' => [
                'in'             => $qtyIn,
                'out'            => $qtyOut,
                'available'      => max(0, $availableQty),
                'raw_available'  => $availableQty,
                'in_formatted'   => number_format($qtyIn),
                'out_formatted'  => number_format($qtyOut),
                'formatted'      => number_format(max(0, $availableQty)),
            ],
            'available' => max(0, $availableQty),
            'ledger' => [
                'in'            => $amountIn,
                'out'           => $amountOut,
                'balance'       => $balance,
                'in_formatted'  => number_format($amountIn, 2),
                'out_formatted' => number_format($amountOut, 2),
                'formatted'     => number_format($balance, 2),
            ],
        ];
    }

    /**
     * @param  mixed  $value
     * @return array<int, int>
     */
    private function normalizeExclusions($value): array
    {
        $ids = is_array($value) ? $value : [$value];

        return array_values(array_unique(array_filter(
            array_map(function ($id) {
                if (is_numeric($id)) {
                    $id = (int) $id;
                    return $id > 0 ? $id : null;
                }

                return null;
            }, $ids)
        )));
    }

    /**
     * @param  Builder  $query
     * @param  array<int, string>  $directions
     * @param  int  $transactionTypeId
     * @param  array<int, int>  $statusIds
     */
    private function applyLedgerDirectionFilter(Builder $query, array $directions, int $transactionTypeId, array $statusIds = []): void
    {
        $normalizedDirections = [];

        foreach ($directions as $dir) {
            if (!is_string($dir)) {
                continue;
            }

            $dir = trim($dir);

            if ($dir === '') {
                continue;
            }

            $lower = strtolower($dir);
            $normalizedDirections[] = $lower;
            $normalizedDirections[] = strtoupper($lower);
            $normalizedDirections[] = ucfirst($lower);
        }

        $normalizedDirections = array_values(array_unique($normalizedDirections));

        $query->where(function ($q) use ($normalizedDirections, $transactionTypeId, $statusIds) {
            if (!empty($normalizedDirections)) {
                $q->whereIn('le.direction', $normalizedDirections);
            }

            $q->orWhere(function ($q) use ($transactionTypeId, $statusIds) {
                $q->whereNull('le.direction')
                    ->where(function ($q) use ($transactionTypeId, $statusIds) {
                        $q->where('le.transaction_type_id', $transactionTypeId);

                        if (!empty($statusIds)) {
                            $q->orWhereIn('le.transaction_status_id', $statusIds);
                        }
                    });
            });
        });
    }
    /**
     * @return array<int, int>
     */
    private function transactionStatusIdsForType(int $transactionTypeId): array
    {
        return DB::table('transaction_statuses')
            ->where('transaction_type_id', $transactionTypeId)
            ->pluck('id')
            ->map(static fn($id) => (int) $id)
            ->all();
    }
}
