<?php

namespace App\Services;

use App\Models\ProductType;
use App\Models\ProductTransaction;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;

class ProductAvailabilityService
{
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
        // الكميات "داخل" بعد العكس = قيود نوعها "خارج" (2) أو حالتها تابعة لنوع 2
        $qtyInByType = (clone $txBase)
            ->where(function ($q) {
                $q->where('le.transaction_type_id', 2)
                  ->orWhereIn('le.transaction_status_id', function ($sub) {
                      $sub->select('id')->from('transaction_statuses')->where('transaction_type_id', 2);
                  });
            })
            ->selectRaw('pt.product_type_id as pt_id, SUM(pt.quantity) as s')
            ->groupBy('pt.product_type_id')
            ->pluck('s', 'pt_id');

        // الكميات "خارج" بعد العكس = قيود نوعها "داخل" (1) أو حالتها تابعة لنوع 1
        $qtyOutByType = (clone $txBase)
            ->where(function ($q) {
                $q->where('le.transaction_type_id', 1)
                  ->orWhereIn('le.transaction_status_id', function ($sub) {
                      $sub->select('id')->from('transaction_statuses')->where('transaction_type_id', 1);
                  });
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
                    ->when(!empty($filters['from'] ?? null), fn($q) => $q->whereDate('entry_date', '>=', $filters['from']))
                    ->when(!empty($filters['to']   ?? null), fn($q) => $q->whereDate('entry_date', '<=', $filters['to']))
                    ->when(($filters['account_type'] ?? null) === 'bank', fn($q) => $q->whereNotNull('bank_account_id')->whereNull('safe_id'))
                    ->when(($filters['account_type'] ?? null) === 'safe', fn($q) => $q->whereNotNull('safe_id')->whereNull('bank_account_id'))
                    ->when(!empty($filters['bank_ids'] ?? null), fn($q) => $q->whereIn('bank_account_id', array_filter($filters['bank_ids'])))
                    ->when(!empty($filters['safe_ids'] ?? null), fn($q) => $q->whereIn('safe_id', array_filter($filters['safe_ids'])))
                    ->whereExists(function ($sub) use ($ptId) {
                        $sub->select(DB::raw(1))
                            ->from('product_transactions as pt2')
                            ->whereColumn('pt2.ledger_entry_id', 'ledger_entries.id')
                            ->where('pt2.product_type_id', $ptId);
                    });

                $amountIn = (clone $qLedgerBase)
                    ->where(function ($q) {
                        $q->where('transaction_type_id', 1)
                          ->orWhereIn('transaction_status_id', function ($sub) {
                              $sub->select('id')->from('transaction_statuses')->where('transaction_type_id', 1);
                          });
                    })
                    ->sum('amount');

                $amountOut = (clone $qLedgerBase)
                    ->where(function ($q) {
                        $q->where('transaction_type_id', 2)
                          ->orWhereIn('transaction_status_id', function ($sub) {
                              $sub->select('id')->from('transaction_statuses')->where('transaction_type_id', 2);
                          });
                    })
                    ->sum('amount');

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
}
