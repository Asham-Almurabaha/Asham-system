<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <title>جرد الإيداعات / المسحوبات — {{ $investor->name }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  
</head>
<body>
@php
  use Illuminate\Support\Carbon;

  $cs = $currencySymbol ?? 'ر.س';

  // عناصر الصفحة الحالية (للعرض فقط)
  $items = $transactions instanceof \Illuminate\Pagination\LengthAwarePaginator
    ? $transactions->items()
    : (array) $transactions;

  // العدّ والمجموع جايين من الكنترولر
  $countAll = (int) ($transactionsCount ?? 0);
  $totalDepositsAll = (float) ($transactionsTotal ?? 0.0); // مجموع الإيداعات فقط حسب الكنترولر

  // نطاق التاريخ (اختياري)
  $from = request('from');
  $to   = request('to');
@endphp

<div class="page shadow-sm">
  <div class="content">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
      <div>
        <h5 class="mb-0 fw-bold">جرد الإيداعات / المسحوبات</h5>
        <div class="small-muted">
          المستثمر: <strong>{{ $investor->name }}</strong>
          @if($from || $to)
            — الفترة:
            <strong>{{ $from ? Carbon::parse($from)->format('d-m-Y') : '—' }}</strong>
            إلى
            <strong>{{ $to   ? Carbon::parse($to)->format('d-m-Y')   : '—' }}</strong>
          @endif
        </div>
      </div>
      <div class="text-end">
        <div class="small-muted">التاريخ: {{ now()->format('d-m-Y') }}</div>
      </div>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 kpi mb-4">
      <div class="col-12 col-md-4">
        <div class="card"><div class="card-body p-3 text-center">
          <div class="small-muted">إجمالي عدد الحركات</div>
          <div class="fs-4 fw-bold">{{ number_format($countAll) }}</div>
        </div></div>
      </div>

      <div class="col-6 col-md-4">
        <div class="card"><div class="card-body p-3 text-center">
          <div class="small-muted">إجمالي الإيداعات</div>
          <div class="fs-4 fw-bold text-success">
            {{ number_format($depositsTotal, 2) }} <span class="fs-6 small-muted">{{ $cs }}</span>
          </div>
        </div></div>
      </div>

      <div class="col-6 col-md-4">
        <div class="card"><div class="card-body p-3 text-center">
          <div class="small-muted">إجمالي المسحوبات</div>
          <div class="fs-4 fw-bold text-danger">
            {{ number_format($withdrawalsTotal, 2) }} <span class="fs-6 small-muted">{{ $cs }}</span>
          </div>
        </div></div>
      </div>

      <div class="col-12 col-md-12">
        <div class="card"><div class="card-body p-3 text-center">
          <div class="small-muted">الإيداعات - المسحوبات</div>
          <div class="fs-4 fw-bold text-success">
            {{ number_format($totalDepositsAll, 2) }} <span class="fs-6 small-muted">{{ $cs }}</span>
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
              <td colspan="8" class="py-5 text-muted">لا توجد حركات مطابقة في التقرير.</td>
            </tr>
          @endforelse
        </tbody>

        @if($transactions instanceof \Illuminate\Pagination\LengthAwarePaginator)
          <tfoot>
            <tr>
              <th colspan="8" class="bg-white">
                <div class="no-print d-flex justify-content-center p-2">
                  {{ $transactions->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
              </th>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>

    <div class="no-print text-end mt-3">
      <a href="{{ route('investors.show', $investor) }}" class="btn btn-outline-secondary">↩ @lang('app.Back')</a>
      <button class="btn btn-primary" onclick="window.print()">🖨 @lang('app.Print')</button>
    </div>

  </div>
</div>

</body>
</html>

