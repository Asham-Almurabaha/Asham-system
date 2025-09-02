{{-- resources/views/investors/statement.blade.php --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <title>@lang('app.Withdrawals Summary') — {{ $investor->name }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  {{-- Favicon (اختياري) --}}
  @if(!empty($setting?->favicon))
    <link rel="icon" href="{{ asset('storage/'.$setting->favicon) }}">
  @endif

  {{-- Bootstrap RTL/LTR تلقائي --}}
  @if(app()->getLocale() === 'ar')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  @else
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  @endif
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    body { background:#fff; margin:0; padding:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .small-muted { font-size:.9rem; color:#6c757d; }

    /* إعداد ورقة A4 للطباعة */
    @page { size: A4; margin: 0; }
    .page {
      width:210mm; min-height:297mm; margin:auto; padding:12mm;
      background:#fff; position:relative; box-sizing:border-box;
    }
    .content { position:relative; z-index:1; }

    /* واترمارك الشعار */
    .watermark{
      position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
      opacity:.07; z-index:0; pointer-events:none;
    }
    .watermark img{ max-width:70%; max-height:70%; transform:rotate(-15deg); }

    /* تكرار رأس الجدول بكل صفحة */
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }

    @media print{
      .no-print { display:none !important; }
      .page { box-shadow:none !important; margin:0; padding:10mm; }
      a[href]:after { content:""; }
    }
  </style>
</head>
<body>
@php
  $cs = $data['currencySymbol'] ?? 'ر.س';

  // إجماليات من الخدمة (للعقود النشطة)
  $totalCapitalShare      = (float)($data['totalCapitalShare']      ?? 0);
  $totalProfitNet         = (float)($data['totalProfitNet']         ?? 0);
  $totalPaidPortion       = (float)($data['totalPaidPortionToInvestor'] ?? 0);
  $totalRemainingOnCust   = (float)($data['totalRemainingOnCustomers']  ?? 0);

  // إجماليات كل العقود (نشط + منتهي)
  $totalCapitalShareAll   = (float)($data['totalCapitalShareAll'] ?? 0);
  $totalProfitNetAll      = (float)($data['totalProfitNetAll']    ?? 0);

  // عدادات
  $contractsTotal         = (int)($data['contractsTotal']  ?? 0);
  $contractsActive        = (int)($data['contractsActive'] ?? 0);
  $contractsEnded         = (int)($data['contractsEnded']  ?? 0);

  // السيولة الحالية ورأس المال
  $liquidity              = (float)($data['liquidity'] ?? 0);
  $initialCapital         = (float)($data['initialCapital'] ?? 0);

  // الرصيد المتوقع
  $total                  = $liquidity + $totalRemainingOnCust;

  // تفصيل العقود
  $rows                   = $data['contractBreakdown'] ?? [];

  // شعار واسم المنشأة (مثل باقي تقارير البرنت)
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
        <div>
          <h5 class="mb-0 fw-bold">{{ $brandName }}</h5>
          <div class="small-muted">@lang('app.Investor'): <strong>{{ $investor->name }}</strong></div>
        </div>
      </div>
      <div class="text-end">
        <h6 class="mb-0 fw-bold">@lang('app.Withdrawals Summary')</h6>
        <div class="small-muted">التاريخ: {{ now()->format('d-m-Y') }}</div>
      </div>
    </div>

    {{-- KPIs الأساسية --}}
    <div class="row g-3 kpi mb-4">
      <div class="col-6 col-md-3">
        <div class="card"><div class="card-body p-3">
          <div class="small-muted">{{ __('Contracts') }} ({{ $contractsTotal }})</div>
          <div class="fs-6">سارية: <strong>{{ $contractsActive }}</strong> — منتهية: <strong>{{ $contractsEnded }}</strong></div>
        </div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card"><div class="card-body p-3">
          <div class="small-muted">رأس المال الابتدائي</div>
          <div class="fs-6 fw-bold">{{ number_format($initialCapital,2) }} <span class="small-muted">{{ $cs }}</span></div>
        </div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card"><div class="card-body p-3">
          <div class="small-muted">السيولة الحالية</div>
          <div class="fs-6 fw-bold">{{ number_format($liquidity,2) }} <span class="small-muted">{{ $cs }}</span></div>
        </div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card"><div class="card-body p-3">
          <div class="small-muted">المتبقي على العملاء</div>
          <div class="fs-6 fw-bold">{{ number_format(max(0,$totalRemainingOnCust),2) }} <span class="small-muted">{{ $cs }}</span></div>
        </div></div>
      </div>
      <div class="col-12 col-md-6">
        <div class="card"><div class="card-body p-3">
          <div class="small-muted">الرصيد المتوقع بعد انتهاء الأقساط</div>
          <div class="fs-5 fw-bold">{{ number_format(max(0,$total),2) }} <span class="small-muted">{{ $cs }}</span></div>
        </div></div>
      </div>
    </div>

    {{-- KPIs إضافية (الكل/النشطة) --}}
    <div class="row g-3 kpi mb-3">
      <div class="col-6 col-md-3">
        <div class="card"><div class="card-body p-3">
          <div class="small-muted">{{ __('Capital (participating in all contracts)') }}</div>
          <div class="fs-6 fw-bold">{{ number_format($totalCapitalShareAll,2) }} <span class="small-muted">{{ $cs }}</span></div>
        </div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card"><div class="card-body p-3">
          <div class="small-muted">{{ __('Net profit (from all contracts)') }}</div>
          <div class="fs-6 fw-bold">{{ number_format($totalProfitNetAll,2) }} <span class="small-muted">{{ $cs }}</span></div>
        </div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card"><div class="card-body p-3">
          <div class="small-muted">{{ __('Capital (active contracts)') }}</div>
          <div class="fs-6 fw-bold">{{ number_format($totalCapitalShare,2) }} <span class="small-muted">{{ $cs }}</span></div>
        </div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card"><div class="card-body p-3">
          <div class="small-muted">{{ __('Net profit (active contracts)') }}</div>
          <div class="fs-6 fw-bold">{{ number_format($totalProfitNet,2) }} <span class="small-muted">{{ $cs }}</span></div>
        </div></div>
      </div>
    </div>

    {{-- جدول تفصيل العقود --}}
    <div class="table-responsive">
      <table class="table table-striped table-bordered align-middle text-center">
        <thead class="table-light">
          <tr>
            <th style="width:56px">#</th>
            <th class="text-start">العقد</th>
            <th>الحالة</th>
            <th class="text-start">العميل</th>
            <th>نسبة المشاركة %</th>
            <th>رأس المال</th>
            <th>الربح الصافي</th>
            <th>المحصل للمستثمر</th>
            <th>المتبقي على العملاء</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $i => $row)
            @php
              $statusTxt = $statusMap[$row['contract_id']] ?? '—';
              $badge = 'bg-secondary';
              $s = (string)$statusTxt;
              if (str_contains($s,'ساري') || str_contains($s,'نشط')) $badge = 'bg-success';
              if (str_contains($s,'غلق') || str_contains(strtolower($s),'closed')) $badge = 'bg-danger';
            @endphp
            <tr>
              <td>{{ $i+1 }}</td>
              <td class="text-start">#{{ $row['contract_id'] }}</td>
              <td><span class="badge {{ $badge }} badge-status">{{ $statusTxt }}</span></td>
              <td class="text-start">{{ $row['customer'] }}</td>
              <td>{{ number_format($row['share_pct'] ?? 0, 2) }}</td>
              <td>{{ number_format($row['share_value'] ?? 0, 2) }} <span class="small-muted">{{ $cs }}</span></td>
              <td>{{ number_format($row['profit_net'] ?? 0, 2) }} <span class="small-muted">{{ $cs }}</span></td>
              <td>{{ number_format($row['paid_to_investor_from_customer'] ?? 0, 2) }} <span class="small-muted">{{ $cs }}</span></td>
              <td>{{ number_format(max(0, $row['remaining_on_customers'] ?? 0), 2) }} <span class="small-muted">{{ $cs }}</span></td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="py-5 text-muted">{{ __('No active contracts linked to this investor.') }}</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- أزرار (لا تُطبع) --}}
    <div class="no-print text-end mt-3 d-flex justify-content-end gap-2">
      <a href="{{ route('investors.show', $investor) }}" class="btn btn-outline-secondary">↩ @lang('app.Back')</a>
      <button class="btn btn-primary" onclick="window.print()">🖨 @lang('app.Print')</button>
    </div>
  </div>
</div>
</body>
</html>
