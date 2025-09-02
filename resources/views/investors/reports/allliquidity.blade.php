{{-- resources/views/investors/liquidity.blade.php --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <title>تقرير سيولة المستثمرين الحالية</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  {{-- Favicon (اختياري) --}}
  @if(!empty($setting?->favicon))
    <link rel="icon" href="{{ asset('storage/'.$setting->favicon) }}">
  @endif

  {{-- Bootstrap 5 RTL/LTR تلقائي --}}
  @if(app()->getLocale() === 'ar')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  @else
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  @endif
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    body { background:#fff; margin:0; padding:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .small-muted { font-size:.9rem; color:#6c757d; }

    /* حجم ورقة A4 للطباعة */
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

    /* جداول الطباعة متعددة الصفحات */
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
  use Illuminate\Support\Arr;

  $cs = $currencySymbol ?? 'ر.س';

  // الـ paginator جاي من الكنترولر باسم rows
  $isPaginated = $rows instanceof \Illuminate\Pagination\LengthAwarePaginator;
  $items = $isPaginated ? $rows->items() : (is_iterable($rows) ? $rows : []);
  $items = collect($items);

  // إحصائيات
  $countAll     = $isPaginated ? $rows->total() : $items->count();
  $pageCount    = $items->count();
  $pageSum      = (float) $items->sum('liquidity');
  $avgLiquidity = $countAll > 0 ? (($grandTotal ?? 0) / $countAll) : 0;

  $posCount     = (int) $items->filter(fn($r)=>(float)$r->liquidity > 0)->count();
  $negCount     = (int) $items->filter(fn($r)=>(float)$r->liquidity < 0)->count();
  $zeroCount    = max(0, $pageCount - $posCount - $negCount);

  $q        = Arr::get($filters ?? [], 'q', '');
  $perPage  = (int) Arr::get($filters ?? [], 'per_page', 25);

  // الشعار واسم المنشأة (نفس منطق البرنت)
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
          <div class="small-muted">
            يعتمد على دفتر القيود: <strong>داخل − خارج</strong> لكل مستثمر.
          </div>
        </div>
      </div>
      <div class="text-end">
        <h6 class="mb-0 fw-bold">تقرير سيولة المستثمرين الحالية</h6>
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

    {{-- KPIs مختصرة --}}
    <div class="row g-3 kpi mb-4">
      <div class="col-12 col-md-3">
        <div class="card"><div class="card-body text-center">
          <div class="small-muted">عدد المستثمرين (الكل)</div>
          <div class="fs-5 fw-bold">{{ number_format($countAll) }}</div>
        </div></div>
      </div>
      <div class="col-12 col-md-3">
        <div class="card"><div class="card-body text-center">
          <div class="small-muted">إجمالي السيولة (الكل)</div>
          <div class="fs-5 fw-bold {{ ($grandTotal??0)>=0 ? 'text-success' : 'text-danger' }}">
            {{ number_format((float)($grandTotal ?? 0), 2) }} <span class="small-muted">{{ $cs }}</span>
          </div>
        </div></div>
      </div>
      <div class="col-12 col-md-3">
        <div class="card"><div class="card-body text-center">
          <div class="small-muted">متوسط السيولة/مستثمر</div>
          <div class="fs-5 fw-bold">{{ number_format($avgLiquidity, 2) }} <span class="small-muted">{{ $cs }}</span></div>
        </div></div>
      </div>
      <div class="col-12 col-md-3">
        <div class="card"><div class="card-body text-center">
          <div class="small-muted">صفحة: موجب/صفر/سالب</div>
          <div class="fs-5 fw-bold">
            {{ $posCount }} / {{ $zeroCount }} / {{ $negCount }}
          </div>
        </div></div>
      </div>
    </div>

    {{-- الجدول --}}
    <div class="table-responsive">
      <table class="table table-striped table-bordered text-center align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:56px">#</th>
            <th class="text-start">المستثمر</th>
            <th>العقود (نشِط/إجمالي)</th>
            <th>رأس المال المبدئي</th>
            <th>السيولة الحالية</th>
            <th class="no-print" style="width:120px">إجراءات</th>
          </tr>
        </thead>
        <tbody>
          @forelse($items as $i => $r)
            @php
              $liq  = (float) ($r->liquidity ?? 0);
              $init = (float) ($r->initial_capital ?? 0);
              $act  = (int)   ($r->contracts_active ?? 0);
              $tot  = (int)   ($r->contracts_total  ?? 0);
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
              <td class="fw-semibold">{{ $act }} / {{ $tot }}</td>
              <td class="text-primary fw-semibold">
                {{ number_format($init, 2) }} <span class="small-muted">{{ $cs }}</span>
              </td>
              <td class="fw-bold {{ $liq>=0 ? 'text-success' : 'text-danger' }}">
                {{ number_format($liq, 2) }} <span class="small-muted">{{ $cs }}</span>
              </td>
              <td class="no-print">
                @if(Route::has('investors.show'))
                  <a href="{{ route('investors.show', $r->id) }}" class="btn btn-sm btn-outline-primary">تفاصيل</a>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="py-5 text-muted">لا توجد بيانات مطابقة.</td>
            </tr>
          @endforelse
        </tbody>

        @if($isPaginated)
          <tfoot>
            <tr>
              <th colspan="6" class="bg-white">
                <div class="no-print d-flex justify-content-center p-2">
                  {{ $rows->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
              </th>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>

    {{-- الأزرار (لا تُطبع) --}}
    <div class="no-print d-flex justify-content-end gap-2 mt-3">
      <a href="{{ route('investors.index') }}" class="btn btn-outline-secondary">↩ @lang('app.Back')</a>
      <a href="{{ url()->current() }}" class="btn btn-outline-secondary">↺ تحديث</a>
      <button class="btn btn-primary" onclick="window.print()">🖨 @lang('app.Print')</button>
    </div>

  </div>
</div>
</body>
</html>
