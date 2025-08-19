<?php

namespace App\Http\Controllers;

use App\Models\ProductType;
use App\Models\ProductTransaction;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AjaxProductTypeController extends Controller
{
    /**
     * GET /product-types/{productType}/available
     *
     * يرجّع:
     * - stock.in / stock.out: تجميع الكميات من product_transactions
     *   مع عكس اتجاه الحركة حسب نوع/حالة القيد في ledger_entries:
     *     لو القيد "داخل" => تُحتسب الكمية كـ "خارج"
     *     لو القيد "خارج" => تُحتسب الكمية كـ "داخل"
     * - stock.available: الصافي (in - out)
     * - available (top-level): نفس قيمة stock.available
     * - ledger (اختياري): إجمالي الداخل/الخارج/الرصيد بالفلوس من ledger_entries (بدون عكس)
     *
     * فلاتر اختيارية بالكويري:
     * - from, to  (على le.entry_date)
     * - account_type = bank|safe  (على le.bank_account_id / le.safe_id)
     */
    public function available(ProductType $productType, Request $request)
    {
        try {
            /*
            |-----------------------------------------------
            | 1) كميات المخزون من product_transactions
            |    + عكس اتجاه الحركة بناءً على القيد
            |-----------------------------------------------
            */
            $txBase = ProductTransaction::query()
                ->join('ledger_entries as le', 'le.id', '=', 'product_transactions.ledger_entry_id')
                ->where('product_transactions.product_type_id', $productType->id)
                // فلاتر التاريخ على تاريخ القيد
                ->when($request->filled('from'), fn($q) => $q->whereDate('le.entry_date', '>=', $request->from))
                ->when($request->filled('to'),   fn($q) => $q->whereDate('le.entry_date', '<=', $request->to))
                // نوع الحساب (خزنة/بنك)
                ->when($request->filled('account_type'), function ($q) use ($request) {
                    if ($request->account_type === 'bank') {
                        $q->whereNotNull('le.bank_account_id')->whereNull('le.safe_id');
                    } elseif ($request->account_type === 'safe') {
                        $q->whereNotNull('le.safe_id')->whereNull('le.bank_account_id');
                    }
                });

            // ✅ عكس الاتجاه:
            //   - الكميات "داخل" فعلياً (le.transaction_type_id = 1) تُحسب هنا كـ "خارج"
            //   - الكميات "خارج" فعلياً (le.transaction_type_id = 2) تُحسب هنا كـ "داخل"

            // الكميات "داخل" بعد العكس = القيود التي نوعها "خارج" (2) أو حالتها تتبع نوع 2
            $qtyIn = (clone $txBase)
                ->where(function ($q) {
                    $q->where('le.transaction_type_id', 2)
                      ->orWhereIn('le.transaction_status_id', function ($sub) {
                          $sub->select('id')
                              ->from('transaction_statuses')
                              ->where('transaction_type_id', 2);
                      });
                })
                ->sum('product_transactions.quantity');

            // الكميات "خارج" بعد العكس = القيود التي نوعها "داخل" (1) أو حالتها تتبع نوع 1
            $qtyOut = (clone $txBase)
                ->where(function ($q) {
                    $q->where('le.transaction_type_id', 1)
                      ->orWhereIn('le.transaction_status_id', function ($sub) {
                          $sub->select('id')
                              ->from('transaction_statuses')
                              ->where('transaction_type_id', 1);
                      });
                })
                ->sum('product_transactions.quantity');

            $qtyIn  = (int) round($qtyIn);
            $qtyOut = (int) round($qtyOut);
            $availableQty = $qtyIn - $qtyOut;

            /*
            |-----------------------------------------------
            | 2) (اختياري) فلوس القيد من ledger_entries (بدون عكس)
            |    ونقيّدها بالقيود التي لها سطور pt لنفس النوع
            |-----------------------------------------------
            */
            $ledgerBase = LedgerEntry::query()
                ->whereExists(function ($sub) use ($productType) {
                    $sub->select(DB::raw(1))
                        ->from('product_transactions as pt')
                        ->whereColumn('pt.ledger_entry_id', 'ledger_entries.id')
                        ->where('pt.product_type_id', $productType->id);
                })
                ->when($request->filled('from'), fn($q) => $q->whereDate('entry_date', '>=', $request->from))
                ->when($request->filled('to'),   fn($q) => $q->whereDate('entry_date', '<=', $request->to))
                ->when($request->filled('account_type'), function ($q) use ($request) {
                    if ($request->account_type === 'bank') {
                        $q->whereNotNull('bank_account_id')->whereNull('safe_id');
                    } elseif ($request->account_type === 'safe') {
                        $q->whereNotNull('safe_id')->whereNull('bank_account_id');
                    }
                });

            $amountIn = (clone $ledgerBase)
                ->where(function ($q) {
                    $q->where('transaction_type_id', 1)
                      ->orWhereIn('transaction_status_id', function ($sub) {
                          $sub->select('id')
                              ->from('transaction_statuses')
                              ->where('transaction_type_id', 1);
                      });
                })
                ->sum('amount');

            $amountOut = (clone $ledgerBase)
                ->where(function ($q) {
                    $q->where('transaction_type_id', 2)
                      ->orWhereIn('transaction_status_id', function ($sub) {
                          $sub->select('id')
                              ->from('transaction_statuses')
                              ->where('transaction_type_id', 2);
                      });
                })
                ->sum('amount');

            $amountIn  = round((float) $amountIn, 2);
            $amountOut = round((float) $amountOut, 2);
            $balance   = round($amountIn - $amountOut, 2);

            return response()->json([
                'success'   => true,

                // الكميات (بعد العكس)
                'stock'     => [
                    'in'             => $qtyIn,
                    'out'            => $qtyOut,
                    'available'      => max(0, $availableQty),
                    'raw_available'  => $availableQty,
                    'in_formatted'   => number_format($qtyIn),
                    'out_formatted'  => number_format($qtyOut),
                    'formatted'      => number_format(max(0, $availableQty)),
                ],

                // للتوافق مع الواجهة
                'available' => max(0, $availableQty),

                // فلوس القيد (بدون عكس)
                'ledger'    => [
                    'in'            => $amountIn,
                    'out'           => $amountOut,
                    'balance'       => $balance,
                    'in_formatted'  => number_format($amountIn, 2),
                    'out_formatted' => number_format($amountOut, 2),
                    'formatted'     => number_format($balance, 2),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Server Error',
            ], 500);
        }
    }
}
