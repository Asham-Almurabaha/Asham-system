<?php

namespace App\Http\Controllers;

use App\Services\ProductTypeAvailabilityService;
use Illuminate\Http\Request;
use Modules\Lookups\Entities\ProductType;

class AjaxProductTypeController extends Controller
{
    public function __construct(private ProductTypeAvailabilityService $availabilityService)
    {
    }

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
            $filters = $request->only(['from', 'to', 'account_type', 'exclude_contract_id', 'exclude_contract_ids']);
            $data    = $this->availabilityService->compute($productType, $filters);

            return response()->json(array_merge([
                'success' => true,
            ], $data));
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Server Error',
            ], 500);
        }
    }
}
