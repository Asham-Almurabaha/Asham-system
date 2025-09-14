@extends('layouts.print-portrait')

@section('title', 'مخالصة نهائية للعقد ' . ($contract->contract_number ?? $contract->id))

@php
  // نفس المنطق والقيم الافتراضية كما كانت
  $ownerName = $ownerName ?? ($setting?->owner_name ?? "البائع");

  // تواريخ اليوم/الأسبوع (fallback)
  $weekdayAr = $weekdayAr ?? '';
  $gregDate  = $gregDate  ?? optional(now())->format('Y/m/d');
  $hijriDate = $hijriDate ?? '?';

  // المجاميع
  $totalRequired  = (float)($contract->total_value ?? 0);
  $discountAmount = max(0, (float)($contract->discount_amount ?? 0));
  $totalPaid      = max(0, $totalRequired - $discountAmount);
  $remaining      = max(0, $totalRequired - $totalPaid - $discountAmount);
@endphp

@section('report_title', 'مخالصة نهائية')

@section('header_right')
  <h6 class="mb-0 fw-bold">مخالصة عقد رقم</h6>
  <div class="small-muted"># {{ $contract->contract_number }}</div>
@endsection

@section('content')
<div class="text-center fw-bold fs-4 mb-3">مخالصة نهائية</div>
  {{-- Dates --}}
  <div class="mb-3">
    <div class="line"><strong>اليوم:</strong> {{ $weekdayAr ?: '-' }}</div>
    <div class="line">
      <strong>التاريخ:</strong>
      ميلادي {{ $gregDate ?: '-' }}
      <span class="text-muted"> - هجري {{ $hijriDate ?: '-' }}</span>
    </div>
  </div>

  {{-- Parties --}}
  <div class="mb-3">
    <div class="line"><strong>حررت هذه المخالصة بين:</strong></div>
    <div class="line"><strong>البائع:</strong> {{$ownerName}}</div>
    <div class="line">
      <strong>العميل:</strong>
      @if($contract->customer)
        {{ $contract->customer->name ?? '-' }}
        <span class="text-muted"> - هوية/إقامة: {{ $contract->customer->national_id ?? '-' }}</span>
        <span class="text-muted"> - جوال: {{ $contract->customer->phone ?? '-' }}</span>
      @else
        -
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
    <div class="line"><strong>إجمالي قيمة العقد:</strong> {{ number_format($totalRequired, 2) }}</div>
    <div class="line"><strong>إجمالي الخصومات:</strong> {{ number_format($discountAmount ?? 0, 2) }}</div>
    <div class="line"><strong>إجمالي المدفوع:</strong> {{ number_format($totalPaid, 2) }}</div>
    <div class="line"><strong>المتبقي:</strong> {{ number_format($remaining, 2) }}</div>
  </div>

  {{-- Signatures --}}
  <div class="mb-3">
    <div class="row text-center signatures">
      <div class="col">
        <div><strong>البائع</strong><br>{{$ownerName}}</div>
        <div class="mt-4">التوقيع: ____________________</div>
      </div>
      <div class="col">
        <div><strong>العميل</strong><br>{{ $contract->customer->name ?? '-' }}</div>
        <div class="mt-4">التوقيع: ____________________</div>
      </div>
      @if($contract->guarantor)
        <div class="col">
          <div><strong>الكفيل</strong><br>{{ $contract->guarantor->name ?? '-' }}</div>
          <div class="mt-4">التوقيع: ____________________</div>
        </div>
      @endif
    </div>
  </div>
@endsection

@section('actions')
  <a href="{{ route('contracts.show' , $contract) }}" class="btn btn-outline-secondary">@lang('app.Back')</a>
@endsection
