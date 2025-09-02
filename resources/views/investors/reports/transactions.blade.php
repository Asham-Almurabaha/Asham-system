{{-- resources/views/investors/transactions_summary.blade.php --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <title>@lang('reports.Deposits / Withdrawals Summary') — {{ $investor->name }}</title>
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

    /* A4 للطباعة */
    @page { size: A4; margin: 0; }
    .page {
      width:210mm; min-height:297mm; margin:auto; padding:12mm;
      background:#fff; position:relative; box-sizing:border-box;
    }
    .content { position:relative; z-index:1; }

    /* واترمارك الشعار */
    .watermark {
      position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
      opacity:.07; z-index:0; pointer-events:none;
    }
    .watermark img { max-width:70%; max-height:70%; transform: rotate(-15deg); }

    /* تكرار رأس الجدول في كل صفحة */
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }

    @media print {
      .no-print { display:none !important; }
      .page { box-shadow:none !important; margin:0; padding:10mm; }
      a[href]:after { content:""; }
    }
  </style>
</head>
<body>
@php
  use Illuminate\Support\Carbon;

  $cs = $currencySymbol ?? 'ر.س';

  // عناصر الصفحة الحالية (للعرض فقط)
  $items = $transactions instanceof \Illuminate\Pagination\LengthAwarePaginator
    ? $transactions->items()
    : (array) $transactions;

  // العدّ الكلي يأتي من الكنترولر
  $countAll = (int) ($transactionsCount ?? 0);

  // مجاميع الإيداعات/المسحوبات (مرسلة من الكنترولر)
  $depositsTotal    = (float) ($depositsTotal    ?? 0.0);
  $withdrawalsTotal = (float) ($withdrawalsTotal ?? 0.0);
  $netTotal         = $depositsTotal - $withdrawalsTotal; // الإيداعات - المسحوبات

  // نطاق التاريخ (اختياري)
  $from = request('from');
  $to   = request('to');

  // شعار واسم المنشأة (نفس منطق بقية تقارير البرنت)
  $logoUrl   = $logoUrl   ?? (!empty($setting?->logo) ? asset('storage/'.$setting->logo) : asset('assets/img/logo.png'));
  $brandName = $brandName ?? ($setting?->name_ar ?? $setting?->name ?? config('app.name','اسم المنشأة'));
@endphp

<div class="page shadow-sm">
  {{-- Watermark --}}
  <div class="watermark"><img src="{{ $logoUrl }}" alt="Logo"></div>

  <div class="content">
    {{-- Header موحّد --}}
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
      <div class="d-flex align-items-center gap-2">
        <img src="{{ $logoUrl }}" alt="Logo" style="height:40px">
        <div>
          <h5 class="mb-0 fw-bold">{{ $brandName }}</h5>
          <div class="small-muted">
            @lang('app.Investor'): <strong>{{ $investor->name }}</strong>
            @if($from || $to)
              — @lang('app.Period'):
              <strong>{{ $from ? Carbon::parse($from)->format('d-m-Y') : '—' }}</strong>
              @lang('app.to')
              <strong>{{ $to ? Carbon::parse($to)->format('d-m-Y') : '—' }}</strong>
            @endif
          </div>
        </div>
      </div>
      <div class="text-end">
        <h6 class="mb-0 fw-bold">@lang('reports.Deposits / Withdrawals Summary')</h6>
        <div class="small-muted">@lang('app.Date'): {{ now()->format('d-m-Y') }}</div>
      </div>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 kpi mb-4">
      <div class="col-12 col-md-3">
        <div class="card"><div class="card-body p-3 text-center">
          <div class="small-muted">@lang('reports.Total Transactions')</div>
          <div class="fs-5 fw-bold">{{ number_format($countAll) }}</div>
        </div></div>
      </div>

      <div class="col-6 col-md-3">
        <div class="card"><div class="card-body p-3 text-center">
          <div class="small-muted">@lang('reports.Total Deposits')</div>
          <div class="fs-5 fw-bold text-success">
            {{ number_format($depositsTotal, 2) }} <span class="small-muted">{{ $cs }}</span>
          </div>
        </div></div>
      </div>

      <div class="col-6 col-md-3">
        <div class="card"><div class="card-body p-3 text-center">
          <div class="small-muted">@lang('app.Total Withdrawals')</div>
          <div class="fs-5 fw-bold text-danger">
            {{ number_format($withdrawalsTotal, 2) }} <span class="small-muted">{{ $cs }}</span>
          </div>
        </div></div>
      </div>

      <div class="col-12 col-md-3">
        <div class="card"><div class="card-body p-3 text-center">
          <div class="small-muted">@lang('reports.Deposits minus Withdrawals')</div>
          <div class="fs-5 fw-bold {{ $netTotal >= 0 ? 'text-success' : 'text-danger' }}">
            {{ number_format($netTotal, 2) }} <span class="small-muted">{{ $cs }}</span>
          </div>
        </div></div>
      </div>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
      <table class="table table-striped table-bordered text-center align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:56px">#</th>
            <th>@lang('app.Date')</th>
            <th>@lang('app.Amount')</th>
            <th>@lang('app.Type')</th>
            <th>@lang('app.Status')</th>
            <th class="text-start">@lang('app.Notes')</th>
          </tr>
        </thead>
        <tbody>
          @forelse($items as $i => $e)
            @php
              $statusName = optional($e->status)->name ?? optional($e->transactionStatus)->name ?? '—';
              $typeName   = optional($e->type)->name   ?? optional($e->transactionType)->name   ?? '—';
              $isDeposit  = (string)$e->direction === 'in';
              $amountCls  = $isDeposit ? 'text-success' : 'text-danger';
            @endphp
            <tr>
              <td>{{ $i + 1 }}</td>
              <td>{{ Carbon::parse($e->entry_date)->format('d-m-Y') }}</td>
              <td class="fw-semibold {{ $amountCls }}">
                {{ number_format($e->amount, 2) }} <span class="small-muted">{{ $cs }}</span>
              </td>
              <td>{{ $typeName }}</td>
              <td>{{ $statusName }}</td>
              <td class="text-start">{{ $e->notes ?? '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="py-5 text-muted">@lang('reports.No matching transactions in the report.')</td>
            </tr>
          @endforelse
        </tbody>

        @if($transactions instanceof \Illuminate\Pagination\LengthAwarePaginator)
          <tfoot>
            <tr>
              <th colspan="6" class="bg-white">
                <div class="no-print d-flex justify-content-center p-2">
                  {{ $transactions->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
              </th>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>

    {{-- Actions --}}
    <div class="no-print text-end mt-3 d-flex justify-content-end gap-2">
      <a href="{{ route('investors.show', $investor) }}" class="btn btn-outline-secondary">↩ @lang('app.Back')</a>
      <button class="btn btn-primary" onclick="window.print()">🖨 @lang('app.Print')</button>
    </div>
  </div>
</div>
</body>
</html>
