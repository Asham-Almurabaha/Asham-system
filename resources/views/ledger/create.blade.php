@extends('layouts.master')

@section('title', 'إضافة قيد')

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">إضافة قيد</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('ledger.index') }}">دفتر القيود</a></li>
            <li class="breadcrumb-item active">إضافة قيد</li>
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

@php
    $oldCat = old('party_category', 'investors');
    $oldAccountPicker = old('bank_account_id') ? 'bank:'.old('bank_account_id') : (old('safe_id') ? 'safe:'.old('safe_id') : '');
@endphp

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('ledger.store') }}" method="POST" class="row g-3 mt-1" id="createForm">
            @csrf

            <div class="row">
                {{-- الفئة --}}
                <div class="col-md-4">
                    <label class="form-label" for="party_category">الفئة</label>
                    <select name="party_category" id="party_category" class="form-select" required>
                        <option value="investors" @selected($oldCat==='investors')>المستثمرون</option>
                        <option value="office"    @selected($oldCat==='office')>المكتب</option>
                    </select>
                </div>

                {{-- المستثمر (شرطي عند investors) --}}
                <div class="col-md-4" id="investorWrap">
                    <label class="form-label" for="investor_id">المستثمر</label>
                    <select name="investor_id" id="investor_id" class="form-select" aria-describedby="investorHelp">
                        <option value="" disabled {{ old('investor_id') ? '' : 'selected' }}>اختر المستثمر</option>
                        @foreach ($investors as $investor)
                            <option value="{{ $investor->id }}" @selected(old('investor_id') == $investor->id)>{{ $investor->name }}</option>
                        @endforeach
                    </select>
                    <div id="investorHelp" class="form-text">إلزامي عند اختيار فئة المستثمرين.</div>
                    @error('investor_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                {{-- الحالة: قائمتان منفصلتان + حقل مخفي يوحّد الإرسال --}}
                <div class="col-md-4">
                    <label class="form-label">الحالة</label>

                    <select id="status_investors" class="form-select mb-2" {{ $oldCat==='investors' ? '' : 'hidden' }}>
                        <option value="" disabled {{ old('status_id') ? '' : 'selected' }}>اختر الحالة (مستثمر)</option>
                        @foreach(($statusesByCategory['investors'] ?? []) as $st)
                            <option value="{{ $st->id }}" data-type="{{ $st->transaction_type_id }}" @selected(old('status_id') == $st->id)>{{ $st->name }}</option>
                        @endforeach
                    </select>

                    <select id="status_office" class="form-select mb-2" {{ $oldCat==='office' ? '' : 'hidden' }}>
                        <option value="" disabled {{ old('status_id') ? '' : 'selected' }}>اختر الحالة (المكتب)</option>
                        @foreach(($statusesByCategory['office'] ?? []) as $st)
                            <option value="{{ $st->id }}" data-type="{{ $st->transaction_type_id }}" @selected(old('status_id') == $st->id)>{{ $st->name }}</option>
                        @endforeach
                    </select>

                    <input type="hidden" name="status_id" id="status_id_hidden" value="{{ old('status_id') }}">
                    <div class="mt-1">
                        <span class="badge rounded-pill bg-secondary" id="dirBadge">—</span>
                    </div>
                    @error('status_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
            {{-- مُلتقط الحساب (يجمع البنك والخزنة) + حقول مخفية --}}
            <div class="col-md-4 mt-0">
                <label class="form-label" for="account_picker">الحساب</label>
                <select id="account_picker" class="form-select" required>
                    <option value="" disabled {{ $oldAccountPicker ? '' : 'selected' }}>اختر حسابًا</option>
                    <optgroup label="الحسابات البنكية">
                        @foreach ($banks as $bank)
                            <option value="bank:{{ $bank->id }}" @selected($oldAccountPicker==='bank:'.$bank->id)>{{ $bank->name }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="الخزن">
                        @foreach ($safes as $safe)
                            <option value="safe:{{ $safe->id }}" @selected($oldAccountPicker==='safe:'.$safe->id)>{{ $safe->name }}</option>
                        @endforeach
                    </optgroup>
                </select>

                <input type="hidden" name="bank_account_id" id="bank_account_id" value="{{ old('bank_account_id') }}">
                <input type="hidden" name="safe_id"         id="safe_id"         value="{{ old('safe_id') }}">

                @error('bank_account_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                @error('safe_id')         <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- المبلغ + التاريخ --}}
            <div class="col-md-4 mt-0">
                <label class="form-label" for="amount">المبلغ</label>
                <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control" value="{{ old('amount') }}" required>
                @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4 mt-0">
                <label class="form-label" for="transaction_date">تاريخ العملية</label>
                <input type="date" name="transaction_date" id="transaction_date" class="form-control js-date" value="{{ old('transaction_date', now()->toDateString()) }}" required>
                @error('transaction_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- ملاحظات --}}
            <div class="col-12">
                <label class="form-label" for="notes">ملاحظات</label>
                <textarea name="notes" id="notes" rows="3" class="form-control" maxlength="1000">{{ old('notes') }}</textarea>
            </div>

            <div class="col-12 d-flex gap-2 mt-2">
                <button class="btn btn-primary" id="btnSave">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="btnSpinner" role="status" aria-hidden="true"></span>
                    حفظ
                </button>
                <a href="{{ route('ledger.index') }}" class="btn btn-secondary">إلغاء</a>

                <div class="ms-auto d-flex gap-2">
                    <a href="{{ route('ledger.transfer.create') }}" class="btn btn-outline-primary">تحويل داخلي</a>
                    <a href="{{ route('ledger.split.create') }}" class="btn btn-outline-secondary">قيد مُجزّأ</a>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const catSel = document.getElementById('party_category');
    const investorWrap = document.getElementById('investorWrap');

    const statusInv = document.getElementById('status_investors');
    const statusOff = document.getElementById('status_office');
    const statusHidden = document.getElementById('status_id_hidden');
    const dirBadge = document.getElementById('dirBadge');

    const accountPicker = document.getElementById('account_picker');
    const bankHidden = document.getElementById('bank_account_id');
    const safeHidden = document.getElementById('safe_id');

    const btnSave = document.getElementById('btnSave');
    const btnSpinner = document.getElementById('btnSpinner');
    const form = document.getElementById('createForm');

    function currentStatusSelect(){
        return catSel.value === 'investors' ? statusInv : statusOff;
    }

    function syncCategoryUI(){
        investorWrap.style.display = (catSel.value === 'investors') ? '' : 'none';
        statusInv.hidden = !(catSel.value === 'investors');
        statusOff.hidden = !(catSel.value === 'office');
        syncStatusHiddenAndBadge();
    }

    function syncStatusHiddenAndBadge(){
        const sel = currentStatusSelect();
        const opt = sel.options[sel.selectedIndex];
        statusHidden.value = opt ? (opt.value || '') : '';
        const t = opt ? (opt.dataset.type || '') : '';
        let text='—', cls='bg-secondary';
        if (t==='1'){ text='داخل (إيداع)'; cls='bg-success'; }
        else if (t==='2'){ text='خارج (سحب)'; cls='bg-danger'; }
        else if (t==='3'){ text='تحويل'; cls='bg-warning text-dark'; }
        dirBadge.textContent = text; dirBadge.className = 'badge rounded-pill ' + cls;
    }

    function syncAccountHidden(){
        const val = accountPicker.value || '';
        if (!val){ bankHidden.value=''; safeHidden.value=''; return; }
        const [type, id] = val.split(':');
        if (type === 'bank'){ bankHidden.value = id; safeHidden.value = ''; }
        else if (type === 'safe'){ safeHidden.value = id; bankHidden.value = ''; }
    }

    catSel.addEventListener('change', syncCategoryUI);
    statusInv.addEventListener('change', syncStatusHiddenAndBadge);
    statusOff.addEventListener('change', syncStatusHiddenAndBadge);
    accountPicker.addEventListener('change', syncAccountHidden);

    form.addEventListener('submit', function(){
        btnSave.disabled = true;
        btnSpinner.classList.remove('d-none');
        // تأكيد المزامنة قبل الإرسال
        syncStatusHiddenAndBadge();
        syncAccountHidden();
    });

    // init
    syncCategoryUI();
    syncStatusHiddenAndBadge();
    syncAccountHidden();
});
</script>
@endpush
@endsection
