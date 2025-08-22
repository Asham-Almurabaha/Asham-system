{{-- resources/views/investors/deposits.blade.php --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>جرد الإيداعات — {{ $investor->name }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <style>
    body { font-family: Tahoma, Arial, sans-serif; background:#fff; margin:0; padding:0; }
    @page { size: A4 landscape; margin: 0; }
    .page   { width:297mm; min-height:201mm; margin:auto; padding:14mm; background:#fff; position:relative; box-sizing:border-box; }
    .content{ position:relative; z-index:1; }
    .small-muted{ font-size:.85rem; color:#6b7280; }
    .kpi .card{ border:1px solid #eef2f7; }
    .badge-status{ font-size:.75rem; }
    @media print {
      .no-print{ display:none !important; }
      .page{ margin:0; padding:12mm; box-shadow:none !important; }
      a { color:inherit; text-decoration:none; }
      .table-responsive { overflow: visible !important; }
    }
  </style>
</head>
<body>
@php
  use Illuminate\Support\Carbon;
  // رمز العملة
  $cs = $currencySymbol ?? ($data['currencySymbol'] ?? 'ر.س');

  // تحويل المجموعة المعروضة للتجميع المحلي عند الحاجة
  $dCollection = $deposits instanceof \Illuminate\Pagination\LengthAwarePaginator
    ? collect($deposits->items())
    : (collect($deposits ?? []));

  // مؤشرات عليا (لو الكنترولر ما بعتهومش، نحسب من المجموعة الحالية)
  $depositsCount = isset($depositsCount) ? (int)$depositsCount : (int)$dCollection->count();
  $depositsTotal = isset($depositsTotal) ? (float)$depositsTotal : (float)$dCollection->sum('amount');

  $avg = $depositsCount ? ($depositsTotal / $depositsCount) : 0.0;
  $max = (float) $dCollection->max('amount');

  // نطاق التاريخ (خياري)
  $from = request('from');
  $to   = request('to');
@endphp

<div class="page shadow-sm">
  <div class="content">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
      <div>
        <h5 class="mb-0 fw-bold">جرد الإيداعات</h5>
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
      <div class="col-6 col-md-6">
        <div class="card"><div class="card-body p-3 text-center">
          <div class="small-muted">عدد الإيداعات</div>
          <div class="fs-4 fw-bold">{{ number_format($depositsCount) }}</div>
        </div></div>
      </div>
      <div class="col-6 col-md-6">
        <div class="card"><div class="card-body p-3 text-center">
          <div class="small-muted">إجمالي الإيداعات</div>
          <div class="fs-4 fw-bold text-success">
            {{ number_format($depositsTotal, 2) }} <span class="fs-6 small-muted">{{ $cs }}</span>
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
              <td class="text-success fw-semibold">{{ number_format($d->amount, 2) }} <span class="small-muted">{{ $cs }}</span></td>
              <td>{{ $typeName }}</td>
              <td>{{ $statusName }}</td>
              <td class="text-start">{{ $d->notes ?? '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="py-5 text-muted">لا توجد إيداعات مسجلة لهذا المستثمر ضمن النطاق الحالي.</td>
            </tr>
          @endforelse
        </tbody>
        @if($deposits instanceof \Illuminate\Pagination\LengthAwarePaginator)
          <tfoot>
            <tr>
              <th colspan="7" class="bg-white">
                <div class="no-print d-flex justify-content-center p-2">
                  {{ $deposits->withQueryString()->links('pagination::bootstrap-5') }}
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
      <a href="{{ route('investors.show', $investor) }}" class="btn btn-outline-secondary">↩ رجوع</a>
      <button class="btn btn-primary" onclick="window.print()">🖨 طباعة</button>
    </div>

  </div>
</div>

</body>
</html>
