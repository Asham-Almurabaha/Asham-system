@extends('layouts.master')

@php
    $pageTitle = $pageTitle ?? 'القيود — البضائع';
    $pageHeading = $pageHeading ?? 'قيود البضائع';
    $activeTab = old('active_tab', $defaultTab ?? 'purchase');
    if (!in_array($activeTab, ['purchase', 'partial'], true)) {
        $activeTab = 'purchase';
    }

    $today = now()->toDateString();

    $primaryTabLabel = $primaryTabLabel ?? 'قيد شراء بضائع';
    $primaryFormAction = $primaryFormAction ?? route('accounts.entries.goods.store');
    $partialFormAction = $partialFormAction ?? route('accounts.entries.goods.store-partial');

    $purchaseProducts = $activeTab === 'partial' ? [] : old('products', []);
    $partialProducts = $activeTab === 'partial' ? old('products', []) : [];

    $purchaseBankId = $activeTab === 'partial' ? null : old('bank_account_id');
    $purchaseSafeId = $activeTab === 'partial' ? null : old('safe_id');
    $purchaseAmount = $activeTab === 'partial' ? '0.00' : old('amount', '0.00');
    $purchaseDate = $activeTab === 'partial' ? $today : old('transaction_date', $today);
    $purchaseNotes = $activeTab === 'partial' ? '' : old('notes');

    $partialBankId = $activeTab === 'partial' ? old('bank_account_id') : null;
    $partialSafeId = $activeTab === 'partial' ? old('safe_id') : null;
    $partialAmount = $activeTab === 'partial' ? old('amount', '0.00') : '0.00';
    $partialBankShare = $activeTab === 'partial' ? old('bank_share', '0.00') : '0.00';
    $partialSafeShare = $activeTab === 'partial' ? old('safe_share', '0.00') : '0.00';
    $partialDate = $activeTab === 'partial' ? old('transaction_date', $today) : $today;
    $partialNotes = $activeTab === 'partial' ? old('notes') : '';

    $statusId = $statusId ?? null;
    $statusType = $statusType ?? 2;
    $directionClass = $statusType === 1 ? 'bg-success' : 'bg-danger';
@endphp

@section('title', $pageTitle)

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ $pageHeading }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('ledger.index') }}">{{ __('sidebar.Ledger') }}</a></li>
            <li class="breadcrumb-item active">البضائع</li>
        </ol>
    </nav>
</div>

<div class="alert alert-info d-flex justify-content-between align-items-center">
    <div>
        يتم إنشاء القيود للفئة <strong>المكتب</strong> وبحالة <strong>{{ $statusName }}</strong>.
    </div>
    <span class="badge rounded-pill {{ $directionClass }}">{{ $directionLabel }}</span>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <ul class="nav nav-tabs" id="goodsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'purchase' ? 'active' : '' }}" id="purchase-tab" data-bs-toggle="tab" data-bs-target="#tab-purchase" type="button" role="tab">
                    {{ $primaryTabLabel }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'partial' ? 'active' : '' }}" id="partial-tab" data-bs-toggle="tab" data-bs-target="#tab-partial" type="button" role="tab">
                    قيد مُجزّأ
                </button>
            </li>
        </ul>

        <div class="tab-content pt-3">
            <div class="tab-pane fade {{ $activeTab === 'purchase' ? 'show active' : '' }}" id="tab-purchase" role="tabpanel" aria-labelledby="purchase-tab">
                <form action="{{ $primaryFormAction }}" method="POST" class="row g-3 mt-1" id="goodsPurchaseForm">
                    @csrf
                    <input type="hidden" name="active_tab" value="purchase">
                    <input type="hidden" name="party_category" value="office">
                    @if($statusId)
                        <input type="hidden" name="status_id" value="{{ $statusId }}">
                    @endif
                    <input type="hidden" name="bank_account_id" id="purchase_bank_account_id" value="{{ $purchaseBankId }}">
                    <input type="hidden" name="safe_id" id="purchase_safe_id" value="{{ $purchaseSafeId }}">

                    <div class="col-md-4 mt-0">
                        <label class="form-label" for="purchase_account_picker">@lang('accounts::ledger.Account')</label>
                        <select id="purchase_account_picker" class="form-select" required>
                            <option value="" disabled {{ $purchaseBankId || $purchaseSafeId ? '' : 'selected' }}>اختر حسابًا</option>
                            <optgroup label="الحسابات البنكية">
                                @foreach ($banks as $bank)
                                    <option value="bank:{{ $bank->id }}" @selected($purchaseBankId == $bank->id)> {{ $bank->name }} </option>
                                @endforeach
                            </optgroup>
                            <optgroup label="الخزن">
                                @foreach ($safes as $safe)
                                    <option value="safe:{{ $safe->id }}" @selected($purchaseSafeId == $safe->id)> {{ $safe->name }} </option>
                                @endforeach
                            </optgroup>
                        </select>
                        <div class="form-text mt-1">
                            <span class="text-muted">المتاح في الحساب: </span>
                            <strong id="purchase_available_value">—</strong>
                            <span id="purchase_available_loading" class="spinner-border spinner-border-sm align-middle d-none" role="status" aria-hidden="true"></span>
                        </div>
                        @error('bank_account_id', 'goodsPurchase')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @error('safe_id', 'goodsPurchase')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4 mt-0">
                        <label class="form-label" for="purchase_amount">@lang('accounts::ledger.Amount')</label>
                        <input type="number" step="0.01" min="0" name="amount" id="purchase_amount" class="form-control" value="{{ $purchaseAmount }}" required>
                        <div class="invalid-feedback">المبلغ يتجاوز المتاح في الحساب.</div>
                        @error('amount', 'goodsPurchase')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4 mt-0">
                        <label class="form-label" for="purchase_transaction_date">@lang('accounts::ledger.Transaction Date')</label>
                        <input type="date" name="transaction_date" id="purchase_transaction_date" class="form-control js-date" value="{{ $purchaseDate }}" required>
                        @error('transaction_date', 'goodsPurchase')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12" id="purchase_goods_section">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-box-seam me-1"></i> تفاصيل البضائع</span>
                                <x-button.action type="button" variant="primary" :outline="true" size="sm" id="purchase_add_product">إضافة نوع</x-button.action>
                            </div>
                            <div class="card-body" id="purchase_products_wrapper">
                                @if (!empty($purchaseProducts))
                                    @foreach ($purchaseProducts as $i => $row)
                                        <div class="row g-2 product-row align-items-end {{ $i > 0 ? 'mt-2' : '' }}">
                                            <div class="col-md-8">
                                                <label class="form-label small mb-1">@lang('accounts::ledger.Product Type')</label>
                                                <select name="products[{{ $i }}][product_type_id]" class="form-select js-product-select">
                                                    <option value="">— اختر —</option>
                                                    @foreach ($products as $product)
                                                        <option value="{{ $product->id }}" @selected(($row['product_type_id'] ?? null) == $product->id)>{{ $product->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error("products.$i.product_type_id", 'goodsPurchase')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
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
                                                @error("products.$i.quantity", 'goodsPurchase')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="row g-2 product-row align-items-end">
                                        <div class="col-md-8">
                                            <label class="form-label small mb-1">@lang('accounts::ledger.Product Type')</label>
                                            <select name="products[0][product_type_id]" class="form-select js-product-select">
                                                <option value="">— اختر —</option>
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
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
                            <div class="card-footer small text-muted">* إدخال تفاصيل البضائع إلزامي في هذه الشاشة.</div>
                        </div>
                        @error('products', 'goodsPurchase')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="purchase_notes">@lang('accounts::ledger.Notes')</label>
                        <textarea name="notes" id="purchase_notes" rows="3" class="form-control" maxlength="1000">{{ $purchaseNotes }}</textarea>
                        @error('notes', 'goodsPurchase')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 d-flex gap-2 mt-2">
                        <x-button.action type="submit" variant="success" :outline="true" id="purchase_save_btn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="purchase_spinner" role="status" aria-hidden="true"></span>
                            <span class="d-inline-flex align-items-center gap-1">
                                <i class="bi bi-check2-circle"></i>
                                <span>حفظ</span>
                            </span>
                        </x-button.action>
                        <button type="reset" class="btn btn-outline-secondary">إعادة تعيين</button>
                        <x-button.action href="{{ route('ledger.index') }}" variant="secondary" :outline="true">@lang('app.Cancel')</x-button.action>
                    </div>
                </form>
            </div>

            <div class="tab-pane fade {{ $activeTab === 'partial' ? 'show active' : '' }}" id="tab-partial" role="tabpanel" aria-labelledby="partial-tab">
                <form action="{{ $partialFormAction }}" method="POST" class="row g-3 mt-1" id="goodsPartialForm">
                    @csrf
                    <input type="hidden" name="active_tab" value="partial">
                    <input type="hidden" name="party_category" value="office">
                    @if($statusId)
                        <input type="hidden" name="status_id" value="{{ $statusId }}">
                    @endif

                    <div class="col-md-3 mt-0">
                        <label class="form-label" for="partial_amount">@lang('accounts::ledger.Total Amount')</label>
                        <input type="number" step="0.01" min="0" name="amount" id="partial_amount" class="form-control" value="{{ $partialAmount }}" required>
                        <div class="invalid-feedback">المبلغ يتجاوز المتاح.</div>
                        @error('amount', 'goodsPartial')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-3 mt-0">
                        <label class="form-label" for="partial_bank_share">@lang('accounts::ledger.Amount (Bank)')</label>
                        <input type="number" step="0.01" min="0" name="bank_share" id="partial_bank_share" class="form-control" value="{{ $partialBankShare }}">
                        <div class="invalid-feedback">المبلغ أكبر من المتاح في الحساب البنكي.</div>
                        @error('bank_share', 'goodsPartial')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-3 mt-0">
                        <label class="form-label" for="partial_safe_share">@lang('accounts::ledger.Amount (Safe)')</label>
                        <input type="number" step="0.01" min="0" name="safe_share" id="partial_safe_share" class="form-control" value="{{ $partialSafeShare }}">
                        <div class="invalid-feedback">المبلغ أكبر من المتاح في الخزنة.</div>
                        @error('safe_share', 'goodsPartial')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-3 mt-0">
                        <label class="form-label" for="partial_transaction_date">@lang('accounts::ledger.Transaction Date')</label>
                        <input type="date" name="transaction_date" id="partial_transaction_date" class="form-control js-date" value="{{ $partialDate }}" required>
                        @error('transaction_date', 'goodsPartial')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">جزء البنك</h6>
                                    <div>
                                        <label class="form-label" for="partial_bank_account_id">@lang('accounts::ledger.Bank Account')</label>
                                        <select name="bank_account_id" id="partial_bank_account_id" class="form-select">
                                            <option value="" disabled {{ $partialBankId ? '' : 'selected' }}>اختر الحساب البنكي</option>
                                            @foreach ($banks as $bank)
                                                <option value="{{ $bank->id }}" @selected($partialBankId == $bank->id)> {{ $bank->name }} </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text mt-1">
                                            <span class="text-muted">المتاح: </span>
                                            <strong id="partial_bank_available">—</strong>
                                            <span id="partial_bank_loading" class="spinner-border spinner-border-sm align-middle d-none" role="status" aria-hidden="true"></span>
                                        </div>
                                        @error('bank_account_id', 'goodsPartial')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">جزء الخزنة</h6>
                                    <div>
                                        <label class="form-label" for="partial_safe_id">@lang('accounts::ledger.Safe')</label>
                                        <select name="safe_id" id="partial_safe_id" class="form-select">
                                            <option value="" disabled {{ $partialSafeId ? '' : 'selected' }}>اختر الخزنة</option>
                                            @foreach ($safes as $safe)
                                                <option value="{{ $safe->id }}" @selected($partialSafeId == $safe->id)> {{ $safe->name }} </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text mt-1">
                                            <span class="text-muted">المتاح: </span>
                                            <strong id="partial_safe_available">—</strong>
                                            <span id="partial_safe_loading" class="spinner-border spinner-border-sm align-middle d-none" role="status" aria-hidden="true"></span>
                                        </div>
                                        @error('safe_id', 'goodsPartial')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="small">
                            <span id="partial_sum_hint" class="text-muted">مجموع البنك + الخزنة يجب أن يساوي الإجمالي.</span>
                            <span id="partial_ratio_hint" class="ms-2 text-muted"></span>
                        </div>
                    </div>

                    <div class="col-12" id="partial_goods_section">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-box-seam me-1"></i> تفاصيل البضائع</span>
                                <x-button.action type="button" variant="primary" :outline="true" size="sm" id="partial_add_product">إضافة نوع</x-button.action>
                            </div>
                            <div class="card-body" id="partial_products_wrapper">
                                @if (!empty($partialProducts))
                                    @foreach ($partialProducts as $i => $row)
                                        <div class="row g-2 product-row align-items-end {{ $i > 0 ? 'mt-2' : '' }}">
                                            <div class="col-md-8">
                                                <label class="form-label small mb-1">@lang('accounts::ledger.Product Type')</label>
                                                <select name="products[{{ $i }}][product_type_id]" class="form-select js-product-select">
                                                    <option value="">— اختر —</option>
                                                    @foreach ($products as $product)
                                                        <option value="{{ $product->id }}" @selected(($row['product_type_id'] ?? null) == $product->id)>{{ $product->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error("products.$i.product_type_id", 'goodsPartial')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
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
                                                @error("products.$i.quantity", 'goodsPartial')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="row g-2 product-row align-items-end">
                                        <div class="col-md-8">
                                            <label class="form-label small mb-1">@lang('accounts::ledger.Product Type')</label>
                                            <select name="products[0][product_type_id]" class="form-select js-product-select">
                                                <option value="">— اختر —</option>
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
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
                            <div class="card-footer small text-muted">* إدخال تفاصيل البضائع إلزامي في هذه الشاشة.</div>
                        </div>
                        @error('products', 'goodsPartial')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="partial_notes">@lang('accounts::ledger.Notes')</label>
                        <textarea name="notes" id="partial_notes" rows="3" class="form-control" maxlength="1000">{{ $partialNotes }}</textarea>
                        @error('notes', 'goodsPartial')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 d-flex gap-2 mt-2">
                        <x-button.action type="submit" variant="success" :outline="true" id="partial_save_btn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="partial_spinner" role="status" aria-hidden="true"></span>
                            <span class="d-inline-flex align-items-center gap-1">
                                <i class="bi bi-check2-circle"></i>
                                <span>حفظ</span>
                            </span>
                        </x-button.action>
                        <button type="reset" class="btn btn-outline-secondary">إعادة تعيين</button>
                        <x-button.action href="{{ route('ledger.index') }}" variant="secondary" :outline="true">@lang('app.Cancel')</x-button.action>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<template id="purchase_product_template">
    <div class="row g-2 product-row align-items-end mt-2">
        <div class="col-md-8">
            <label class="form-label small mb-1">@lang('accounts::ledger.Product Type')</label>
            <select class="form-select js-product-select">
                <option value="">— اختر —</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
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

<template id="partial_product_template">
    <div class="row g-2 product-row align-items-end mt-2">
        <div class="col-md-8">
            <label class="form-label small mb-1">@lang('accounts::ledger.Product Type')</label>
            <select class="form-select js-product-select">
                <option value="">— اختر —</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
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
    const ACCOUNT_AVAIL_URL = @json(route('ajax.accounts.availability'));
    const PRODUCT_AVAIL_URL = @json(route('product-types.available', ['productType' => '__ID__']));
    const STATUS_TYPE = Number({{ $statusType ?? 2 }});

    const fetchAccountAvailability = async (type, id) => {
        if (!type || !id) {
            return null;
        }
        const url = `${ACCOUNT_AVAIL_URL}?account_type=${encodeURIComponent(type)}&account_id=${encodeURIComponent(id)}`;
        try {
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            return { success: false, message: error.message };
        }
    };

    const fetchProductAvailability = (() => {
        const cache = Object.create(null);
        return async (typeId) => {
            if (!typeId) {
                return { success: true, available: 0 };
            }
            if (cache[typeId] !== undefined) {
                return cache[typeId];
            }
            const url = PRODUCT_AVAIL_URL.replace('__ID__', encodeURIComponent(typeId));
            try {
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
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

    const isSaleGoods = () => STATUS_TYPE === 1;

    function goodsManagerFactory(sectionId, wrapperId, templateId, addButtonId) {
        const section = document.getElementById(sectionId);
        const wrapper = document.getElementById(wrapperId);
        const template = document.getElementById(templateId);
        const addButton = document.getElementById(addButtonId);

        if (!window.LedgerGoods) {
            return null;
        }

        const manager = window.LedgerGoods.create({
            section,
            wrapper,
            template,
            addButton,
            isSectionActive: () => true,
            isSaleMode: isSaleGoods,
            fetchAvailability: fetchProductAvailability,
            prepareNewRow: (row) => {
                if (!wrapper) {
                    return;
                }
                let maxIndex = -1;
                wrapper.querySelectorAll('.js-product-select[name^="products["]').forEach((select) => {
                    const match = select.name.match(/^products\[(\d+)\]/);
                    if (match) {
                        maxIndex = Math.max(maxIndex, Number(match[1]));
                    }
                });
                const nextIndex = maxIndex + 1;
                const select = row.querySelector('.js-product-select');
                const qty = row.querySelector('.js-qty-input');
                if (select) {
                    select.name = `products[${nextIndex}][product_type_id]`;
                }
                if (qty) {
                    qty.name = `products[${nextIndex}][quantity]`;
                }
            },
            minRows: 1,
        });

        if (manager) {
            manager.bindExisting();
            manager.toggleSection();
            manager.refreshRows();
        }

        return manager;
    }

    const purchaseGoodsManager = goodsManagerFactory('purchase_goods_section', 'purchase_products_wrapper', 'purchase_product_template', 'purchase_add_product');
    const partialGoodsManager = goodsManagerFactory('partial_goods_section', 'partial_products_wrapper', 'partial_product_template', 'partial_add_product');

    setupPurchaseForm(purchaseGoodsManager);
    setupPartialForm(partialGoodsManager);

    function setupPurchaseForm(goodsManager) {
        const form = document.getElementById('goodsPurchaseForm');
        if (!form) {
            return;
        }

        const accountPicker = document.getElementById('purchase_account_picker');
        const bankHidden = document.getElementById('purchase_bank_account_id');
        const safeHidden = document.getElementById('purchase_safe_id');
        const amountInput = document.getElementById('purchase_amount');
        const availabilityValue = document.getElementById('purchase_available_value');
        const availabilityLoading = document.getElementById('purchase_available_loading');
        const saveButton = document.getElementById('purchase_save_btn');
        const spinner = document.getElementById('purchase_spinner');

        let accountAvail = null;

        async function refreshAvailability() {
            const value = accountPicker ? accountPicker.value : '';
            accountAvail = null;
            if (availabilityValue) {
                availabilityValue.textContent = '—';
            }

            if (!value) {
                if (bankHidden) bankHidden.value = '';
                if (safeHidden) safeHidden.value = '';
                applyMax();
                validateAmount();
                return;
            }

            const [type, id] = value.split(':');
            if (type === 'bank') {
                if (bankHidden) bankHidden.value = id;
                if (safeHidden) safeHidden.value = '';
            } else if (type === 'safe') {
                if (safeHidden) safeHidden.value = id;
                if (bankHidden) bankHidden.value = '';
            }

            if (availabilityLoading) {
                availabilityLoading.classList.remove('d-none');
            }

            const payload = await fetchAccountAvailability(type, id);
            if (availabilityLoading) {
                availabilityLoading.classList.add('d-none');
            }

            if (!payload || payload.success !== true) {
                if (availabilityValue) {
                    availabilityValue.textContent = payload && payload.message ? `خطأ: ${payload.message}` : 'تعذّر الجلب';
                }
                accountAvail = null;
                applyMax();
                validateAmount();
                return;
            }

            accountAvail = Number(payload.available ?? 0);
            if (availabilityValue) {
                const formatted = payload.available_formatted ?? accountAvail.toLocaleString('ar-EG', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
                availabilityValue.textContent = formatted;
            }

            applyMax();
            validateAmount();
        }

        function applyMax() {
            if (!amountInput) {
                return;
            }
            if (STATUS_TYPE === 2 && accountAvail !== null) {
                amountInput.setAttribute('max', String(accountAvail));
            } else {
                amountInput.removeAttribute('max');
            }
        }

        function validateAmount() {
            if (!amountInput) {
                return;
            }
            const value = Number.parseFloat(String(amountInput.value).replace(',', '.')) || 0;
            if (STATUS_TYPE === 2 && accountAvail !== null && value > accountAvail + 1e-9) {
                amountInput.setCustomValidity('المبلغ يتجاوز المتاح في الحساب.');
            } else {
                amountInput.setCustomValidity('');
            }
            amountInput.classList.toggle('is-invalid', !!amountInput.validationMessage);
        }

        if (accountPicker) {
            accountPicker.addEventListener('change', refreshAvailability);
            refreshAvailability();
        }

        if (amountInput) {
            amountInput.addEventListener('input', validateAmount);
            amountInput.addEventListener('blur', () => {
                if (!amountInput.value) {
                    amountInput.value = '0.00';
                }
                validateAmount();
            });
            validateAmount();
        }

        form.addEventListener('reset', () => {
            window.setTimeout(() => {
                if (accountPicker) {
                    accountPicker.selectedIndex = 0;
                }
                if (bankHidden) bankHidden.value = '';
                if (safeHidden) safeHidden.value = '';
                accountAvail = null;
                if (availabilityValue) availabilityValue.textContent = '—';
                applyMax();
                validateAmount();
                if (goodsManager) {
                    goodsManager.refreshRows();
                    goodsManager.validate();
                }
            }, 0);
        });

        form.addEventListener('submit', (event) => {
            if (goodsManager && !goodsManager.validate()) {
                event.preventDefault();
                event.stopPropagation();
                alert('لا يمكنك بيع كمية أكبر من المتاح في المخزون.');
                return;
            }

            if (saveButton) {
                saveButton.disabled = true;
            }
            if (spinner) {
                spinner.classList.remove('d-none');
            }
        });
    }

    function setupPartialForm(goodsManager) {
        const form = document.getElementById('goodsPartialForm');
        if (!form) {
            return;
        }

        const amountInput = document.getElementById('partial_amount');
        const bankShareInput = document.getElementById('partial_bank_share');
        const safeShareInput = document.getElementById('partial_safe_share');
        const bankSelect = document.getElementById('partial_bank_account_id');
        const safeSelect = document.getElementById('partial_safe_id');
        const bankAvailValue = document.getElementById('partial_bank_available');
        const bankAvailLoading = document.getElementById('partial_bank_loading');
        const safeAvailValue = document.getElementById('partial_safe_available');
        const safeAvailLoading = document.getElementById('partial_safe_loading');
        const sumHint = document.getElementById('partial_sum_hint');
        const ratioHint = document.getElementById('partial_ratio_hint');
        const saveButton = document.getElementById('partial_save_btn');
        const spinner = document.getElementById('partial_spinner');

        let bankAvail = null;
        let safeAvail = null;
        let programmatic = false;
        let lastEdited = null;

        const parseDec = (value) => {
            if (value == null) {
                return null;
            }
            const str = String(value).trim().replace(',', '.');
            if (str === '' || str === '.' || str === '-.' ) {
                return null;
            }
            const num = Number(str);
            return Number.isFinite(num) ? num : null;
        };

        const round2 = (value) => Math.round(value * 100) / 100;

        function enforceAccountBeforeShare() {
            const bankChosen = !!(bankSelect && bankSelect.value);
            const safeChosen = !!(safeSelect && safeSelect.value);

            if (bankShareInput) {
                bankShareInput.readOnly = !bankChosen;
                bankShareInput.classList.toggle('bg-light', !bankChosen);
                if (!bankChosen) {
                    bankShareInput.value = '0.00';
                }
            }

            if (safeShareInput) {
                safeShareInput.readOnly = !safeChosen;
                safeShareInput.classList.toggle('bg-light', !safeChosen);
                if (!safeChosen) {
                    safeShareInput.value = '0.00';
                }
            }
        }

        function updateFromBank() {
            if (!bankShareInput || !safeShareInput || programmatic || bankShareInput.readOnly) {
                return;
            }
            lastEdited = 'bank';
            const total = parseDec(amountInput.value);
            const bankVal = parseDec(bankShareInput.value);
            programmatic = true;
            if (total != null && bankVal != null) {
                const safeVal = round2(total - bankVal);
                safeShareInput.value = safeVal >= 0 ? safeVal.toFixed(2) : '';
            }
            programmatic = false;
            refreshHints();
        }

        function updateFromSafe() {
            if (!bankShareInput || !safeShareInput || programmatic || safeShareInput.readOnly) {
                return;
            }
            lastEdited = 'safe';
            const total = parseDec(amountInput.value);
            const safeVal = parseDec(safeShareInput.value);
            programmatic = true;
            if (total != null && safeVal != null) {
                const bankVal = round2(total - safeVal);
                bankShareInput.value = bankVal >= 0 ? bankVal.toFixed(2) : '';
            }
            programmatic = false;
            refreshHints();
        }

        function updateFromAmount() {
            if (!bankShareInput || !safeShareInput || programmatic) {
                return;
            }
            programmatic = true;
            if (!bankShareInput.readOnly) {
                bankShareInput.value = '0.00';
            }
            if (!safeShareInput.readOnly) {
                safeShareInput.value = '0.00';
            }
            lastEdited = null;
            programmatic = false;
            refreshHints();
        }

        async function refreshBankAvailability() {
            bankAvail = null;
            if (bankAvailValue) {
                bankAvailValue.textContent = '—';
            }
            if (!bankSelect || !bankSelect.value) {
                refreshHints();
                return;
            }
            if (bankAvailLoading) {
                bankAvailLoading.classList.remove('d-none');
            }
            const payload = await fetchAccountAvailability('bank', bankSelect.value);
            if (bankAvailLoading) {
                bankAvailLoading.classList.add('d-none');
            }
            if (payload && payload.success) {
                bankAvail = Number(payload.available ?? 0);
                if (bankAvailValue) {
                    const formatted = payload.available_formatted ?? bankAvail.toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    bankAvailValue.textContent = formatted;
                }
            } else if (bankAvailValue) {
                bankAvailValue.textContent = payload && payload.message ? `خطأ: ${payload.message}` : 'تعذّر الجلب';
            }
            refreshHints();
        }

        async function refreshSafeAvailability() {
            safeAvail = null;
            if (safeAvailValue) {
                safeAvailValue.textContent = '—';
            }
            if (!safeSelect || !safeSelect.value) {
                refreshHints();
                return;
            }
            if (safeAvailLoading) {
                safeAvailLoading.classList.remove('d-none');
            }
            const payload = await fetchAccountAvailability('safe', safeSelect.value);
            if (safeAvailLoading) {
                safeAvailLoading.classList.add('d-none');
            }
            if (payload && payload.success) {
                safeAvail = Number(payload.available ?? 0);
                if (safeAvailValue) {
                    const formatted = payload.available_formatted ?? safeAvail.toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    safeAvailValue.textContent = formatted;
                }
            } else if (safeAvailValue) {
                safeAvailValue.textContent = payload && payload.message ? `خطأ: ${payload.message}` : 'تعذّر الجلب';
            }
            refreshHints();
        }

        function refreshHints() {
            const total = parseDec(amountInput.value) ?? 0;
            const bankVal = parseDec(bankShareInput.value) ?? 0;
            const safeVal = parseDec(safeShareInput.value) ?? 0;
            const sum = round2(bankVal + safeVal);
            const okSum = total > 0 && round2(total) === sum;

            if (sumHint) {
                sumHint.textContent = `المجموع الحالي: ${sum.toFixed(2)} / الإجمالي: ${round2(total).toFixed(2)}`;
                sumHint.className = okSum ? 'text-success' : 'text-danger';
            }

            if (ratioHint) {
                if (total > 0) {
                    const bankPercent = Math.round((round2(bankVal) / round2(total)) * 100);
                    const safePercent = 100 - bankPercent;
                    ratioHint.textContent = `النِسب: بنك ${bankPercent}% — خزنة ${safePercent}%`;
                } else {
                    ratioHint.textContent = '';
                }
            }

            let bankOk = true;
            let safeOk = true;

            if (STATUS_TYPE === 2) {
                if (bankAvail !== null && bankVal > bankAvail + 1e-9) {
                    bankOk = false;
                }
                if (safeAvail !== null && safeVal > safeAvail + 1e-9) {
                    safeOk = false;
                }
            }

            if (bankShareInput) {
                bankShareInput.setCustomValidity(bankOk ? '' : 'المبلغ أكبر من المتاح في الحساب البنكي');
                bankShareInput.classList.toggle('is-invalid', !bankOk);
            }
            if (safeShareInput) {
                safeShareInput.setCustomValidity(safeOk ? '' : 'المبلغ أكبر من المتاح في الخزنة');
                safeShareInput.classList.toggle('is-invalid', !safeOk);
            }

            const bankNeedsAccount = bankVal > 0 && bankSelect && !bankSelect.value;
            const safeNeedsAccount = safeVal > 0 && safeSelect && !safeSelect.value;

            let ok = okSum && bankOk && safeOk && !bankNeedsAccount && !safeNeedsAccount;

            if (saveButton) {
                saveButton.disabled = !ok;
            }
        }

        enforceAccountBeforeShare();
        refreshHints();

        if (amountInput) {
            amountInput.addEventListener('input', updateFromAmount);
            amountInput.addEventListener('blur', () => {
                const value = parseDec(amountInput.value);
                if (value != null) {
                    amountInput.value = round2(Math.max(0, value)).toFixed(2);
                }
            });
        }

        if (bankShareInput) {
            bankShareInput.addEventListener('input', updateFromBank);
            bankShareInput.addEventListener('blur', () => {
                const value = parseDec(bankShareInput.value);
                if (value != null) {
                    bankShareInput.value = round2(Math.max(0, value)).toFixed(2);
                }
            });
        }

        if (safeShareInput) {
            safeShareInput.addEventListener('input', updateFromSafe);
            safeShareInput.addEventListener('blur', () => {
                const value = parseDec(safeShareInput.value);
                if (value != null) {
                    safeShareInput.value = round2(Math.max(0, value)).toFixed(2);
                }
            });
        }

        if (bankSelect) {
            bankSelect.addEventListener('change', () => {
                enforceAccountBeforeShare();
                refreshBankAvailability();
            });
            refreshBankAvailability();
        }

        if (safeSelect) {
            safeSelect.addEventListener('change', () => {
                enforceAccountBeforeShare();
                refreshSafeAvailability();
            });
            refreshSafeAvailability();
        }

        form.addEventListener('reset', () => {
            window.setTimeout(() => {
                bankAvail = null;
                safeAvail = null;
                if (bankAvailValue) bankAvailValue.textContent = '—';
                if (safeAvailValue) safeAvailValue.textContent = '—';
                enforceAccountBeforeShare();
                refreshHints();
                if (goodsManager) {
                    goodsManager.refreshRows();
                    goodsManager.validate();
                }
            }, 0);
        });

        form.addEventListener('submit', (event) => {
            if (goodsManager && !goodsManager.validate()) {
                event.preventDefault();
                event.stopPropagation();
                alert('لا يمكنك بيع كمية أكبر من المتاح في المخزون.');
                return;
            }

            if (saveButton) {
                saveButton.disabled = true;
            }
            if (spinner) {
                spinner.classList.remove('d-none');
            }
        });
    }
});
</script>
@endpush
