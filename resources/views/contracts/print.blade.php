{{-- resources/views/contracts/print.blade.php --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>طباعة عقد رقم {{ $contract->contract_number }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  {{-- Favicon --}}
  @if(!empty($setting?->favicon))
    <link rel="icon" href="{{ asset('storage/'.$setting->favicon) }}">
  @endif

  {{-- Bootstrap 5 --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

  <style>
    body {
      font-family:"Tahoma", Arial, sans-serif;
      background:#fff;
      margin:0;
      padding:0;
    }

    /* تعريف حجم الورقة A4 للطباعة */
    @page {
      size: A4;
      margin: 0;
    }

    .page {
      width: 210mm;
      min-height: 297mm;
      margin: auto;
      padding: 15mm;
      background: #fff;
      position: relative;
      box-sizing: border-box;
    }

    .watermark {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0.07;
      z-index: 0;
      pointer-events: none;
    }
    .watermark img {
      max-width: 70%;
      max-height: 70%;
      transform: rotate(-15deg);
    }

    .content { position: relative; z-index: 1; }
    .line { margin: 4px 0; }
    .clauses ol { padding-start: 1.5rem; }
    .clauses li { margin: 6px 0; }
    .signatures .col { border-top: 1px dashed #bbb; padding-top: 8px; }

    @media print {
      .no-print { display: none !important; }
      body { margin: 0; }
      .page {
        box-shadow: none !important;
        margin: 0;
        padding: 12mm;
      }
    }
  </style>
</head>
<body>
@php
  $logoUrl   = $logoUrl   ?? (!empty($setting?->logo) ? asset('storage/'.$setting->logo) : asset('assets/img/logo.png'));
  $brandName = $brandName ?? ($setting?->name_ar ?? $setting?->name ?? config('app.name','اسم المنشأة'));
  $ownerName = $ownerName ?? ($setting?->owner_name ?? "اسم البائع");

  $weekdayMap = ['Saturday'=>'السبت','Sunday'=>'الأحد','Monday'=>'الاثنين','Tuesday'=>'الثلاثاء','Wednesday'=>'الأربعاء','Thursday'=>'الخميس','Friday'=>'الجمعة'];
  $weekdayAr  = $weekdayAr ?? ($contract->start_date ? ($weekdayMap[$contract->start_date->format('l')] ?? '') : '');

  $gregDate              = optional($contract->start_date)->format('Y/m/d');
  $firstInstallmentGreg  = optional($contract->first_installment_date)->format('Y/m/d');
  $hijriDate             = $hijriDate             ?? '—';
  $firstInstallmentHijri = $firstInstallmentHijri ?? ($contract->first_installment_date ? $hijriDate : '—');
@endphp

<div class="page shadow-sm">
  {{-- Watermark --}}
  <div class="watermark"><img src="{{ $logoUrl }}" alt="Logo"></div>

  <div class="content">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
      <div class="d-flex align-items-center gap-2">
        <img src="{{ $logoUrl }}" alt="Logo" style="height:48px">
        <h5 class="mb-0 fw-bold">{{ $brandName }}</h5>
      </div>
      <h5 class="mb-0 fw-bold">عقد رقم: {{ $contract->contract_number }}</h5>
    </div>

    {{-- Document Title --}}
    <div class="text-center fw-bold fs-4 mb-3">عقد بيع أقساط</div>

    {{-- Dates --}}
    <div class="mb-3">
      <div class="line"><strong>إنه في يوم:</strong> {{ $weekdayAr ?: '—' }}</div>
      <div class="line">
        <strong>التاريخ:</strong>
        ميلادي {{ $gregDate ?: '—' }}
        <span class="text-muted">— هجري {{ $hijriDate }}</span>
      </div>
    </div>

    {{-- Parties --}}
    <div class="mb-3">
      <div class="line"><strong>تم الاتفاق بين كلا من:</strong></div>
      <div class="line"><strong>البائع: </strong>{{$ownerName}}</div>
      <div class="line">
        <strong>العميل:</strong>
        @if($contract->customer)
          {{ $contract->customer->name ?? '—' }}
          <span class="text-muted">— هوية/إقامة: {{ $contract->customer->national_id ?? '—' }}</span>
          <span class="text-muted">— جوال: {{ $contract->customer->phone ?? '—' }}</span>
        @else
          —
        @endif
      </div>
    </div>

    {{-- Clauses --}}
    <div class="mb-3 clauses">
      <h4 class="text-center fw-bold mb-3">بنود العقد</h4>
      <ol>
        <li>
          سُلِّف العميل مبلغًا وقدره (<strong>{{ number_format($contract->total_value, 2) }}</strong> ريال)،
          على أن يكون السداد على دفعات {{ optional($contract->installmentType)->name ?? '—' }}
          قيمة كل دفعة (<strong>{{ number_format($contract->installment_value, 2) }}</strong> ريال)
          ابتداءً من تاريخ <strong>{{ $firstInstallmentGreg ?: ($gregDate ?: '—') }}</strong>
          <span class="text-muted">/ هجري {{ $firstInstallmentHijri }}</span>
          بعدد (<strong>{{ number_format($contract->installments_count) }}</strong>) دفعة.
        </li>
        <li>في حال عدم السداد يتحمّل العميل أتعاب المحامي المشار إليها في البند السابع.</li>
        <li>يلتزم العميل بسداد المبلغ الموضّح أعلاه دون مماطلة أو تأخير عن المواعيد المحددة للدفعات.</li>
        <li>في حال عدم سداد دفعتين متتاليتين أو متباعدتين يحق للبائع المطالبة بكامل المديونية دفعة واحدة.</li>
        <li>لا يحق للعميل الاعتراض أو تقديم الأعذار بعد توقيع العقد.</li>
        <li>اتفق الطرفان في حال النزاع أن تكون الدعوى في محاكم الرياض دون غيرها طبقًا للنظام.</li>
        <li>
          أتعهد أنا المشتري بتسديد مبلغ 
          (<strong>{{ number_format($contract->total_value, 2) }}</strong> ريال) 
          للمحاماة أو مكتب تحصيل الديون في حالة عدم الالتزام بالبنود الموضحة أعلاه.
        </li>
      </ol>
    </div>

    {{-- Guarantor --}}
    @if($contract->guarantor)
    <div class="mb-3">
      <h5 class="text-center fw-bold mb-3">إقرار بالكفالة الحضورية والغرامية</h5>
      <div class="line">
        أقر أنا 
        <strong>الكفيل:</strong>
        {{ $contract->guarantor->name ?? '—' }}
        <span class="text-muted">— هوية/إقامة: {{ $contract->guarantor->national_id ?? '—' }}</span>
        <span class="text-muted">— جوال: {{ $contract->guarantor->phone ?? '—' }}</span>
      </div>
      <div class="line">
        <strong>بأنني أكفل </strong>
        {{ $contract->customer->name ?? '—' }}
        <span class="text-muted">— هوية/إقامة: {{ $contract->customer->national_id ?? '—' }}</span>
        <span class="text-muted">— جوال: {{ $contract->customer->phone ?? '—' }}</span>
      </div>
      <div class="line">
        كفالة حضورية غرامية في مبلغ وقدره
        (<strong>{{ number_format($contract->total_value, 2) }}</strong> ريال)
        وفي حال عدم سداده ألتزم بجميع بنود العقد وتعويض البائع.
      </div>
    </div>
    @endif

    {{-- Signatures --}}
    <div class="mb-3">
      <div class="row text-center signatures">
        <div class="col">
          <div><strong>طرف أول</strong><br>{{$ownerName}}</div>
          <div class="mt-4">التوقيع: ____________________</div>
        </div>
        <div class="col">
          <div><strong>العميل</strong><br>{{ $contract->customer->name ?? '—' }}</div>
          <div class="mt-4">التوقيع: ____________________</div>
        </div>
         @if($contract->guarantor)
          <div class="col">
            <div><strong>الكفيل</strong><br>{{ $contract->guarantor->name ?? '—' }}</div>
            <div class="mt-4">التوقيع: ____________________</div>
          </div>
        @endif
        </div>
      </div>
    </div>

    {{-- Buttons --}}
    <div class="no-print d-flex justify-content-end gap-2">
      <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary">↩ رجوع</a>
      <button onclick="window.print()" class="btn btn-primary">🖨 طباعة</button>
    </div>
  </div>
</div>
</body>
</html>
