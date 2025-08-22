{{-- resources/views/investors/statement.blade.php --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>جرد حساب المستثمر — {{ $investor->name }}</title>
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

  // السيولة الحالية (دفتر المستثمر)
  $liquidity              = (float)($data['liquidity'] ?? 0);

  $rows                   = $data['contractBreakdown'] ?? [];
@endphp

<div class="page shadow-sm">
  <div class="content">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
      <div>
        <h5 class="mb-0 fw-bold">جرد حساب المستثمر</h5>
        <div class="small-muted">المستثمر: <strong>{{ $investor->name }}</strong></div>
      </div>
      <div class="text-end">
        <div class="small-muted">التاريخ: {{ now()->format('Y-m-d') }}</div>
        <button class="btn btn-primary btn-sm no-print" onclick="window.print()">🖨 طباعة</button>
      </div>
    </div>

    <div class="row g-3 kpi mb-4">
      <div class="col-6 col-md-3">
        <div class="card"><div class="card-body p-3">
          <div class="small-muted">عدد العقود</div>
          <div class="fs-6">سارية: <strong>{{ $contractsActive }}</strong> — منتهية: <strong>{{ $contractsEnded }}</strong></div>
          <div class="small-muted">الإجمالي: {{ $contractsTotal }}</div>
        </div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card"><div class="card-body p-3">
          <div class="small-muted">رأس المال (الكل)</div>
          <div class="fs-6 fw-bold">{{ number_format($totalCapitalShareAll,2) }} <span class="small-muted">{{ $cs }}</span></div>
        </div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card"><div class="card-body p-3">
          <div class="small-muted">الربح الصافي (الكل)</div>
          <div class="fs-6 fw-bold">{{ number_format($totalProfitNetAll,2) }} <span class="small-muted">{{ $cs }}</span></div>
        </div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card"><div class="card-body p-3">
          <div class="small-muted">السيولة الحالية (دفتر المستثمر)</div>
          <div class="fs-6 fw-bold">{{ number_format($liquidity,2) }} <span class="small-muted">{{ $cs }}</span></div>
        </div></div>
      </div>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 kpi mb-3">
      <div class="col-6 col-md-3">
        <div class="card"><div class="card-body p-3">
          <div class="small-muted">رأس المال (نشط)</div>
          <div class="fs-5 fw-bold">{{ number_format($totalCapitalShare,2) }} <span class="small-muted">{{ $cs }}</span></div>
        </div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card"><div class="card-body p-3">
          <div class="small-muted">الربح الصافي (نشط)</div>
          <div class="fs-5 fw-bold">{{ number_format($totalProfitNet,2) }} <span class="small-muted">{{ $cs }}</span></div>
        </div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card"><div class="card-body p-3">
          <div class="small-muted">المحصل للمستثمر</div>
          <div class="fs-5 fw-bold">{{ number_format($totalPaidPortion,2) }} <span class="small-muted">{{ $cs }}</span></div>
        </div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card"><div class="card-body p-3">
          <div class="small-muted">المتبقي على العملاء</div>
          <div class="fs-5 fw-bold">{{ number_format(max(0,$totalRemainingOnCust),2) }} <span class="small-muted">{{ $cs }}</span></div>
        </div></div>
      </div>
    </div>

    

    {{-- Table --}}
    <div class="table-responsive">
      <table class="table table-striped table-bordered align-middle text-center">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>العقد</th>
            <th>الحالة</th>
            <th>العميل</th>
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
            @endphp
            <tr>
              <td>{{ $i+1 }}</td>
              <td class="text-start">#{{ $row['contract_id'] }}</td>
              <td>
                @php
                  $badge = 'bg-secondary';
                  $s = (string)$statusTxt;
                  if (str_contains($s,'ساري') || str_contains($s,'نشط')) $badge = 'bg-success';
                  if (str_contains($s,'غلق') || str_contains(strtolower($s),'closed')) $badge = 'bg-danger';
                @endphp
                <span class="badge {{ $badge }} badge-status">{{ $statusTxt }}</span>
              </td>
              <td class="text-start">{{ $row['customer'] }}</td>
              <td>{{ number_format($row['share_pct'] ?? 0, 2) }}</td>
              <td>{{ number_format($row['share_value'] ?? 0, 2) }} <span class="small-muted">{{ $cs }}</span></td>
              <td>{{ number_format($row['profit_net'] ?? 0, 2) }} <span class="small-muted">{{ $cs }}</span></td>
              <td>{{ number_format($row['paid_to_investor_from_customer'] ?? 0, 2) }} <span class="small-muted">{{ $cs }}</span></td>
              <td>{{ number_format(max(0, $row['remaining_on_customers'] ?? 0), 2) }} <span class="small-muted">{{ $cs }}</span></td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="py-5 text-muted">لا توجد عقود مرتبطة بهذا المستثمر.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="small-muted mt-3">
      * الأرقام مبنية على خدمة InvestorDataService، ويمكن تعديل معادلات الربح/المتبقي بحسب السياسة المحاسبية لديكم.
    </div>

    <div class="no-print text-end mt-3">
      <a href="{{ route('investors.index') }}" class="btn btn-outline-secondary">↩ رجوع</a>
      <button class="btn btn-primary" onclick="window.print()">🖨 طباعة</button>
    </div>
  </div>
</div>

</body>
</html>
