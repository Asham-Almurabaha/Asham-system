<?php
// app/Http/Controllers/ContractPrintController.php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Setting;
use Carbon\Carbon;
use Alkoumi\LaravelHijriDate\Hijri;

class ContractPrintController extends Controller
{
    public function show(Contract $contract)
    {
        // تحميل العلاقات
        $contract->load([
            'customer',
            'guarantor',
            'contractStatus',
            'productType',
            'installmentType',
            'investors',
            'installments',
            'officeTransactions',
        ]);

        // إعدادات (شعار + اسم)
        $setting = Setting::first();

        $logoUrl = $setting && $setting->logo
            ? asset('storage/'.$setting->logo)
            : asset('assets/img/logo.png');

        $brandName = $setting?->name_ar
            ?? $setting?->name
            ?? config('app.name', 'اسم المنشأة');

        // ====== اليوم بالعربية ======
        $weekdayAr = '';
        if ($contract->start_date instanceof Carbon) {
            $weekdayMap = [
                'Saturday'  => 'السبت',
                'Sunday'    => 'الأحد',
                'Monday'    => 'الاثنين',
                'Tuesday'   => 'الثلاثاء',
                'Wednesday' => 'الأربعاء',
                'Thursday'  => 'الخميس',
                'Friday'    => 'الجمعة',
            ];
            $weekdayAr = $weekdayMap[$contract->start_date->format('l')] ?? '';
        }

        // ====== التاريخ الهجري باستخدام الحزمة ======
        $hijriDate = null;
        $firstInstallmentHijri = null;

        if ($contract->start_date instanceof Carbon) {
            $hijriDate = Hijri::Date('Y/m/d', $contract->start_date->format('Y-m-d'));
        }

        if ($contract->first_installment_date instanceof Carbon) {
            $firstInstallmentHijri = Hijri::Date('Y/m/d', $contract->first_installment_date->format('Y-m-d'));
        }

        return view('contracts.print', [
            'contract'              => $contract,
            'logoUrl'               => $logoUrl,
            'brandName'             => $brandName,
            'setting'               => $setting,
            'weekdayAr'             => $weekdayAr,
            'hijriDate'             => $hijriDate,
            'firstInstallmentHijri' => $firstInstallmentHijri,
        ]);
    }

    public function closure(Contract $contract)
    {
        $contract->load([
            'customer',
            'guarantor',
            'contractStatus',
            'productType',
            'installmentType',
            'installments',
        ]);

        $setting = Setting::first();

        $logoUrl = $setting && $setting->logo
            ? asset('storage/'.$setting->logo)
            : asset('assets/img/logo.png');

        $brandName = $setting?->name_ar
            ?? $setting?->name
            ?? config('app.name', 'اسم المنشأة');

        // إجمالي المطلوب وإجمالي المدفوع
        $totalRequired = (float) $contract->installments->sum('amount');
        // fallback لو العقد ما عندوش أقساط مسجلة
        if ($totalRequired <= 0) {
            $totalRequired = (float) ($contract->total_value ?? 0);
        }
        $totalPaid = (float) $contract->installments->sum('paid_amount');

        // تاريخ المخالصة: لو عندك عمود settled_at استخدمه، وإلا استخدم الآن
        $settlementDate = $contract->settled_at ?? now();

        // يوم الأسبوع بالعربي
        $weekdayMap = [
            'Saturday'=>'السبت','Sunday'=>'الأحد','Monday'=>'الاثنين',
            'Tuesday'=>'الثلاثاء','Wednesday'=>'الأربعاء','Thursday'=>'الخميس','Friday'=>'الجمعة'
        ];
        $weekdayAr = $weekdayMap[$settlementDate->format('l')] ?? '';

        // تحويل هجري (حزمة alkoumi/laravel-hijri-date)
        // الصيغة: يوم/شهر/سنة
        $gregDate = $settlementDate->format('Y/m/d');
        try {
            $hijriDate = Hijri::Date('Y/m/d', $settlementDate);
        } catch (\Throwable $e) {
            $hijriDate = '—';
        }

        return view('contracts.closure', [
            'contract'      => $contract,
            'setting'       => $setting,
            'logoUrl'       => $logoUrl,
            'brandName'     => $brandName,
            'weekdayAr'     => $weekdayAr,
            'gregDate'      => $gregDate,
            'hijriDate'     => $hijriDate,
            'totalRequired' => $totalRequired,
            'totalPaid'     => $totalPaid,
            'settlementDate'=> $settlementDate,
        ]);
    }

    public function paidInstallments(Contract $contract)
    {
        // نحمل العلاقات الصحيحة حسب الموديل (installments)
        $contract->load([
            'customer',
            'installments' => fn($q) => $q->orderBy('due_date'),
        ]);

        // إعدادات (شعار + اسم)
        $setting = Setting::first();
        $logoUrl = $setting && $setting->logo ? asset('storage/'.$setting->logo) : asset('assets/img/logo.png');
        $brandName = $setting?->name_ar ?? $setting?->name ?? config('app.name', 'اسم المنشأة');

        // مختصر للأقساط من الجدول contract_installments عبر العلاقة الصحيحة
        $items = $contract->installments;

        // إجمالي قيمة العقد = مجموع due_amount (ولو صفر نستخدم total_value إن وجد)
        $contractTotal = (float) (
            $items->sum(fn($i) => (float) ($i->due_amount ?? 0))
            ?: ($contract->total_value ?? 0)
        );

        // إجمالي المدفوع = مجموع payment_amount (null تُعتبر 0)
        $totalPaid = (float) $items->sum(fn($i) => (float) ($i->payment_amount ?? 0));

        // المتبقي
        $remaining = max(0.0, $contractTotal - $totalPaid);

        // الأقساط المدفوعة كليًا: payment_amount >= due_amount
        $fullyPaidInstallments = $items->filter(function ($i) {
            $due  = (float) ($i->due_amount ?? 0);
            $paid = (float) ($i->payment_amount ?? 0);
            return $paid >= $due && $due > 0;
        })->values();

        // الأقساط المتبقية: due_amount > payment_amount
        $remainingInstallments = $items->filter(function ($i) {
            $due  = (float) ($i->due_amount ?? 0);
            $paid = (float) ($i->payment_amount ?? 0);
            return $due > $paid;
        })->values();

        // الأقساط التي فيها أي مبلغ مدفوع: payment_amount > 0
        $paidInstallments = $items->filter(function ($i) {
            return (float) ($i->payment_amount ?? 0) > 0;
        })->values();

        // العدّادات
        $countPaidFully = $fullyPaidInstallments->count();
        $countRemaining = $remainingInstallments->count();

        // رمز العملة
        $currency = 'ر.س';

        // تجهيز عناصر العرض للـ Blade بالمفاتيح الموحدة
        $paidInstallmentsView = $paidInstallments->map(function ($i) {
            $due  = (float) ($i->due_amount ?? 0);
            $paid = (float) ($i->payment_amount ?? 0);
            return [
                'id'          => $i->id,
                'amount'      => $due,                              // due_amount
                'paid_amount' => $paid,                             // payment_amount
                'still_due'   => max(0.0, $due - $paid),
                'due_date'    => optional($i->due_date)->toDateString(),
                'paid_at'     => optional($i->payment_date)->toDateString(), // payment_date (cast: date)
                'note'        => $i->notes ?? null,                 // notes
            ];
        });

        return view('contracts.paid', [
            'contract'              => $contract,
            'setting'               => $setting,
            'logoUrl'               => $logoUrl,
            'brandName'             => $brandName,
            'currency'              => $currency,

            // إجماليات
            'contractTotal'         => round($contractTotal, 2),
            'totalPaid'             => round($totalPaid, 2),
            'remaining'             => round($remaining, 2),

            // العدّادات
            'countPaidFully'        => $countPaidFully,
            'countRemaining'        => $countRemaining,

            // القوائم للعرض
            'paidInstallments'      => $paidInstallmentsView,   // أقساط فيها أي مبلغ مدفوع
            'fullyPaidInstallments' => $fullyPaidInstallments,
            'remainingInstallments' => $remainingInstallments,
        ]);
    }

}

