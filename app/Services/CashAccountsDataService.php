<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\BankAccount;
use App\Models\Safe;
use App\Models\TransactionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CashAccountsDataService
{
    /**
     * تبني مجاميع الحسابات البنكية والخزن مع تفصيل الحالات لكل حساب
     * (بدون أي علاقة بالمستثمرين).
     *
     * الفلاتر المتاحة (كلها اختيارية):
     * - account_type: 'bank' | 'safe' | '' (لو فاضي => الاثنين)
     * - bank_ids: int[]   (لتحديد حسابات بنكية بعينها)
     * - safe_ids: int[]   (لتحديد خزائن بعينها)
     * - status_id: int    (لتقييد النتائج بحالة معينة)
     * - from: 'YYYY-MM-DD'
     * - to:   'YYYY-MM-DD'
     *
     * @return array{
     *    totals: array{in:float, out:float, net:float},
     *    bankTotals?: array{in:float, out:float, net:float},
     *    safeTotals?: array{in:float, out:float, net:float},
     *    banks?: array<int, array{
     *      id:int, name:string, in:float, out:float, net:float, in_pct:float, out_pct:float,
     *      statuses: array<int, array{id:int, name:?string, in:float, out:float, net:float, in_pct:float, out_pct:float}>
     *    }>,
     *    safes?: array<int, array{
     *      id:int, name:string, in:float, out:float, net:float, in_pct:float, out_pct:float,
     *      statuses: array<int, array{id:int, name:?string, in:float, out:float, net:float, in_pct:float, out_pct:float}>
     *    }>
     * }
     */
    public function build(array $filters = []): array
    {
        $wantBank = ($filters['account_type'] ?? '') !== 'safe';
        $wantSafe = ($filters['account_type'] ?? '') !== 'bank';

        // ===== تجميع البنوك (لكل حساب + بالحالات)
        $bankRows = collect();
        $bankStatusRows = collect();

        if ($wantBank) {
            $bankRows = $this->baseQuery($filters)
                ->whereNotNull('bank_account_id')
                ->selectRaw("
                    bank_account_id,
                    SUM(CASE WHEN direction = 'in'  THEN amount ELSE 0 END) AS sum_in,
                    SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END) AS sum_out
                ")
                ->groupBy('bank_account_id')
                ->get();

            // لاحظ استخدام transaction_status_id كما في المايجريشن
            $bankStatusRows = $this->baseQuery($filters)
                ->whereNotNull('bank_account_id')
                ->selectRaw("
                    bank_account_id,
                    transaction_status_id AS status_id,
                    SUM(CASE WHEN direction = 'in'  THEN amount ELSE 0 END) AS sum_in,
                    SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END) AS sum_out
                ")
                ->groupBy('bank_account_id', 'transaction_status_id')
                ->get();
        }

        // ===== تجميع الخزن (لكل خزنة + بالحالات)
        $safeRows = collect();
        $safeStatusRows = collect();

        if ($wantSafe) {
            $safeRows = $this->baseQuery($filters)
                ->whereNotNull('safe_id')
                ->selectRaw("
                    safe_id,
                    SUM(CASE WHEN direction = 'in'  THEN amount ELSE 0 END) AS sum_in,
                    SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END) AS sum_out
                ")
                ->groupBy('safe_id')
                ->get();

            $safeStatusRows = $this->baseQuery($filters)
                ->whereNotNull('safe_id')
                ->selectRaw("
                    safe_id,
                    transaction_status_id AS status_id,
                    SUM(CASE WHEN direction = 'in'  THEN amount ELSE 0 END) AS sum_in,
                    SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END) AS sum_out
                ")
                ->groupBy('safe_id', 'transaction_status_id')
                ->get();
        }

        // ===== أسماء الحسابات والحالات
        [$bankNames, $safeNames, $statusNames] = $this->fetchNamesMaps(
            $bankRows->pluck('bank_account_id')->filter()->unique()->values(),
            $safeRows->pluck('safe_id')->filter()->unique()->values(),
            collect()
                ->merge($bankStatusRows->pluck('status_id'))
                ->merge($safeStatusRows->pluck('status_id'))
                ->filter()->unique()->values()
        );

        // ===== تشكيل بيانات البنوك
        $banks = [];
        $bankTotalIn = $bankTotalOut = 0.0;

        $bankStatusesByAcc = $bankStatusRows->groupBy('bank_account_id');

        foreach ($bankRows as $row) {
            $id   = (int) $row->bank_account_id;
            $in   = (float) $row->sum_in;
            $out  = (float) $row->sum_out;
            $net  = $in - $out;
            $flow = max($in + $out, 0.00001);

            $bankTotalIn  += $in;
            $bankTotalOut += $out;

            $statuses = [];
            foreach ($bankStatusesByAcc->get($id, collect()) as $srow) {
                $sin   = (float) $srow->sum_in;
                $sout  = (float) $srow->sum_out;
                $snet  = $sin - $sout;
                $sflow = max($sin + $sout, 0.00001);
                $sid   = (int) ($srow->status_id ?? 0);

                $statuses[] = [
                    'id'      => $sid,
                    'name'    => $statusNames[$sid] ?? null,
                    'in'      => $sin,
                    'out'     => $sout,
                    'net'     => $snet,
                    'in_pct'  => round($sin  / $sflow * 100, 1),
                    'out_pct' => round($sout / $sflow * 100, 1),
                ];
            }

            usort($statuses, function ($a, $b) {
                $fa = $a['in'] + $a['out'];
                $fb = $b['in'] + $b['out'];
                return $fa <=> $fb ? ($fa > $fb ? -1 : 1) : 0;
            });

            $banks[] = [
                'id'       => $id,
                'name'     => $bankNames[$id] ?? ('#'.$id),
                'in'       => $in,
                'out'      => $out,
                'net'      => $net,
                'in_pct'   => round($in  / $flow * 100, 1),
                'out_pct'  => round($out / $flow * 100, 1),
                'statuses' => $statuses,
            ];
        }

        usort($banks, fn($a,$b) => strnatcasecmp($a['name'], $b['name']));

        // ===== تشكيل بيانات الخزن
        $safes = [];
        $safeTotalIn = $safeTotalOut = 0.0;

        $safeStatusesByAcc = $safeStatusRows->groupBy('safe_id');

        foreach ($safeRows as $row) {
            $id   = (int) $row->safe_id;
            $in   = (float) $row->sum_in;
            $out  = (float) $row->sum_out;
            $net  = $in - $out;
            $flow = max($in + $out, 0.00001);

            $safeTotalIn  += $in;
            $safeTotalOut += $out;

            $statuses = [];
            foreach ($safeStatusesByAcc->get($id, collect()) as $srow) {
                $sin   = (float) $srow->sum_in;
                $sout  = (float) $srow->sum_out;
                $snet  = $sin - $sout;
                $sflow = max($sin + $sout, 0.00001);
                $sid   = (int) ($srow->status_id ?? 0);

                $statuses[] = [
                    'id'      => $sid,
                    'name'    => $statusNames[$sid] ?? null,
                    'in'      => $sin,
                    'out'     => $sout,
                    'net'     => $snet,
                    'in_pct'  => round($sin  / $sflow * 100, 1),
                    'out_pct' => round($sout / $sflow * 100, 1),
                ];
            }

            usort($statuses, function ($a, $b) {
                $fa = $a['in'] + $a['out'];
                $fb = $b['in'] + $b['out'];
                return $fa <=> $fb ? ($fa > $fb ? -1 : 1) : 0;
            });

            $safes[] = [
                'id'       => $id,
                'name'     => $safeNames[$id] ?? ('#'.$id),
                'in'       => $in,
                'out'      => $out,
                'net'      => $net,
                'in_pct'   => round($in  / $flow * 100, 1),
                'out_pct'  => round($out / $flow * 100, 1),
                'statuses' => $statuses,
            ];
        }

        usort($safes, fn($a,$b) => strnatcasecmp($a['name'], $b['name']));

        // إجمالي عام
        $grandIn  = ($bankTotalIn ?? 0) + ($safeTotalIn ?? 0);
        $grandOut = ($bankTotalOut ?? 0) + ($safeTotalOut ?? 0);

        $out = [
            'totals' => [
                'in'  => $grandIn,
                'out' => $grandOut,
                'net' => $grandIn - $grandOut,
            ],
        ];

        if ($wantBank) {
            $out['bankTotals'] = [
                'in'  => $bankTotalIn,
                'out' => $bankTotalOut,
                'net' => $bankTotalIn - $bankTotalOut,
            ];
            $out['banks'] = $banks;
        }

        if ($wantSafe) {
            $out['safeTotals'] = [
                'in'  => $safeTotalIn,
                'out' => $safeTotalOut,
                'net' => $safeTotalIn - $safeTotalOut,
            ];
            $out['safes'] = $safes;
        }

        return $out;
    }

    /**
     * كويري الأساس مع الفلاتر العامة (بدون أي علاقة بالمستثمرين).
     */
    private function baseQuery(array $filters): Builder
    {
        $q = LedgerEntry::query();

        // حالة محددة
        if (!empty($filters['status_id'])) {
            $q->where('transaction_status_id', $filters['status_id']);
        }

        // تحديد نوع الحساب
        if (($filters['account_type'] ?? '') === 'bank') {
            $q->whereNotNull('bank_account_id');
        } elseif (($filters['account_type'] ?? '') === 'safe') {
            $q->whereNotNull('safe_id');
        }

        // حصر بحسابات معينة
        if (!empty($filters['bank_ids']) && is_array($filters['bank_ids'])) {
            $q->whereIn('bank_account_id', array_filter($filters['bank_ids']));
        }
        if (!empty($filters['safe_ids']) && is_array($filters['safe_ids'])) {
            $q->whereIn('safe_id', array_filter($filters['safe_ids']));
        }

        // تاريخ من/إلى
        if (!empty($filters['from'])) {
            $q->whereDate('entry_date', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $q->whereDate('entry_date', '<=', $filters['to']);
        }

        return $q;
    }

    /**
     * يجلب خرائط الأسماء للحسابات البنكية والخزن والحالات.
     *
     * @return array{0: array<int,string>, 1: array<int,string>, 2: array<int,string>}
     */
    private function fetchNamesMaps(Collection $bankIds, Collection $safeIds, Collection $statusIds): array
    {
        $bankNames = [];
        $safeNames = [];
        $statusNames = [];

        if ($bankIds->isNotEmpty()) {
            $bankNames = BankAccount::whereIn('id', $bankIds)->pluck('name', 'id')->toArray();
        }
        if ($safeIds->isNotEmpty()) {
            $safeNames = Safe::whereIn('id', $safeIds)->pluck('name', 'id')->toArray();
        }
        if ($statusIds->isNotEmpty()) {
            $statusNames = TransactionStatus::whereIn('id', $statusIds)->pluck('name', 'id')->toArray();
        }

        return [$bankNames, $safeNames, $statusNames];
    }
}
