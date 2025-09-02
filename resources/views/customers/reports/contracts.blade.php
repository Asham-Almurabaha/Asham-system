{{-- resources/views/reports/customers_and_contracts.blade.php --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <title>@lang('reports.Customers and Contracts Report')</title>
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
    body { background:#fff; margin:0; padding:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }

    /* إعداد A4 للطباعة */
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

    /* واترمارك الشعار */
    .watermark {
      position: absolute; inset: 0;
      display:flex; align-items:center; justify-content:center;
      opacity:.07; z-index:0; pointer-events:none;
    }
    .watermark img { max-width:70%; max-height:70%; transform: rotate(-15deg); }

    /* الطباعة متعددة الصفحات */
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }

    @media print {
      .no-print { display: none !important; }
      .page { box-shadow:none !important; margin:0; padding:10mm; }
      a[href]:after { content: ""; }
    }
  </style>
</head>
<body>
@php
  $rows      = $rows ?? collect();

  // شعار واسم المنشأة (نفس منطق البرنت)
  $logoUrl   = $logoUrl   ?? (!empty($setting?->logo) ? asset('storage/'.$setting->logo) : asset('assets/img/logo.png'));
  $brandName = $brandName ?? ($setting?->name_ar ?? $setting?->name ?? config('app.name','اسم المنشأة'));
@endphp

<div class="page shadow-sm">
  {{-- Watermark --}}
  <div class="watermark">
    <img src="{{ $logoUrl }}" alt="Logo">
  </div>

  <div class="content">
    {{-- Header موحّد --}}
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
      <div class="d-flex align-items-center gap-2">
        <img src="{{ $logoUrl }}" alt="Logo" style="height:40px">
        <h5 class="mb-0 fw-bold">{{ $brandName }}</h5>
      </div>
      <div class="text-end">
        <h6 class="mb-0 fw-bold">@lang('reports.Customers and Contracts Report')</h6>
        <div class="small-muted">@lang('app.Date'): {{ now()->format('d-m-Y') }}</div>
      </div>
    </div>

    {{-- الجدول --}}
    <div class="table-responsive">
      <table class="table table-striped table-bordered text-center align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:56px">#</th>
            <th class="text-start">{{ __('Customer') }}</th>
            <th>{{ __('Total Contracts') }}</th>
            <th>{{ __('Active Contracts') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $i => $c)
            <tr>
              <td>{{ $i + 1 }}</td>
              <td class="text-start">{{ $c->name }}</td>
              <td>{{ $c->contracts_count }}</td>
              <td>{{ $c->active_contracts }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="py-5 text-muted">@lang('reports.No data available.')</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- الأزرار (لا تُطبع) --}}
    <div class="no-print d-flex justify-content-end gap-2 mt-3">
      <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">↩ @lang('app.Back')</a>
      <button class="btn btn-primary" onclick="window.print()">🖨 @lang('app.Print')</button>
    </div>
  </div>
</div>
</body>
</html>
