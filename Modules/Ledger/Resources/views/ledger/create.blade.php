@extends('layouts.master')

@php
    $pageTitleText           = $pageTitleText           ?? 'إضافة قيد';
    $pageHeading             = $pageHeading             ?? 'إضافة قيد';
    $breadcrumbParentUrl     = $breadcrumbParentUrl     ?? route('ledger.index');
    $breadcrumbParentLabel   = $breadcrumbParentLabel   ?? __('sidebar.Ledger');
    $formAction              = $formAction              ?? route('ledger.store');
    $cancelUrl               = $cancelUrl               ?? route('ledger.index');
    $showTransferLinks       = $showTransferLinks       ?? true;
    $restrictPartyToInvestors= $restrictPartyToInvestors?? false;
    $redirectRouteName       = $redirectRouteName       ?? null;
@endphp

@section('title', $pageTitleText)

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ $pageHeading }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ $breadcrumbParentUrl }}">{{ $breadcrumbParentLabel }}</a></li>
            <li class="breadcrumb-item active">{{ __('Add') }}</li>
        </ol>
    </nav>
</div>

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
        <form action="{{ $formAction }}" method="POST" class="row g-3 mt-1" id="createForm" novalidate>
            @csrf
            @if(!empty($redirectRouteName))
                <input type="hidden" name="redirect_to" value="{{ $redirectRouteName }}">
            @endif

            <div class="row">
                {{-- الفئة --}}
                <div class="col-md-4">
                    <label class="form-label" for="party_category">@lang('ledger::ledger.Category')</label>
                    @if($restrictPartyToInvestors)
                        <select id="party_category" class="form-select" disabled>
                            <option value="investors" selected>المستثمرون</option>
                        </select>
                        <input type="hidden" name="party_category" value="investors">
                    @else
                        <select name="party_category" id="party_category" class="form-select" required>
                            <option value="investors" @selected($oldCat==='investors')>المستثمرون</option>
                            <option value="office"    @selected($oldCat==='office')>المكتب</option>
                        </select>
                    @endif
                </div>

                {{-- المستثمر (شرطي عند investors) --}}
                <div class="col-md-4" id="investorWrap">
                    <label class="form-label" for="investor_id">@lang('ledger::ledger.Investor')</label>
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
                    <label class="form-label">@lang('ledger::ledger.Status')</label>

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

                    @if(!$restrictPartyToInvestors)
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
                    @else
                        <select id="status_office" class="form-select mb-2 d-none" hidden data-goods-ids="[]"></select>
                    @endif

                    <input type="hidden" name="status_id" id="status_id_hidden" value="{{ old('status_id') }}">
                    <div class="mt-1">
                        <span class="badge rounded-pill bg-secondary" id="dirBadge">—</span>
                    </div>
                    @error('status_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- مُلتقط الحساب + عرض المتاح --}}
            <div class="col-md-4 mt-0">
                <label class="form-label" for="account_picker">@lang('ledger::ledger.Account')</label>
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
                <label class="form-label" for="amount">@lang('ledger::ledger.Amount')</label>
                <input type="number" step="0.01" min="0" name="amount" id="amount" class="form-control" value="{{ old('amount', '0') }}" required>
                <div class="invalid-feedback">المبلغ يتجاوز الحد المسموح.</div>
                @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4 mt-0">
                <label class="form-label" for="transaction_date">@lang('ledger::ledger.Transaction Date')</label>
                <input type="date" name="transaction_date" id="transaction_date" class="form-control js-date" value="{{ old('transaction_date', now()->toDateString()) }}" required>
                @error('transaction_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- ====== قسم البضائع ====== --}}
            <div class="col-12" id="goods_section" style="display:none;">
                <div class="card border-0 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam me-1"></i> تفاصيل البضائع</span>
                        <x-button.action type="button" variant="primary" :outline="true" size="sm" id="btnAddProduct">إضافة نوع</x-button.action>
                    </div>
                    <div class="card-body" id="products_wrapper">
                        @if(!empty($oldProducts))
                            @foreach($oldProducts as $i => $row)
                                @php $oldTypeId = $row['product_type_id'] ?? $row['product_id'] ?? null; @endphp
                                <div class="row g-2 product-row align-items-end {{ $i>0 ? 'mt-2' : '' }}">
                                    <div class="col-md-8">
                                        <label class="form-label small mb-1">@lang('ledger::ledger.Product Type')</label>
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
                                            <x-button.action type="button" variant="danger" :outline="true" class="js-remove-product" title="حذف">حذف</x-button.action>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="row g-2 product-row align-items-end">
                                <div class="col-md-8">
                                    <label class="form-label small mb-1">@lang('ledger::ledger.Product Type')</label>
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
                                        <x-button.action type="button" variant="danger" :outline="true" class="js-remove-product" title="حذف">حذف</x-button.action>
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
                <label class="form-label" for="notes">@lang('ledger::ledger.Notes')</label>
                <textarea name="notes" id="notes" rows="3" class="form-control" maxlength="1000">{{ old('notes') }}</textarea>
            </div>

            <div class="col-12 d-flex gap-2 mt-2">
                <x-button.action type="submit" variant="success" :outline="true" id="btnSave">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="btnSpinner" role="status" aria-hidden="true"></span>
                    <span class="d-inline-flex align-items-center gap-1">
                        <i class="bi bi-check2-circle"></i>
                        <span>حفظ</span>
                    </span>
                </x-button.action>
                <x-button.action href="{{ $cancelUrl }}" variant="secondary" :outline="true">@lang('app.Cancel')</x-button.action>

                @if($showTransferLinks)
                    <div class="ms-auto d-flex gap-2">
                        <x-button.action href="{{ route('ledger.transfer.create') }}" variant="primary" :outline="true">تحويل داخلي</x-button.action>
                        <x-button.action href="{{ route('ledger.split.create') }}" variant="secondary" :outline="true">قيد مُجزّأ</x-button.action>
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- قالب صف بضاعة جديد --}}
<template id="product_row_tpl">
    <div class="row g-2 product-row align-items-end mt-2">
        <div class="col-md-8">
            <label class="form-label small mb-1">@lang('ledger::ledger.Product Type')</label>
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
                <x-button.action type="button" variant="danger" :outline="true" class="js-remove-product" title="حذف">حذف</x-button.action>
            </div>
        </div>
    </div>
</template>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/ledger-goods-manager.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const $ = (id) => document.getElementById(id);

    const catSel        = $('party_category');
    const investorWrap  = $('investorWrap');
    const investorSel   = $('investor_id');
    const invAvailValue = $('invAvailValue');
    const invAvailLoading = $('invAvailLoading');

    const statusSelects = {
        investors: $('status_investors'),
        office: $('status_office'),
    };
    const statusHidden = $('status_id_hidden');
    const dirBadge     = $('dirBadge');

    const accountPicker = $('account_picker');
    const bankHidden    = $('bank_account_id');
    const safeHidden    = $('safe_id');

    const amountInput  = $('amount');
    const availSpan    = $('availValue');
    const availLoading = $('availLoading');

    const btnSave    = $('btnSave');
    const btnSpinner = $('btnSpinner');
    const form       = $('createForm');

    const goodsSection    = $('goods_section');
    const productsWrapper = $('products_wrapper');
    const btnAddProduct   = $('btnAddProduct');
    const rowTpl          = $('product_row_tpl');

    let accountAvail  = null;
    let investorAvail = null;

    const parseGoodsIds = (select) => {
        if (!select) {
            return [];
        }
        try {
            return JSON.parse(select.dataset.goodsIds || '[]').map(Number);
        } catch (error) {
            return [];
        }
    };

    const currentStatusKey = () => (catSel.value === 'office' ? 'office' : 'investors');
    const currentStatusSelect = () => statusSelects[currentStatusKey()];
    const selectedStatusOption = () => {
        const select = currentStatusSelect();
        return select ? select.options[select.selectedIndex] : null;
    };
    const selectedStatusId = () => {
        const option = selectedStatusOption();
        return option ? Number(option.value || 0) : 0;
    };
    const currentDirectionType = () => {
        const option = selectedStatusOption();
        return option ? (option.dataset.type || '') : '';
    };
    const isGoodsStatus = () => {
        const select = currentStatusSelect();
        const ids = parseGoodsIds(select);
        return ids.includes(selectedStatusId());
    };

    const AVAIL_URL_TPL = @json(route('product-types.available', ['productType' => '__ID__']));
    const fetchGoodsAvailability = (() => {
        const cache = Object.create(null);
        return async (typeId) => {
            if (!typeId) {
                return { success: true, available: 0 };
            }
            if (cache[typeId] !== undefined) {
                return cache[typeId];
            }
            try {
                const url = AVAIL_URL_TPL.replace('__ID__', encodeURIComponent(typeId));
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                const data = await response.json();
                cache[typeId] = data;
                return data;
            } catch (error) {
                const payload = { success: false, message: error.message };
                cache[typeId] = payload;
                return payload;
            }
        };
    })();

    let nextProductIndex = (() => {
        if (!productsWrapper) {
            return 0;
        }
        let max = -1;
        productsWrapper.querySelectorAll('.js-product-select[name^="products["]').forEach((select) => {
            const match = select.name.match(/^products\[(\d+)\]/);
            if (match) {
                max = Math.max(max, Number(match[1]));
            }
        });
        return max + 1;
    })();

    const prepareNewProductRow = (row) => {
        const select = row.querySelector('.js-product-select');
        const qty = row.querySelector('.js-qty-input');
        const index = nextProductIndex++;
        if (select) {
            select.name = `products[${index}][product_type_id]`;
        }
        if (qty) {
            qty.name = `products[${index}][quantity]`;
        }
    };

    let goodsManager = null;

    const refreshGoodsRows = () => {
        if (!goodsManager) {
            return;
        }
        goodsManager.toggleSection();
        goodsManager.refreshRows();
        goodsManager.validate();
    };

    const validateGoods = () => (goodsManager ? goodsManager.validate() : true);

    if (window.LedgerGoods) {
        goodsManager = window.LedgerGoods.create({
            section: goodsSection,
            wrapper: productsWrapper,
            template: rowTpl,
            addButton: btnAddProduct,
            isSectionActive: isGoodsStatus,
            isSaleMode: () => isGoodsStatus() && currentDirectionType() === '1',
            fetchAvailability: fetchGoodsAvailability,
            prepareNewRow: prepareNewProductRow,
            minRows: 1,
        });

        if (goodsManager) {
            goodsManager.bindExisting();
            goodsManager.toggleSection();
            goodsManager.refreshRows();
        }
    }

    function clearAccountSelection() {
        accountPicker.value = '';
        bankHidden.value = '';
        safeHidden.value = '';
        availSpan.textContent = '—';
        amountInput.removeAttribute('max');
        accountAvail = null;
    }

    function enforceStatusBeforeAccount() {
        const hasStatus = !!statusHidden.value;
        accountPicker.disabled = !hasStatus;
        if (!hasStatus) {
            clearAccountSelection();
        }
    }

    function syncAccountHidden() {
        const value = accountPicker.value || '';
        if (!value) {
            bankHidden.value = '';
            safeHidden.value = '';
            return;
        }
        const [type, id] = value.split(':');
        if (type === 'bank') {
            bankHidden.value = id;
            safeHidden.value = '';
        } else if (type === 'safe') {
            safeHidden.value = id;
            bankHidden.value = '';
        }
    }

    function refreshGoodsState() {
        refreshGoodsRows();
        validateGoods();
    }

    function syncStatusHiddenAndBadge() {
        const option = selectedStatusOption();
        statusHidden.value = option ? (option.value || '') : '';

        const type = option ? (option.dataset.type || '') : '';
        let text = '—';
        let cls = 'bg-secondary';
        if (type === '1') {
            text = 'داخل (إيداع)';
            cls = 'bg-success';
        } else if (type === '2') {
            text = 'خارج (سحب)';
            cls = 'bg-danger';
        }
        dirBadge.textContent = text;
        dirBadge.className = 'badge rounded-pill ' + cls;

        enforceStatusBeforeAccount();
        applyMaxByDirection();
        validateAmount();
        refreshGoodsState();
    }

    function applyMaxByDirection() {
        if (currentDirectionType() === '2') {
            let cap = accountAvail !== null ? accountAvail : null;
            const isInvestorFlow = (catSel.value === 'investors') && investorSel && investorSel.value;
            if (isInvestorFlow && investorAvail !== null) {
                cap = cap === null ? investorAvail : Math.min(cap, investorAvail);
            }

            if (cap !== null) {
                amountInput.setAttribute('max', String(cap));
            } else {
                amountInput.removeAttribute('max');
            }
        } else {
            amountInput.removeAttribute('max');
            amountInput.setCustomValidity('');
            amountInput.classList.remove('is-invalid');
        }
    }

    function validateAmount() {
        const type = currentDirectionType();
        const val = parseFloat(amountInput.value || '0');

        let cap = null;
        if (type === '2') {
            if (accountAvail !== null) {
                cap = accountAvail;
            }
            const isInvestorFlow = (catSel.value === 'investors') && investorSel && investorSel.value;
            if (isInvestorFlow && investorAvail !== null) {
                cap = cap === null ? investorAvail : Math.min(cap, investorAvail);
            }
        }

        let message = '';
        if (!Number.isNaN(val) && val < 0) {
            message = 'لا يمكن إدخال مبلغ سالب.';
        } else if (type === '2' && cap !== null && val > cap + 1e-9) {
            message = 'المبلغ يتجاوز الحد المسموح (سيولة المستثمر/متاح الحساب).';
        }

        amountInput.setCustomValidity(message);
        amountInput.classList.toggle('is-invalid', !!message);
    }

    async function refreshAvailability() {
        const value = accountPicker.value || '';
        accountAvail = null;
        availSpan.textContent = '—';
        amountInput.removeAttribute('max');

        if (!value) {
            applyMaxByDirection();
            validateAmount();
            return;
        }

        const [type, id] = value.split(':');
        if (!type || !id) {
            applyMaxByDirection();
            validateAmount();
            return;
        }

        availLoading.classList.remove('d-none');
        try {
            const params = new URLSearchParams({
                account_type: type,
                account_id: id
            });
            const url = `{{ route('ajax.accounts.availability') }}` + `?${params.toString()}`;
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            const data = await response.json();
            if (data && data.success) {
                accountAvail = Number(data.available);
                const formatted = data.available_formatted ?? accountAvail.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                availSpan.textContent = formatted;
            } else {
                accountAvail = null;
                availSpan.textContent = '—';
            }
        } catch (error) {
            accountAvail = null;
            availSpan.textContent = '—';
        } finally {
            availLoading.classList.add('d-none');
            applyMaxByDirection();
            validateAmount();
        }
    }

    const INVESTOR_LIQ_URL_TPL = @json(route('ajax.investors.liquidity', ['investor' => '__ID__']));

    async function refreshInvestorLiquidity() {
        if (catSel.value !== 'investors') {
            investorAvail = null;
            invAvailValue.textContent = '—';
            return;
        }

        const id = investorSel.value || '';
        investorAvail = null;
        invAvailValue.textContent = '—';
        if (!id) {
            applyMaxByDirection();
            validateAmount();
            return;
        }

        invAvailLoading.classList.remove('d-none');
        try {
            const url = INVESTOR_LIQ_URL_TPL.replace('__ID__', encodeURIComponent(id));
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            const data = await response.json();
            const raw = Number(data.cash ?? data.balance ?? 0);
            if (Number.isFinite(raw)) {
                investorAvail = raw;
                const formatted = data.formatted ?? raw.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                invAvailValue.textContent = formatted;
            } else {
                investorAvail = null;
                invAvailValue.textContent = '—';
            }
        } catch (error) {
            investorAvail = null;
            invAvailValue.textContent = '—';
        } finally {
            invAvailLoading.classList.add('d-none');
            applyMaxByDirection();
            validateAmount();
        }
    }

    function syncCategoryUI() {
        const isInvestors = catSel.value === 'investors';
        investorWrap.style.display = isInvestors ? '' : 'none';
        statusSelects.investors.hidden = !isInvestors;
        statusSelects.office.hidden = isInvestors;

        if (!isInvestors) {
            investorAvail = null;
            invAvailValue.textContent = '—';
        }

        syncStatusHiddenAndBadge();

        if (isInvestors) {
            refreshInvestorLiquidity();
        }
    }

    catSel.addEventListener('change', () => {
        syncCategoryUI();
        refreshGoodsState();
    });

    Object.values(statusSelects).forEach((select) => {
        if (!select) {
            return;
        }
        select.addEventListener('change', () => {
            syncStatusHiddenAndBadge();
            clearAccountSelection();
        });
    });

    accountPicker.addEventListener('change', () => {
        syncAccountHidden();
        refreshAvailability();
    });

    investorSel.addEventListener('change', () => {
        refreshInvestorLiquidity();
    });

    amountInput.addEventListener('input', validateAmount);

    form.addEventListener('submit', (event) => {
        if (!statusHidden.value) {
            event.preventDefault();
            event.stopPropagation();
            alert('يرجى اختيار الحالة أولاً.');
            return;
        }

        syncStatusHiddenAndBadge();
        syncAccountHidden();
        applyMaxByDirection();
        validateAmount();

        if (!validateGoods()) {
            event.preventDefault();
            event.stopPropagation();
            alert('لا يمكنك بيع كمية أكبر من المتاح في المخزون.');
            return;
        }

        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            amountInput.reportValidity();
            return;
        }

        btnSave.disabled = true;
        btnSpinner.classList.remove('d-none');
    });

    syncCategoryUI();
    syncStatusHiddenAndBadge();
    syncAccountHidden();

    if (!amountInput.value || isNaN(parseFloat(amountInput.value))) {
        amountInput.value = '0';
    }

    refreshInvestorLiquidity();
    refreshAvailability();
    refreshGoodsState();
});
</script>
@endpush
