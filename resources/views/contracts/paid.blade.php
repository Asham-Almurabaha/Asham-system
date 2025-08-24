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
      width: 210mm;
      min-height: 297mm;
      margin: auto;
      padding: 15mm;
      background: #fff;
      position: relative;
      box-sizing: border-box;
    }

    .watermark {
      position:absolute; inset:0;
      display:flex; align-items:center; justify-content:center;
      opacity:0.07; z-index:0; pointer-events:none;
    }
    .watermark img { max-width:70%; max-height:70%; transform:rotate(-15deg); }
    .content { position:relative; z-index:1; }

    .line { margin: .35rem 0; }
    .section-title { font-weight:700; margin: .75rem 0 .5rem; }

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
  $logoUrl   = $logoUrl   ?? (!empty($setting?->logo) ? asset('storage/'.$setting->logo) : asset('assets/img/logo.png'));
  $brandName = $brandName ?? ($setting?->name_ar ?? $setting?->name ?? config('app.name','اسم المنشأة'));
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
            <th>ملاحظات</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($paidInstallments as $idx => $ins)
            <tr>
              <td class="text-center">{{ $idx + 1 }}</td>
              <td>{{ optional($ins->due_date)->format('Y-m-d') ?? '—' }}</td>
              <td class="text-end">{{ number_format((float)($ins->amount ?? 0), 2) }}</td>
              <td>{{ optional($ins->paid_at)->format('Y-m-d') ?? '—' }}</td>
              <td class="text-end">{{ number_format((float)($ins->paid_amount ?? 0), 2) }}</td>
              <td>{{ $ins->note ?? '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted">لا توجد أقساط مسددة بعد.</td>
            </tr>
          @endforelse
        </tbody>
        @if($paidInstallments->isNotEmpty())
        <tfoot>
          <tr>
            <th colspan="2" class="text-end">إجمالي قيمة العقد:</th>
            <th class="text-end">{{ number_format($contractTotal, 2) }}</th>
            <th class="text-end">إجمالي المدفوع:</th>
            <th class="text-end">{{ number_format($totalPaid, 2) }}</th>
            <th></th>
          </tr>
          <tr>
            <th colspan="4" class="text-end">المتبقي على العقد:</th>
            <th class="text-end" colspan="2">{{ number_format($remaining, 2) }} {{ $currency }}</th>
          </tr>
        </tfoot>
        @endif
      </table>
    </div>

    {{-- Buttons --}}
    <div class="no-print d-flex justify-content-end gap-2">
      <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary">↩ رجوع للقائمة</a>
      <button onclick="window.print()" class="btn btn-success">✅ طباعة</button>
    </div>
  </div>
</div>
</body>
</html>
