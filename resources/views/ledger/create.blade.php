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

    // متغيرات البضائع (لو الكنترولر لسه مبعتهومش)
    $goodsStatusIds = $goodsStatusIds ?? [];
    $products = $products ?? collect();
    $oldProducts = old('products', []);
@endphp

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('ledger.store') }}" method="POST" class="row g-3 mt-1" id="createForm" novalidate>
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

                    <select id="status_investors" class="form-select mb-2" {{ $oldCat==='investors' ? '' : 'hidden' }}
                            data-goods-ids='@json($goodsStatusIds)'>
                        <option value="" disabled {{ old('status_id') ? '' : 'selected' }}>اختر الحالة (مستثمر)</option>
                        @foreach(($statusesByCategory['investors'] ?? []) as $st)
                            @continue($st->transaction_type_id == 3) {{-- إخفاء التحويل --}}
                            <option value="{{ $st->id }}" data-type="{{ $st->transaction_type_id }}" @selected(old('status_id') == $st->id)>{{ $st->name }}</option>
                        @endforeach
                    </select>

                    <select id="status_office" class="form-select mb-2" {{ $oldCat==='office' ? '' : 'hidden' }}
                            data-goods-ids='@json($goodsStatusIds)'>
                        <option value="" disabled {{ old('status_id') ? '' : 'selected' }}>اختر الحالة (المكتب)</option>
                        @foreach(($statusesByCategory['office'] ?? []) as $st)
                            @continue($st->transaction_type_id == 3) {{-- إخفاء التحويل --}}
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

            {{-- مُلتقط الحساب + عرض المتاح --}}
            <div class="col-md-4 mt-0">
                <label class="form-label" for="account_picker">الحساب</label>
                <select id="account_picker" class="form-select" required disabled>
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

                <div id="accountAvailability" class="form-text mt-1">
                    <span class="text-muted">المتاح في الحساب: </span>
                    <strong id="availValue">—</strong>
                    <span id="availLoading" class="spinner-border spinner-border-sm align-middle d-none" role="status" aria-hidden="true"></span>
                </div>

                @error('bank_account_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                @error('safe_id')         <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- المبلغ + التاريخ --}}
            <div class="col-md-4 mt-0">
                <label class="form-label" for="amount">المبلغ</label>
                <input type="number" step="0.01" min="0" name="amount" id="amount" class="form-control" value="{{ old('amount', '0') }}" required>
                <div class="invalid-feedback">المبلغ يتجاوز المتاح في الحساب.</div>
                @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4 mt-0">
                <label class="form-label" for="transaction_date">تاريخ العملية</label>
                <input type="date" name="transaction_date" id="transaction_date" class="form-control js-date" value="{{ old('transaction_date', now()->toDateString()) }}" required>
                @error('transaction_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- ====== قسم البضائع ====== --}}
            <div class="col-12" id="goods_section" style="display:none;">
                <div class="card border-0 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam me-1"></i> تفاصيل البضائع</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddProduct">إضافة نوع</button>
                    </div>
                    <div class="card-body" id="products_wrapper">
                        @if(!empty($oldProducts))
                            @foreach($oldProducts as $i => $row)
                                @php $oldTypeId = $row['product_type_id'] ?? $row['product_id'] ?? null; @endphp
                                <div class="row g-2 product-row align-items-end {{ $i>0 ? 'mt-2' : '' }}">
                                    <div class="col-md-8">
                                        <label class="form-label small mb-1">نوع البضاعة</label>
                                        <select name="products[{{ $i }}][product_type_id]" class="form-select">
                                            <option value="">— اختر —</option>
                                            @foreach($products as $p)
                                                <option value="{{ $p->id }}" @selected($oldTypeId==$p->id)>{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small mb-1">الكمية</label>
                                        <div class="input-group">
                                            <input type="number" min="1" name="products[{{ $i }}][quantity]" class="form-control" value="{{ $row['quantity'] ?? '' }}" placeholder="0">
                                            <button type="button" class="btn btn-outline-danger js-remove-product" title="حذف">حذف</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="row g-2 product-row align-items-end">
                                <div class="col-md-8">
                                    <label class="form-label small mb-1">نوع البضاعة</label>
                                    <select name="products[0][product_type_id]" class="form-select">
                                        <option value="">— اختر —</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">الكمية</label>
                                    <div class="input-group">
                                        <input type="number" min="1" name="products[0][quantity]" class="form-control" placeholder="0">
                                        <button type="button" class="btn btn-outline-danger js-remove-product" title="حذف">حذف</button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer small text-muted">
                        * لا يتم إجبارك على إدخال البضائع إلا إذا كانت الحالة شراء/بيع بضائع.
                    </div>
                </div>
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

{{-- قالب صف بضاعة جديد (Template) --}}
<template id="product_row_tpl">
    <div class="row g-2 product-row align-items-end mt-2">
        <div class="col-md-8">
            <label class="form-label small mb-1">نوع البضاعة</label>
            <select class="form-select js-product-select">
                <option value="">— اختر —</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1">الكمية</label>
            <div class="input-group">
                <input type="number" min="1" class="form-control js-qty-input" placeholder="0">
                <button type="button" class="btn btn-outline-danger js-remove-product" title="حذف">حذف</button>
            </div>
        </div>
    </div>
</template>
@endsection

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

    const amountInput = document.getElementById('amount');
    const availSpan = document.getElementById('availValue');
    const availLoading = document.getElementById('availLoading');

    const btnSave = document.getElementById('btnSave');
    const btnSpinner = document.getElementById('btnSpinner');
    const form = document.getElementById('createForm');

    // ====== البضائع
    const goodsSection = document.getElementById('goods_section');
    const productsWrapper = document.getElementById('products_wrapper');
    const btnAddProduct = document.getElementById('btnAddProduct');
    const rowTpl = document.getElementById('product_row_tpl');

    function goodsIdsFrom(el){
        try { return JSON.parse(el.dataset.goodsIds || '[]').map(Number); }
        catch(e){ return []; }
    }
    function currentStatusSelect(){
        return catSel.value === 'investors' ? statusInv : statusOff;
    }

    function syncCategoryUI(){
        investorWrap.style.display = (catSel.value === 'investors') ? '' : 'none';
        statusInv.hidden = !(catSel.value === 'investors');
        statusOff.hidden = !(catSel.value === 'office');
        syncStatusHiddenAndBadge();
        toggleGoodsSection();
        applyMaxByDirection();
        validateAmount();
        enforceStatusBeforeAccount();
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
        enforceStatusBeforeAccount();
    }

    function clearAccountSelection(){
        accountPicker.value = '';
        bankHidden.value = '';
        safeHidden.value = '';
        availSpan.textContent = '—';
        amountInput.removeAttribute('max');
    }

    function enforceStatusBeforeAccount(){
        const hasStatus = !!statusHidden.value;
        accountPicker.disabled = !hasStatus;
        if (!hasStatus){
            clearAccountSelection();
        }
    }

    function syncAccountHidden(){
        const val = accountPicker.value || '';
        if (!val){ bankHidden.value=''; safeHidden.value=''; return; }
        const [type, id] = val.split(':');
        if (type === 'bank'){ bankHidden.value = id; safeHidden.value = ''; }
        else if (type === 'safe'){ safeHidden.value = id; bankHidden.value = ''; }
    }

    // ====== عرض/إخفاء قسم البضائع
    function selectedStatusId(){
        const sel = currentStatusSelect();
        const opt = sel.options[sel.selectedIndex];
        return opt ? Number(opt.value || 0) : 0;
    }
    function isGoodsStatus(){
        const sel = currentStatusSelect();
        const ids = goodsIdsFrom(sel);
        const cur = selectedStatusId();
        return ids.includes(cur);
    }
    function toggleGoodsSection(){
        goodsSection.style.display = isGoodsStatus() ? '' : 'none';
    }

    // ====== إدارة صفوف البضائع
    function nextProductIndex(){
        const rows = productsWrapper.querySelectorAll('.product-row');
        return rows.length ? Math.max(...Array.from(rows).map(r => {
            const sel = r.querySelector('select[name^="products["]');
            if (!sel) return -1;
            const m = sel.name.match(/^products\[(\d+)\]/);
            return m ? Number(m[1]) : -1;
        })) + 1 : 0;
    }
    function wireRowNames(row, index){
        const sel = row.querySelector('.js-product-select');
        const qty = row.querySelector('.js-qty-input');
        if (sel) sel.setAttribute('name', `products[${index}][product_type_id]`);
        if (qty) qty.setAttribute('name', `products[${index}][quantity]`);
    }
    function addProductRow(){
        const frag = rowTpl.content.cloneNode(true);
        const row = frag.querySelector('.product-row');
        wireRowNames(row, nextProductIndex());
        productsWrapper.appendChild(frag);
    }
    function handleRemoveClick(e){
        if (!e.target.classList.contains('js-remove-product')) return;
        const row = e.target.closest('.product-row');
        if (!row) return;
        if (productsWrapper.querySelectorAll('.product-row').length > 1){
            row.remove();
        }
    }

    // ====== المتاح في الحساب + منع السحب بأكثر من المتاح
    let accountAvail = null; // رقم خام (float)

    function currentDirectionType(){
        const sel = currentStatusSelect();
        const opt = sel.options[sel.selectedIndex];
        return opt ? (opt.dataset.type || '') : '';
    }

    function applyMaxByDirection(){
        const t = currentDirectionType();
        if (t === '2' && accountAvail !== null){ // سحب فقط
            amountInput.setAttribute('max', String(accountAvail));
        } else {
            amountInput.removeAttribute('max');
        }
    }

    function validateAmount(){
        const t = currentDirectionType();
        const val = parseFloat(amountInput.value || '0');
        if (t === '2' && accountAvail !== null && val > accountAvail + 1e-9){
            amountInput.setCustomValidity('المبلغ يتجاوز المتاح في الحساب');
        } else {
            amountInput.setCustomValidity('');
        }
        amountInput.classList.toggle('is-invalid', !!amountInput.validationMessage);
    }

    async function refreshAvailability(){
        const val = accountPicker.value || '';
        accountAvail = null;
        availSpan.textContent = '—';
        amountInput.removeAttribute('max');

        if (!val){ validateAmount(); return; }

        const [type, id] = val.split(':');
        if (!type || !id){ validateAmount(); return; }

        availLoading.classList.remove('d-none');
        try {
            const url = `{{ route('ajax.accounts.availability') }}?account_type=${encodeURIComponent(type)}&account_id=${encodeURIComponent(id)}`;
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });

            if (!res.ok) {
                console.error('Availability fetch failed:', res.status, res.statusText);
                accountAvail = null;
                availSpan.textContent = '—';
                return;
            }

            const data = await res.json();
            if (data && data.success){
                accountAvail = Number(data.available);
                const s = (data.available_formatted ?? accountAvail.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}));
                availSpan.textContent = s;
                applyMaxByDirection();
            } else {
                accountAvail = null;
                availSpan.textContent = '—';
            }
        } catch (e){
            console.error('Availability fetch error:', e);
            accountAvail = null;
            availSpan.textContent = '—';
        } finally {
            availLoading.classList.add('d-none');
            validateAmount();
        }
    }

    // Events
    catSel.addEventListener('change', syncCategoryUI);

    statusInv.addEventListener('change', function(){
        syncStatusHiddenAndBadge();
        toggleGoodsSection();
        applyMaxByDirection();
        validateAmount();
        clearAccountSelection(); // يلزم اختيار الحساب بعد تغيير الحالة
    });

    statusOff.addEventListener('change', function(){
        syncStatusHiddenAndBadge();
        toggleGoodsSection();
        applyMaxByDirection();
        validateAmount();
        clearAccountSelection(); // يلزم اختيار الحساب بعد تغيير الحالة
    });

    accountPicker.addEventListener('change', function(){
        syncAccountHidden();
        refreshAvailability();
    });

    if (btnAddProduct) btnAddProduct.addEventListener('click', addProductRow);
    productsWrapper.addEventListener('click', handleRemoveClick);

    amountInput.addEventListener('input', validateAmount);

    form.addEventListener('submit', function(e){
        // الحالة أولاً
        if (!statusHidden.value){
            e.preventDefault();
            e.stopPropagation();
            alert('يرجى اختيار الحالة أولاً.');
            return;
        }

        // تأكيد المزامنة قبل الإرسال
        syncStatusHiddenAndBadge();
        syncAccountHidden();
        applyMaxByDirection();
        validateAmount();

        if (!form.checkValidity()){
            e.preventDefault();
            e.stopPropagation();
            amountInput.reportValidity();
            return;
        }

        btnSave.disabled = true;
        btnSpinner.classList.remove('d-none');
    });

    // init
    syncCategoryUI();
    syncStatusHiddenAndBadge();
    syncAccountHidden();
    // المبلغ افتراضي 0
    if (!amountInput.value || isNaN(parseFloat(amountInput.value))) {
        amountInput.value = '0';
    }
    enforceStatusBeforeAccount();
    refreshAvailability(); // في حال فيه حساب مختار من old()
});
</script>
@endpush
