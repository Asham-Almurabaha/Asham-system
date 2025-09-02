{{-- resources/views/investors/deposits.blade.php --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <title>جرد الإيداعات — {{ $investor->name }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  {{-- Favicon --}}
  @if(!empty($setting?->favicon))
    <link rel="icon" href="{{ asset('storage/'.$setting->favicon) }}">
  @endif

  {{-- Bootstrap RTL/LTR --}}
  @if(app()->getLocale() === 'ar')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  @else
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  @endif
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    body { background:#fff; margin:0; padding:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .small-muted { font-size:.9rem; color:#6c757d; }

    @page { size: A4; margin: 0; }
    .page {
      width:210mm; min-height:297mm; margin:auto; padding:12mm;
      background:#fff; position:relative; box-sizing:border-box;
    }
    .content { position:relative; z-index:1; }

    .watermark {
      position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
      opacity:.07; z-index:0; pointer-events:none;
    }
    .watermark img { max-width:70%; max-height:70%; transform: rotate(-15deg); }

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

  $cs = $currencySymbol ?? ($data['currencySymbol'] ?? 'ر.س');

  $dCollection = $deposits instanceof \Illuminate\Pagination\LengthAwarePaginator
    ? collect($deposits->items())
    : collect($deposits ?? []);

  $depositsCount = isset($depositsCount) ? (int)$depositsCount : (int)$dCollection->count();
  $depositsTotal = isset($depositsTotal) ? (float)$depositsTotal : (float)$dCollection->sum('amount');

  $avg = $depositsCount ? ($depositsTotal / $depositsCount) : 0.0;
  $max = (float) $dCollection->max('amount');

  $from = request('from');
  $to   = request('to');

  // شعار واسم المنشأة
  $logoUrl   = $logoUrl   ?? (!empty($setting?->logo) ? asset('storage/'.$setting->logo) : asset('assets/img/logo.png'));
  $brandName = $brandName ?? ($setting?->name_ar ?? $setting?->name ?? config('app.name','اسم المنشأة'));
@endphp

<div class="page shadow-sm">
  {{-- Watermark --}}
  <div class="watermark">
    <img src="{{ $logoUrl }}" alt="Logo">
  </div>

  <div class="content">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
      <div class="d-flex align-items-center gap-2">
        <img src="{{ $logoUrl }}" alt="Logo" style="height:40px">
        <div>
          <h5 class="mb-0 fw-bold">{{ $brandName }}</h5>
          <div class="small-muted">
            المستثمر: <strong>{{ $investor->name }}</strong>
            @if($from || $to)
              — الفترة:
              <strong>{{ $from ? Carbon::parse($from)->format('d-m-Y') : '—' }}</strong>
              إلى
              <strong>{{ $to ? Carbon::parse($to)->format('d-m-Y') : '—' }}</strong>
            @endif
          </div>
        </div>
      </div>
      <div class="text-end">
        <h6 class="mb-0 fw-bold">جرد الإيداعات</h6>
        <div class="small-muted">التاريخ: {{ now()->format('d-m-Y') }}</div>
      </div>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 kpi mb-4">
      <div class="col-6 col-md-6">
        <div class="card"><div class="card-body text-center">
          <div class="small-muted">عدد الإيداعات</div>
          <div class="fs-5 fw-bold">{{ number_format($depositsCount) }}</div>
        </div></div>
      </div>
      <div class="col-6 col-md-6">
        <div class="card"><div class="card-body text-center">
          <div class="small-muted">إجمالي الإيداعات</div>
          <div class="fs-5 fw-bold text-success">
            {{ number_format($depositsTotal, 2) }} <span class="small-muted">{{ $cs }}</span>
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
            <th>التاريخ</th>
            <th>المبلغ</th>
            <th>النوع</th>
            <th>الحالة</th>
            <th>الملاحظات</th>
          </tr>
        </thead>
        <tbody>
          @forelse($deposits as $i => $d)
            @php
              $statusName = optional($d->status)->name ?? optional($d->transactionStatus)->name ?? '—';
              $typeName   = optional($d->type)->name   ?? optional($d->transactionType)->name   ?? '—';
            @endphp
            <tr>
              <td>{{ $i + 1 }}</td>
              <td>{{ Carbon::parse($d->entry_date)->format('d-m-Y') }}</td>
              <td class="text-success fw-semibold">
                {{ number_format($d->amount, 2) }} <span class="small-muted">{{ $cs }}</span>
              </td>
              <td>{{ $typeName }}</td>
              <td>{{ $statusName }}</td>
              <td class="text-start">{{ $d->notes ?? '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="py-5 text-muted">لا توجد إيداعات مسجلة لهذا المستثمر ضمن النطاق الحالي.</td>
            </tr>
          @endforelse
        </tbody>
        @if($deposits instanceof \Illuminate\Pagination\LengthAwarePaginator)
          <tfoot>
            <tr>
              <th colspan="6" class="bg-white">
                <div class="no-print d-flex justify-content-center p-2">
                  {{ $deposits->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
              </th>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>

    {{-- Actions --}}
    <div class="no-print d-flex justify-content-end gap-2 mt-3">
      <a href="{{ route('investors.show', $investor) }}" class="btn btn-outline-secondary">↩ @lang('app.Back')</a>
      <button class="btn btn-primary" onclick="window.print()">🖨 @lang('app.Print')</button>
    </div>

  </div>
</div>
</body>
</html>
