{{-- resources/views/contracts/paid.blade.php --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>سجل سداد الأقساط لعقد رقم {{ $contract->contract_number }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  @if(!empty($setting?->favicon))
    <link rel="icon" href="{{ asset('storage/'.$setting->favicon) }}">
  @endif

  {{-- Bootstrap 5 RTL --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

  <style>
    body { font-family:"Tahoma", Arial, sans-serif; background:#fff; margin:0; padding:0; }
    @page { size: A4; margin: 0; }

    .page {
      width: 297mm; min-height: 210mm; margin: auto; padding: 15mm;
      background: #fff; position: relative; box-sizing: border-box;
    }
    .watermark {
      position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
      opacity:0.07; z-index:0; pointer-events:none;
    }
    .watermark img { max-width:70%; max-height:70%; transform:rotate(-15deg); }
    .content { position:relative; z-index:1; }
    .line { margin: .35rem 0; }
    .stat { border:1px solid #eee; border-radius:.75rem; padding:.75rem .9rem; background:#fafafa }
    .stat .label { font-size:.85rem; color:#6c757d }
    .stat .value { font-weight:700; font-size:1.05rem }

    @media print {
      .no-print { display:none !important; }
      body { margin:0; }
      .page { margin:0; padding:12mm; box-shadow:none; }
      a { color:inherit; text-decoration:none; }
    }
  </style>
</head>
<body>
@php
  use Carbon\Carbon;

  $logoUrl   = $logoUrl   ?? (!empty($setting?->logo) ? asset('storage/'.$setting->logo) : asset('assets/img/logo.png'));
  $brandName = $brandName ?? ($setting?->name_ar ?? $setting?->name ?? config('app.name','اسم المنشأة'));

  // مُساعد: يعمل مع كائنات/مصفوفات
  $get = function($item, $key, $default = null) {
      return data_get($item, $key, $default);
  };

  $fmtDate = function($date) {
      if (!$date) return '—';
      try {
          if ($date instanceof \DateTimeInterface) return $date->format('Y-m-d');
          return Carbon::parse($date)->format('Y-m-d');
      } catch (\Throwable $e) {
          return (string) $date;
      }
  };

  $fmtNum = fn($n) => number_format((float)$n, 2);

  // لو جت أقساط بالخطأ بدون paid_amount>0، تأكد من تصفيتها هنا أيضاً
  $rows = collect($paidInstallments ?? [])->filter(function ($i) use ($get) {
      return (float)$get($i, 'paid_amount', 0) > 0;
  })->values();
@endphp

<div class="page shadow-sm">
  <div class="watermark"><img src="{{ $logoUrl }}" alt="Logo"></div>

  <div class="content">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
      <div class="d-flex align-items-center gap-2">
        <img src="{{ $logoUrl }}" alt="Logo" style="height:48px">
        <h5 class="mb-0 fw-bold">{{ $brandName }}</h5>
      </div>
      <h5 class="mb-0 fw-bold">سداد أقساط عقد رقم: {{ $contract->contract_number }}</h5>
    </div>

    {{-- Title --}}
    <div class="text-center fw-bold fs-4 mb-3">سجل سداد الأقساط</div>

    {{-- Parties --}}
    <div class="mb-3">
      <div class="line"><strong>العميل:</strong> {{ $contract->customer->name ?? '—' }}</div>
      <div class="line"><strong>الجوال:</strong> {{ $contract->customer->phone ?? '—' }}</div>
      <div class="line"><strong>الهوية/الإقامة:</strong> {{ $contract->customer->national_id ?? '—' }}</div>
    </div>

    {{-- Summary stats --}}
    <div class="row g-2 mb-3">
      <div class="col-6 col-md-4">
        <div class="stat">
          <div class="label">إجمالي قيمة العقد</div>
          <div class="value">{{ $fmtNum($contractTotal) }} {{ $currency }}</div>
        </div>
      </div>
      <div class="col-6 col-md-4">
        <div class="stat">
          <div class="label">إجمالي المدفوع</div>
          <div class="value">{{ $fmtNum($totalPaid) }} {{ $currency }}</div>
        </div>
      </div>
      <div class="col-6 col-md-4">
        <div class="stat">
          <div class="label">المتبقي</div>
          <div class="value">{{ $fmtNum($remaining) }} {{ $currency }}</div>
        </div>
      </div>

      @isset($countPaidFully)
      <div class="col-6 col-md-4">
        <div class="stat">
          <div class="label">عدد الأقساط المدفوعة كليًا</div>
          <div class="value">{{ $countPaidFully }}</div>
        </div>
      </div>
      @endisset

      @isset($countRemaining)
      <div class="col-6 col-md-4">
        <div class="stat">
          <div class="label">عدد الأقساط المتبقية</div>
          <div class="value">{{ $countRemaining }}</div>
        </div>
      </div>
      @endisset
    </div>

    {{-- Table --}}
    <div class="table-responsive mb-3">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th class="text-center" style="width:60px;">#</th>
            <th>تاريخ الاستحقاق</th>
            <th>المبلغ المستحق ({{ $currency }})</th>
            <th>تاريخ السداد</th>
            <th>المبلغ المدفوع ({{ $currency }})</th>
            <th>المتبقي على القسط ({{ $currency }})</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($rows as $idx => $ins)
            @php
              $amount = (float) $get($ins, 'amount', 0);        // من الكنترولر: due_amount -> amount
              $paid   = (float) $get($ins, 'paid_amount', 0);    // من الكنترولر: payment_amount -> paid_amount
              $still  = max(0.0, $amount - $paid);
            @endphp
            <tr>
              <td class="text-center">{{ $idx + 1 }}</td>
              <td>{{ $fmtDate($get($ins, 'due_date')) }}</td>   {{-- من الكنترولر: due_date --}}
              <td class="text-end">{{ $fmtNum($amount) }}</td>
              <td>{{ $fmtDate($get($ins, 'paid_at')) }}</td>    {{-- من الكنترولر: payment_date -> paid_at --}}
              <td class="text-end">{{ $fmtNum($paid) }}</td>
              <td class="text-end">{{ $fmtNum($still) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted">لا توجد أقساط مسددة بعد.</td>
            </tr>
          @endforelse
        </tbody>

        @if($rows->isNotEmpty())
        <tfoot>
          <tr>
            <th colspan="2" class="text-end">إجمالي قيمة العقد:</th>
            <th class="text-end">{{ $fmtNum($contractTotal) }}</th>
            <th class="text-end">إجمالي المدفوع:</th>
            <th class="text-end">{{ $fmtNum($totalPaid) }}</th>
            <th class="text-end">{{ $fmtNum($remaining) }}</th>
            <th></th>
          </tr>
        </tfoot>
        @endif
      </table>
    </div>

    {{-- Buttons --}}
    <div class="no-print d-flex justify-content-end gap-2">
      <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary">↩ رجوع</a>
      <button onclick="window.print()" class="btn btn-primary">🖨 طباعة</button>
    </div>
  </div>
</div>
</body>
</html>
