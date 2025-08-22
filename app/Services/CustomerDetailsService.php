<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Contract;
use App\Models\ContractInstallment;
use Illuminate\Support\Carbon;

class CustomerDetailsService
{
    /**
     * يكتشف Namespace الخاص بالـ DTOs (يدعم App\DTOs و App\DTO).
     */
    private function dtoNamespace(): string
    {
        $candidates = [
            'App\\DTOs\\CustomerDetails',
            'App\\DTO\\CustomerDetails',
        ];

        foreach ($candidates as $ns) {
            if (class_exists($ns . '\\CustomerDetailsResult')) {
                return '\\' . $ns;
            }
        }

        // افتراضيًا: النسخة بدون s
        return '\\App\\DTO\\CustomerDetails';
    }

    /**
     * يبني تفاصيل العميل.
     * ملاحظة:
     * - عقود «منتهي/سداد مبكر» تُحسب كـ finished في التصنيف.
     * - لكنها لا تدخل في ملخص الأقساط المجمّع (مدفوع/غير مدفوع/متأخر/القسط القادم/آخر سداد).
     *
     * فلاتر اختيارية:
     * - status_ids[] ، from_start/to_start ، from_due/to_due
     */
    public function build(int $customerId, array $filters = [])
    {
        $ns = $this->dtoNamespace();
        $ResultClass        = $ns . '\\CustomerDetailsResult';
        $CustomerBasicClass = $ns . '\\CustomerBasic';
        $BriefClass         = $ns . '\\ContractBrief';
        $InstSumClass       = $ns . '\\InstallmentsSummary';

        /** @var object $result */
        $result = new $ResultClass();
        $today  = Carbon::today();

        $customer = Customer::find($customerId);
        if (!$customer) {
            $result->customer = new $CustomerBasicClass(0, 'غير موجود');
            $result->installments_summary = new $InstSumClass(0, 0, 0, 0, 0, 0, null, null);
            return $result;
        }

        // بطاقة العميل
        $result->customer = new $CustomerBasicClass(
            $customer->id,
            $customer->name,
            $customer->phone,
            $customer->email,
            $customer->national_id,
            $customer->address
        );

        // عقود العميل (لا نستبعد المنتهي/السداد المبكر من الاستعلام)
        $contracts = Contract::query()
            ->with(['productType:id,name', 'contractStatus:id,name'])
            ->where('customer_id', $customerId)
            ->when(!empty($filters['status_ids'] ?? null), fn($q) =>
                $q->whereIn('contract_status_id', array_filter((array) $filters['status_ids']))
            )
            ->when(!empty($filters['from_start'] ?? null), fn($q) =>
                $q->whereDate('start_date', '>=', $filters['from_start'])
            )
            ->when(!empty($filters['to_start'] ?? null), fn($q) =>
                $q->whereDate('start_date', '<=', $filters['to_start'])
            )
            ->get();

        if ($contracts->isEmpty()) {
            $result->installments_summary = new $InstSumClass(0, 0, 0, 0, 0, 0, null, null);
            $result->active = $result->finished = $result->other = [];
            $result->active_count = $result->finished_count = $result->other_count = 0;
            $result->total_contracts = 0;
            $result->statuses_breakdown = [];
            return $result;
        }

        $ids = $contracts->pluck('id')->all();

        // تجميع الأقساط على مستوى العقد
        $agg = ContractInstallment::query()
            ->selectRaw("
                contract_id,
                COUNT(*) as cnt,
                SUM(due_amount) as due_sum,
                SUM(COALESCE(payment_amount,0)) as paid_sum,
                SUM(CASE WHEN payment_date IS NULL OR payment_amount < due_amount
                         THEN (due_amount - COALESCE(payment_amount,0)) ELSE 0 END) as unpaid_sum,
                SUM(CASE WHEN (payment_date IS NULL OR payment_amount < due_amount) AND due_date < ?
                         THEN (due_amount - COALESCE(payment_amount,0)) ELSE 0 END) as overdue_sum,
                SUM(CASE WHEN (payment_date IS NULL OR payment_amount < due_amount) AND due_date < ?
                         THEN 1 ELSE 0 END) as overdue_cnt,
                SUM(CASE WHEN payment_date IS NOT NULL AND payment_amount >= due_amount
                         THEN 1 ELSE 0 END) as paid_cnt,
                MIN(CASE WHEN (payment_date IS NULL OR payment_amount < due_amount) THEN due_date END) as next_due,
                MAX(payment_date) as last_payment
            ", [$today, $today])
            ->whereIn('contract_id', $ids)
            ->when(!empty($filters['from_due'] ?? null), fn($q) =>
                $q->whereDate('due_date', '>=', $filters['from_due'])
            )
            ->when(!empty($filters['to_due'] ?? null), fn($q) =>
                $q->whereDate('due_date', '<=', $filters['to_due'])
            )
            ->groupBy('contract_id')
            ->get()
            ->keyBy('contract_id');

        // تعريف حالات "نشط/منتهي" للتصنيف (تشمل «منتهي/سداد مبكر» ضمن finished)
        $configActiveNames   = array_map('mb_strtolower', (array) (config('contracts.active_status_names')   ?? []));
        $configFinishedNames = array_map('mb_strtolower', (array) (config('contracts.finished_status_names') ?? []));

        $defaultActiveNames = ['نشط','ساري','جاري','active','ongoing'];
        $defaultFinishedNames = [
            'منتهي','منتهى',
            'سداد مبكر','سداد مُبكر','سداد مبكّر',
            'completed','early settlement','finished'
        ];

        $activeNames   = array_unique(array_filter(array_merge($configActiveNames, $defaultActiveNames)));
        $finishedNames = array_unique(array_filter(array_merge($configFinishedNames, $defaultFinishedNames)));

        $activeIds     = (array) (config('contracts.active_status_ids')   ?? []);
        $finishedIds   = (array) (config('contracts.finished_status_ids') ?? []);

        // 👇 حالات تُستثنى من ملخص الأقساط المجمّع فقط (مدفوع/غير مدفوع…)
        // بشكل افتراضي: «منتهي» و «سداد مبكر» فقط
        $excludeFromTotalsIds   = (array) (config('contracts.exclude_from_totals_status_ids')   ?? []);
        $excludeFromTotalsNames = array_map('mb_strtolower', array_unique(array_filter(array_merge(
            (array) (config('contracts.exclude_from_totals_status_names') ?? []),
            ['منتهي','منتهى','completed','finished', 'سداد مبكر','سداد مُبكر','سداد مبكّر','early settlement']
        ))));

        $isExcludedFromTotals = function (?int $sid, string $sname) use ($excludeFromTotalsIds, $excludeFromTotalsNames): bool {
            if ($sid && in_array($sid, $excludeFromTotalsIds, true)) return true;
            $nm = mb_strtolower(trim($sname));
            return $nm !== '' && in_array($nm, $excludeFromTotalsNames, true);
        };

        // مجاميع عامة (للعقود غير المستثناة فقط)
        $globalTotalInst = 0; $globalDue = 0.0; $globalPaid = 0.0; $globalUnpaid = 0.0;
        $globalOverdueCnt = 0; $globalOverdue = 0.0;
        $globalNext = null; $globalLast = null;

        $statusMap = []; // sid => ['id'=>?int,'name'=>string,'count'=>int,'total_value_sum'=>float]

        foreach ($contracts as $c) {
            $a = $agg->get($c->id);

            $cnt         = (int)   ($a->cnt ?? 0);
            $due_sum     = (float) ($a->due_sum ?? 0);
            $paid_sum    = (float) ($a->paid_sum ?? 0);
            $unpaid_sum  = (float) ($a->unpaid_sum ?? 0);
            $overdue_sum = (float) ($a->overdue_sum ?? 0);
            $overdue_cnt = (int)   ($a->overdue_cnt ?? 0);
            $paid_cnt    = (int)   ($a->paid_cnt ?? 0);
            $unpaid_cnt  = max(0, $cnt - $paid_cnt);
            $next_due    = !empty($a->next_due) ? Carbon::parse($a->next_due) : null;
            $last_pay    = !empty($a->last_payment) ? Carbon::parse($a->last_payment) : null;

            $statusId   = (int)($c->contract_status_id ?? 0) ?: null;
            $statusName = (string)($c->contractStatus->name ?? '');

            // تصنيف العقد إلى active / finished / other للعرض
            $bucket = $this->classify(
                $statusId,
                $statusName,
                $unpaid_sum,
                $activeIds,
                $finishedIds,
                $activeNames,
                $finishedNames
            );

            // تكوين الـ Brief (يظل يعرض أرقام العقد نفسه حتى لو مستثنى من المجاميع)
            $brief = new $BriefClass(
                (int) $c->id,
                (string) $c->contract_number,
                $c->start_date ? Carbon::parse($c->start_date)->format('Y-m-d') : null,
                $statusId,
                $statusName ?: null,
                (int) $c->product_type_id,
                $c->productType->name ?? null,
                (int) ($c->products_count ?? 0),
                (float) $c->purchase_price,
                (float) $c->sale_price,
                (float) $c->contract_value,
                (float) $c->investor_profit,
                (float) $c->total_value,
                (float) ($c->discount_amount ?? 0),
                $cnt,
                $paid_cnt,
                $unpaid_cnt,
                $due_sum,
                $paid_sum,
                $unpaid_sum,
                $overdue_cnt,
                $overdue_sum,
                $next_due?->format('Y-m-d'),
                $last_pay?->format('Y-m-d')
            );

            if ($bucket === 'active') {
                $result->active[] = $brief;
            } elseif ($bucket === 'finished') {
                $result->finished[] = $brief;
            } else {
                $result->other[] = $brief;
            }

            // breakdown للحالة (دائمًا يشمل كل العقود)
            $sid = $statusId ?? 0;
            $nm  = $statusName !== '' ? $statusName : 'غير مُحدد';
            if (!isset($statusMap[$sid])) {
                $statusMap[$sid] = ['id' => $statusId, 'name' => $nm, 'count' => 0, 'total_value_sum' => 0.0];
            }
            $statusMap[$sid]['count']++;
            $statusMap[$sid]['total_value_sum'] += (float) ($c->total_value ?? 0);

            // ✅ ملخص الأقساط المجمّع: استبعاد «منتهي/سداد مبكر»
            if (!$isExcludedFromTotals($statusId, $statusName)) {
                $globalTotalInst += $cnt;
                $globalDue       += $due_sum;
                $globalPaid      += $paid_sum;
                $globalUnpaid    += $unpaid_sum;
                $globalOverdue   += $overdue_sum;
                $globalOverdueCnt+= $overdue_cnt;

                if ($next_due && (is_null($globalNext) || $next_due->lt($globalNext))) { $globalNext = $next_due; }
                if ($last_pay && (is_null($globalLast) || $last_pay->gt($globalLast))) { $globalLast = $last_pay; }
            }
        }

        // ملخصات العقود
        $result->active_count    = count($result->active ?? []);
        $result->finished_count  = count($result->finished ?? []);
        $result->other_count     = count($result->other ?? []);
        $result->total_contracts = $result->active_count + $result->finished_count + $result->other_count;

        // breakdown منسّق
        foreach ($statusMap as &$r) {
            $r['total_value_sum'] = round($r['total_value_sum'], 2);
            $r['formatted'] = ['total_value_sum' => number_format($r['total_value_sum'], 2)];
        }
        unset($r);
        usort($statusMap, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
        $result->statuses_breakdown = array_values($statusMap);

        // ملخص الأقساط العام (بعد الاستبعاد)
        $result->installments_summary = new $InstSumClass(
            total_installments: $globalTotalInst,
            total_due_amount:   $globalDue,
            total_paid_amount:  $globalPaid,
            total_unpaid_amount:$globalUnpaid,
            overdue_count:      $globalOverdueCnt,
            overdue_amount:     $globalOverdue,
            next_due_date:      $globalNext?->format('Y-m-d'),
            last_payment_date:  $globalLast?->format('Y-m-d')
        );

        // ترتيب شكلي حسب رقم العقد
        $sortNo = fn($a, $b) => strnatcasecmp($a->contract_number, $b->contract_number);
        if (!empty($result->active))   usort($result->active,   $sortNo);
        if (!empty($result->finished)) usort($result->finished, $sortNo);
        if (!empty($result->other))    usort($result->other,    $sortNo);

        return $result;
    }

    /**
     * يصنّف العقد إلى active / finished / other بناءً على الحالة والمبالغ المتبقية.
     * «منتهي/سداد مبكر» ضمن finished عبر finishedNames الافتراضية.
     */
    private function classify(
        ?int $statusId,
        string $statusName,
        float $unpaidSum,
        array $activeIds,
        array $finishedIds,
        array $activeNames,
        array $finishedNames
    ): string {
        if ($statusId && in_array($statusId, $activeIds, true))   return 'active';
        if ($statusId && in_array($statusId, $finishedIds, true)) return 'finished';

        $nm = mb_strtolower(trim($statusName));
        if ($nm !== '') {
            if (in_array($nm, $activeNames, true))   return 'active';
            if (in_array($nm, $finishedNames, true)) return 'finished';
        }

        // fallback منطقي: إن فيه مبالغ غير مدفوعة اعتبره نشط، وإلا منتهي
        return $unpaidSum > 0 ? 'active' : 'finished';
    }
}
