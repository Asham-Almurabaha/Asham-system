@extends('layouts.master')

@section('title', 'قيد مُجزّأ (بنك + خزنة)')

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">قيد مُجزّأ (جزء بنك + جزء خزنة)</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('ledger.index') }}">@lang('sidebar.Ledger')</a></li>
            <li class="breadcrumb-item active">{{ __('Split Entry') }}</li>
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

    // متغيرات البضائع (fallback لو الكنترولر لسه مبعتهومش)
    $goodsStatusIds = $goodsStatusIds ?? [];
    $products       = $products ?? collect();
    $oldProducts    = old('products', []);
@endphp

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('ledger.split.store') }}" method="POST" class="row g-3 mt-1" id="splitForm">
            @csrf
            <div class="row">
                {{-- الفئة + المستثمر --}}
                <div class="col-md-4">
                    <label class="form-label" for="party_category">@lang('accounts::ledger.Category')</label>
                    <select name="party_category" id="party_category" class="form-select" required>
                        <option value="investors" @selected($oldCat==='investors')>المستثمرون</option>
                        <option value="office"    @selected($oldCat==='office')>المكتب</option>
                    </select>
                </div>

                <div class="col-md-4" id="investorWrap">
                    <label class="form-label" for="investor_id">@lang('accounts::ledger.Investor')</label>
                    <select name="investor_id" id="investor_id" class="form-select">
                        <option value="" disabled {{ old('investor_id') ? '' : 'selected' }}>اختر المستثمر</option>
                        @foreach ($investors as $investor)
                            <option value="{{ $investor->id }}" @selected(old('investor_id') == $investor->id)>{{ $investor->name }}</option>
                        @endforeach
                    </select>

                    {{-- سيولة المستثمر (مخفية إلا بعد اختيار مستثمر) --}}
                    <div class="form-text mt-1 d-none" id="invLiquidityWrap">
                        <span class="text-muted">سيولة المستثمر المتاحة: </span>
                        <strong id="invAvailValue">—</strong>
                        <span id="invAvailLoading" class="spinner-border spinner-border-sm align-middle d-none" role="status" aria-hidden="true"></span>
                    </div>

                    @error('investor_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                {{-- الحالة: قائمتان منفصلتان + حقل مخفي (ونخفي التحويل) --}}
                <div class="col-md-4">
                    <label class="form-label">@lang('accounts::ledger.Status')</label>

                    <select id="status_investors" class="form-select mb-2" {{ $oldCat==='investors' ? '' : 'hidden' }}
                            data-goods-ids='@json($goodsStatusIds)'>
                        <option value="" disabled {{ old('status_id') ? '' : 'selected' }}>اختر الحالة (مستثمر)</option>
                        @foreach(($statusesByCategory['investors'] ?? []) as $st)
                            @if(($st->transaction_type_id ?? null) != 3)
                                <option value="{{ $st->id }}" data-type="{{ $st->transaction_type_id }}" @selected(old('status_id') == $st->id)>{{ $st->name }}</option>
                            @endif
                        @endforeach
                    </select>

                    <select id="status_office" class="form-select mb-2" {{ $oldCat==='office' ? '' : 'hidden' }}
                            data-goods-ids='@json($goodsStatusIds)'>
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
                <label class="form-label" for="amount">@lang('accounts::ledger.Total Amount')</label>
                <input
                    type="number" step="any" min="0" name="amount" id="amount"
                    class="form-control" value="{{ old('amount', 0) }}" required
                    inputmode="decimal" lang="en" dir="ltr" pattern="[0-9]*[.,]?[0-9]*">
                @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3 mt-0">
                <label class="form-label" for="transaction_date">@lang('accounts::ledger.Transaction Date')</label>
                <input type="date" name="transaction_date" id="transaction_date" class="form-control js-date" value="{{ old('transaction_date', now()->toDateString()) }}" required>
                @error('transaction_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- تفاصيل التوزيع --}}
            <div class="col-12">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-3">جزء البنك</h6>

                            <div>
                                <label class="form-label" for="bank_account_id">@lang('accounts::ledger.Bank Account')</label>
                                <select name="bank_account_id" id="bank_account_id" class="form-select" disabled>
                                    <option value="" disabled {{ old('bank_account_id') ? '' : 'selected' }}>اختر الحساب البنكي</option>
                                    @foreach ($banks as $bank)
                                        <option value="{{ $bank->id }}" @selected(old('bank_account_id') == $bank->id)>{{ $bank->name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text mt-1">
                                    <span class="text-muted">المتاح: </span>
                                    <strong id="bankAvailValue">—</strong>
                                    <span id="bankAvailLoading" class="spinner-border spinner-border-sm align-middle d-none" role="status" aria-hidden="true"></span>
                                </div>
                                @error('bank_account_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-2">
                                <label class="form-label" for="bank_share">@lang('accounts::ledger.Amount (Bank)')</label>
                                <input
                                    type="number" step="any" min="0" name="bank_share" id="bank_share"
                                    class="form-control" value="{{ old('bank_share', 0) }}"
                                    inputmode="decimal" lang="en" dir="ltr" pattern="[0-9]*[.,]?[0-9]*">
                                @error('bank_share') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-3">جزء الخزنة</h6>

                            <div>
                                <label class="form-label" for="safe_id">@lang('accounts::ledger.Safe')</label>
                                <select name="safe_id" id="safe_id" class="form-select" disabled>
                                    <option value="" disabled {{ old('safe_id') ? '' : 'selected' }}>اختر الخزنة</option>
                                    @foreach ($safes as $safe)
                                        <option value="{{ $safe->id }}" @selected(old('safe_id') == $safe->id)>{{ $safe->name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text mt-1">
                                    <span class="text-muted">المتاح: </span>
                                    <strong id="safeAvailValue">—</strong>
                                    <span id="safeAvailLoading" class="spinner-border spinner-border-sm align-middle d-none" role="status" aria-hidden="true"></span>
                                </div>
                                @error('safe_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-2">
                                <label class="form-label" for="safe_share">@lang('accounts::ledger.Amount (Safe)')</label>
                                <input
                                    type="number" step="any" min="0" name="safe_share" id="safe_share"
                                    class="form-control" value="{{ old('safe_share', 0) }}"
                                    inputmode="decimal" lang="en" dir="ltr" pattern="[0-9]*[.,]?[0-9]*">
                                @error('safe_share') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
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

            {{-- ====== قسم البضائع (يظهر تلقائيًا لحالات شراء/بيع بضائع) ====== --}}
            <div class="col-12" id="goods_section" style="display:none;">
                <div class="card border-0 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam me-1"></i> تفاصيل البضائع</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddProduct">إضافة نوع</button>
                    </div>
                    <div class="card-body" id="products_wrapper">
                        @if(!empty($oldProducts))
                            @foreach($oldProducts as $i => $row)
                                @php
                                    $oldTypeId = $row['product_type_id'] ?? $row['product_id'] ?? null;
                                @endphp
                                <div class="row g-2 product-row align-items-end {{ $i>0 ? 'mt-2' : '' }}">
                                    <div class="col-md-8">
                                        <label class="form-label small mb-1">@lang('accounts::ledger.Product Type')</label>
                                        <select name="products[{{ $i }}][product_type_id]" class="form-select js-product-select">
                                            <option value="">— اختر —</option>
                                            @foreach($products as $p)
                                                <option value="{{ $p->id }}" @selected($oldTypeId==$p->id)>{{ $p->name }}</option>
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
                                    <label class="form-label small mb-1">@lang('accounts::ledger.Product Type')</label>
                                    <select name="products[0][product_type_id]" class="form-select js-product-select">
                                        <option value="">— اختر —</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }}</option>
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
                        * يتم طلب إدخال البضائع فقط إذا كانت الحالة شراء/بيع بضائع.
                    </div>
                </div>
            </div>

            {{-- ملاحظات --}}
            <div class="col-12">
                <label class="form-label" for="notes">@lang('accounts::ledger.Notes')</label>
                <textarea name="notes" id="notes" rows="3" class="form-control" maxlength="1000">{{ old('notes') }}</textarea>
            </div>

            <div class="col-12 d-flex gap-2 mt-2">
                <button class="btn btn-primary" id="btnSubmit">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="btnSpinner" role="status" aria-hidden="true"></span>
                    حفظ
                </button>
                <a href="{{ route('ledger.index') }}" class="btn btn-secondary">@lang('app.Cancel')</a>

                <div class="ms-auto d-flex gap-2">
                    <a href="{{ route('ledger.create') }}" class="btn btn-outline-success">إضافة قيد</a>
                    <a href="{{ route('ledger.transfer.create') }}" class="btn btn-outline-primary">تحويل داخلي</a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- قالب صف بضاعة جديد --}}
<template id="product_row_tpl">
    <div class="row g-2 product-row align-items-end mt-2">
        <div class="col-md-8">
            <label class="form-label small mb-1">@lang('accounts::ledger.Product Type')</label>
            <select class="form-select js-product-select">
                <option value="">— اختر —</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
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

@push('scripts')
<script src="{{ asset('assets/js/ledger-goods-manager.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const $ = (id) => document.getElementById(id);

    const catSel     = $('party_category');
    const invWrap    = $('investorWrap');
    const investorSel = $('investor_id');
    const invLiquidityWrap = $('invLiquidityWrap');
    const invAvailValue   = $('invAvailValue');
    const invAvailLoading = $('invAvailLoading');

    const statusSelects = {
        investors: $('status_investors'),
        office: $('status_office'),
    };
    const statusHidden = $('status_id_hidden');
    const dirBadge     = $('dirBadge');

    const amount    = $('amount');
    const bankShare = $('bank_share');
    const safeShare = $('safe_share');
    const bankSel   = $('bank_account_id');
    const safeSel   = $('safe_id');
    const sumHint   = $('sumHint');
    const ratioHint = $('ratioHint');
    const btnSubmit = $('btnSubmit');
    const btnSpinner= $('btnSpinner');
    const form      = $('splitForm');

    const bankAvailValue   = $('bankAvailValue');
    const bankAvailLoading = $('bankAvailLoading');
    const safeAvailValue   = $('safeAvailValue');
    const safeAvailLoading = $('safeAvailLoading');

    const goodsSection    = $('goods_section');
    const productsWrapper = $('products_wrapper');
    const btnAddProduct   = $('btnAddProduct');
    const rowTpl          = $('product_row_tpl');

    let bankAvail = null;
    let safeAvail = null;
    let investorAvail = null;
    let lastEdited = null;
    let programmatic = false;

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
    const isSaleGoods = () => isGoodsStatus() && currentDirectionType() === '1';
    const investorSelected = () => catSel.value === 'investors' && investorSel && investorSel.value;

    const PT_AVAIL_URL = @json(route('product-types.available', ['productType' => '__ID__']));
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
                const url = PT_AVAIL_URL.replace('__ID__', encodeURIComponent(typeId));
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
        const qty    = row.querySelector('.js-qty-input');
        const index  = nextProductIndex++;
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
            isSaleMode: isSaleGoods,
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

    function toggleInvestorLiquidityUI() {
        if (!invLiquidityWrap) {
            return;
        }
        const visible = investorSelected();
        invLiquidityWrap.classList.toggle('d-none', !visible);
        if (!visible) {
            investorAvail = null;
            if (invAvailValue) {
                invAvailValue.textContent = '—';
            }
        }
    }

    const INVESTOR_LIQ_URL_TPL = @json(route('ajax.investors.liquidity', ['investor' => '__ID__']));

    async function refreshInvestorLiquidity() {
        toggleInvestorLiquidityUI();
        if (!investorSelected()) {
            applyMaxByDirection();
            validate();
            return;
        }

        const id = investorSel.value || '';
        investorAvail = null;
        if (invAvailValue) {
            invAvailValue.textContent = '—';
        }
        if (!id) {
            applyMaxByDirection();
            validate();
            return;
        }

        if (invAvailLoading) {
            invAvailLoading.classList.remove('d-none');
        }
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
                if (invAvailValue) {
                    invAvailValue.textContent = formatted;
                }
            }
        } catch (error) {
            investorAvail = null;
        } finally {
            if (invAvailLoading) {
                invAvailLoading.classList.add('d-none');
            }
            applyMaxByDirection();
            validate();
        }
    }

    function clearBankSelection() {
        bankSel.value = '';
        bankAvail = null;
        bankAvailValue.textContent = '—';
        bankAvailLoading.classList.add('d-none');
        bankShare.removeAttribute('max');
        bankShare.setCustomValidity('');
        bankShare.classList.remove('is-invalid');
    }

    function clearSafeSelection() {
        safeSel.value = '';
        safeAvail = null;
        safeAvailValue.textContent = '—';
        safeAvailLoading.classList.add('d-none');
        safeShare.removeAttribute('max');
        safeShare.setCustomValidity('');
        safeShare.classList.remove('is-invalid');
    }

    function enforceStatusBeforeAccounts() {
        const hasStatus = !!statusHidden.value;
        bankSel.disabled = !hasStatus;
        safeSel.disabled = !hasStatus;
        if (!hasStatus) {
            clearBankSelection();
            clearSafeSelection();
            enforceAccountBeforeShare();
        }
    }

    function enforceAccountBeforeShare() {
        const bankChosen = !!bankSel.value;
        const safeChosen = !!safeSel.value;

        bankShare.readOnly = !bankChosen;
        bankShare.classList.toggle('bg-light', !bankChosen);
        if (!bankChosen) {
            bankShare.value = '0';
        }

        safeShare.readOnly = !safeChosen;
        safeShare.classList.toggle('bg-light', !safeChosen);
        if (!safeChosen) {
            safeShare.value = '0';
        }

        validate();
    }

    const refreshGoodsState = () => {
        refreshGoodsRows();
        validateGoods();
    };

    function syncStatusHiddenAndBadge() {
        const select = currentStatusSelect();
        if (select) {
            Array.from(select.options).forEach((option) => {
                if (option.dataset && option.dataset.type === '3') {
                    option.hidden = true;
                    option.disabled = true;
                }
            });
        }

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

        refreshGoodsState();
        enforceStatusBeforeAccounts();
        applyMaxByDirection();
        validate();
        enforceAccountBeforeShare();
    }

    function syncCategoryUI() {
        const isInvestors = catSel.value === 'investors';
        invWrap.style.display = isInvestors ? '' : 'none';
        statusSelects.investors.hidden = !isInvestors;
        statusSelects.office.hidden = isInvestors;

        if (!isInvestors) {
            investorAvail = null;
            if (invAvailValue) {
                invAvailValue.textContent = '—';
            }
        }

        syncStatusHiddenAndBadge();
        toggleInvestorLiquidityUI();
        if (isInvestors) {
            refreshInvestorLiquidity();
        }
    }

    function parseDec(value) {
        if (value == null) {
            return null;
        }
        const str = String(value).trim().replace(',', '.');
        if (str === '' || str === '.' || str === '-.') {
            return null;
        }
        const num = Number(str);
        return Number.isFinite(num) ? num : null;
    }

    const r2 = (n) => Math.round(n * 100) / 100;
    const fmt2 = (n) => (Number.isFinite(n) ? n : 0).toFixed(2);

    function formatOnBlur(el) {
        const n = parseDec(el.value);
        if (n == null) {
            return;
        }
        el.value = fmt2(Math.max(0, n));
    }

    function updateFromBank() {
        if (programmatic || bankShare.readOnly) {
            return;
        }
        lastEdited = 'bank';
        const total = parseDec(amount.value);
        const bankVal = parseDec(bankShare.value);
        programmatic = true;
        if (total == null || bankVal == null) {
            safeShare.value = '';
            programmatic = false;
            validate();
            return;
        }
        const safeVal = total - bankVal;
        safeShare.value = safeVal >= 0 ? String(r2(safeVal)) : '';
        programmatic = false;
        validate();
    }

    function updateFromSafe() {
        if (programmatic || safeShare.readOnly) {
            return;
        }
        lastEdited = 'safe';
        const total = parseDec(amount.value);
        const safeVal = parseDec(safeShare.value);
        programmatic = true;
        if (total == null || safeVal == null) {
            bankShare.value = '';
            programmatic = false;
            validate();
            return;
        }
        const bankVal = total - safeVal;
        bankShare.value = bankVal >= 0 ? String(r2(bankVal)) : '';
        programmatic = false;
        validate();
    }

    function updateFromAmount() {
        if (programmatic) {
            return;
        }
        programmatic = true;
        if (!bankShare.readOnly) {
            bankShare.value = '0';
        }
        if (!safeShare.readOnly) {
            safeShare.value = '0';
        }
        lastEdited = null;
        programmatic = false;
        validate();
    }

    async function fetchAvailability(type, id) {
        const params = new URLSearchParams({ account_type: type, account_id: id });
        const url = `{{ route('ajax.accounts.availability') }}` + `?${params.toString()}`;
        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!response.ok) {
            return null;
        }
        const data = await response.json();
        if (data && data.success) {
            const raw = Number(data.available);
            const formatted = data.available_formatted ?? raw.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            return { raw, formatted };
        }
        return null;
    }

    async function refreshBankAvailability() {
        bankAvail = null;
        bankAvailValue.textContent = '—';
        bankAvailLoading.classList.remove('d-none');
        try {
            const id = bankSel.value || '';
            if (!id) {
                return;
            }
            const payload = await fetchAvailability('bank', id);
            if (payload) {
                bankAvail = payload.raw;
                bankAvailValue.textContent = payload.formatted;
            }
        } catch (error) {
            bankAvail = null;
        } finally {
            bankAvailLoading.classList.add('d-none');
            applyMaxByDirection();
            validate();
            enforceAccountBeforeShare();
        }
    }

    async function refreshSafeAvailability() {
        safeAvail = null;
        safeAvailValue.textContent = '—';
        safeAvailLoading.classList.remove('d-none');
        try {
            const id = safeSel.value || '';
            if (!id) {
                return;
            }
            const payload = await fetchAvailability('safe', id);
            if (payload) {
                safeAvail = payload.raw;
                safeAvailValue.textContent = payload.formatted;
            }
        } catch (error) {
            safeAvail = null;
        } finally {
            safeAvailLoading.classList.add('d-none');
            applyMaxByDirection();
            validate();
            enforceAccountBeforeShare();
        }
    }

    function applyMaxByDirection() {
        if (currentDirectionType() === '2') {
            if (investorSelected() && investorAvail !== null) {
                amount.setAttribute('max', String(investorAvail));
            } else {
                amount.removeAttribute('max');
            }

            let bankCap = bankAvail !== null ? bankAvail : null;
            let safeCap = safeAvail !== null ? safeAvail : null;
            if (investorSelected() && investorAvail !== null) {
                bankCap = bankCap === null ? investorAvail : Math.min(bankCap, investorAvail);
                safeCap = safeCap === null ? investorAvail : Math.min(safeCap, investorAvail);
            }

            if (bankCap !== null) {
                bankShare.setAttribute('max', String(bankCap));
            } else {
                bankShare.removeAttribute('max');
            }

            if (safeCap !== null) {
                safeShare.setAttribute('max', String(safeCap));
            } else {
                safeShare.removeAttribute('max');
            }
        } else {
            amount.removeAttribute('max');
            bankShare.removeAttribute('max');
            safeShare.removeAttribute('max');
            bankShare.setCustomValidity('');
            safeShare.setCustomValidity('');
            bankShare.classList.remove('is-invalid');
            safeShare.classList.remove('is-invalid');
        }
    }

    function validate() {
        const total = parseDec(amount.value);
        const bankVal = parseDec(bankShare.value);
        const safeVal = parseDec(safeShare.value);

        let okSum = false;
        let sum = 0;
        if (total != null && bankVal != null && safeVal != null) {
            sum = r2(bankVal + safeVal);
            okSum = (total > 0) && (r2(total) === sum);
        }

        sumHint.textContent = `المجموع الحالي: ${sum.toFixed ? sum.toFixed(2) : '0.00'} / الإجمالي: ${total != null ? r2(total).toFixed(2) : '0.00'}`;
        sumHint.className = okSum ? 'text-success' : 'text-danger';

        const bankPercent = (total && bankVal != null) ? Math.round((r2(bankVal) / r2(total)) * 100) : 0;
        const safePercent = (total && safeVal != null) ? (100 - bankPercent) : 0;
        ratioHint.textContent = (total && (bankVal != null || safeVal != null))
            ? `النِسب: بنك ${bankPercent}% — خزنة ${safePercent}%`
            : '';

        bankSel.required = !!(bankVal && bankVal > 0);
        safeSel.required = !!(safeVal && safeVal > 0);

        const direction = currentDirectionType();
        let bankOk = true;
        let safeOk = true;
        let investorOk = true;

        if (direction === '2') {
            if (bankAvail !== null && bankVal != null && bankVal > bankAvail + 1e-9) {
                bankOk = false;
            }
            if (safeAvail !== null && safeVal != null && safeVal > safeAvail + 1e-9) {
                safeOk = false;
            }
            if (investorSelected() && investorAvail !== null && total != null && total > investorAvail + 1e-9) {
                investorOk = false;
            }
        }

        bankShare.setCustomValidity(bankOk ? '' : 'المبلغ أكبر من المتاح في الحساب البنكي');
        safeShare.setCustomValidity(safeOk ? '' : 'المبلغ أكبر من المتاح في الخزنة');
        bankShare.classList.toggle('is-invalid', !bankOk);
        safeShare.classList.toggle('is-invalid', !safeOk);

        amount.setCustomValidity(investorOk ? '' : 'إجمالي المبلغ أكبر من سيولة المستثمر المتاحة');
        amount.classList.toggle('is-invalid', !investorOk);

        let ok = okSum && bankOk && safeOk && investorOk;
        if (ok && bankVal && bankVal > 0 && !bankSel.value) {
            ok = false;
        }
        if (ok && safeVal && safeVal > 0 && !safeSel.value) {
            ok = false;
        }

        btnSubmit.disabled = !ok;
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
            clearBankSelection();
            clearSafeSelection();
            enforceAccountBeforeShare();
        });
    });

    amount.addEventListener('input', updateFromAmount);
    bankShare.addEventListener('input', updateFromBank);
    safeShare.addEventListener('input', updateFromSafe);

    [amount, bankShare, safeShare].forEach((el) => {
        el.addEventListener('blur', () => formatOnBlur(el));
        el.addEventListener('wheel', (event) => {
            event.preventDefault();
            el.blur();
        }, { passive: false });
    });

    bankSel.addEventListener('change', refreshBankAvailability);
    safeSel.addEventListener('change', refreshSafeAvailability);

    if (investorSel) {
        investorSel.addEventListener('change', () => {
            toggleInvestorLiquidityUI();
            refreshInvestorLiquidity();
        });
    }

    form.addEventListener('submit', (event) => {
        if (!validateGoods()) {
            event.preventDefault();
            event.stopPropagation();
            alert('لا يمكنك بيع كمية أكبر من المتاح في المخزون.');
            return;
        }

        btnSubmit.disabled = true;
        btnSpinner.classList.remove('d-none');
        [amount, bankShare, safeShare].forEach(formatOnBlur);
    });

    syncCategoryUI();
    enforceAccountBeforeShare();

    if (!amount.value || isNaN(parseFloat(String(amount.value).replace(',', '.')))) {
        amount.value = '0';
    }

    updateFromAmount();
    refreshInvestorLiquidity();
    refreshBankAvailability();
    refreshSafeAvailability();
    refreshGoodsState();
});
</script>
@endpush


@endsection
