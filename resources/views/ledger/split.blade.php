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

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('ledger.split.store') }}" method="POST" class="row g-3 mt-1" id="splitForm">
            @csrf

            {{-- الفئة + المستثمر --}}
            <div class="col-md-4">
                <label class="form-label">الفئة</label>
                <select name="party_category" id="party_category" class="form-select" required>
                    <option value="investors" @selected(old('party_category','investors')==='investors')>المستثمرون</option>
                    <option value="office"    @selected(old('party_category')==='office')>المكتب</option>
                </select>
            </div>

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

            {{-- الحالة --}}
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

            {{-- إجمالي المبلغ + تاريخ --}}
            <div class="col-md-3">
                <label class="form-label">إجمالي المبلغ</label>
                <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control" value="{{ old('amount') }}" required>
                @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <label class="form-label">تاريخ العملية</label>
                <input type="date" name="transaction_date" class="form-control js-date" value="{{ old('transaction_date', now()->toDateString()) }}" required>
                @error('transaction_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- تفاصيل التوزيع --}}
            <div class="col-12">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <h6 class="mb-3">جزء البنك</h6>

                            <div class="mb-2">
                                <label class="form-label">المبلغ (بنك)</label>
                                <input type="number" step="0.01" min="0" name="bank_share" id="bank_share" class="form-control" value="{{ old('bank_share', 0) }}">
                                @error('bank_share') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <label class="form-label">الحساب البنكي</label>
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
                        <div class="border rounded p-3">
                            <h6 class="mb-3">جزء الخزنة</h6>

                            <div class="mb-2">
                                <label class="form-label">المبلغ (خزنة)</label>
                                <input type="number" step="0.01" min="0" name="safe_share" id="safe_share" class="form-control" value="{{ old('safe_share', 0) }}">
                                @error('safe_share') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <label class="form-label">الخزنة</label>
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
                </div>
            </div>

            {{-- ملاحظات --}}
            <div class="col-12">
                <label class="form-label">ملاحظات</label>
                <textarea name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
            </div>

            <div class="col-12 d-flex gap-2 mt-2">
                <button class="btn btn-primary" id="btnSubmit">حفظ</button>
                <a href="{{ route('ledger.index') }}" class="btn btn-secondary">إلغاء</a>
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

    const amount     = document.getElementById('amount');
    const bankShare  = document.getElementById('bank_share');
    const safeShare  = document.getElementById('safe_share');
    const sumHint    = document.getElementById('sumHint');
    const btnSubmit  = document.getElementById('btnSubmit');

    function syncCategoryUI() {
        const cat = catSelect.value;
        investorDiv.style.display = (cat === 'investors') ? '' : 'none';

        // فلترة الحالات حسب الفئة
        [...statusSel.querySelectorAll('optgroup, option')].forEach(el => {
            if (!el.dataset.cat) return;
            const show = el.dataset.cat === cat;
            el.disabled = !show;
            el.style.display = show ? '' : 'none';
        });

        if (statusSel.selectedOptions.length) {
            const sel = statusSel.selectedOptions[0];
            if (sel && sel.dataset.cat && sel.dataset.cat !== cat) {
                statusSel.value = '';
            }
        }
    }

    function validateSum() {
        const a = parseFloat(amount.value || '0');
        const b = parseFloat(bankShare.value || '0');
        const s = parseFloat(safeShare.value || '0');
        const sum = +(b + s).toFixed(2);

        let ok = true;
        if (a <= 0) ok = false;
        if (sum !== +a.toFixed(2)) ok = false;

        sumHint.textContent = `المجموع الحالي: ${sum} / الإجمالي: ${a}`;
        sumHint.className = ok ? 'text-success' : 'text-danger';
        btnSubmit.disabled = !ok;
    }

    [catSelect].forEach(el => el.addEventListener('change', syncCategoryUI));
    [amount, bankShare, safeShare].forEach(el => el.addEventListener('input', validateSum));

    // init
    syncCategoryUI();
    validateSum();
});
</script>
@endpush
@endsection
