<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DashboardDataService;
use App\Services\ProductAvailabilityService;
use App\Models\ProductType;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        DashboardDataService $dashboard,
        ProductAvailabilityService $inventory
    ) {
        // فلاتر موحّدة (تُستخدم للأجزاء اللي بتحتاج التاريخ فقط)
        $filters = [
            'from'             => $request->input('from'),
            'to'               => $request->input('to'),
            'account_type'     => $request->input('account_type'),
            'bank_ids'         => (array) $request->input('bank_ids', []),
            'safe_ids'         => (array) $request->input('safe_ids', []),
            'status_id'        => $request->input('status_id'),
            'status_ids'       => (array) $request->input('status_ids', []),
            'types'            => (array) $request->input('types', []),
            'keywords'         => (array) $request->input('keywords', []),
            'product_type_ids' => (array) $request->input('product_type_ids', []),
        ];

        // بيانات الداشبورد الأساسية
        $vm = $dashboard->build($filters);

        /* =========================
         * المتاح من البطاقات — غير متأثر بالتاريخ
         * ========================= */
        $cardIds = array_filter((array) config('inventory.card_type_ids', []));

        if (empty($cardIds)) {
            $keywords = (array) config('inventory.card_keywords', ['بطاق','كرت','card','cards']);
            $kw = array_values(array_filter(array_map('trim', $keywords)));

            $q = ProductType::query();
            if (!empty($filters['product_type_ids'])) {
                $q->whereIn('id', $filters['product_type_ids']);
            }
            $q->where(function ($w) use ($kw) {
                foreach ($kw as $k) {
                    $w->orWhere('name', 'like', "%{$k}%");
                }
            });

            $found = $q->pluck('id')->all();
            if (!empty($found)) {
                $cardIds = array_values(array_unique(array_merge($cardIds, $found)));
            }
        }

        $invFilters = $filters;
        $invFilters['compact'] = true;
        // تجاهل التاريخ صراحة
        $invFilters['from'] = null;
        $invFilters['to']   = null;
        if (!empty($cardIds)) {
            $invFilters['product_type_ids'] = $cardIds;
        }

        $stock = $inventory->build($invFilters);
        $items = collect($stock['items'] ?? []);

        if (empty($cardIds)) {
            $keywords = (array) config('inventory.card_keywords', ['بطاق','كرت','card','cards']);
            $kw = array_map(fn($s) => mb_strtolower(trim($s)), $keywords);

            $items = $items->filter(function ($row) use ($kw) {
                $name = mb_strtolower((string) ($row['name'] ?? ''));
                foreach ($kw as $k) {
                    if ($k !== '' && mb_strpos($name, $k) !== false) {
                        return true;
                    }
                }
                return false;
            });
        }

        $vm['cardsAvailable'] = (int) $items->sum('available');

        return view('dashboard.index', $vm);
    }
}
