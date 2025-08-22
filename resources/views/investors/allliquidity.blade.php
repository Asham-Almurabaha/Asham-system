{{-- resources/views/investors/liquidity.blade.php --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>تقرير سيولة المستثمرين الحالية</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <style>
    body { font-family: Tahoma, Arial, sans-serif; background:#fff; }
    @page { size: A4 landscape; margin: 0; }
    .page   { width:297mm; min-height:201mm; margin:auto; padding:14mm; background:#fff; position:relative; box-sizing:border-box; }
    .content{ position:relative; z-index:1; }
    .small-muted{ font-size:.85rem; color:#6b7280; }
    .kpi .card{ border:1px solid #eef2f7; }
    .kpi .card .card-body{ padding:1rem 1rem; }
    .badge-status{ font-size:.75rem; }
    .soft { box-shadow: 0 6px 18px rgba(0,0,0,.06); border:1px solid #eef2f7; border-radius:16px; }
    .toolbar .form-control, .toolbar .form-select{ height:38px }
    .sticky-th thead th { position: sticky; top: 0; background: #f8f9fb; z-index: 1; }
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
  use Illuminate\Support\Arr;

  $cs = $currencySymbol ?? 'ر.س';

  // الـ paginator جاي من الكنترولر باسم rows
  $isPaginated = $rows instanceof \Illuminate\Pagination\LengthAwarePaginator;
  $items = $isPaginated ? $rows->items() : (is_iterable($rows) ? $rows : []);
  $items = collect($items);

  // إحصائيات للعرض
  $countAll     = $isPaginated ? $rows->total() : $items->count();
  $pageCount    = $items->count();
  $pageSum      = (float) $items->sum('liquidity');
  $avgLiquidity = $countAll > 0 ? (($grandTotal ?? 0)/$countAll) : 0;

  $posCount     = (int) $items->filter(fn($r)=> (float)$r->liquidity > 0)->count();
  $negCount     = (int) $items->filter(fn($r)=> (float)$r->liquidity < 0)->count();
  $zeroCount    = max(0, $pageCount - $posCount - $negCount);

  $q        = Arr::get($filters ?? [], 'q', '');
  $perPage  = (int) Arr::get($filters ?? [], 'per_page', 25);
@endphp

<div class="page shadow-sm">
  <div class="content">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
      <div>
        <h5 class="mb-0 fw-bold">تقرير سيولة المستثمرين الحالية</h5>
        <div class="small-muted">
          يعتمد على دفتر القيود: <strong>داخل − خارج</strong> لكل مستثمر.
        </div>
      </div>
      <div class="text-end">
        <div class="small-muted">التاريخ: {{ now()->format('d-m-Y') }}</div>
      </div>
    </div>

    {{-- Toolbar: بحث / عدد صفوف الصفحة --}}
    <div class="toolbar soft p-3 mb-3 no-print">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-12 col-md-6">
          <label class="form-label mb-1 small">بحث بالاسم</label>
          <input type="text" name="q" class="form-control" value="{{ e($q) }}" placeholder="اكتب اسم المستثمر...">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label mb-1 small">لكل صفحة</label>
          <select name="per_page" class="form-select">
            @foreach([10,25,50,100] as $n)
              <option value="{{ $n }}" @selected($perPage==$n)>{{ $n }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-4 d-flex gap-2">
          <button class="btn btn-primary flex-fill"><i class="bi bi-search"></i> بحث</button>
          <a href="{{ url()->current() }}" class="btn btn-outline-secondary flex-fill">مسح</a>
        </div>
      </form>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 kpi mb-4">
      <div class="col-6 col-md-6">
        <div class="card"><div class="card-body text-center">
          <div class="small-muted">عدد المستثمرين (الكل)</div>
          <div class="fs-4 fw-bold">{{ number_format($countAll) }}</div>
        </div></div>
      </div>

      <div class="col-6 col-md-6">
        <div class="card"><div class="card-body text-center">
          <div class="small-muted">إجمالي السيولة (الكل)</div>
          <div class="fs-4 fw-bold {{ ($grandTotal??0)>=0 ? 'text-success' : 'text-danger' }}">
            {{ number_format((float)($grandTotal ?? 0), 2) }}
            <span class="fs-6 small-muted">{{ $cs }}</span>
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
            <th class="text-start">المستثمر</th>
            <th>العقود (نشِط/إجمالي)</th>
            <th>السيولة الحالية</th>
            <th class="no-print" style="width:120px">إجراءات</th>
          </tr>
        </thead>
        <tbody>
          @forelse($items as $i => $r)
            @php
              $liq    = (float) ($r->liquidity ?? 0);
              $init   = (float) ($r->initial_capital ?? 0);
              $act    = (int)   ($r->contracts_active ?? 0);
              $tot    = (int)   ($r->contracts_total ?? 0);
              $ratio  = $init != 0 ? round(($liq / $init) * 100, 1) : null;
              $badge  = $liq > 0 ? 'success' : ($liq < 0 ? 'danger' : 'secondary');
            @endphp
            <tr>
              <td>{{ $isPaginated ? ($rows->firstItem() + $i) : ($i + 1) }}</td>
              <td class="text-start">
                <div class="fw-semibold">
                  @if(Route::has('investors.show'))
                    <a href="{{ route('investors.show', $r->id) }}">{{ $r->name }}</a>
                  @else
                    {{ $r->name }}
                  @endif
                </div>
              </td>
              <td class="text-primary fw-semibold">
                {{ number_format($init, 2) }} <span class="small-muted">{{ $cs }}</span>
              </td>
              <td class="fw-bold {{ $liq>=0 ? 'text-success' : 'text-danger' }}">
                {{ number_format($liq, 2) }} <span class="small-muted">{{ $cs }}</span>
              </td>
              <td class="no-print">
                @if(Route::has('investors.show'))
                  <a href="{{ route('investors.show', $r->id) }}" class="btn btn-sm btn-outline-primary">
                    تفاصيل
                  </a>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="py-5 text-muted">لا توجد بيانات مطابقة.</td>
            </tr>
          @endforelse
        </tbody>

        @if($isPaginated)
          <tfoot>
            <tr>
              <th colspan="7" class="bg-white">
                <div class="no-print d-flex justify-content-center p-2">
                  {{ $rows->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
              </th>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>

    {{-- Actions --}}
    <div class="no-print d-flex justify-content-end gap-2 mt-3">
      <a href="{{ route('investors.index') }}" class="btn btn-outline-secondary">↩ رجوع</a>
      <a href="{{ url()->current() }}" class="btn btn-outline-secondary">↺ تحديث</a>
      <button class="btn btn-primary" onclick="window.print()">🖨 طباعة</button>
    </div>

  </div>
</div>

{{-- Icons (Bootstrap) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</body>
</html>
