<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\BankAccount;
use App\Models\Safe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OfficeIncomeMetricsService
{
    /**
     * يبني مؤشرات المكتب: فرق البطاقات / المكاتبة / ربح المكتب
     * - يعتمد على قيود المكتب فقط (is_office = true) واتجاه داخل فقط (direction = 'in')
     *
     * @param array $filters
     *   المفاتيح المتاحة:
     *   - from: 'YYYY-MM-DD'         (اختياري)
     *   - to:   'YYYY-MM-DD'         (اختياري)
     *   - account_type: 'bank'|'safe'|''  (اختياري) لتقييد النوع
     *   - bank_ids: int[]            (اختياري) حصر على حسابات بنكية محددة
     *   - safe_ids: int[]            (اختياري) حصر على خزائن محددة
     *   - status_ids: int[]          (اختياري) تقييد بالحالات
     *   - types: array{cards?:int[],mukataba?:int[],profit?:int[]}
     *       => لو موجودة، بنفلتر بـ transaction_type_id IN (...) بدلاً من الكلمات
     *   - keywords: array{cards?:string[],mukataba?:string[],profit?:string[]}
     *       => لو types غير موجودة، بنستخدم الكلمات لمطابقة أسماء الحالات
     *
     * @return array{
     *   cards:   array{ total: float, by_bank: array<int,array{id:int,name:string,sum:float}>, by_safe: array<int,array{id:int,name:string,sum:float}>, top_statuses: array<int,array{name:string,sum:float}> },
     *   mukataba:array{ total: float, by_bank: array<int,array{id:int,name:string,sum:float}>, by_safe: array<int,array{id:int,name:string,sum:float}>, top_statuses: array<int,array{name:string,sum:float}> },
     *   profit:  array{ total: float, by_bank: array<int,array{id:int,name:string,sum:float}>, by_safe: array<int,array{id:int,name:string,sum:float}>, top_statuses: array<int,array{name:string,sum:float}> }
     * }
     */
    public function build(array $filters = []): array
    {
        // كلمات افتراضية لو مافيش types محددة
        $defaultKeywords = [
            'cards'    => ['بطاقة','بطاقات','فيزا','ماستر','مدى','mada','visa','mastercard','نقاط بيع','نقاطِ البيع','POS','شبكة','card','cards'],
            'mukataba' => ['مكاتبة','مُكاتبة','كتابة','mukataba','mukātaba'],
            'profit'   => ['ربح','أرباح','عوائد','عمولة','عمولات','profit','revenue','return','commission'],
        ];

        $keywords = array_replace_recursive($defaultKeywords, $filters['keywords'] ?? []);

        // ========== فئات المؤشر ==========
        $categories = [
            'cards'    => ['keywords' => $keywords['cards'] ?? [] , 'type_ids' => $filters['types']['cards']    ?? []],
            'mukataba' => ['keywords' => $keywords['mukataba'] ?? [], 'type_ids' => $filters['types']['mukataba'] ?? []],
            'profit'   => ['keywords' => $keywords['profit'] ?? []  , 'type_ids' => $filters['types']['profit']   ?? []],
        ];

        $out = [];

        foreach ($categories as $key => $cfg) {
            $out[$key] = $this->buildCategory($filters, $cfg['type_ids'], $cfg['keywords']);
        }

        return $out;
    }

    /**
     * يبني مؤشر فئة واحدة (إجمالي + تفصيل بنك/خزنة + أعلى حالات)
     */
    private function buildCategory(array $filters, array $typeIds, array $statusKeywords): array
    {
        // إجمالي
        $total = (float) $this->applyCategory(
            $this->baseQuery($filters),
            $typeIds,
            $statusKeywords
        )->sum('amount');

        // تفصيل حسب الحساب البنكي
        $byBank = $this->sumByAccount(
            $this->applyCategory(
                $this->baseQuery($filters)->whereNotNull('bank_account_id'),
                $typeIds,
                $statusKeywords
            ),
            'bank_account_id',
            BankAccount::class
        );

        // تفصيل حسب الخزنة
        $bySafe = $this->sumByAccount(
            $this->applyCategory(
                $this->baseQuery($filters)->whereNotNull('safe_id'),
                $typeIds,
                $statusKeywords
            ),
            'safe_id',
            Safe::class
        );

        // أعلى الحالات (Top statuses)
        $topStatuses = $this->topStatuses(
            $this->applyCategory(
                $this->baseQuery($filters),
                $typeIds,
                $statusKeywords
            )
        );

        return [
            'total'        => $total,
            'by_bank'      => $byBank,      // [ [id,name,sum], ... ]
            'by_safe'      => $bySafe,      // [ [id,name,sum], ... ]
            'top_statuses' => $topStatuses, // [ [name,sum], ... ]
        ];
    }

    /**
     * كويري الأساس: قيود المكتب فقط + اتجاه داخل فقط + فلاتر عامة
     */
    private function baseQuery(array $filters): Builder
    {
        $q = LedgerEntry::query()
            ->where('is_office', true)
            ->where('direction', 'in');

        // نوع الحساب
        if (($filters['account_type'] ?? '') === 'bank') {
            $q->whereNotNull('bank_account_id');
        } elseif (($filters['account_type'] ?? '') === 'safe') {
            $q->whereNotNull('safe_id');
        }

        // حصر بحسابات محددة
        if (!empty($filters['bank_ids']) && is_array($filters['bank_ids'])) {
            $q->whereIn('bank_account_id', array_filter($filters['bank_ids']));
        }
        if (!empty($filters['safe_ids']) && is_array($filters['safe_ids'])) {
            $q->whereIn('safe_id', array_filter($filters['safe_ids']));
        }

        // تقييد بحالات معينة (لو مطلوب)
        if (!empty($filters['status_ids']) && is_array($filters['status_ids'])) {
            $q->whereIn('transaction_status_id', array_filter($filters['status_ids']));
        }

        // من/إلى التواريخ
        if (!empty($filters['from'])) {
            $q->whereDate('entry_date', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $q->whereDate('entry_date', '<=', $filters['to']);
        }

        return $q;
    }

    /**
     * يطبّق فلتر الفئة:
     * - لو فيه typeIds => يفلتر بـ transaction_type_id IN (...)
     * - غير كدا => يفلتر بكلمات مفتاحية على اسم الحالة
     */
    private function applyCategory(Builder $q, array $typeIds, array $statusKeywords): Builder
    {
        if (!empty($typeIds)) {
            return $q->whereIn('transaction_type_id', array_filter($typeIds));
        }

        // مطابقة اسم الحالة بالكلمات (OR LIKE)
        $statusKeywords = array_values(array_filter(array_map('trim', $statusKeywords)));
        if (empty($statusKeywords)) {
            // لو مفيش كلمات، رجّع الكويري كما هو
            return $q;
        }

        return $q->whereHas('status', function ($qq) use ($statusKeywords) {
            $qq->where(function ($wq) use ($statusKeywords) {
                foreach ($statusKeywords as $w) {
                    $wq->orWhere('name', 'like', "%{$w}%");
                }
            });
        });
    }

    /**
     * مجموع حسب حساب (بنك/خزنة) + أسماء الحسابات
     *
     * @param class-string<\Illuminate\Database\Eloquent\Model> $nameModel
     * @return array<int,array{id:int,name:string,sum:float}>
     */
    private function sumByAccount(Builder $q, string $col, string $nameModel): array
    {
        $rows = $q->selectRaw("$col as id, SUM(amount) as s")
            ->groupBy($col)
            ->pluck('s', 'id'); // [id => sum]

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

        // ترتيب أبجدي بالاسم
        usort($out, fn($a,$b) => strnatcasecmp($a['name'], $b['name']));
        return $out;
    }

    /**
     * أعلى الحالات مساهمة (اسم الحالة + الإجمالي)
     *
     * @return array<int,array{name:string,sum:float}>
     */
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
