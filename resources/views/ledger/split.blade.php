@extends('layouts.master')

@section('title', 'قيد مُجزّأ (بنك + خزنة)')

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">قيد مُجزّأ (جزء بنك + جزء خزنة)</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('ledger.index') }}">دفتر القيود</a></li>
            <li class="breadcrumb-item active">قيد مُجزّأ</li>
        </ol>
    </nav>
</div>

@if ($errors->any())
<div class="alert alert-danger">
    <div class="fw-semibold mb-1">تحقّق من الحقول التالية:</div>
    <ul class="mb-0">
        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
    </ul>
</div>
@endif

@php $oldCat = old('party_category', 'investors'); @endphp

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('ledger.split.store') }}" method="POST" class="row g-3 mt-1" id="splitForm">
            @csrf
            <div class="row">
                {{-- الفئة + المستثمر --}}
                <div class="col-md-4">
                    <label class="form-label" for="party_category">الفئة</label>
                    <select name="party_category" id="party_category" class="form-select" required>
                        <option value="investors" @selected($oldCat==='investors')>المستثمرون</option>
                        <option value="office"    @selected($oldCat==='office')>المكتب</option>
                    </select>
                </div>

                <div class="col-md-4" id="investorWrap">
                    <label class="form-label" for="investor_id">المستثمر</label>
                    <select name="investor_id" id="investor_id" class="form-select">
                        <option value="" disabled {{ old('investor_id') ? '' : 'selected' }}>اختر المستثمر</option>
                        @foreach ($investors as $investor)
                            <option value="{{ $investor->id }}" @selected(old('investor_id') == $investor->id)>{{ $investor->name }}</option>
                        @endforeach
                    </select>
                    @error('investor_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                {{-- الحالة: قائمتان منفصلتان + حقل مخفي (مع إخفاء حالات التحويل) --}}
                <div class="col-md-4">
                    <label class="form-label">الحالة</label>

                    <select id="status_investors" class="form-select mb-2" {{ $oldCat==='investors' ? '' : 'hidden' }}>
                        <option value="" disabled {{ old('status_id') ? '' : 'selected' }}>اختر الحالة (مستثمر)</option>
                        @foreach(($statusesByCategory['investors'] ?? []) as $st)
                            @if(($st->transaction_type_id ?? null) != 3)
                                <option value="{{ $st->id }}" data-type="{{ $st->transaction_type_id }}" @selected(old('status_id') == $st->id)>{{ $st->name }}</option>
                            @endif
                        @endforeach
                    </select>

                    <select id="status_office" class="form-select mb-2" {{ $oldCat==='office' ? '' : 'hidden' }}>
                        <option value="" disabled {{ old('status_id') ? '' : 'selected' }}>اختر الحالة (المكتب)</option>
                        @foreach(($statusesByCategory['office'] ?? []) as $st)
                            @if(($st->transaction_type_id ?? null) != 3)
                                <option value="{{ $st->id }}" data-type="{{ $st->transaction_type_id }}" @selected(old('status_id') == $st->id)>{{ $st->name }}</option>
                            @endif
                        @endforeach
                    </select>

                    <input type="hidden" name="status_id" id="status_id_hidden" value="{{ old('status_id') }}">
                    <div class="mt-1">
                        <span class="badge rounded-pill bg-secondary" id="dirBadge">—</span>
                    </div>
                    @error('status_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- إجمالي المبلغ + تاريخ --}}
            <div class="col-md-3 mt-0">
                <label class="form-label" for="amount">إجمالي المبلغ</label>
                <input
                    type="number" step="any" min="0.01" name="amount" id="amount"
                    class="form-control" value="{{ old('amount') }}" required
                    inputmode="decimal" lang="en" dir="ltr" pattern="[0-9]*[.,]?[0-9]*">
                @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3 mt-0">
                <label class="form-label" for="transaction_date">تاريخ العملية</label>
                <input type="date" name="transaction_date" id="transaction_date" class="form-control js-date" value="{{ old('transaction_date', now()->toDateString()) }}" required>
                @error('transaction_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- تفاصيل التوزيع --}}
            <div class="col-12">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-3">جزء البنك</h6>

                            <div class="mb-2">
                                <label class="form-label" for="bank_share">المبلغ (بنك)</label>
                                <input
                                    type="number" step="any" min="0" name="bank_share" id="bank_share"
                                    class="form-control" value="{{ old('bank_share', 0) }}"
                                    inputmode="decimal" lang="en" dir="ltr" pattern="[0-9]*[.,]?[0-9]*">
                                @error('bank_share') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <label class="form-label" for="bank_account_id">الحساب البنكي</label>
                                <select name="bank_account_id" id="bank_account_id" class="form-select">
                                    <option value="" disabled {{ old('bank_account_id') ? '' : 'selected' }}>اختر الحساب البنكي</option>
                                    @foreach ($banks as $bank)
                                        <option value="{{ $bank->id }}" @selected(old('bank_account_id') == $bank->id)>{{ $bank->name }}</option>
                                    @endforeach
                                </select>
                                @error('bank_account_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-3">جزء الخزنة</h6>

                            <div class="mb-2">
                                <label class="form-label" for="safe_share">المبلغ (خزنة)</label>
                                <input
                                    type="number" step="any" min="0" name="safe_share" id="safe_share"
                                    class="form-control" value="{{ old('safe_share', 0) }}"
                                    inputmode="decimal" lang="en" dir="ltr" pattern="[0-9]*[.,]?[0-9]*">
                                @error('safe_share') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <label class="form-label" for="safe_id">الخزنة</label>
                                <select name="safe_id" id="safe_id" class="form-select">
                                    <option value="" disabled {{ old('safe_id') ? '' : 'selected' }}>اختر الخزنة</option>
                                    @foreach ($safes as $safe)
                                        <option value="{{ $safe->id }}" @selected(old('safe_id') == $safe->id)>{{ $safe->name }}</option>
                                    @endforeach
                                </select>
                                @error('safe_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-2 small">
                    <span class="text-muted">المطلوب: بنك + خزنة = إجمالي المبلغ.</span>
                    <span id="sumHint" class="fw-semibold ms-2"></span>
                    <span id="ratioHint" class="ms-2 text-muted"></span>
                </div>
            </div>

            {{-- ملاحظات --}}
            <div class="col-12">
                <label class="form-label" for="notes">ملاحظات</label>
                <textarea name="notes" id="notes" rows="3" class="form-control" maxlength="1000">{{ old('notes') }}</textarea>
            </div>

            <div class="col-12 d-flex gap-2 mt-2">
                <button class="btn btn-primary" id="btnSubmit">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="btnSpinner" role="status" aria-hidden="true"></span>
                    حفظ
                </button>
                <a href="{{ route('ledger.index') }}" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // عناصر عامة
    const catSel     = document.getElementById('party_category');
    const invWrap    = document.getElementById('investorWrap');
    const statusInv  = document.getElementById('status_investors');
    const statusOff  = document.getElementById('status_office');
    const statusHid  = document.getElementById('status_id_hidden');
    const dirBadge   = document.getElementById('dirBadge');

    const amount     = document.getElementById('amount');
    const bankShare  = document.getElementById('bank_share');
    const safeShare  = document.getElementById('safe_share');
    const bankSel    = document.getElementById('bank_account_id');
    const safeSel    = document.getElementById('safe_id');
    const sumHint    = document.getElementById('sumHint');
    const ratioHint  = document.getElementById('ratioHint');
    const btnSubmit  = document.getElementById('btnSubmit');
    const btnSpinner = document.getElementById('btnSpinner');
    const form       = document.getElementById('splitForm');

    let lastEdited = null;     // 'bank' | 'safe' | null
    let programmatic = false;  // منع حلقات التحديث

    // ===== فئة/حالات =====
    function currentStatusSelect(){ return catSel.value==='investors' ? statusInv : statusOff; }

    function syncCategoryUI(){
        invWrap.style.display = (catSel.value==='investors') ? '' : 'none';
        statusInv.hidden = !(catSel.value==='investors');
        statusOff.hidden = !(catSel.value==='office');
        syncStatusHiddenAndBadge();
    }

    function syncStatusHiddenAndBadge(){
        const sel = currentStatusSelect();
        let idx = sel.selectedIndex;
        // حماية إضافية: لو اتسرّبت حالة تحويل (type=3) لأي سبب، نخفيها/نتجاوزها
        for (let i=0; i<sel.options.length; i++){
            const o = sel.options[i];
            if (o.dataset && o.dataset.type === '3') { o.hidden = true; o.disabled = true; if (i === idx) idx = 0; }
        }
        if (sel.options.length > 0) sel.selectedIndex = idx;

        const opt = sel.options[sel.selectedIndex];
        statusHid.value = opt ? (opt.value || '') : '';

        const t = opt ? (opt.dataset.type || '') : '';
        let text='—', cls='bg-secondary';
        if (t==='1'){ text='داخل (إيداع)'; cls='bg-success'; }
        else if (t==='2'){ text='خارج (سحب)'; cls='bg-danger'; }
        else { text='—'; cls='bg-secondary'; }
        dirBadge.textContent = text; dirBadge.className = 'badge rounded-pill ' + cls;
    }

    catSel.addEventListener('change', syncCategoryUI);
    statusInv.addEventListener('change', syncStatusHiddenAndBadge);
    statusOff.addEventListener('change', syncStatusHiddenAndBadge);

    // ===== أرقام =====
    function parseDec(v){
        if (v == null) return null;
        const s = String(v).trim().replace(',', '.');
        if (s === '' || s === '.' || s === '-.' ) return null; // السماح بالكتابة الجزئية
        const n = Number(s);
        return Number.isFinite(n) ? n : null;
    }
    function r2(n){ return Math.round(n * 100) / 100; }
    function fmt2(n){ return (Number.isFinite(n) ? n : 0).toFixed(2); }
    function formatOnBlur(el){
        const n = parseDec(el.value);
        if (n == null) return; // سيبه لو كتابة جزئية
        el.value = fmt2(Math.max(0, n));
    }

    // ===== منطق التقسيم =====
    function updateFromBank(){
        if (programmatic) return;
        lastEdited = 'bank';
        const a = parseDec(amount.value);
        const b = parseDec(bankShare.value);
        programmatic = true;
        if (a == null || b == null){ safeShare.value = ''; programmatic = false; return validate(); }
        const s = a - b;
        safeShare.value = s >= 0 ? String(r2(s)) : ''; // لو أكبر من الإجمالي يفرّغ المقابل
        programmatic = false;
        validate();
    }

    function updateFromSafe(){
        if (programmatic) return;
        lastEdited = 'safe';
        const a = parseDec(amount.value);
        const s = parseDec(safeShare.value);
        programmatic = true;
        if (a == null || s == null){ bankShare.value = ''; programmatic = false; return validate(); }
        const b = a - s;
        bankShare.value = b >= 0 ? String(r2(b)) : '';
        programmatic = false;
        validate();
    }

    function updateFromAmount(){
        if (programmatic) return;
        // كل تغيير في الإجمالي يُصفر الجزأين
        programmatic = true;
        bankShare.value = '0';
        safeShare.value = '0';
        lastEdited = null;
        programmatic = false;
        validate();
    }

    // ===== فاليوديشن =====
    function validate(){
        const a = parseDec(amount.value);
        const b = parseDec(bankShare.value);
        const s = parseDec(safeShare.value);

        let okSum = false, sum = 0;
        if (a != null && b != null && s != null){
            sum = r2(b + s);
            okSum = (a > 0) && (r2(a) === sum);
        }

        sumHint.textContent = `المجموع الحالي: ${sum.toFixed ? sum.toFixed(2) : '0.00'} / الإجمالي: ${a!=null ? r2(a).toFixed(2) : '0.00'}`;
        sumHint.className   = okSum ? 'text-success' : 'text-danger';

        const bp = (a && b!=null) ? Math.round((r2(b)/r2(a))*100) : 0;
        const sp = (a && s!=null) ? (100 - bp) : 0;
        ratioHint.textContent = (a && (b!=null || s!=null)) ? `النِسب: بنك ${bp}% — خزنة ${sp}%` : '';

        // required الديناميكي للاختيارات
        bankSel.required = !!(b && b > 0);
        safeSel.required = !!(s && s > 0);

        // تفعيل زر الحفظ بعد تحقق المجموع واختيار الحسابات المطلوبة
        let ok = okSum;
        if (ok && b && b > 0 && !bankSel.value) ok = false;
        if (ok && s && s > 0 && !safeSel.value) ok = false;
        btnSubmit.disabled = !ok;
    }

    // ===== Events =====
    amount.addEventListener('input',  updateFromAmount);
    bankShare.addEventListener('input', updateFromBank);
    safeShare.addEventListener('input', updateFromSafe);

    [amount, bankShare, safeShare].forEach(el => {
        el.addEventListener('blur', ()=>formatOnBlur(el));                 // نسّق عند الخروج
        el.addEventListener('wheel', e => { e.preventDefault(); el.blur(); }, { passive:false }); // منع تغيّر القيمة بالسكرول
    });

    [bankSel, safeSel].forEach(el => el.addEventListener('change', validate));

    form.addEventListener('submit', function(){
        btnSubmit.disabled = true;
        btnSpinner.classList.remove('d-none');
        [amount, bankShare, safeShare].forEach(formatOnBlur); // تنسيق نهائي
    });

    // init
    syncCategoryUI();
    syncStatusHiddenAndBadge();
    updateFromAmount();
});
</script>
@endpush
@endsection
