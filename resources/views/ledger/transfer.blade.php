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

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('ledger.transfer.store') }}" method="POST" class="row g-3 mt-1" id="transferForm">
            @csrf

            {{-- من: الحساب المصدر --}}
            <div class="col-12">
                <h6 class="mb-2">الحساب المصدر</h6>
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label d-block">نوع الحساب</label>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio"
                                   name="from_type" id="from_type_bank" value="bank"
                                   {{ old('from_type','bank')==='bank' ? 'checked' : '' }}>
                            <label class="form-check-label" for="from_type_bank">حساب بنكي</label>
                        </div>

                        <div class="form-check form-check-inline me-3">
                            <input class="form-check-input" type="radio"
                                   name="from_type" id="from_type_safe" value="safe"
                                   {{ old('from_type')==='safe' ? 'checked' : '' }}>
                            <label class="form-check-label" for="from_type_safe">خزنة</label>
                        </div>

                        @error('from_type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4" id="fromBankWrap">
                        <label class="form-label">الحساب البنكي (مصدر)</label>
                        <select name="from_bank_account_id" class="form-select">
                            <option value="" disabled {{ old('from_bank_account_id') ? '' : 'selected' }}>اختر الحساب البنكي</option>
                            @foreach ($banks as $bank)
                                <option value="{{ $bank->id }}" @selected(old('from_bank_account_id') == $bank->id)>{{ $bank->name }}</option>
                            @endforeach
                        </select>
                        @error('from_bank_account_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 d-none" id="fromSafeWrap">
                        <label class="form-label">الخزنة (مصدر)</label>
                        <select name="from_safe_id" class="form-select">
                            <option value="" disabled {{ old('from_safe_id') ? '' : 'selected' }}>اختر الخزنة</option>
                            @foreach ($safes as $safe)
                                <option value="{{ $safe->id }}" @selected(old('from_safe_id') == $safe->id)>{{ $safe->name }}</option>
                            @endforeach
                        </select>
                        @error('from_safe_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            {{-- إلى: الحساب الوجهة --}}
            <div class="col-12 mt-2">
                <h6 class="mb-2">الحساب الوجهة</h6>
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label d-block">نوع الحساب</label>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio"
                                   name="to_type" id="to_type_bank" value="bank"
                                   {{ old('to_type','bank')==='bank' ? 'checked' : '' }}>
                            <label class="form-check-label" for="to_type_bank">حساب بنكي</label>
                        </div>

                        <div class="form-check form-check-inline me-3">
                            <input class="form-check-input" type="radio"
                                   name="to_type" id="to_type_safe" value="safe"
                                   {{ old('to_type')==='safe' ? 'checked' : '' }}>
                            <label class="form-check-label" for="to_type_safe">خزنة</label>
                        </div>

                        @error('to_type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4" id="toBankWrap">
                        <label class="form-label">الحساب البنكي (وجهة)</label>
                        <select name="to_bank_account_id" class="form-select">
                            <option value="" disabled {{ old('to_bank_account_id') ? '' : 'selected' }}>اختر الحساب البنكي</option>
                            @foreach ($banks as $bank)
                                <option value="{{ $bank->id }}" @selected(old('to_bank_account_id') == $bank->id)>{{ $bank->name }}</option>
                            @endforeach
                        </select>
                        @error('to_bank_account_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 d-none" id="toSafeWrap">
                        <label class="form-label">الخزنة (وجهة)</label>
                        <select name="to_safe_id" class="form-select">
                            <option value="" disabled {{ old('to_safe_id') ? '' : 'selected' }}>اختر الخزنة</option>
                            @foreach ($safes as $safe)
                                <option value="{{ $safe->id }}" @selected(old('to_safe_id') == $safe->id)>{{ $safe->name }}</option>
                            @endforeach
                        </select>
                        @error('to_safe_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            {{-- المبلغ + التاريخ --}}
            <div class="col-md-6 mt-2">
                <label class="form-label">المبلغ</label>
                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
                @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6 mt-2">
                <label class="form-label">تاريخ العملية</label>
                <input type="date" name="transaction_date" class="form-control js-date" value="{{ old('transaction_date', now()->toDateString()) }}" required>
                @error('transaction_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- ملاحظات --}}
            <div class="col-12">
                <label class="form-label">ملاحظات</label>
                <textarea name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
            </div>

            <div class="col-12 d-flex gap-2 mt-2">
                <button class="btn btn-primary">تنفيذ التحويل</button>
                <a href="{{ route('ledger.index') }}" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fromTypeBank = document.getElementById('from_type_bank');
    const fromTypeSafe = document.getElementById('from_type_safe');
    const fromBankWrap = document.getElementById('fromBankWrap');
    const fromSafeWrap = document.getElementById('fromSafeWrap');

    const toTypeBank = document.getElementById('to_type_bank');
    const toTypeSafe = document.getElementById('to_type_safe');
    const toBankWrap = document.getElementById('toBankWrap');
    const toSafeWrap = document.getElementById('toSafeWrap');

    const form = document.getElementById('transferForm');

    function syncFrom() {
        const bank = fromTypeBank.checked;
        fromBankWrap.classList.toggle('d-none', !bank);
        fromSafeWrap.classList.toggle('d-none',  bank);
    }
    function syncTo() {
        const bank = toTypeBank.checked;
        toBankWrap.classList.toggle('d-none', !bank);
        toSafeWrap.classList.toggle('d-none',  bank);
    }

    [fromTypeBank, fromTypeSafe].forEach(r => r.addEventListener('change', syncFrom));
    [toTypeBank, toTypeSafe].forEach(r => r.addEventListener('change', syncTo));

    form.addEventListener('submit', function (e) {
        // منع اختيار نفس الحساب (بنك ↔ بنك نفس الـ id، أو خزنة ↔ خزنة نفس الـ id)
        const fromBank = document.querySelector('[name="from_bank_account_id"]');
        const toBank   = document.querySelector('[name="to_bank_account_id"]');
        const fromSafe = document.querySelector('[name="from_safe_id"]');
        const toSafe   = document.querySelector('[name="to_safe_id"]');

        if (fromTypeBank.checked && toTypeBank.checked) {
            if (fromBank && toBank && fromBank.value && toBank.value && fromBank.value === toBank.value) {
                e.preventDefault();
                alert('لا يمكن التحويل لنفس الحساب البنكي.');
                return false;
            }
        }
        if (fromTypeSafe.checked && toTypeSafe.checked) {
            if (fromSafe && toSafe && fromSafe.value && toSafe.value && fromSafe.value === toSafe.value) {
                e.preventDefault();
                alert('لا يمكن التحويل لنفس الخزنة.');
                return false;
            }
        }
    });

    // init (يحترم old() الافتراضي)
    syncFrom(); syncTo();
});
</script>
@endpush
@endsection
