@extends('layouts.master')

@section('title', 'دفتر القيود')

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">دفتر القيود</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">الحسابات</li>
            <li class="breadcrumb-item active">دفتر القيود</li>
        </ol>
    </nav>
</div>

{{-- شريط أدوات سريع --}}
<div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">
        <a href="{{ route('ledger.create') }}" class="btn btn-outline-success">+ إضافة قيد</a>
        <a href="{{ route('ledger.transfer.create') }}" class="btn btn-outline-primary">تحويل داخلي (مكتب)</a>
        <a href="{{ route('ledger.split.create') }}" class="btn btn-outline-secondary">قيد مُجزّأ (بنك + خزنة)</a>

        <span class="ms-auto small text-muted">
            النتائج: <strong>{{ $entries->total() }}</strong>
        </span>

        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#filterBar" aria-expanded="false">
            تصفية متقدمة
        </button>
    </div>

    <div class="collapse @if(($filters['party_category'] ?? '') || ($filters['investor_id'] ?? '') || ($filters['status_id'] ?? '') || ($filters['account_type'] ?? '') || ($filters['from'] ?? '') || ($filters['to'] ?? '')) show @endif border-top" id="filterBar">
        <div class="card-body">
            <form method="GET" action="{{ route('ledger.index') }}" class="row gy-2 gx-2 align-items-end" id="filtersForm">
                <div class="col-12 col-md-2">
                    <label class="form-label mb-1">الفئة</label>
                    <select name="party_category" id="party_category" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        <option value="investors" @selected(($filters['party_category'] ?? '') === 'investors')>المستثمرون</option>
                        <option value="office"    @selected(($filters['party_category'] ?? '') === 'office')>المكتب</option>
                    </select>
                </div>

                <div class="col-12 col-md-2" id="investorWrap">
                    <label class="form-label mb-1">المستثمر</label>
                    <select name="investor_id" id="investor_id" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        @foreach($investors as $inv)
                            <option value="{{ $inv->id }}" @selected((string)($filters['investor_id'] ?? '') === (string)$inv->id)>{{ $inv->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <label class="form-label mb-1">الحالة</label>
                    <select name="status_id" id="status_id" class="form-select form-select-sm">
                        <option value="">الكل</option>

                        <optgroup label="حالات المستثمرين" data-cat="investors">
                            @foreach($statusesInvestors as $st)
                                @if(($st->transaction_type_id ?? null) != 3)
                                    <option value="{{ $st->id }}" data-cat="investors" data-type="{{ $st->transaction_type_id }}" @selected((string)($filters['status_id'] ?? '') === (string)$st->id)>{{ $st->name }}</option>
                                @endif
                            @endforeach
                        </optgroup>

                        <optgroup label="حالات المكتب" data-cat="office">
                            @foreach($statusesOffice as $st)
                                @if(($st->transaction_type_id ?? null) != 3)
                                    <option value="{{ $st->id }}" data-cat="office" data-type="{{ $st->transaction_type_id }}" @selected((string)($filters['status_id'] ?? '') === (string)$st->id)>{{ $st->name }}</option>
                                @endif
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">نوع الحساب</label>
                    <select name="account_type" id="account_type" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        <option value="bank" @selected(($filters['account_type'] ?? '') === 'bank')>حساب بنكي</option>
                        <option value="safe" @selected(($filters['account_type'] ?? '') === 'safe')>خزنة</option>
                    </select>
                </div>

                <div class="col-6 col-md-1 js-date">
                    <label class="form-label mb-1">من</label>
                    <input type="date" name="from" id="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-1 js-date">
                    <label class="form-label mb-1">إلى</label>
                    <input type="date" name="to" id="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
                </div>

                <div class="col-12 col-md-2 d-flex gap-2">
                    <a href="{{ route('ledger.index') }}" class="btn btn-outline-secondary btn-sm" id="btnClear">مسح</a>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    // ====== مصدر البيانات ======
    $coll = $entries instanceof \Illuminate\Pagination\LengthAwarePaginator
        ? $entries->getCollection()
        : collect($entries);

    // ====== تجميع الحسابات البنكيّة والخزن (على مستوى الحسابات فقط) ======
    $bankAgg = [];  $safeAgg = [];
    $bankTotalIn = $bankTotalOut = 0.0;
    $safeTotalIn = $safeTotalOut = 0.0;

    foreach ($coll as $e) {
        $amt = (float)($e->amount ?? 0);
        $dir = $e->direction ?? null;

        if (!empty($e->bankAccount)) {
            $bid = (string)$e->bank_account_id;
            $bankAgg[$bid] ??= [
                'name' => $e->bankAccount->name ?? ('#'.$bid),
                'in'   => 0.0,
                'out'  => 0.0,
            ];
            if ($dir === 'in')  { $bankAgg[$bid]['in']  += $amt; $bankTotalIn  += $amt; }
            if ($dir === 'out') { $bankAgg[$bid]['out'] += $amt; $bankTotalOut += $amt; }
        }

        if (!empty($e->safe)) {
            $sid = (string)$e->safe_id;
            $safeAgg[$sid] ??= [
                'name' => $e->safe->name ?? ('#'.$sid),
                'in'   => 0.0,
                'out'  => 0.0,
            ];
            if ($dir === 'in')  { $safeAgg[$sid]['in']  += $amt; $safeTotalIn  += $amt; }
            if ($dir === 'out') { $safeAgg[$sid]['out'] += $amt; $safeTotalOut += $amt; }
        }
    }

    // حساب الصافي والنِسَب للبار المصغّر
    foreach ($bankAgg as $k => $b) {
        $flow = max(($b['in'] ?? 0) + ($b['out'] ?? 0), 0.00001);
        $b['net']     = ($b['in'] ?? 0) - ($b['out'] ?? 0);
        $b['in_pct']  = round(($b['in']  ?? 0) / $flow * 100, 1);
        $b['out_pct'] = round(($b['out'] ?? 0) / $flow * 100, 1);
        $bankAgg[$k]  = $b;
    }
    foreach ($safeAgg as $k => $s) {
        $flow = max(($s['in'] ?? 0) + ($s['out'] ?? 0), 0.00001);
        $s['net']     = ($s['in'] ?? 0) - ($s['out'] ?? 0);
        $s['in_pct']  = round(($s['in']  ?? 0) / $flow * 100, 1);
        $s['out_pct'] = round(($s['out'] ?? 0) / $flow * 100, 1);
        $safeAgg[$k]  = $s;
    }

    $bankAgg = collect($bankAgg)->sortBy('name', SORT_NATURAL|SORT_FLAG_CASE)->values();
    $safeAgg = collect($safeAgg)->sortBy('name', SORT_NATURAL|SORT_FLAG_CASE)->values();

    // ====== كروت إضافية: المكاتبة / فرق البيع / ربح المكتب (داخل فقط من دفتر القيود للمكتب) ======
    $containsAny = function($txt, $words){
        $txt = mb_strtolower($txt ?? '');
        foreach ($words as $w) {
            if ($w === null || $w === '') continue;
            if (mb_stripos($txt, mb_strtolower($w)) !== false) return true;
        }
        return false;
    };

    $saleKeywords      = ['بيع','مبيع','مبيعات','sale','sales','فرق بيع','فرق البيع'];
    $mukatabaKeywords  = ['مكاتبة','مُكاتبة','كتابة','mukataba','mukātaba'];
    $officeProfitInKW  = ['ربح','أرباح','عوائد','عمولة','عمولات','profit','revenue','return'];

    $saleOnlyIn = $mktOnlyIn = $officeProfitOnlyIn = 0.0;

    foreach ($coll as $e) {
        if (!($e->is_office ?? false)) continue;          // المكتب فقط
        if (($e->direction ?? null) !== 'in') continue;   // داخل فقط

        $statusName = trim($e->status->name ?? '');
        $amt        = (float)($e->amount ?? 0);

        if ($containsAny($statusName, $saleKeywords))     $saleOnlyIn         += $amt;
        if ($containsAny($statusName, $mukatabaKeywords)) $mktOnlyIn          += $amt;
        if ($containsAny($statusName, $officeProfitInKW)) $officeProfitOnlyIn += $amt;
    }
@endphp

{{-- كروت: الحسابات البنكية + الخزن --}}
<div class="row g-3 mb-3" dir="rtl">
    {{-- الحسابات البنكية --}}
    <div class="col-12 col-xl-6">
        <div class="kpi-card p-0 h-100">
            <div class="card-head bank-grad p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="kpi-icon"><i class="bi bi-bank fs-4 text-success"></i></div>
                    <div>
                        <div class="fw-bold">الحسابات البنكية</div>
                        <div class="subnote">ضمن النتائج الحالية</div>
                    </div>
                </div>
                <span class="chip soft">عدد الحسابات: <strong>{{ $bankAgg->count() }}</strong></span>
            </div>

            <div class="p-3 pt-2">
                <div class="stat-box mb-2">
                    <div class="d-flex justify-content-between mini"><span>إجمالي داخل</span><strong class="text-success">{{ number_format($bankTotalIn,2) }}</strong></div>
                    <div class="d-flex justify-content-between mini"><span>إجمالي خارج</span><strong class="text-danger">{{ number_format($bankTotalOut,2) }}</strong></div>
                    @php $bankNet = $bankTotalIn - $bankTotalOut; @endphp
                    <div class="d-flex justify-content-between mini">
                        <span>الصافي</span>
                        <strong class="{{ $bankNet>=0?'text-success':'text-danger' }}">{{ number_format($bankNet,2) }}</strong>
                    </div>
                </div>

                @if($bankAgg->isNotEmpty())
                    <div class="table-responsive nice-scroll">
                        <table class="table table-sm align-middle mb-0 table-hover">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>الحساب</th>
                                    <th style="width:34%"></th>
                                    <th class="text-end" style="width:12%">داخل</th>
                                    <th class="text-end" style="width:12%">خارج</th>
                                    <th class="text-end" style="width:12%">صافي</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bankAgg as $b)
                                    <tr>
                                        <td class="text-truncate" style="max-width:220px">
                                            <i class="bi bi-building-fill-check text-success me-1"></i>{{ $b['name'] }}
                                        </td>
                                        <td>
                                            <div class="stacked-bar" title="داخل {{ $b['in_pct'] }}% / خارج {{ $b['out_pct'] }}%">
                                                <span class="in"  style="width: {{ $b['in_pct'] }}%"></span>
                                                <span class="out" style="width: {{ $b['out_pct'] }}%"></span>
                                            </div>
                                        </td>
                                        <td class="text-end text-success">{{ number_format($b['in'],2) }}</td>
                                        <td class="text-end text-danger">{{ number_format($b['out'],2) }}</td>
                                        <td class="text-end fw-semibold {{ ($b['net']??0)>=0?'text-success':'text-danger' }}">{{ number_format($b['net']??0,2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="2" class="text-end">الإجمالي</th>
                                    <th class="text-end text-success">{{ number_format($bankTotalIn,2) }}</th>
                                    <th class="text-end text-danger">{{ number_format($bankTotalOut,2) }}</th>
                                    <th class="text-end fw-semibold {{ $bankNet>=0?'text-success':'text-danger' }}">{{ number_format($bankNet,2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-muted mini">لا توجد حركات بنكية ضمن النتائج.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- الخزن --}}
    <div class="col-12 col-xl-6">
        <div class="kpi-card p-0 h-100">
            <div class="card-head safe-grad p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="kpi-icon"><i class="bi bi-safe2 fs-4 text-warning"></i></div>
                    <div>
                        <div class="fw-bold">الخزن</div>
                        <div class="subnote">ضمن النتائج الحالية</div>
                    </div>
                </div>
                <span class="chip soft">عدد الخزن: <strong>{{ $safeAgg->count() }}</strong></span>
            </div>

            <div class="p-3 pt-2">
                <div class="stat-box mb-2">
                    <div class="d-flex justify-content-between mini"><span>إجمالي داخل</span><strong class="text-success">{{ number_format($safeTotalIn,2) }}</strong></div>
                    <div class="d-flex justify-content-between mini"><span>إجمالي خارج</span><strong class="text-danger">{{ number_format($safeTotalOut,2) }}</strong></div>
                    @php $safeNet = $safeTotalIn - $safeTotalOut; @endphp
                    <div class="d-flex justify-content-between mini">
                        <span>الصافي</span>
                        <strong class="{{ $safeNet>=0?'text-success':'text-danger' }}">{{ number_format($safeNet,2) }}</strong>
                    </div>
                </div>

                @if($safeAgg->isNotEmpty())
                    <div class="table-responsive nice-scroll">
                        <table class="table table-sm align-middle mb-0 table-hover">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>الخزنة</th>
                                    <th style="width:34%"></th>
                                    <th class="text-end" style="width:12%">داخل</th>
                                    <th class="text-end" style="width:12%">خارج</th>
                                    <th class="text-end" style="width:12%">صافي</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($safeAgg as $s)
                                    <tr>
                                        <td class="text-truncate" style="max-width:220px">
                                            <i class="bi bi-archive-fill text-warning me-1"></i>{{ $s['name'] }}
                                        </td>
                                        <td>
                                            <div class="stacked-bar" title="داخل {{ $s['in_pct'] }}% / خارج {{ $s['out_pct'] }}%">
                                                <span class="in"  style="width: {{ $s['in_pct'] }}%"></span>
                                                <span class="out" style="width: {{ $s['out_pct'] }}%"></span>
                                            </div>
                                        </td>
                                        <td class="text-end text-success">{{ number_format($s['in'],2) }}</td>
                                        <td class="text-end text-danger">{{ number_format($s['out'],2) }}</td>
                                        <td class="text-end fw-semibold {{ ($s['net']??0)>=0?'text-success':'text-danger' }}">{{ number_format($s['net']??0,2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="2" class="text-end">الإجمالي</th>
                                    <th class="text-end text-success">{{ number_format($safeTotalIn,2) }}</th>
                                    <th class="text-end text-danger">{{ number_format($safeTotalOut,2) }}</th>
                                    <th class="text-end fw-semibold {{ $safeNet>=0?'text-success':'text-danger' }}">{{ number_format($safeNet,2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-muted mini">لا توجد حركات خزنة ضمن النتائج.</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- كروت إضافية: المكاتبة / فرق البيع / ربح المكتب (داخل فقط) --}}
<div class="row g-3 mb-3" dir="rtl">
    <div class="col-12 col-md-4">
        <div class="kpi-card pretty p-3">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="kpi-icon"><i class="bi bi-journal-text fs-4 text-primary"></i></div>
                <div>
                    <div class="fw-bold">المكاتبة</div>
                    <div class="subnote">مجمّعة من دفتر القيود</div>
                </div>
            </div>
            <div class="kpi-value fw-bold text-success">{{ number_format($mktOnlyIn,2) }}</div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="kpi-card pretty p-3">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="kpi-icon"><i class="bi bi-bag-check fs-4 text-success"></i></div>
                <div>
                    <div class="fw-bold">فرق البيع</div>
                    <div class="subnote">مجمّعة من دفتر القيود</div>
                </div>
            </div>
            <div class="kpi-value fw-bold text-success">{{ number_format($saleOnlyIn,2) }}</div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="kpi-card pretty p-3">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="kpi-icon"><i class="bi bi-briefcase fs-4 text-warning"></i></div>
                <div>
                    <div class="fw-bold">ربح المكتب</div>
                    <div class="subnote">مجمّعة من دفتر القيود</div>
                </div>
            </div>
            <div class="kpi-value fw-bold text-success">{{ number_format($officeProfitOnlyIn,2) }}</div>
        </div>
    </div>
</div>

{{-- الجدول --}}
<div class="card shadow-sm">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover align-middle text-center mb-0">
            <thead class="table-light" style="position: sticky; top: 0; z-index: 2;">
                <tr>
                    <th style="width:120px">التاريخ</th>
                    <th>الجهة</th>
                    <th>الحالة</th>
                    <th>النوع</th>
                    <th>الاتجاه</th>
                    <th class="text-end">المبلغ</th>
                    <th>الحساب</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $e)
                    <tr>
                        <td>{{ $e->entry_date?->format('Y-m-d') }}</td>
                        <td>
                            @if($e->is_office)
                                <span class="badge bg-secondary">المكتب</span>
                            @else
                                {{ $e->investor->name ?? '-' }}
                            @endif
                        </td>
                        <td>
                            @php $statusText = $e->status->name ?? '-'; @endphp
                            @if(!empty($e->notes))
                                <span data-bs-toggle="tooltip" data-bs-container="body" data-bs-placement="top" title="{{ $e->notes }}">
                                    {{ $statusText }}
                                </span>
                            @else
                                {{ $statusText }}
                            @endif
                        </td>
                        <td>{{ $e->type->name ?? '-' }}</td>
                        <td>
                            @if($e->direction === 'in')
                                <span class="badge bg-success">داخل</span>
                            @else
                                <span class="badge bg-danger">خارج</span>
                            @endif
                        </td>
                        <td class="text-end fw-semibold">
                            @if($e->direction === 'out') - @endif
                            {{ number_format($e->amount, 2) }}
                        </td>
                        <td>
                            @if($e->bankAccount)
                                <i class="bi bi-bank"></i> {{ $e->bankAccount->name }}
                            @elseif($e->safe)
                                <i class="bi bi-safe2"></i> {{ $e->safe->name }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-5 text-muted">لا توجد قيود مطابقة للبحث.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($entries->hasPages())
        <div class="mt-3 p-3">
            {{ $entries->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
.table-responsive { max-height: 65vh; }
.nice-scroll { max-height: 42vh; }

/* كروت + لمسات */
:root{
  --card-r:1rem; --soft:0 6px 18px rgba(0,0,0,.06); --soft2:0 10px 24px rgba(0,0,0,.08);
  --grad-bank: linear-gradient(135deg,#e8f7ff 0%,#ffffff 70%);
  --grad-safe: linear-gradient(135deg,#fff7e6 0%,#ffffff 70%);
}
.kpi-card{ border:1px solid #eef2f7; border-radius:var(--card-r); box-shadow:var(--soft); transition:.2s; }
.kpi-card:hover{ box-shadow:var(--soft2); transform: translateY(-2px); }
.kpi-card.pretty{ background: #fff; }
.card-head{ border-bottom:1px solid #eef2f7; border-top-left-radius:var(--card-r); border-top-right-radius:var(--card-r); }
.bank-grad{ background: var(--grad-bank); }
.safe-grad{ background: var(--grad-safe); }
.kpi-icon{ width:52px;height:52px;border-radius:.9rem;display:grid;place-items:center;background:#f4f6fb; }
.kpi-value{ font-size:1.65rem; line-height:1; }
.subnote{ font-size:.85rem; color:#6b7280; }
.chip{ background:#f1f4f9; color:#374151; border-radius:999px; padding:.25rem .6rem; font-weight:600; }
.chip.soft{ background:#eef6ff; color:#1d4ed8; }
.stat-box{ border:1px dashed #e5e7eb; border-radius:.75rem; padding:.5rem .75rem; background:#fafafa; }

/* البار المصغّر (داخل/خارج) */
.stacked-bar{ position:relative; width:100%; height:8px; background:#f3f4f6; border-radius:999px; overflow:hidden; box-shadow: inset 0 0 0 1px #eef2f7; }
.stacked-bar > span{ display:block; height:100%; float:right; } /* RTL */
.stacked-bar .in{ background:#16a34a55; }
.stacked-bar .out{ background:#ef444455; }

/* جدول */
.table > :not(caption) > * > *{ vertical-align: middle; }
.table-hover tbody tr:hover{ background:#f9fbff; }
.text-truncate{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.sticky-top{ top:0; z-index: 1; }

/* عموميات */
.bar-8{ height:8px; }
.mini{ font-size:.9rem; }
.invisible{ visibility: hidden !important; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form         = document.getElementById('filtersForm');
    const catSelect    = document.getElementById('party_category');
    const investorWrap = document.getElementById('investorWrap');
    const investorSel  = document.getElementById('investor_id');
    const statusSel    = document.getElementById('status_id');
    const accountType  = document.getElementById('account_type');
    const fromDate     = document.getElementById('from');
    const toDate       = document.getElementById('to');

    // Debounce
    let timer = null;
    function autosubmit() { clearTimeout(timer); timer = setTimeout(() => form.requestSubmit(), 300); }

    // إظهار/إخفاء المستثمر بدون تغيير الشبكة
    function syncInvestorVisibility() {
        const isInv = (catSelect.value === 'investors' || catSelect.value === '');
        investorWrap.classList.toggle('invisible', !isInv);
        investorSel.disabled = !isInv;
        if (!isInv) investorSel.value = '';
    }

    // فلترة الحالات حسب الفئة + إخفاء التحويل (type=3)
    const allStatusOptions = Array.from(statusSel.querySelectorAll('option[data-cat]'));
    function filterStatusesByCategory() {
        const cat = catSelect.value;
        let keepSelected = false;

        allStatusOptions.forEach(op => {
            const isTransfer = (op.dataset.type === '3');
            const show = (cat === '' ? true : (op.dataset.cat === cat));
            if (isTransfer || !show) {
                op.disabled = true; op.hidden = true; if (op.selected) keepSelected = true;
            } else { op.disabled = false; op.hidden = false; }
        });

        if (keepSelected) statusSel.value = '';
    }

    // تفعيل Tooltips
    if (window.bootstrap && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el, {container: 'body'});
        });
    }

    // ربط الأحداث مع Auto-submit
    [catSelect, investorSel, statusSel, accountType].forEach(el => {
        el && el.addEventListener('change', () => {
            if (el === catSelect) { syncInvestorVisibility(); filterStatusesByCategory(); }
            autosubmit();
        });
    });
    [fromDate, toDate].forEach(el => {
        el && el.addEventListener('change', autosubmit);
        el && el.addEventListener('keyup', (e)=> { if (e.key === 'Enter') autosubmit(); });
    });

    // زر مسح
    const btnClear = document.getElementById('btnClear');
    if (btnClear) {
        btnClear.addEventListener('click', function (e) {
            e.preventDefault();
            [catSelect, investorSel, statusSel, accountType, fromDate, toDate].forEach(el => { if (el) el.value = ''; });
            form.requestSubmit();
        });
    }

    // init
    syncInvestorVisibility();
    filterStatusesByCategory();
});
</script>
@endpush
