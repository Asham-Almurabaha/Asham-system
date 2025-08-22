{{-- resources/views/contracts/closure.blade.php --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>مخالصة عقد رقم {{ $contract->contract_number }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  @if(!empty($setting?->favicon))
    <link rel="icon" href="{{ asset('storage/'.$setting->favicon) }}">
  @endif

  {{-- Bootstrap 5 RTL --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

  <style>
    body { font-family:"Tahoma", Arial, sans-serif; background:#fff; margin:0; padding:0; }

    /* إجبار حجم الطباعة A4 وهوامش صفر */
    @page { size: A4; margin: 0; }

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
      position:absolute; inset:0;
      display:flex; align-items:center; justify-content:center;
      opacity:0.07; z-index:0; pointer-events:none;
    }
    .watermark img { max-width:70%; max-height:70%; transform:rotate(-15deg); }
    .content { position:relative; z-index:1; }

    .line { margin: .35rem 0; }
    .section-title { font-weight:700; margin: .75rem 0 .5rem; }
    .signatures .col { padding-top:8px; border-top:1px dashed #bbb; }

    @media print {
      .no-print { display:none !important; }
      body { margin:0; }
      .page { margin:0; padding:12mm; box-shadow:none; }
      a { color:inherit; text-decoration:none; }
    }
  </style>
</head>
<body>
@php
  // شعار واسم المنشأة (fallback لو لم تُمرّر من الكنترولر)
  $logoUrl   = $logoUrl   ?? (!empty($setting?->logo) ? asset('storage/'.$setting->logo) : asset('assets/img/logo.png'));
  $brandName = $brandName ?? ($setting?->name_ar ?? $setting?->name ?? config('app.name','اسم المنشأة'));
  $ownerName = $ownerName ?? ($setting?->owner_name ?? "اسم البائع");

  // تواريخ/يوم (fallback)
  $weekdayAr = $weekdayAr ?? '';
  $gregDate  = $gregDate  ?? optional(now())->format('Y/m/d');
  $hijriDate = $hijriDate ?? '—';

  // المبالغ
  $totalRequired  = (float)($contract->total_value ?? 0);              // إجمالي قيمة العقد
  $discountAmount = max(0, (float)($contract->discount_amount ?? 0));  // إجمالي الخصومات (إن وجدت)
  // المدفوع = إجمالي العقد - الخصومات (مع عدم السماح بالسالب)
  $totalPaid      = max(0, $totalRequired - $discountAmount);
  // الرصيد المتبقي بعد المخالصة (متوقع صفر إذا كانت مخالصة نهائية)
  $remaining      = max(0, $totalRequired - $totalPaid - $discountAmount);
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
      <h5 class="mb-0 fw-bold">مخالصة عقد رقم: {{ $contract->contract_number }}</h5>
    </div>

    {{-- Title --}}
    <div class="text-center fw-bold fs-4 mb-3">مخالصة نهائية لسداد سلفة</div>

    {{-- Dates --}}
    <div class="mb-3">
      <div class="line"><strong>إنه في يوم:</strong> {{ $weekdayAr ?: '—' }}</div>
      <div class="line">
        <strong>التاريخ:</strong>
        ميلادي {{ $gregDate ?: '—' }}
        <span class="text-muted"> — هجري {{ $hijriDate }}</span>
      </div>
    </div>

    {{-- Parties --}}
    <div class="mb-3">
      <div class="line"><strong>تم تحرير هذه المخالصة بين:</strong></div>
      <div class="line"><strong>البائع: </strong>{{$ownerName}}</div>
      <div class="line">
        <strong>العميل:</strong>
        @if($contract->customer)
          {{ $contract->customer->name ?? '—' }}
          <span class="text-muted"> — هوية/إقامة: {{ $contract->customer->national_id ?? '—' }}</span>
          <span class="text-muted"> — جوال: {{ $contract->customer->phone ?? '—' }}</span>
        @else
          —
        @endif
      </div>
    </div>

    {{-- Statement --}}
    <div class="mb-3">
      <h5 class="text-center fw-bold mb-3">نص المخالصة</h5>

      <p class="mb-2">
        يقر البائع بأنه قد استلم كامل مستحقاته المالية المترتبة بموجب العقد رقم
        (<strong>{{ $contract->contract_number }}</strong>) المبرم مع العميل، والبالغة
        (<strong>{{ number_format($totalRequired, 2) }}</strong> ريال)،
        حيث بلغ إجمالي ما تم سداده فعليًا
        (<strong>{{ number_format($totalPaid, 2) }}</strong> ريال)،
        وكان إجمالي الخصومات
        (<strong>{{ number_format($discountAmount, 2) }}</strong> ريال).
        وبذلك تكون ذمت الطرف العميل بريئة تجاه البائع من أي مطالبات مالية متعلقة بهذا العقد حتى تاريخه.
      </p>

      <p class="mb-2">
        تُعتبر هذه المخالصة نهائية ونافذة اعتبارًا من تاريخها، وتشمل أصل الدين وأي التزامات أو مطالبات ناشئة
        عن العقد المذكور.
      </p>
    </div>

    {{-- Totals --}}
    <div class="mb-3">
      <div class="line"><strong>إجمالي قيمة العقد:</strong> {{ number_format($totalRequired, 2) }} ريال</div>
      <div class="line"><strong>إجمالي الخصومات:</strong> {{ number_format($discountAmount, 2) }} ريال</div>
      <div class="line"><strong>إجمالي المدفوع:</strong> {{ number_format($totalPaid, 2) }} ريال</div>
      <div class="line"><strong>الرصيد المتبقي:</strong> {{ number_format($remaining, 2) }} ريال</div>
    </div>

    {{-- Signatures --}}
    <div class="mb-3">
      <div class="row text-center signatures">
        <div class="col">
          <div><strong>البائع</strong><br>{{$ownerName}}</div>
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

    {{-- Buttons --}}
    <div class="no-print d-flex justify-content-end gap-2">
      <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary">↩ رجوع للقائمة</a>
      <button onclick="window.print()" class="btn btn-success">✅ طباعة المخالصة</button>
    </div>
  </div>
</div>
</body>
</html>
