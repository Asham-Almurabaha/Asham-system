<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DashboardDataService;
use App\Services\ProductAvailabilityService;
use Carbon\Carbon;
use Modules\Ledger\Entities\LedgerEntry;
use Modules\Lookups\Entities\ProductType;

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

    public function printDailyLedger(Request $request)
    {
        $dateInput = $request->input('date');

        try {
            $day = $dateInput ? Carbon::parse($dateInput) : Carbon::today();
        } catch (\Throwable $e) {
            $day = Carbon::today();
        }

        $day = $day->startOfDay();
        $dateString = $day->toDateString();

        $entries = LedgerEntry::query()
            ->with(['bankAccount', 'safe', 'status', 'type', 'investor'])
            ->whereDate('entry_date', $dateString)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $buildReport = function ($collection, callable $nameResolver, string $groupKey) {
            $collection = $collection->values();

            $accounts = $collection
                ->groupBy($groupKey)
                ->map(function ($group) use ($nameResolver, $groupKey) {
                    $first = $group->first();

                    $totalIn = (float) $group->where('direction', 'in')->sum('amount');
                    $totalOut = (float) $group->where('direction', 'out')->sum('amount');

                    return [
                        'id' => (int) ($first?->{$groupKey} ?? 0),
                        'name' => $nameResolver($first),
                        'total_in' => $totalIn,
                        'total_out' => $totalOut,
                        'net' => $totalIn - $totalOut,
                        'entries' => $group
                            ->map(function (LedgerEntry $entry) use ($nameResolver) {
                                return [
                                    'id' => $entry->id,
                                    'date' => optional($entry->entry_date)->format('Y-m-d'),
                                    'ref' => $entry->ref,
                                    'direction' => $entry->direction,
                                    'amount' => (float) $entry->amount,
                                    'status' => optional($entry->status)->name,
                                    'type' => optional($entry->type)->name,
                                    'notes' => $entry->notes,
                                    'investor' => optional($entry->investor)->name,
                                    'is_office' => (bool) $entry->is_office,
                                    'account_name' => $nameResolver($entry),
                                ];
                            })
                            ->values()
                            ->all(),
                    ];
                })
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();

            $totalIn = (float) $collection->where('direction', 'in')->sum('amount');
            $totalOut = (float) $collection->where('direction', 'out')->sum('amount');

            return [
                'accounts' => $accounts,
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'net' => $totalIn - $totalOut,
                'entries_count' => $collection->count(),
                'accounts_count' => count($accounts),
            ];
        };

        $bankEntries = $entries->filter(fn (LedgerEntry $entry) => !is_null($entry->bank_account_id));
        $safeEntries = $entries->filter(fn (LedgerEntry $entry) => !is_null($entry->safe_id));

        $bankReport = $buildReport(
            $bankEntries,
            fn (LedgerEntry $entry) => $entry->bankAccount?->name ?? ('#' . $entry->bank_account_id),
            'bank_account_id'
        );

        $safeReport = $buildReport(
            $safeEntries,
            fn (LedgerEntry $entry) => $entry->safe?->name ?? ('#' . $entry->safe_id),
            'safe_id'
        );

        $grandTotals = [
            'total_in' => $bankReport['total_in'] + $safeReport['total_in'],
            'total_out' => $bankReport['total_out'] + $safeReport['total_out'],
            'net' => ($bankReport['net'] ?? 0) + ($safeReport['net'] ?? 0),
            'entries_count' => $entries->count(),
        ];

        return view('dashboard.daily-ledger-print', [
            'reportDay' => $day,
            'reportDate' => $day->format('Y-m-d'),
            'hasEntries' => $entries->isNotEmpty(),
            'bankReport' => $bankReport,
            'safeReport' => $safeReport,
            'grandTotals' => $grandTotals,
        ]);
    }
}
