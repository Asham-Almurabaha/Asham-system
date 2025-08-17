@extends('layouts.master')

@section('title', 'تحويل داخلي بين حسابات المكتب')

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">تحويل داخلي (المكتب)</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('ledger.index') }}">دفتر القيود</a></li>
            <li class="breadcrumb-item active">تحويل داخلي</li>
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
    $oldFrom = old('from_bank_account_id') ? 'bank:'.old('from_bank_account_id') : (old('from_safe_id') ? 'safe:'.old('from_safe_id') : '');
    $oldTo   = old('to_bank_account_id')   ? 'bank:'.old('to_bank_account_id')   : (old('to_safe_id')   ? 'safe:'.old('to_safe_id')   : '');
@endphp

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('ledger.transfer.store') }}" method="POST" class="row g-3 mt-1" id="transferForm">
            @csrf

            {{-- من (مصدر) --}}
            <div class="col-md-5">
                <label class="form-label" for="from_picker">الحساب المصدر</label>
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
                @error('from_bank_account_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                @error('from_safe_id')         <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- زر تبديل --}}
            <div class="col-md-2 d-flex align-items-end justify-content-center">
                <button type="button" class="btn btn-outline-secondary" id="btnSwap" title="تبديل المصدر والوجهة">⇄</button>
            </div>

            {{-- إلى (وجهة) --}}
            <div class="col-md-5">
                <label class="form-label" for="to_picker">الحساب الوجهة</label>
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
                <label class="form-label" for="amount">المبلغ</label>
                <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control" value="{{ old('amount') }}" required>
                @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
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
                <button class="btn btn-primary" id="btnTransfer">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="btnSpinner" role="status" aria-hidden="true"></span>
                    تنفيذ التحويل
                </button>
                <a href="{{ route('ledger.index') }}" class="btn btn-secondary">إلغاء</a>
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
        // فعّل كل الخيارات أولاً (مع ترك placeholder disabled)
        [...fromPicker.options].forEach(o => { if (o.value) o.disabled = false; });
        [...toPicker.options].forEach(o => { if (o.value) o.disabled = false; });

        const fv = fromPicker.value;
        const tv = toPicker.value;

        if (fv){
            const tOpt = [...toPicker.options].find(o => o.value === fv);
            if (tOpt) tOpt.disabled = true;
            // لو الوجهة كانت مساوية للمصدر، صفّر الوجهة
            if (tv === fv){ toPicker.value = ''; sinkPickToHidden('to'); }
        }
        if (tv){
            const fOpt = [...fromPicker.options].find(o => o.value === tv);
            if (fOpt) fOpt.disabled = true;
            // لو المصدر كان مساوي للوجهة، صفّر المصدر
            if (fv === tv){ fromPicker.value = ''; sinkPickToHidden('from'); }
        }
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
    }

    fromPicker.addEventListener('change', ()=>{ sinkPickToHidden('from'); syncMutualDisable(); });
    toPicker.addEventListener('change',   ()=>{ sinkPickToHidden('to');   syncMutualDisable(); });
    btnSwap.addEventListener('click', swap);

    form.addEventListener('submit', function (e) {
        // حارس إضافي (ما المفروض يحصل مع التعطيل، بس للاحتياط)
        if (sameAccount()) {
            e.preventDefault();
            alert('لا يمكن التحويل لنفس الحساب.');
            return false;
        }
        btnTransfer.disabled = true;
        btnSpinner.classList.remove('d-none');
        sinkPickToHidden('from'); 
        sinkPickToHidden('to');
    });

    // init
    // احترم old() الممرّر من السيرفر
    if ('{{ $oldFrom }}') fromPicker.value = '{{ $oldFrom }}';
    if ('{{ $oldTo }}')   toPicker.value   = '{{ $oldTo }}';
    sinkPickToHidden('from'); 
    sinkPickToHidden('to');
    syncMutualDisable();
});
</script>
@endpush
@endsection
