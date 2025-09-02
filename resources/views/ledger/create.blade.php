@extends('layouts.master')

@section('title', 'إضافة قيد')

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">إضافة قيد</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('ledger.index') }}">@lang('sidebar.Ledger')</a></li>
            <li class="breadcrumb-item active">{{ __('Add') }}</li>
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
    $oldAccountPicker = old('bank_account_id') ? 'bank:'.old('bank_account_id')
                        : (old('safe_id') ? 'safe:'.old('safe_id') : '');

    // متغيرات البضائع (fallback لو الكنترولر لسه مبعتهومش)
    $goodsStatusIds = $goodsStatusIds ?? [];
    $products       = $products ?? collect();
    $oldProducts    = old('products', []);

    // مصفوفة IDs لأنواع البطاقات (اختياري لو ما عندك عمود is_card)
    // مرّر $cardTypeIds من الكنترولر إن وُجد
    $cardTypeIds    = $cardTypeIds ?? [];
@endphp

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('ledger.store') }}" method="POST" class="row g-3 mt-1" id="createForm" novalidate>
            @csrf

            <div class="row">
                {{-- الفئة --}}
                <div class="col-md-4">
                    <label class="form-label" for="party_category">@lang('ledger.Category')</label>
                    <select name="party_category" id="party_category" class="form-select" required>
                        <option value="investors" @selected($oldCat==='investors')>المستثمرون</option>
                        <option value="office"    @selected($oldCat==='office')>المكتب</option>
                    </select>
                </div>

                {{-- المستثمر (شرطي عند investors) --}}
                <div class="col-md-4" id="investorWrap">
                    <label class="form-label" for="investor_id">@lang('ledger.Investor')</label>
                    <select name="investor_id" id="investor_id" class="form-select" aria-describedby="investorHelp">
                        <option value="" disabled {{ old('investor_id') ? '' : 'selected' }}>اختر المستثمر</option>
                        @foreach ($investors as $investor)
                            <option value="{{ $investor->id }}" @selected(old('investor_id') == $investor->id)>{{ $investor->name }}</option>
                        @endforeach
                    </select>
                    <div id="investorHelp" class="form-text"></div>

                    {{-- سيولة المستثمر --}}
                    <div class="form-text mt-1">
                        <span class="text-muted">سيولة المستثمر المتاحة: </span>
                        <strong id="invAvailValue">—</strong>
                        <span id="invAvailLoading" class="spinner-border spinner-border-sm align-middle d-none" role="status" aria-hidden="true"></span>
                    </div>

                    @error('investor_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                {{-- الحالة: قائمتان منفصلتان + حقل مخفي يوحّد الإرسال --}}
                <div class="col-md-4">
                    <label class="form-label">@lang('ledger.Status')</label>

                    <select id="status_investors" class="form-select mb-2" {{ $oldCat==='investors' ? '' : 'hidden' }}
                            data-goods-ids='@json($goodsStatusIds)'>
                        <option value="" disabled {{ old('status_id') ? '' : 'selected' }}>اختر الحالة (مستثمر)</option>
                        @foreach(($statusesByCategory['investors'] ?? []) as $st)
                            @continue(($st->transaction_type_id ?? null) == 3) {{-- إخفاء التحويل --}}
                            <option value="{{ $st->id }}"
                                    data-type="{{ $st->transaction_type_id }}"
                                    @selected(old('status_id') == $st->id)>{{ $st->name }}</option>
                        @endforeach
                    </select>

                    <select id="status_office" class="form-select mb-2" {{ $oldCat==='office' ? '' : 'hidden' }}
                            data-goods-ids='@json($goodsStatusIds)'>
                        <option value="" disabled {{ old('status_id') ? '' : 'selected' }}>اختر الحالة (المكتب)</option>
                        @foreach(($statusesByCategory['office'] ?? []) as $st)
                            @continue(($st->transaction_type_id ?? null) == 3) {{-- إخفاء التحويل --}}
                            <option value="{{ $st->id }}"
                                    data-type="{{ $st->transaction_type_id }}"
                                    @selected(old('status_id') == $st->id)>{{ $st->name }}</option>
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
                <label class="form-label" for="account_picker">@lang('ledger.Account')</label>
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
                <label class="form-label" for="amount">@lang('ledger.Amount')</label>
                <input type="number" step="0.01" min="0" name="amount" id="amount" class="form-control" value="{{ old('amount', '0') }}" required>
                <div class="invalid-feedback">المبلغ يتجاوز الحد المسموح.</div>
                @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4 mt-0">
                <label class="form-label" for="transaction_date">@lang('ledger.Transaction Date')</label>
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
                                        <label class="form-label small mb-1">@lang('ledger.Product Type')</label>
                                        <select name="products[{{ $i }}][product_type_id]" class="form-select js-product-select">
                                            <option value="">— اختر —</option>
                                            @foreach($products as $p)
                                                <option value="{{ $p->id }}"
                                                        data-card="{{ in_array($p->id, $cardTypeIds) || ($p->is_card ?? false) ? 1 : 0 }}"
                                                        @selected($oldTypeId==$p->id)>{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small mb-1 d-flex align-items-center justify-content-between">
                                            <span>الكمية</span>
                                            <span class="badge bg-light text-dark js-available-badge">المتاح: —</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" min="1" name="products[{{ $i }}][quantity]" class="form-control js-qty-input" value="{{ $row['quantity'] ?? '' }}" placeholder="0">
                                            <button type="button" class="btn btn-outline-danger js-remove-product" title="حذف">حذف</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="row g-2 product-row align-items-end">
                                <div class="col-md-8">
                                    <label class="form-label small mb-1">@lang('ledger.Product Type')</label>
                                    <select name="products[0][product_type_id]" class="form-select js-product-select">
                                        <option value="">— اختر —</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}"
                                                    data-card="{{ in_array($p->id, $cardTypeIds) || ($p->is_card ?? false) ? 1 : 0 }}">
                                                {{ $p->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small mb-1 d-flex align-items-center justify-content-between">
                                        <span>الكمية</span>
                                        <span class="badge bg-light text-dark js-available-badge">المتاح: —</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" min="1" name="products[0][quantity]" class="form-control js-qty-input" placeholder="0">
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
                <label class="form-label" for="notes">@lang('ledger.Notes')</label>
                <textarea name="notes" id="notes" rows="3" class="form-control" maxlength="1000">{{ old('notes') }}</textarea>
            </div>

            <div class="col-12 d-flex gap-2 mt-2">
                <button class="btn btn-outline-success" id="btnSave">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="btnSpinner" role="status" aria-hidden="true"></span>
                    حفظ
                </button>
                <a href="{{ route('ledger.index') }}" class="btn btn-outline-secondary">@lang('app.Cancel')</a>

                <div class="ms-auto d-flex gap-2">
                    <a href="{{ route('ledger.transfer.create') }}" class="btn btn-outline-primary">تحويل داخلي</a>
                    <a href="{{ route('ledger.split.create') }}" class="btn btn-outline-secondary">قيد مُجزّأ</a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- قالب صف بضاعة جديد --}}
<template id="product_row_tpl">
    <div class="row g-2 product-row align-items-end mt-2">
        <div class="col-md-8">
            <label class="form-label small mb-1">@lang('ledger.Product Type')</label>
            <select class="form-select js-product-select">
                <option value="">— اختر —</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}"
                            data-card="{{ in_array($p->id, $cardTypeIds) || ($p->is_card ?? false) ? 1 : 0 }}">
                        {{ $p->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1 d-flex align-items-center justify-content-between">
                <span>الكمية</span>
                <span class="badge bg-light text-dark js-available-badge">المتاح: —</span>
            </label>
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
    const catSel        = document.getElementById('party_category');
    const investorWrap  = document.getElementById('investorWrap');
    const investorSel   = document.getElementById('investor_id');
    const invAvailValue = document.getElementById('invAvailValue');
    const invAvailLoading = document.getElementById('invAvailLoading');

    const statusInv   = document.getElementById('status_investors');
    const statusOff   = document.getElementById('status_office');
    const statusHidden= document.getElementById('status_id_hidden');
    const dirBadge    = document.getElementById('dirBadge');

    const accountPicker = document.getElementById('account_picker');
    const bankHidden    = document.getElementById('bank_account_id');
    const safeHidden    = document.getElementById('safe_id');

    const amountInput = document.getElementById('amount');
    const availSpan   = document.getElementById('availValue');
    const availLoading= document.getElementById('availLoading');

    const btnSave     = document.getElementById('btnSave');
    const btnSpinner  = document.getElementById('btnSpinner');
    const form        = document.getElementById('createForm');

    // ====== البضائع
    const goodsSection    = document.getElementById('goods_section');
    const productsWrapper = document.getElementById('products_wrapper');
    const btnAddProduct   = document.getElementById('btnAddProduct');
    const rowTpl          = document.getElementById('product_row_tpl');

    // Helpers
    function goodsIdsFrom(el){ try { return JSON.parse(el.dataset.goodsIds || '[]').map(Number); } catch(e){ return []; } }
    function currentStatusSelect(){ return catSel.value === 'investors' ? statusInv : statusOff; }

    // حالة المتاح
    let accountAvail  = null; // متاح الحساب (بنك/خزنة)
    let investorAvail = null; // سيولة المستثمر

    function currentDirectionType(){
        const sel = currentStatusSelect();
        const opt = sel ? sel.options[sel.selectedIndex] : null;
        return opt ? (opt.dataset.type || '') : '';
    }

    function syncCategoryUI(){
        investorWrap.style.display = (catSel.value === 'investors') ? '' : 'none';
        statusInv.hidden = !(catSel.value === 'investors');
        statusOff.hidden = !(catSel.value === 'office');
        syncStatusHiddenAndBadge();
        toggleGoodsSection();
        enforceStatusBeforeAccount();

        // تحديث/تصفير سيولة المستثمر حسب الفئة
        if (catSel.value === 'investors') {
            refreshInvestorLiquidity();
        } else {
            investorAvail = null;
            invAvailValue.textContent = '—';
        }

        applyMaxByDirection();
        validateAmount();
        reapplyRowsAvailabilityPolicy();
        validateGoodsQuantities();
    }

    function syncStatusHiddenAndBadge(){
        const sel = currentStatusSelect();
        const opt = sel.options[sel.selectedIndex];
        statusHidden.value = opt ? (opt.value || '') : '';
        const t = opt ? (opt.dataset.type || '') : '';
        let text='—', cls='bg-secondary';
        if (t==='1'){ text='داخل (إيداع)'; cls='bg-success'; }
        else if (t==='2'){ text='خارج (سحب)'; cls='bg-danger'; }
        dirBadge.textContent = text; dirBadge.className = 'badge rounded-pill ' + cls;
        enforceStatusBeforeAccount();
        applyMaxByDirection();
        validateAmount();
    }

    function clearAccountSelection(){
        accountPicker.value = '';
        bankHidden.value = '';
        safeHidden.value = '';
        availSpan.textContent = '—';
        amountInput.removeAttribute('max');
        accountAvail = null;
    }

    function enforceStatusBeforeAccount(){
        const hasStatus = !!statusHidden.value;
        accountPicker.disabled = !hasStatus;
        if (!hasStatus){ clearAccountSelection(); }
    }

    function syncAccountHidden(){
        const val = accountPicker.value || '';
        if (!val){ bankHidden.value=''; safeHidden.value=''; return; }
        const [type, id] = val.split(':');
        if (type === 'bank'){ bankHidden.value = id; safeHidden.value = ''; }
        else if (type === 'safe'){ safeHidden.value = id; bankHidden.value = ''; }
    }

    // عرض/إخفاء قسم البضائع
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

    // إدارة صفوف البضائع
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
        const appended = productsWrapper.querySelector('.product-row:last-child');
        if (appended) bindProductRow(appended);
        validateGoodsQuantities();
    }
    function handleRemoveClick(e){
        if (!e.target.classList.contains('js-remove-product')) return;
        const row = e.target.closest('.product-row');
        if (!row) return;
        if (productsWrapper.querySelectorAll('.product-row').length > 1){
            row.remove();
            validateGoodsQuantities();
        }
    }

    // --- توابع للمساعدة سابقًا (قد لا تُستخدم الآن) ---
    function isCardSaleMode(){
        return isGoodsStatus() && currentDirectionType() === '1';
    }
    function rowIsCard(row){
        const sel = row.querySelector('.js-product-select');
        if (!sel) return false;
        const opt = sel.options[sel.selectedIndex];
        return !!(opt && Number(opt.dataset.card) === 1);
    }
    function reapplyRowsAvailabilityPolicy(){
        productsWrapper.querySelectorAll('.product-row').forEach(row => {
            const sel = row.querySelector('.js-product-select');
            if (sel && sel.value) {
                reloadRowAvailability(row);
            } else {
                setRowAvailabilityUI(row, { success:true, available:0 });
            }
        });
    }

    // حد السحب = min(متاح الحساب، سيولة المستثمر إن وُجد مستثمر مختار)
    function applyMaxByDirection(){
        const t = currentDirectionType();

        if (t === '2') { // سحب
            let cap = null;

            if (accountAvail !== null) cap = accountAvail;

            const isInvestorFlow = (catSel.value === 'investors') && investorSel && investorSel.value;
            if (isInvestorFlow && investorAvail !== null) {
                cap = (cap === null) ? investorAvail : Math.min(cap, investorAvail);
            }

            if (cap !== null) amountInput.setAttribute('max', String(cap));
            else amountInput.removeAttribute('max');
        } else {
            amountInput.removeAttribute('max');
            amountInput.setCustomValidity('');
            amountInput.classList.remove('is-invalid');
        }
    }

    function validateAmount(){
        const t = currentDirectionType();
        const val = parseFloat(amountInput.value || '0');

        let cap = null;
        if (t === '2') {
            if (accountAvail !== null) cap = accountAvail;

            const isInvestorFlow = (catSel.value === 'investors') && investorSel && investorSel.value;
            if (isInvestorFlow && investorAvail !== null) {
                cap = (cap === null) ? investorAvail : Math.min(cap, investorAvail);
            }
        }

        if (t === '2' && cap !== null && val > cap + 1e-9){
            amountInput.setCustomValidity('المبلغ يتجاوز الحد المسموح (سيولة المستثمر/متاح الحساب).');
        } else {
            amountInput.setCustomValidity('');
        }
        amountInput.classList.toggle('is-invalid', !!amountInput.validationMessage);
    }

    // جلب متاح الحساب (بنك/خزنة)
    async function refreshAvailability(){
        const val = accountPicker.value || '';
        accountAvail = null;
        availSpan.textContent = '—';
        amountInput.removeAttribute('max');

        if (!val){ applyMaxByDirection(); validateAmount(); return; }

        const [type, id] = val.split(':');
        if (!type || !id){ applyMaxByDirection(); validateAmount(); return; }

        availLoading.classList.remove('d-none');
        try {
            const url = `{{ route('ajax.accounts.availability') }}` + `?account_type=${encodeURIComponent(type)}&account_id=${encodeURIComponent(id)}`;
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });

            if (!res.ok) {
                accountAvail = null;
                availSpan.textContent = '—';
            } else {
                const data = await res.json();
                if (data && data.success){
                    accountAvail = Number(data.available);
                    const s = (data.available_formatted ?? accountAvail.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}));
                    availSpan.textContent = s;
                } else {
                    accountAvail = null;
                    availSpan.textContent = '—';
                }
            }
        } catch (e){
            accountAvail = null;
            availSpan.textContent = '—';
        } finally {
            availLoading.classList.add('d-none');
            applyMaxByDirection();
            validateAmount();
        }
    }

    // سيولة المستثمر
    const INVESTOR_LIQ_URL_TPL = @json(route('ajax.investors.liquidity', ['investor' => '__ID__']));

    async function refreshInvestorLiquidity(){
        // نطبّق فقط لو الفئة "المستثمرون" ومختار مستثمر
        if (catSel.value !== 'investors') { investorAvail = null; invAvailValue.textContent = '—'; return; }

        const id = investorSel.value || '';
        investorAvail = null;
        invAvailValue.textContent = '—';
        if (!id) { applyMaxByDirection(); validateAmount(); return; }

        invAvailLoading.classList.remove('d-none');
        try{
            const url = INVESTOR_LIQ_URL_TPL.replace('__ID__', encodeURIComponent(id));
            const res = await fetch(url, { headers: { 'Accept':'application/json' }, credentials: 'same-origin' });
            if (!res.ok) {
                investorAvail = null;
                invAvailValue.textContent = '—';
            } else {
                const data = await res.json();
                const raw = Number(data.cash ?? data.balance ?? 0);
                if (Number.isFinite(raw)) {
                    investorAvail = raw;
                    const fmt = (data.formatted ?? raw.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}));
                    invAvailValue.textContent = fmt;
                } else {
                    investorAvail = null;
                    invAvailValue.textContent = '—';
                }
            }
        } catch(e){
            investorAvail = null;
            invAvailValue.textContent = '—';
        } finally {
            invAvailLoading.classList.add('d-none');
            applyMaxByDirection();
            validateAmount();
        }
    }

    // المتاح لكل نوع بضاعة (جلب من السيرفر + ضبط max فقط لو بيع بضائع)
    const AVAIL_URL_TPL = @json(route('product-types.available', ['productType' => '__ID__']));
    const availableCache = Object.create(null);

    async function fetchAvailableFor(typeId){
        if (!typeId) return { success:true, available:0 };
        if (availableCache[typeId] !== undefined) return availableCache[typeId];
        try{
            const url = AVAIL_URL_TPL.replace('__ID__', encodeURIComponent(typeId));
            const res = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (!res.ok) throw new Error('HTTP '+res.status);
            const data = await res.json();
            availableCache[typeId] = data;
            return data;
        }catch(e){
            availableCache[typeId] = { success:false, message:e.message };
            return availableCache[typeId];
        }
    }

    function setRowAvailabilityUI(row, payload){
        const badge = row.querySelector('.js-available-badge');
        const qty   = row.querySelector('.js-qty-input');

        if (!badge || !qty) return;

        if (!payload || payload.success !== true){
            const msg = (payload && payload.message) ? payload.message : 'تعذّر جلب المتاح';
            badge.textContent = 'خطأ: ' + msg;
            badge.className = 'badge bg-danger text-white js-available-badge';
            qty.removeAttribute('max');     // لا نفرض max عند فشل الجلب
            return;
        }

        const avail = Number(payload.available ?? payload.stock?.available ?? 0);
        const safeAvail = Number.isFinite(avail) ? Math.max(0, Math.floor(avail)) : 0;

        badge.textContent = 'المتاح: ' + safeAvail.toLocaleString('ar-EG');
        badge.className = 'badge bg-light text-dark js-available-badge';

        // ✅ شرط: max فقط في حالة "بيع البضائع" (أي نوع)
        if (isGoodsStatus() && currentDirectionType() === '1') {
            qty.setAttribute('max', String(safeAvail)); // قد تكون 0
        } else {
            qty.removeAttribute('max');
        }

        // لقط القيمة ضمن الحدود لو max موجود
        const maxAttr = qty.getAttribute('max');
        const max = maxAttr ? parseInt(maxAttr,10) : Infinity;
        let v = parseInt(qty.value || '0', 10) || 0;
        if (v < 0) v = 0;
        if (isFinite(max) && v > max) v = max;
        qty.value = v ? String(v) : '';
    }

    async function reloadRowAvailability(row){
        const sel = row.querySelector('.js-product-select');
        const qty = row.querySelector('.js-qty-input');
        const badge = row.querySelector('.js-available-badge');
        if (!sel || !qty || !badge) return;

        // حالة التحميل
        badge.textContent = 'جاري التحميل...';
        badge.className = 'badge bg-secondary text-white js-available-badge';

        const typeId = sel.value || '';
        const payload = await fetchAvailableFor(typeId);
        setRowAvailabilityUI(row, payload);
        validateGoodsQuantities();
    }

    function bindProductRow(row){
        const sel = row.querySelector('.js-product-select');
        const qty = row.querySelector('.js-qty-input');

        if (sel){
            sel.addEventListener('change', () => reloadRowAvailability(row));
            // تهيئة أولية إن كان النوع مختارًا من old()
            if (sel.value) reloadRowAvailability(row);
            else setRowAvailabilityUI(row, { success:true, available:0 });
        }
        if (qty){
            const clampQty = () => {
                const maxAttr = qty.getAttribute('max');
                const max = maxAttr ? parseInt(maxAttr,10) : Infinity;
                let v = parseInt(qty.value || '0', 10) || 0;
                if (v < 0) v = 0;
                if (isFinite(max) && v > max) v = max;
                qty.value = v ? String(v) : '';
            };
            qty.addEventListener('input', () => { clampQty(); validateGoodsQuantities(); });
            qty.addEventListener('blur',  () => { clampQty(); validateGoodsQuantities(); });
        }
    }

    // ✅ تحقق عام: لا تبيع أكثر من المتاح في حالة بيع البضائع
    function validateGoodsQuantities(){
        const isSale = isGoodsStatus() && currentDirectionType() === '1';
        let ok = true;

        productsWrapper.querySelectorAll('.product-row').forEach(row => {
            const sel = row.querySelector('.js-product-select');
            const qty = row.querySelector('.js-qty-input');
            if (!sel || !qty || !sel.value) return;

            // نظّف أي حالة قديمة
            qty.classList.remove('is-invalid');
            qty.setCustomValidity('');

            if (!isSale) return; // التحقق ينطبق فقط في البيع

            const maxAttr = qty.getAttribute('max');
            const max = maxAttr !== null ? parseInt(maxAttr,10) : null;
            const val = parseInt(qty.value || '0', 10) || 0;
            const effectiveMax = (max === null || isNaN(max)) ? 0 : max;

            if (val > effectiveMax){
                ok = false;
                qty.classList.add('is-invalid');
                qty.setCustomValidity('الكمية أكبر من المتاح في المخزون.');
            }
        });

        return ok;
    }

    // اربط كل الصفوف الحالية
    productsWrapper.querySelectorAll('.product-row').forEach(bindProductRow);

    // Events
    catSel.addEventListener('change', () => { syncCategoryUI(); reapplyRowsAvailabilityPolicy(); validateGoodsQuantities(); });

    statusInv.addEventListener('change', function(){
        syncStatusHiddenAndBadge();
        toggleGoodsSection();
        clearAccountSelection(); // يلزم اختيار الحساب بعد تغيير الحالة
        reapplyRowsAvailabilityPolicy();
        validateGoodsQuantities();
    });

    statusOff.addEventListener('change', function(){
        syncStatusHiddenAndBadge();
        toggleGoodsSection();
        clearAccountSelection(); // يلزم اختيار الحساب بعد تغيير الحالة
        reapplyRowsAvailabilityPolicy();
        validateGoodsQuantities();
    });

    accountPicker.addEventListener('change', function(){
        syncAccountHidden();
        refreshAvailability();
    });

    investorSel.addEventListener('change', function(){
        refreshInvestorLiquidity();
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

        // ✅ منع بيع كمية أكبر من المتاح قبل الإرسال
        const goodsOk = validateGoodsQuantities();
        if (!goodsOk){
            e.preventDefault();
            e.stopPropagation();
            alert('لا يمكنك بيع كمية أكبر من المتاح في المخزون.');
            return;
        }

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

    if (!amountInput.value || isNaN(parseFloat(amountInput.value))) {
        amountInput.value = '0';
    }

    // جلب سيولة المستثمر ومتـاح الحساب لو في old()
    refreshInvestorLiquidity();
    refreshAvailability();
    reapplyRowsAvailabilityPolicy();
    validateGoodsQuantities();
});
</script>
@endpush
