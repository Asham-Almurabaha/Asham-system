@extends('layouts.print-portrait')

@section('title', 'عقد رقم ' . ($contract->contract_number ?? $contract->id))
@section('report_title', 'تفاصيل عقد')

@php
  $ownerName = $ownerName ?? ($setting?->owner_name ?? 'المالك');

  // اليوم والتواريخ (مع احتياطي)
  $weekdayMap = ['Saturday'=>'السبت','Sunday'=>'الأحد','Monday'=>'الاثنين','Tuesday'=>'الثلاثاء','Wednesday'=>'الأربعاء','Thursday'=>'الخميس','Friday'=>'الجمعة'];
  $weekdayAr  = $weekdayAr ?? ($contract->start_date ? ($weekdayMap[$contract->start_date->format('l')] ?? '') : '');

  $gregDate              = optional($contract->start_date)->format('Y/m/d');
  $firstInstallmentGreg  = optional($contract->first_installment_date)->format('Y/m/d');
  $hijriDate             = $hijriDate             ?? '-';
  $firstInstallmentHijri = $firstInstallmentHijri ?? ($contract->first_installment_date ? $hijriDate : '-');
@endphp

@section('header_right')
  <h6 class="mb-0 fw-bold">رقم العقد</h6>
  <div class="small-muted"># {{ $contract->contract_number }}</div>
@endsection

@section('content')
<div class="text-center fw-bold fs-4 mb-3">عقد بيع أقساط</div>
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
    <div class="line"><strong>أطراف العقد:</strong></div>
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

  {{-- Clauses --}}
  <div class="mb-3 clauses">
    <h4 class="text-center fw-bold mb-3">بنود العقد</h4>
    <ol>
        <li>
          سُلِّف العميل مبلغًا وقدره (<strong>{{ number_format($contract->total_value, 2) }}</strong> ريال)،
          على أن يكون السداد على دفعات {{ optional($contract->installmentType)->name ?? '—' }}
          قيمة كل دفعة (<strong>{{ number_format($contract->installment_value, 2) }}</strong> ريال)
          ابتداءً من تاريخ <strong>{{ $firstInstallmentGreg ?: ($gregDate ?: '—') }}</strong>
          <span class="text-muted">- هجري {{ $firstInstallmentHijri }}</span>
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
  <a href="{{ route('contracts.show' , $contract) }}" class="btn btn-outline-secondary">↩ @lang('app.Back')</a>
@endsection

