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

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('ledger.store') }}" method="POST" class="row g-3 mt-1">
            @csrf

            {{-- الفئة --}}
            <div class="col-md-4">
                <label class="form-label">الفئة</label>
                <select name="party_category" id="party_category" class="form-select" required>
                    <option value="investors" @selected(old('party_category','investors')==='investors')>المستثمرون</option>
                    <option value="office"    @selected(old('party_category')==='office')>المكتب</option>
                </select>
            </div>

            {{-- المستثمر (شرطي) --}}
            <div class="col-md-8" id="investorWrap">
                <label class="form-label">المستثمر</label>
                <select name="investor_id" class="form-select">
                    <option value="" disabled {{ old('investor_id') ? '' : 'selected' }}>اختر المستثمر</option>
                    @foreach ($investors as $investor)
                        <option value="{{ $investor->id }}" @selected(old('investor_id') == $investor->id)>{{ $investor->name }}</option>
                    @endforeach
                </select>
                @error('investor_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- الحالة (مجموعتان) --}}
            <div class="col-md-6">
                <label class="form-label">الحالة</label>
                <select name="status_id" id="status_id" class="form-select" required>
                    <option value="" disabled {{ old('status_id') ? '' : 'selected' }}>اختر الحالة</option>

                    <optgroup label="حالات المستثمرين" data-cat="investors">
                        @foreach(($statusesByCategory['investors'] ?? []) as $st)
                            <option value="{{ $st->id }}" data-cat="investors" @selected(old('status_id') == $st->id)>{{ $st->name }}</option>
                        @endforeach
                    </optgroup>

                    <optgroup label="حالات المكتب" data-cat="office">
                        @foreach(($statusesByCategory['office'] ?? []) as $st)
                            <option value="{{ $st->id }}" data-cat="office" @selected(old('status_id') == $st->id)>{{ $st->name }}</option>
                        @endforeach
                    </optgroup>
                </select>
                @error('status_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- اختيار الحساب: بنك أو خزنة (فرونت فقط) --}}
            <div class="col-md-6">
                <label class="form-label d-block">نوع الحساب</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="__account_type" id="acc_bank" value="bank" checked>
                    <label class="form-check-label" for="acc_bank">حساب بنكي</label>
                </div>
                <div class="form-check form-check-inline me-3">
                    <input class="form-check-input" type="radio" name="__account_type" id="acc_safe" value="safe">
                    <label class="form-check-label" for="acc_safe">خزنة</label>
                </div>
            </div>

            {{-- الحساب البنكي --}}
            <div class="col-md-6" id="bankWrap">
                <label class="form-label">الحساب البنكي</label>
                <select name="bank_account_id" class="form-select">
                    <option value="" disabled {{ old('bank_account_id') ? '' : 'selected' }}>اختر الحساب البنكي</option>
                    @foreach ($banks as $bank)
                        <option value="{{ $bank->id }}" @selected(old('bank_account_id') == $bank->id)>{{ $bank->name }}</option>
                    @endforeach
                </select>
                @error('bank_account_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- الخزنة --}}
            <div class="col-md-6 d-none" id="safeWrap">
                <label class="form-label">الخزنة</label>
                <select name="safe_id" class="form-select">
                    <option value="" disabled {{ old('safe_id') ? '' : 'selected' }}>اختر الخزنة</option>
                    @foreach ($safes as $safe)
                        <option value="{{ $safe->id }}" @selected(old('safe_id') == $safe->id)>{{ $safe->name }}</option>
                    @endforeach
                </select>
                @error('safe_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- المبلغ + التاريخ --}}
            <div class="col-md-6">
                <label class="form-label">المبلغ</label>
                <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
                @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
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
                <button class="btn btn-primary">حفظ</button>
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
    const catSelect   = document.getElementById('party_category');
    const investorDiv = document.getElementById('investorWrap');
    const statusSel   = document.getElementById('status_id');

    const accBank = document.getElementById('acc_bank');
    const accSafe = document.getElementById('acc_safe');
    const bankWrap = document.getElementById('bankWrap');
    const safeWrap = document.getElementById('safeWrap');

    function syncCategoryUI() {
        const cat = catSelect.value;
        // المستثمر يظهر فقط مع investors
        investorDiv.style.display = (cat === 'investors') ? '' : 'none';

        // فلترة الحالات بحسب الفئة
        [...statusSel.querySelectorAll('optgroup, option')].forEach(el => {
            if (!el.dataset.cat) return;
            const show = el.dataset.cat === cat;
            el.disabled = !show;
            el.style.display = show ? '' : 'none';
        });

        // لو الحالة المختارة مش ضمن الفئة، صفّرها
        if (statusSel.selectedOptions.length) {
            const sel = statusSel.selectedOptions[0];
            if (sel && sel.dataset.cat && sel.dataset.cat !== cat) {
                statusSel.value = '';
            }
        }
    }

    function syncAccountUI() {
        const isBank = accBank.checked;
        bankWrap.classList.toggle('d-none', !isBank);
        safeWrap.classList.toggle('d-none',  isBank);
    }

    catSelect.addEventListener('change', syncCategoryUI);
    accBank.addEventListener('change', syncAccountUI);
    accSafe.addEventListener('change', syncAccountUI);

    // init
    syncCategoryUI();
    syncAccountUI();
});
</script>
@endpush
@endsection
