@extends('layouts.master')

@section('title', 'تحويل داخلي بين حسابات المكتب')

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">تحويل داخلي (المكتب)</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('ledger.index') }}">@lang('sidebar.Ledger')</a></li>
            <li class="breadcrumb-item active">{{ __('Internal Transfer') }}</li>
        </ol>
    </nav>
</div>

@php
    $oldFrom = old('from_bank_account_id') ? 'bank:'.old('from_bank_account_id') : (old('from_safe_id') ? 'safe:'.old('from_safe_id') : '');
    $oldTo   = old('to_bank_account_id')   ? 'bank:'.old('to_bank_account_id')   : (old('to_safe_id')   ? 'safe:'.old('to_safe_id')   : '');
@endphp

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('ledger.transfer.store') }}" method="POST" class="row g-3 mt-1" id="transferForm">
            @csrf

            {{-- من (مصدر) --}}
            <div class="col-md-5">
                <label class="form-label" for="from_picker">@lang('accounts::ledger.From Account')</label>
                <select id="from_picker" class="form-select" required>
                    <option value="" disabled {{ $oldFrom ? '' : 'selected' }}>اختر الحساب المصدر</option>
                    <optgroup label="الحسابات البنكية">
                        @foreach ($banks as $bank)
                            <option value="bank:{{ $bank->id }}" @selected($oldFrom==='bank:'.$bank->id)>{{ $bank->name }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="الخزن">
                        @foreach ($safes as $safe)
                            <option value="safe:{{ $safe->id }}" @selected($oldFrom==='safe:'.$safe->id)>{{ $safe->name }}</option>
                        @endforeach
                    </optgroup>
                </select>
                <input type="hidden" name="from_type" id="from_type">
                <input type="hidden" name="from_bank_account_id" id="from_bank_account_id" value="{{ old('from_bank_account_id') }}">
                <input type="hidden" name="from_safe_id"         id="from_safe_id"         value="{{ old('from_safe_id') }}">
                {{-- 👇 عرض المتاح في المصدر --}}
                <div class="form-text mt-1">
                    <span class="text-muted">المتاح بالمصدر: </span>
                    <strong id="fromAvailValue">—</strong>
                    <span id="fromAvailLoading" class="spinner-border spinner-border-sm align-middle d-none" role="status" aria-hidden="true"></span>
                </div>
                @error('from_bank_account_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                @error('from_safe_id')         <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- زر تبديل --}}
            <div class="col-md-2 d-flex align-items-center justify-content-center">
                <button type="button" class="btn btn-outline-secondary" id="btnSwap" title="تبديل المصدر والوجهة">⇄</button>
            </div>

            {{-- إلى (وجهة) --}}
            <div class="col-md-5">
                <label class="form-label" for="to_picker">@lang('accounts::ledger.To Account')</label>
                <select id="to_picker" class="form-select" required>
                    <option value="" disabled {{ $oldTo ? '' : 'selected' }}>اختر الحساب الوجهة</option>
                    <optgroup label="الحسابات البنكية">
                        @foreach ($banks as $bank)
                            <option value="bank:{{ $bank->id }}" @selected($oldTo==='bank:'.$bank->id)>{{ $bank->name }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="الخزن">
                        @foreach ($safes as $safe)
                            <option value="safe:{{ $safe->id }}" @selected($oldTo==='safe:'.$safe->id)>{{ $safe->name }}</option>
                        @endforeach
                    </optgroup>
                </select>
                <input type="hidden" name="to_type" id="to_type">
                <input type="hidden" name="to_bank_account_id" id="to_bank_account_id" value="{{ old('to_bank_account_id') }}">
                <input type="hidden" name="to_safe_id"         id="to_safe_id"         value="{{ old('to_safe_id') }}">
                @error('to_bank_account_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                @error('to_safe_id')         <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- المبلغ + التاريخ --}}
            <div class="col-md-6">
                <label class="form-label" for="amount">@lang('accounts::ledger.Amount')</label>
                <input
                    type="number" step="0.01" min="0"
                    name="amount" id="amount"
                    class="form-control"
                    value="{{ old('amount', 0) }}" required>
                <div class="invalid-feedback">المبلغ يتجاوز المتاح في الحساب المصدر.</div>
                @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label" for="transaction_date">@lang('accounts::ledger.Transaction Date')</label>
                <input type="date" name="transaction_date" id="transaction_date" class="form-control js-date" value="{{ old('transaction_date', now()->toDateString()) }}" required>
                @error('transaction_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- ملاحظات --}}
            <div class="col-12">
                <label class="form-label" for="notes">@lang('accounts::ledger.Notes')</label>
                <textarea name="notes" id="notes" rows="3" class="form-control" maxlength="1000">{{ old('notes') }}</textarea>
            </div>

            <div class="col-12 d-flex gap-2 mt-2">
                <button class="btn btn-primary" id="btnTransfer">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="btnSpinner" role="status" aria-hidden="true"></span>
                    تنفيذ التحويل
                </button>
                <a href="{{ route('ledger.index') }}" class="btn btn-secondary">@lang('app.Cancel')</a>

                <div class="ms-auto d-flex gap-2">
                    <a href="{{ route('ledger.create') }}" class="btn btn-outline-success">إضافة قيد</a>
                    <a href="{{ route('ledger.split.create') }}" class="btn btn-outline-secondary">قيد مُجزّأ</a>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fromPicker = document.getElementById('from_picker');
    const toPicker   = document.getElementById('to_picker');
    const fromType   = document.getElementById('from_type');
    const toType     = document.getElementById('to_type');
    const fromBankId = document.getElementById('from_bank_account_id');
    const toBankId   = document.getElementById('to_bank_account_id');
    const fromSafeId = document.getElementById('from_safe_id');
    const toSafeId   = document.getElementById('to_safe_id');

    const form       = document.getElementById('transferForm');
    const btnTransfer= document.getElementById('btnTransfer');
    const btnSpinner = document.getElementById('btnSpinner');
    const btnSwap    = document.getElementById('btnSwap');

    // المبلغ + المتاح (جديد)
    const amount           = document.getElementById('amount');
    const fromAvailValue   = document.getElementById('fromAvailValue');
    const fromAvailLoading = document.getElementById('fromAvailLoading');
    let fromAvail = null; // رقم خام

    function parsePick(val){
        if(!val) return {type:'', id:''};
        const [t, id] = val.split(':');
        return {type:t, id:id};
    }

    function sinkPickToHidden(which){
        const picker = which==='from' ? fromPicker : toPicker;
        const {type, id} = parsePick(picker.value);
        if (which==='from'){
            fromType.value = type || '';
            if(type==='bank'){ fromBankId.value=id; fromSafeId.value=''; }
            else if(type==='safe'){ fromSafeId.value=id; fromBankId.value=''; }
            else { fromBankId.value=''; fromSafeId.value=''; }
        } else {
            toType.value = type || '';
            if(type==='bank'){ toBankId.value=id; toSafeId.value=''; }
            else if(type==='safe'){ toSafeId.value=id; toBankId.value=''; }
            else { toBankId.value=''; toSafeId.value=''; }
        }
    }

    // تعطيل الخيار المختار في القائمة الأخرى (متبادل)
    function syncMutualDisable(){
        [...fromPicker.options].forEach(o => { if (o.value) o.disabled = false; });
        [...toPicker.options].forEach(o => { if (o.value) o.disabled = false; });

        const fv = fromPicker.value;
        const tv = toPicker.value;

        if (fv){
            const tOpt = [...toPicker.options].find(o => o.value === fv);
            if (tOpt) tOpt.disabled = true;
            if (tv === fv){ toPicker.value = ''; sinkPickToHidden('to'); }
        }
        if (tv){
            const fOpt = [...fromPicker.options].find(o => o.value === tv);
            if (fOpt) fOpt.disabled = true;
            if (fv === tv){ fromPicker.value = ''; sinkPickToHidden('from'); }
        }

        // المبلغ يتفعّل فقط بعد اختيار المصدر والوجهة
        enforceAmountLock();
    }

    function sameAccount(){
        return fromPicker.value && toPicker.value && (fromPicker.value === toPicker.value);
    }

    function swap(){
        const tmp = fromPicker.value;
        fromPicker.value = toPicker.value;
        toPicker.value = tmp;
        sinkPickToHidden('from'); 
        sinkPickToHidden('to');
        syncMutualDisable();
        refreshFromAvailability(); // المصدر اتغيّر
    }

    // ====== جديد: قفل/فتح حقل المبلغ لحين اختيار الحسابات
    function enforceAmountLock(){
        const ready = !!fromPicker.value && !!toPicker.value;
        amount.readOnly = !ready;
        amount.classList.toggle('bg-light', !ready);
        if (!ready){
            amount.value = '0';
            amount.setCustomValidity('');
            amount.classList.remove('is-invalid');
        }
    }

    // ====== جديد: جلب المتاح من المصدر + منع تجاوز المتاح
    async function refreshFromAvailability(){
        fromAvail = null;
        fromAvailValue.textContent = '—';
        amount.removeAttribute('max');
        amount.setCustomValidity('');
        amount.classList.remove('is-invalid');

        const {type, id} = parsePick(fromPicker.value);
        if (!type || !id){ validateAmount(); return; }

        try {
            fromAvailLoading.classList.remove('d-none');
            const url = `{{ route('ajax.accounts.availability') }}?account_type=${encodeURIComponent(type)}&account_id=${encodeURIComponent(id)}`;
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('fetch failed');
            const data = await res.json();
            if (data && data.success){
                fromAvail = Number(data.available);
                const s = (data.available_formatted ?? fromAvail.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}));
                fromAvailValue.textContent = s;
                // التحويل دايمًا سحب من المصدر => طبّق الحد الأقصى
                if (Number.isFinite(fromAvail)) amount.setAttribute('max', String(fromAvail));
            } else {
                fromAvail = null;
            }
        } catch(e){
            console.error(e);
            fromAvail = null;
        } finally {
            fromAvailLoading.classList.add('d-none');
            validateAmount();
        }
    }

    function validateAmount(){
        const ready = !!fromPicker.value && !!toPicker.value;
        if (!ready){
            amount.setCustomValidity('');
            amount.classList.remove('is-invalid');
            return;
        }
        const val = parseFloat(amount.value || '0');
        // تجاوز المتاح؟
        if (fromAvail !== null && Number.isFinite(val) && val > fromAvail + 1e-9){
            amount.setCustomValidity('المبلغ يتجاوز المتاح في الحساب المصدر');
        } else {
            amount.setCustomValidity('');
        }
        amount.classList.toggle('is-invalid', !!amount.validationMessage);
    }

    fromPicker.addEventListener('change', ()=>{
        sinkPickToHidden('from'); 
        syncMutualDisable();
        refreshFromAvailability();
    });
    toPicker.addEventListener('change',   ()=>{
        sinkPickToHidden('to');   
        syncMutualDisable();
        // مجرد فتح/قفل المبلغ؛ المتاح يتحدد من المصدر فقط
        validateAmount();
    });
    btnSwap.addEventListener('click', swap);

    amount.addEventListener('input', validateAmount);

    form.addEventListener('submit', function (e) {
        if (sameAccount()) {
            e.preventDefault();
            alert('لا يمكن التحويل لنفس الحساب.');
            return false;
        }
        // تأكيد عدم تجاوز المتاح
        validateAmount();
        if (!form.checkValidity()){
            e.preventDefault();
            e.stopPropagation();
            amount.reportValidity();
            return;
        }
        btnTransfer.disabled = true;
        btnSpinner.classList.remove('d-none');
        sinkPickToHidden('from'); 
        sinkPickToHidden('to');
    });

    // init
    if ('{{ $oldFrom }}') fromPicker.value = '{{ $oldFrom }}';
    if ('{{ $oldTo }}')   toPicker.value   = '{{ $oldTo }}';
    sinkPickToHidden('from'); 
    sinkPickToHidden('to');
    syncMutualDisable();
    refreshFromAvailability();

    // اجعل القيمة الافتراضية 0 دائمًا لو مش موجودة
    if (!amount.value || isNaN(parseFloat(amount.value))) amount.value = '0';
    enforceAmountLock();
});
</script>
@endpush
@endsection
