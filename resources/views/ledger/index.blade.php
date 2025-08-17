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

                <div class="col-12 col-md-3" id="investorWrap">
                    <label class="form-label mb-1">المستثمر</label>
                    <select name="investor_id" id="investor_id" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        @foreach($investors as $inv)
                            <option value="{{ $inv->id }}" @selected((string)($filters['investor_id'] ?? '') === (string)$inv->id)>{{ $inv->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3">
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

                <div class="col-6 col-md-1">
                    <label class="form-label mb-1">من</label>
                    <input type="date" name="from" id="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label mb-1">إلى</label>
                    <input type="date" name="to" id="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
                </div>

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary btn-sm">تصفية</button>
                    <a href="{{ route('ledger.index') }}" class="btn btn-outline-secondary btn-sm" id="btnClear">مسح</a>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- بطاقات مجاميع --}}
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted">إجمالي داخل</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($totIn, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted">إجمالي خارج</div>
                <div class="fs-4 fw-bold text-danger">{{ number_format($totOut, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        @php $netClass = $net >= 0 ? 'text-success' : 'text-danger'; @endphp
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted">الصافي</div>
                <div class="fs-4 fw-bold {{ $netClass }}">{{ number_format($net, 2) }}</div>
            </div>
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
                                <span
                                    data-bs-toggle="tooltip"
                                    data-bs-container="body"
                                    data-bs-placement="top"
                                    title="{{ $e->notes }}">
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

@push('styles')
<style>
/* تحسين تمرير الجدول */
.table-responsive { max-height: 65vh; }
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

    // --- Debounce helper ---
    let timer = null;
    function autosubmit() {
        clearTimeout(timer);
        timer = setTimeout(() => form.requestSubmit(), 300);
    }

    // --- إظهار/إخفاء المستثمر بدون تحريك الشبكة (نحافظ على المساحة) ---
    function syncInvestorVisibility() {
        const isInv = (catSelect.value === 'investors' || catSelect.value === '');
        investorWrap.classList.toggle('invisible', !isInv);
        investorSel.disabled = !isInv;
        if (!isInv) investorSel.value = '';
    }

    // --- فلترة الحالات حسب الفئة + إخفاء حالات التحويل (type=3) ---
    const allStatusOptions = Array.from(statusSel.querySelectorAll('option[data-cat]'));
    function filterStatusesByCategory() {
        const cat = catSelect.value;
        let keepSelected = false;

        allStatusOptions.forEach(op => {
            const isTransfer = (op.dataset.type === '3'); // اخفاء التحويل نهائيا
            const show = (cat === '' ? true : (op.dataset.cat === cat));
            if (isTransfer || !show) {
                op.disabled = true;
                op.hidden   = true;
                if (op.selected) keepSelected = true;
            } else {
                op.disabled = false;
                op.hidden   = false;
            }
        });

        if (keepSelected) statusSel.value = '';
    }

    // --- تفعيل التولتيب لكل عناصر tooltip ---
    if (window.bootstrap && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el);
        });
    }

    // --- ربط الأحداث مع Auto-submit سريع ---
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
@endsection
