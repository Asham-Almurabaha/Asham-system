{{-- resources/views/investors/withdrawals.blade.php --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <title>@lang('app.Withdrawals Summary') — {{ $investor->name }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

  
</head>
<body>

@php
use Illuminate\Support\Carbon;
  // رموز/قِيَم افتراضية آمنة
  $cs = $currencySymbol ?? ($data['currencySymbol'] ?? 'ر.س');

  // لو الكنترولر موصّل القيم دي مباشرة — تمام
  // وإلا بنحسبها من المجموعة المعروضة
  $wCollection = $withdrawals instanceof \Illuminate\Pagination\LengthAwarePaginator
    ? collect($withdrawals->items())
    : (collect($withdrawals ?? []));

  $withdrawalsCount = isset($withdrawalsCount) ? (int)$withdrawalsCount : (int)$wCollection->count();
  $withdrawalsTotal = isset($withdrawalsTotal) ? (float)$withdrawalsTotal : (float)$wCollection->sum('amount');

  // نطاق التاريخ (لو جاي من الفلتر)
  $from = request('from');
  $to   = request('to');
@endphp

<div class="page shadow-sm">
  <div class="content">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
      <div>
        <h5 class="mb-0 fw-bold">@lang('app.Withdrawals Summary')</h5>
        <div class="small-muted">
          @lang('app.Investor'): <strong>{{ $investor->name }}</strong>
          @if($from || $to)
            — @lang('app.Period'):
            <strong>{{ $from ? Carbon::parse($from)->format('d-m-Y') : '—' }}</strong>
            @lang('app.to')
            <strong>{{ $to   ? Carbon::parse($to)->format('d-m-Y')   : '—' }}</strong>
          @endif
        </div>
      </div>
      <div class="text-end">
        <div class="small-muted">@lang('app.Date'): {{ now()->format('d-m-Y') }}</div>
      </div>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 kpi mb-4">
      <div class="col-6 col-md-6">
        <div class="card"><div class="card-body p-3 text-center">
          <div class="small-muted">@lang('app.Number of Withdrawals')</div>
          <div class="fs-4 fw-bold">{{ number_format($withdrawalsCount) }}</div>
        </div></div>
      </div>
      <div class="col-6 col-md-6">
        <div class="card"><div class="card-body p-3 text-center">
          <div class="small-muted">@lang('app.Total Withdrawals')</div>
          <div class="fs-4 fw-bold text-danger">
            {{ number_format($withdrawalsTotal, 2) }} <span class="fs-6 small-muted">{{ $cs }}</span>
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
            <th>@lang('app.Notes')</th>
          </tr>
        </thead>
        <tbody>
          @forelse($withdrawals as $i => $w)
            @php
              $statusName = optional($w->status)->name ?? optional($w->transactionStatus)->name ?? '—';
              $typeName   = optional($w->type)->name   ?? optional($w->transactionType)->name   ?? '—';
            @endphp
            <tr>
              <td>{{ $i + 1 }}</td>
              <td>{{ Carbon::parse($w->entry_date)->format('d-m-Y') }}</td>
              <td class="text-danger fw-semibold">{{ number_format($w->amount, 2) }} <span class="small-muted">{{ $cs }}</span></td>
              <td>{{ $typeName }}</td>
              <td>{{ $statusName }}</td>
              <td class="text-start">{{ $d->notes ?? '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="py-5 text-muted">@lang('app.No withdrawals found for this investor in the current range.')</td>
            </tr>
          @endforelse
        </tbody>
        @if($withdrawals instanceof \Illuminate\Pagination\LengthAwarePaginator)
          <tfoot>
            <tr>
              <th colspan="7" class="bg-white">
                <div class="no-print d-flex justify-content-center p-2">
                  {{ $withdrawals->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
              </th>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>

    <div class="small-muted mt-3">
    </div>

    <div class="no-print text-end mt-3">
      <a href="{{ route('investors.show', $investor) }}" class="btn btn-outline-secondary">↩ @lang('app.Back')</a>
      <button class="btn btn-primary" onclick="window.print()">🖨 @lang('app.Print')</button>
    </div>

  </div>
</div>

</body>
</html>

