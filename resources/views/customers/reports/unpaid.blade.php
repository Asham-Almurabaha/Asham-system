{{-- resources/views/reports/unpaid_customers.blade.php --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <title>@lang('reports.Unpaid Customers This Month Report')</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  {{-- Favicon (اختياري) --}}
  @if(!empty($setting?->favicon))
    <link rel="icon" href="{{ asset('storage/'.$setting->favicon) }}">
  @endif

  {{-- Bootstrap 5: RTL/LTR تلقائي --}}
  @if(app()->getLocale() === 'ar')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  @else
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  @endif
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    /* خط/خلفية عامة */
    body { background:#fff; margin:0; padding:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }

    /* ضبط صفحة طباعة A4 */
    @page { size: A4; margin: 0; }

    .page {
      width: 210mm;
      min-height: 297mm;
      margin: auto;
      padding: 12mm;
      background: #fff;
      position: relative;
      box-sizing: border-box;
    }

    .content { position: relative; z-index: 1; }
    .small-muted { font-size:.9rem; color:#6c757d; }

    /* واترمارك */
    .watermark {
      position: absolute; inset: 0;
      display:flex; align-items:center; justify-content:center;
      opacity:.07; z-index:0; pointer-events:none;
    }
    .watermark img { max-width:70%; max-height:70%; transform: rotate(-15deg); }

    /* الجدول والطباعة متعددة الصفحات */
    table { width:100%; border-collapse: collapse; }
    thead { display: table-header-group; } /* تكرار العناوين في كل صفحة */
    tr { page-break-inside: avoid; }
    .kpi .card { box-shadow:none; }

    @media print {
      .no-print { display: none !important; }
      .page { box-shadow:none !important; margin:0; padding:10mm; }
      a[href]:after { content: ""; } /* منع إضافة الروابط بعد النص */
    }
  </style>
</head>
<body>
@php
  // إعدادات عامة
  $rows      = $rows ?? collect();
  $count     = $rows->count();

  $logoUrl   = $logoUrl   ?? (!empty($setting?->logo) ? asset('storage/'.$setting->logo) : asset('assets/img/logo.png'));
  $brandName = $brandName ?? ($setting?->name_ar ?? $setting?->name ?? config('app.name','اسم المنشأة'));
@endphp

<div class="page shadow-sm">
  {{-- Watermark بالشعار (اختياري) --}}
  <div class="watermark">
    <img src="{{ $logoUrl }}" alt="Logo">
  </div>

  <div class="content">
    {{-- الهيدر بنفس نمط البرنت --}}
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
      <div class="d-flex align-items-center gap-2">
        <img src="{{ $logoUrl }}" alt="Logo" style="height:40px">
        <h5 class="mb-0 fw-bold">{{ $brandName }}</h5>
      </div>
      <div class="text-end">
        <h6 class="mb-0 fw-bold">@lang('reports.Unpaid Customers This Month Report')</h6>
        <div class="small-muted">@lang('app.Date'): {{ now()->format('d-m-Y') }}</div>
      </div>
    </div>

    {{-- KPIs (بنمط بطاقات خفيفة) --}}
    <div class="row g-3 kpi mb-4">
      <div class="col-12 col-md-4">
        <div class="card">
          <div class="card-body p-3 text-center">
            <div class="small-muted">@lang('reports.Number of Customers')</div>
            <div class="fs-4 fw-bold">{{ number_format($count) }}</div>
          </div>
        </div>
      </div>
      {{-- ممكن تضيف بطاقات أخرى هنا (مثلاً إجمالي المبالغ المتأخرة) --}}
    </div>

    {{-- الجدول --}}
    <div class="table-responsive">
      <table class="table table-striped table-bordered text-center align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:56px">#</th>
            <th class="text-start">{{ __('Customer') }}</th>
            <th>{{ __('Phone') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $i => $c)
            <tr>
              <td>{{ $i + 1 }}</td>
              <td class="text-start">{{ $c->name }}</td>
              <td>{{ $c->phone }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="py-5 text-muted">@lang('reports.No data available.')</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- الأزرار (لا تُطبع) --}}
    <div class="no-print d-flex justify-content-end gap-2 mt-3">
      <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">↩ @lang('app.Back')</a>
      <a href="{{ url()->current() }}" class="btn btn-outline-secondary">↺ @lang('app.Refresh')</a>
      <button class="btn btn-primary" onclick="window.print()">🖨 @lang('app.Print')</button>
    </div>
  </div>
</div>
</body>
</html>
