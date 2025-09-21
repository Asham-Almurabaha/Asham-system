<!DOCTYPE html>
@php
  $locale = app()->getLocale();
  $localeRoot = strtolower(strtok($locale, '_'));
  $rtlLocales = ['ar', 'he'];
  $isRtl = in_array($localeRoot, $rtlLocales, true);
@endphp
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  @php
    $reportLabel = trim(__('reports.Report'));
    if ($reportLabel === 'reports.Report') {
        $reportLabel = trim(__('app.Report'));
    }

    $rawTitle       = trim((string) $__env->yieldContent('title'));
    $rawReportTitle = trim((string) $__env->yieldContent('report_title'));
    $effectiveTitle = $rawTitle !== '' ? $rawTitle : $rawReportTitle;

    if ($effectiveTitle !== '') {
        $effectiveTitle = trim($effectiveTitle);

        if (\Illuminate\Support\Str::startsWith($effectiveTitle, $reportLabel)) {
            $afterLabel    = \Illuminate\Support\Str::after($effectiveTitle, $reportLabel);
            $effectiveTitle = preg_replace('/^[\s\-:ـ–—]+/u', '', $afterLabel) ?? '';
        }
    }

    $documentTitle = $reportLabel.($effectiveTitle !== '' ? ' - '.$effectiveTitle : '');

    $faviconPath = $setting->favicon ?? null;
    $faviconUrl  = $faviconPath ? asset('storage/'.$faviconPath) : asset('assets/img/favicon.png');
  @endphp

  <meta charset="utf-8">
  <title>{{ $documentTitle }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="icon" href="{{ $faviconUrl }}" type="image/x-icon">

  {{-- Bootstrap RTL/LTR تلقائي --}}
  @if($isRtl)
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  @else
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  @endif
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  @stack('styles')

  <style>
    /* إجبار الطباعة A4 عمودي دائماً */
    @page {
      size: 210mm 297mm; /* يعادل A4 Portrait ويجبر PDF على نفس الاتجاه */
      margin: 0;
    }

    html, body { background:#fff; margin:0; padding:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }

    /* مساحة الصفحة (Portrait ثابت) */
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
      html, body { width: 210mm; height: 297mm; }
      .no-print { display: none !important; }
      .page { box-shadow:none !important; margin:0; padding:10mm; }
      a[href]:after { content: ""; }
    }
  </style>
</head>
@php
  // إعدادات افتراضية
  $logoUrl    = $logoUrl    ?? (!empty($setting?->logo) ? asset('storage/'.$setting->logo) : asset('assets/img/logo.png'));
  $brandName  = $brandName  ?? ($setting?->name_ar ?? $setting?->name ?? config('app.name','اسم المنشأة'));
  $reportDate = $reportDate ?? now()->format('d-m-Y');
@endphp
<body>
  @php
    $baseName  = $setting?->name ?? ($brandName ?? config('app.name',''));
    $brandName = app()->getLocale()==='ar' ? ($setting?->name_ar ?? $baseName) : ($setting?->name_en ?? $baseName);
  @endphp
  <div class="page shadow-sm">
    {{-- Watermark (يمكن إلغاؤها بعمل section فارغ) --}}
    @hasSection('watermark')
      <div class="watermark">@yield('watermark')</div>
    @else
      <div class="watermark"><img src="{{ $logoUrl }}" alt="Logo"></div>
    @endif

    <div class="content">
      {{-- Header موحّد --}}
      <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
        <div class="d-flex align-items-center gap-2">
          @hasSection('header_left')
            @yield('header_left')
          @else
            <img src="{{ $logoUrl }}" alt="Logo" style="height:40px">
            <h5 class="mb-0 fw-bold">{{ $brandName }}</h5>
          @endif
        </div>
        <div class="text-end">
          @hasSection('header_right')
            @yield('header_right')
          @else
            <h6 class="mb-0 fw-bold">@yield('report_title', __('reports.Report'))</h6>
            <div class="small-muted">@lang('app.Date'): {{ $reportDate }}</div>
          @endif
        </div>
      </div>

      {{-- المحتوى --}}
      @yield('content')

      {{-- الأزرار (لا تُطبع) --}}
      <div class="no-print d-flex justify-content-end gap-2 mt-3">
        @yield('actions')
        <x-button.print variant="primary" onclick="window.print()">🖨 @lang('app.Print')</x-button.print>
      </div>
    </div>
  </div>

  @stack('scripts')
</body>
</html>
