<?php

namespace App\Services;

use Modules\Lookups\Entities\ProductType;
use Modules\Ledger\Entities\ProductTransaction;
use Modules\Ledger\Entities\LedgerEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductAvailabilityService
{
    private ?bool $hasRequiredTablesCache = null;

    /**
     * احسب المتاح من البضائع لكل نوع
     *
     * الفلاتر المتاحة:
     * - from, to: على le.entry_date
     * - account_type: 'bank' | 'safe'
     * - bank_ids: int[]
     * - safe_ids: int[]
     * - product_type_ids: int[] (اختياري لتقييد أنواع بعينها)
     * - compact | only_available: bool (لو true يرجّع المتاح فقط لكل نوع بدون تفاصيل)
     * - low_threshold: int (حدّ التنبيه على انخفاض المخزون – افتراضي 5)
     *
     * النتيجة (الوضع الافتراضي – مفصل):
     * [
     *   'items' => [
     *      [
     *        'product_type_id' => 1,
     *        'name'            => 'بطاقات',
     *        'stock' => [
     *           'in' => 10, 'out' => 3, 'available' => 7,
     *           'raw_available' => 7,
     *           'in_formatted' => '10', 'out_formatted' => '3', 'formatted' => '7'
     *        ],
     *        'available' => 7,
     *        'ledger' => [
     *           'in' => 1200.00, 'out' => 300.00, 'balance' => 900.00,
     *           'in_formatted' => '1,200.00', 'out_formatted' => '300.00', 'formatted' => '900.00'
     *        ],
     *        'is_low' => false, // تمت إضافتها
     *      ],
     *      ...
     *   ],
     *   'totals' => [
     *      'stock'  => [...],
     *      'ledger' => [...],
     *   ],
     * ]
     *
     * النتيجة (compact=true): ترجع متاح فقط + إجمالي المتاح
     * [
     *   'items' => [
     *      ['product_type_id'=>1,'name'=>'بطاقات','available'=>7,'formatted'=>'7','is_low'=>false],
     *      ...
     *   ],
     *   'totals' => ['available'=>123,'formatted'=>'123','low_threshold'=>5]
     * ]
     */
    public function build(array $filters = []): array
    {
        $compact = (bool)($filters['compact'] ?? $filters['only_available'] ?? false);
        $lowThreshold = (int)($filters['low_threshold'] ?? config('inventory.low_threshold', 5));

        if (!$this->hasRequiredTables()) {
            return $this->emptyResult($compact, $lowThreshold);
        }

        // 1) قائمة الأنواع المطلوبة
        $typeQuery = ProductType::query()
            ->when(!empty($filters['product_type_ids'] ?? null), function ($q) use ($filters) {
                $q->whereIn('id', array_filter($filters['product_type_ids']));
            })
            ->orderBy('name', 'asc');

        $types = $typeQuery->get(['id','name']);
        if ($types->isEmpty()) {
            return $compact
                ? ['items' => [], 'totals' => ['available' => 0, 'formatted' => '0', 'low_threshold' => $lowThreshold]]
                : ['items' => [], 'totals' => [
                    'stock'  => $this->fmtStockTotals(0,0,0),
                    'ledger' => $this->fmtMoneyTotals(0,0,0),
                ]];
        }

        // 2) Base لكميات ProductTransaction + join ledger_entries + فلاتر
        $statusIdsType1 = $this->transactionStatusIdsForType(1);
        $statusIdsType2 = $this->transactionStatusIdsForType(2);

        $txBase = ProductTransaction::query()
            ->from('product_transactions as pt')
            ->join('ledger_entries as le', 'le.id', '=', 'pt.ledger_entry_id')
            ->when(!empty($filters['from'] ?? null), fn($q) => $q->whereDate('le.entry_date', '>=', $filters['from']))
            ->when(!empty($filters['to']   ?? null), fn($q) => $q->whereDate('le.entry_date', '<=', $filters['to']))
            ->when(($filters['account_type'] ?? null) === 'bank', fn($q) => $q->whereNotNull('le.bank_account_id')->whereNull('le.safe_id'))
            ->when(($filters['account_type'] ?? null) === 'safe', fn($q) => $q->whereNotNull('le.safe_id')->whereNull('le.bank_account_id'))
            ->when(!empty($filters['bank_ids'] ?? null), fn($q) => $q->whereIn('le.bank_account_id', array_filter($filters['bank_ids'])))
            ->when(!empty($filters['safe_ids'] ?? null), fn($q) => $q->whereIn('le.safe_id', array_filter($filters['safe_ids'])))
            ->when(!empty($filters['product_type_ids'] ?? null), fn($q) => $q->whereIn('pt.product_type_id', array_filter($filters['product_type_ids'])));

        // ✅ نفس منطق Ajax: عكس الاتجاه للكميات
        // الكميات "داخل" بعد العكس = قيود اتجاهها "خارج" (أو fallback على النوع 2)
        $qtyInByType = (clone $txBase)
            ->where(function ($q) use ($statusIdsType2) {
                $this->applyLedgerDirectionFilter($q, ['out', 'OUT'], 2, $statusIdsType2);
            })
            ->selectRaw('pt.product_type_id as pt_id, SUM(pt.quantity) as s')
            ->groupBy('pt.product_type_id')
            ->pluck('s', 'pt_id');

        // الكميات "خارج" بعد العكس = قيود اتجاهها "داخل" (أو fallback على النوع 1)
        $qtyOutByType = (clone $txBase)
            ->where(function ($q) use ($statusIdsType1) {
                $this->applyLedgerDirectionFilter($q, ['in', 'IN'], 1, $statusIdsType1);
            })
            ->selectRaw('pt.product_type_id as pt_id, SUM(pt.quantity) as s')
            ->groupBy('pt.product_type_id')
            ->pluck('s', 'pt_id');

        $items = [];
        $totQtyIn = $totQtyOut = $totAvail = 0;
        $totAmtIn = $totAmtOut = $totBal   = 0.0;

        foreach ($types as $type) {
            $ptId  = (int) $type->id;
            $qin   = (int) round((float)($qtyInByType[$ptId]  ?? 0));
            $qout  = (int) round((float)($qtyOutByType[$ptId] ?? 0));
            $avail = $qin - $qout;
            $safeAvail = max(0, $avail);
            $isLow = ($safeAvail <= $lowThreshold);

            if ($compact) {
                // نمط مختصر: متاح فقط
                $items[] = [
                    'product_type_id' => $ptId,
                    'name'            => (string) $type->name,
                    'available'       => $safeAvail,
                    'formatted'       => number_format($safeAvail),
                    'is_low'          => $isLow,
                ];
            } else {
                // نمط مفصل: يشمل مخزون وتفاصيل فلوس
                $qLedgerBase = LedgerEntry::query()
                    ->from('ledger_entries as le')
                    ->withoutGlobalScope(SoftDeletingScope::class)
                    ->whereNull('le.deleted_at')
                    ->when(!empty($filters['from'] ?? null), fn($q) => $q->whereDate('entry_date', '>=', $filters['from']))
                    ->when(!empty($filters['to']   ?? null), fn($q) => $q->whereDate('entry_date', '<=', $filters['to']))
                    ->when(($filters['account_type'] ?? null) === 'bank', fn($q) => $q->whereNotNull('bank_account_id')->whereNull('safe_id'))
                    ->when(($filters['account_type'] ?? null) === 'safe', fn($q) => $q->whereNotNull('safe_id')->whereNull('bank_account_id'))
                    ->when(!empty($filters['bank_ids'] ?? null), fn($q) => $q->whereIn('bank_account_id', array_filter($filters['bank_ids'])))
                    ->when(!empty($filters['safe_ids'] ?? null), fn($q) => $q->whereIn('safe_id', array_filter($filters['safe_ids'])))
                    ->whereExists(function ($sub) use ($ptId) {
                        $sub->select(DB::raw(1))
                            ->from('product_transactions as pt2')
                            ->whereColumn('pt2.ledger_entry_id', 'le.id')
                            ->where('pt2.product_type_id', $ptId);
                    });

                $amountIn = (clone $qLedgerBase)
                    ->where(function ($q) use ($statusIdsType1) {
                        $this->applyLedgerDirectionFilter($q, ['in', 'IN'], 1, $statusIdsType1);
                    })
                    ->sum('le.amount');

                $amountOut = (clone $qLedgerBase)
                    ->where(function ($q) use ($statusIdsType2) {
                        $this->applyLedgerDirectionFilter($q, ['out', 'OUT'], 2, $statusIdsType2);
                    })
                    ->sum('le.amount');

                $amountIn  = round((float)$amountIn, 2);
                $amountOut = round((float)$amountOut, 2);
                $balance   = round($amountIn - $amountOut, 2);

                $items[] = [
                    'product_type_id' => $ptId,
                    'name'            => (string) $type->name,
                    'stock'           => [
                        'in'            => $qin,
                        'out'           => $qout,
                        'available'     => $safeAvail,
                        'raw_available' => $avail,
                        'in_formatted'  => number_format($qin),
                        'out_formatted' => number_format($qout),
                        'formatted'     => number_format($safeAvail),
                    ],
                    'available'       => $safeAvail,
                    'ledger'          => [
                        'in'            => $amountIn,
                        'out'           => $amountOut,
                        'balance'       => $balance,
                        'in_formatted'  => number_format($amountIn, 2),
                        'out_formatted' => number_format($amountOut, 2),
                        'formatted'     => number_format($balance, 2),
                    ],
                    'is_low'          => $isLow,
                ];

                // إجماليات للنمط المفصل
                $totAmtIn  += $amountIn;
                $totAmtOut += $amountOut;
                $totBal    += $balance;
            }

            // إجماليات مخزون
            $totQtyIn  += $qin;
            $totQtyOut += $qout;
            $totAvail  += $safeAvail;
        }

        // ترتيب بالاسم
        usort($items, fn($a,$b) => strnatcasecmp($a['name'], $b['name']));

        // مخرجات
        if ($compact) {
            return [
                'items'  => $items,
                'totals' => [
                    'available'     => $totAvail,
                    'formatted'     => number_format($totAvail),
                    'low_threshold' => $lowThreshold,
                ],
            ];
        }

        return [
            'items'  => $items,
            'totals' => [
                'stock'  => $this->fmtStockTotals($totQtyIn, $totQtyOut, $totAvail),
                'ledger' => $this->fmtMoneyTotals($totAmtIn, $totAmtOut, $totBal),
            ],
        ];
    }

    private function hasRequiredTables(): bool
    {
        if ($this->hasRequiredTablesCache !== null) {
            return $this->hasRequiredTablesCache;
        }

        foreach (['product_types', 'product_transactions', 'ledger_entries', 'transaction_statuses'] as $table) {
            if (!Schema::hasTable($table)) {
                return $this->hasRequiredTablesCache = false;
            }
        }

        return $this->hasRequiredTablesCache = true;
    }

    private function emptyResult(bool $compact, int $lowThreshold): array
    {
        if ($compact) {
            return [
                'items'  => [],
                'totals' => [
                    'available'     => 0,
                    'formatted'     => '0',
                    'low_threshold' => $lowThreshold,
                ],
            ];
        }

        return [
            'items'  => [],
            'totals' => [
                'stock'  => $this->fmtStockTotals(0, 0, 0),
                'ledger' => $this->fmtMoneyTotals(0.0, 0.0, 0.0),
            ],
        ];
    }

    private function fmtStockTotals(int $in, int $out, int $avail): array
    {
        return [
            'in'            => $in,
            'out'           => $out,
            'available'     => $avail,
            'in_formatted'  => number_format($in),
            'out_formatted' => number_format($out),
            'formatted'     => number_format($avail),
        ];
    }

    private function fmtMoneyTotals(float $in, float $out, float $bal): array
    {
        $in  = round($in, 2);
        $out = round($out, 2);
        $bal = round($bal, 2);
        return [
            'in'            => $in,
            'out'           => $out,
            'balance'       => $bal,
            'in_formatted'  => number_format($in, 2),
            'out_formatted' => number_format($out, 2),
            'formatted'     => number_format($bal, 2),
        ];
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
