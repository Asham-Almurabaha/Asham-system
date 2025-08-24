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
        // حمّل العلاقات المطلوبة
        $contract->load([
            'customer',
            'installments' => fn($q) => $q->orderBy('due_date'),
        ]);

        // إعدادات (شعار + اسم)
        $setting = Setting::first();

        $logoUrl = $setting && $setting->logo
            ? asset('storage/'.$setting->logo)
            : asset('assets/img/logo.png');

        $brandName = $setting?->name_ar
            ?? $setting?->name
            ?? config('app.name', 'اسم المنشأة');

        // الأقساط المسددة فقط (بأي قيمة مدفوعة)
        $paidInstallments = $contract->installments->filter(function ($i) {
            return (float) ($i->paid_amount ?? 0) > 0
                || !empty($i->paid_at);
        })->values();

        // الإجماليات
        $contractTotal = (float) ($contract->installments->sum('amount') ?: ($contract->total_value ?? 0));
        $totalPaid     = (float) $contract->installments->sum('paid_amount');
        $remaining     = max(0.0, $contractTotal - $totalPaid);

        // رمز العملة (عدّله لو احتجت)
        $currency = 'ر.س';

        return view('contracts.paid', [
            'contract'          => $contract,
            'setting'           => $setting,
            'logoUrl'           => $logoUrl,
            'brandName'         => $brandName,
            'currency'          => $currency,
            'paidInstallments'  => $paidInstallments,
            'contractTotal'     => $contractTotal,
            'totalPaid'         => $totalPaid,
            'remaining'         => $remaining,
        ]);
    }


}

