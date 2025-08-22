<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\BankAccount;
use App\Models\Safe;
use Illuminate\Database\Eloquent\Builder;

class OfficeIncomeMetricsService
{
    /**
     * يبني مؤشرات المكتب: بطاقات / المكاتبة / ربح المكتب / فرق البيع
     * - يعتمد على قيود المكتب فقط (is_office = true) واتجاه داخل فقط (direction = 'in')
     *
     * @param array $filters
     *   المفاتيح المتاحة:
     *   - from: 'YYYY-MM-DD' / to: 'YYYY-MM-DD'
     *   - account_type: 'bank'|'safe'|''
     *   - bank_ids: int[] , safe_ids: int[]
     *   - status_ids: int[]
     *   - types: array{cards?:int[],mukataba?:int[],profit?:int[],sales?:int[]}
     *   - keywords: array{cards?:string[],mukataba?:string[],profit?:string[],sales?:string[]}
     *
     * @return array{
     *   cards:   array{ total: float, by_bank: array<int,array{id:int,name:string,sum:float}>, by_safe: array<int,array{id:int,name:string,sum:float}>, top_statuses: array<int,array{name:string,sum:float}> },
     *   mukataba:array{ total: float, by_bank: array<int,array{id:int,name:string,sum:float}>, by_safe: array<int,array{id:int,name:string,sum:float}>, top_statuses: array<int,array{name:string,sum:float}> },
     *   profit:  array{ total: float, by_bank: array<int,array{id:int,name:string,sum:float}>, by_safe: array<int,array{id:int,name:string,sum:float}>, top_statuses: array<int,array{name:string,sum:float}> },
     *   sales:   array{ total: float, by_bank: array<int,array{id:int,name:string,sum:float}>, by_safe: array<int,array{id:int,name:string,sum:float}>, top_statuses: array<int,array{name:string,sum:float}> }
     * }
     */
    public function build(array $filters = []): array
    {
        // كلمات افتراضية لو مافيش types محددة
        $defaultKeywords = [
            'cards'    => ['بطاقة','بطاقات','فيزا','ماستر','مدى','mada','visa','mastercard','نقاط بيع','نقاطِ البيع','POS','شبكة','card','cards'],
            'mukataba' => ['مكاتبة','مُكاتبة','كتابة','mukataba','mukātaba'],
            'profit'   => ['ربح','أرباح','عوائد','عمولة','عمولات','profit','revenue','return','commission'],
            // ✅ فرق البيع
            'sales'    => ['بيع','مبيع','مبيعات','sale','sales','فرق بيع','فرق البيع'],
        ];

        $keywords = array_replace_recursive($defaultKeywords, $filters['keywords'] ?? []);

        // ========== فئات المؤشر ==========
        $categories = [
            'cards'    => ['keywords' => $keywords['cards'] ?? []    , 'type_ids' => $filters['types']['cards']    ?? []],
            'mukataba' => ['keywords' => $keywords['mukataba'] ?? [] , 'type_ids' => $filters['types']['mukataba'] ?? []],
            'profit'   => ['keywords' => $keywords['profit'] ?? []   , 'type_ids' => $filters['types']['profit']   ?? []],
            // ✅ sales
            'sales'    => ['keywords' => $keywords['sales'] ?? []    , 'type_ids' => $filters['types']['sales']    ?? []],
        ];

        $out = [];
        foreach ($categories as $key => $cfg) {
            $out[$key] = $this->buildCategory($filters, $cfg['type_ids'], $cfg['keywords']);
        }

        return $out;
    }

    /** يبني مؤشر فئة واحدة (إجمالي + تفصيل بنك/خزنة + أعلى حالات) */
    private function buildCategory(array $filters, array $typeIds, array $statusKeywords): array
    {
        $total = (float) $this->applyCategory(
            $this->baseQuery($filters),
            $typeIds,
            $statusKeywords
        )->sum('amount');

        $byBank = $this->sumByAccount(
            $this->applyCategory(
                $this->baseQuery($filters)->whereNotNull('bank_account_id'),
                $typeIds,
                $statusKeywords
            ),
            'bank_account_id',
            BankAccount::class
        );

        $bySafe = $this->sumByAccount(
            $this->applyCategory(
                $this->baseQuery($filters)->whereNotNull('safe_id'),
                $typeIds,
                $statusKeywords
            ),
            'safe_id',
            Safe::class
        );

        $topStatuses = $this->topStatuses(
            $this->applyCategory(
                $this->baseQuery($filters),
                $typeIds,
                $statusKeywords
            )
        );

        return [
            'total'        => $total,
            'by_bank'      => $byBank,
            'by_safe'      => $bySafe,
            'top_statuses' => $topStatuses,
        ];
    }

    /** كويري الأساس: قيود المكتب فقط + اتجاه داخل فقط + فلاتر عامة */
    private function baseQuery(array $filters): Builder
    {
        $q = LedgerEntry::query()
            ->where('is_office', true)
            ->where('direction', 'in');

        if (($filters['account_type'] ?? '') === 'bank') {
            $q->whereNotNull('bank_account_id');
        } elseif (($filters['account_type'] ?? '') === 'safe') {
            $q->whereNotNull('safe_id');
        }

        if (!empty($filters['bank_ids']) && is_array($filters['bank_ids'])) {
            $q->whereIn('bank_account_id', array_filter($filters['bank_ids']));
        }
        if (!empty($filters['safe_ids']) && is_array($filters['safe_ids'])) {
            $q->whereIn('safe_id', array_filter($filters['safe_ids']));
        }

        if (!empty($filters['status_ids']) && is_array($filters['status_ids'])) {
            $q->whereIn('transaction_status_id', array_filter($filters['status_ids']));
        }

        if (!empty($filters['from'])) $q->whereDate('entry_date', '>=', $filters['from']);
        if (!empty($filters['to']))   $q->whereDate('entry_date', '<=', $filters['to']);

        return $q;
    }

    /** يطبّق فلتر الفئة (types أو كلمات) */
    private function applyCategory(Builder $q, array $typeIds, array $statusKeywords): Builder
    {
        if (!empty($typeIds)) {
            return $q->whereIn('transaction_type_id', array_filter($typeIds));
        }

        $statusKeywords = array_values(array_filter(array_map('trim', $statusKeywords)));
        if (empty($statusKeywords)) return $q;

        return $q->whereHas('status', function ($qq) use ($statusKeywords) {
            $qq->where(function ($wq) use ($statusKeywords) {
                foreach ($statusKeywords as $w) {
                    $wq->orWhere('name', 'like', "%{$w}%");
                }
            });
        });
    }

    /** مجموع حسب حساب (بنك/خزنة) + أسماء الحسابات */
    private function sumByAccount(Builder $q, string $col, string $nameModel): array
    {
        $rows = $q->selectRaw("$col as id, SUM(amount) as s")
            ->groupBy($col)
            ->pluck('s', 'id');

        if ($rows->isEmpty()) return [];

        $names = $nameModel::whereIn('id', $rows->keys())->pluck('name', 'id');

        $out = [];
        foreach ($rows as $id => $sum) {
            $out[] = [
                'id'   => (int) $id,
                'name' => (string) ($names[$id] ?? "#$id"),
                'sum'  => (float) $sum,
            ];
        }

        usort($out, fn($a,$b) => strnatcasecmp($a['name'], $b['name']));
        return $out;
    }

    /** أعلى الحالات مساهمة */
    private function topStatuses(Builder $q): array
    {
        $rows = (clone $q)
            ->join('transaction_statuses as ts', 'ts.id', '=', 'ledger_entries.transaction_status_id')
            ->selectRaw('ts.name as name, SUM(ledger_entries.amount) as s')
            ->groupBy('ledger_entries.transaction_status_id', 'ts.name')
            ->orderByDesc('s')
            ->limit(5)
            ->get();

        return $rows->map(fn($r) => [
            'name' => (string) $r->name,
            'sum'  => (float)  $r->s,
        ])->all();
    }
}
