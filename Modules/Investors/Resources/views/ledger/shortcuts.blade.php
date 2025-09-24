@extends('layouts.master')

@php
    $operations = $operations ?? [];
    $defaultOperation = $defaultOperation ?? (array_key_first($operations) ?: null);
    $activeOperation = old('active_operation', $defaultOperation);
    if (!$activeOperation || !isset($operations[$activeOperation])) {
        $activeOperation = $defaultOperation;
    }

    $activeMode = old('active_mode', 'single');
    if (!in_array($activeMode, ['single', 'split'], true)) {
        $activeMode = 'single';
    }

    $pageTitle = $pageTitle ?? 'قيود المستثمرين — عملية سريعة';
    $pageHeading = $pageHeading ?? 'قيود المستثمرين — عملية سريعة';
    $shortcutLinks = $shortcutLinks ?? [];

    $today = now()->toDateString();
    $singleAction = route('ledger.store');
    $splitAction = route('ledger.split.store');
    $redirectRouteName = $redirectRouteName ?? 'investors.ledger.shortcuts';
    $hasOldInput = old('active_operation') !== null || old('active_mode') !== null || $errors->any();
@endphp

@section('title', $pageTitle)

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ $pageHeading }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('investors.index') }}">{{ __('investors::investors.Investors') }}</a></li>
            <li class="breadcrumb-item active">{{ $pageHeading }}</li>
        </ol>
    </nav>
</div>

{{-- @if (!empty($missingStatuses))
    <div class="alert alert-warning">
        الحالات التالية غير متاحة ضمن فئة المستثمرين: <strong>{{ implode('، ', $missingStatuses) }}</strong>.
        يرجى إضافتها من إعدادات الحالات لضمان عمل النماذج بشكل صحيح.
    </div>
@endif --}}

<div class="card shadow-sm">
    <div class="card-body">
        @if (empty($operations))
            <div class="alert alert-info mb-0">لا توجد عمليات متاحة حاليًا.</div>
        @else
            

            @if (count($operations) > 1)
                <ul class="nav nav-tabs" id="investorOperationsTabs" role="tablist">
                    @foreach ($operations as $key => $operation)
                        @php
                            $isActive = $activeOperation === $key;
                        @endphp
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link {{ $isActive ? 'active' : '' }}"
                                id="investor-operation-tab-{{ $key }}"
                                data-bs-toggle="tab"
                                data-bs-target="#investor-operation-{{ $key }}"
                                type="button"
                                role="tab"
                                data-operation="{{ $key }}">
                                {{ $operation['title'] }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="tab-content pt-3" id="investorOperationsContent">
                @foreach ($operations as $key => $operation)
                    @php
                        $isActive = $activeOperation === $key;
                        $singleActive = $isActive && $activeMode === 'single';
                        $splitActive = $isActive && $activeMode === 'split';

                        $investorSingleId = $singleActive ? old('investor_id') : null;
                        $investorSplitId = $splitActive ? old('investor_id') : null;

                        $singleBankId = $singleActive ? old('bank_account_id') : null;
                        $singleSafeId = $singleActive ? old('safe_id') : null;
                        $singleAccountValue = $singleBankId
                            ? ('bank:' . $singleBankId)
                            : ($singleSafeId ? ('safe:' . $singleSafeId) : '');
                        $singleAmount = $singleActive ? old('amount', '0.00') : '0.00';
                        $singleDate = $singleActive ? old('transaction_date', $today) : $today;
                        $singleNotes = $singleActive ? old('notes') : '';

                        $splitAmount = $splitActive ? old('amount', '0.00') : '0.00';
                        $splitBankShare = $splitActive ? old('bank_share', '0.00') : '0.00';
                        $splitSafeShare = $splitActive ? old('safe_share', '0.00') : '0.00';
                        $splitBankId = $splitActive ? old('bank_account_id') : null;
                        $splitSafeId = $splitActive ? old('safe_id') : null;
                        $splitDate = $splitActive ? old('transaction_date', $today) : $today;
                        $splitNotes = $splitActive ? old('notes') : '';
                    @endphp
                    <div
                        class="tab-pane fade {{ $isActive ? 'show active' : '' }}"
                        id="investor-operation-{{ $key }}"
                        role="tabpanel"
                        aria-labelledby="investor-operation-tab-{{ $key }}">
                        <div class="alert alert-info d-flex justify-content-between align-items-center">
                            <div>
                                يتم إنشاء القيود للفئة <strong>المستثمرين</strong>
                                وبحالة <strong>{{ $operation['status_name'] }}</strong>.
                                @if (!empty($operation['description']))
                                    <div class="small text-muted mt-1">{{ $operation['description'] }}</div>
                                @endif
                            </div>
                            <span class="badge rounded-pill {{ $operation['badge_class'] ?? 'bg-secondary' }}">
                                {{ $operation['direction_label'] ?? 'داخل/خارج' }}
                            </span>
                        </div>

                        @if (!($operation['enabled'] ?? false))
                            <div class="alert alert-danger mb-0">
                                لا يمكن استخدام هذا النموذج لعدم توفر الحالة المرتبطة به ضمن فئة المستثمرين.
                            </div>
                            @continue
                        @endif

                        <ul class="nav nav-tabs" id="investor-operation-{{ $key }}-mode-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button
                                    class="nav-link {{ $singleActive ? 'active' : '' }} js-mode-tab"
                                    id="investor-operation-{{ $key }}-single-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#investor-operation-{{ $key }}-single"
                                    type="button"
                                    role="tab"
                                    data-operation="{{ $key }}"
                                    data-mode="single">
                                    قيد عادي
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button
                                    class="nav-link {{ $splitActive ? 'active' : '' }} js-mode-tab"
                                    id="investor-operation-{{ $key }}-split-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#investor-operation-{{ $key }}-split"
                                    type="button"
                                    role="tab"
                                    data-operation="{{ $key }}"
                                    data-mode="split">
                                    قيد مُجزّأ
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content pt-3" id="investor-operation-{{ $key }}-mode-content">
                            <div
                                class="tab-pane fade {{ $singleActive ? 'show active' : '' }}"
                                id="investor-operation-{{ $key }}-single"
                                role="tabpanel"
                                aria-labelledby="investor-operation-{{ $key }}-single-tab">
                                <form
                                    action="{{ $singleAction }}"
                                    method="POST"
                                    class="row g-3 mt-1 js-single-form"
                                    data-status-type="{{ $operation['transaction_type_id'] }}">
                                    @csrf
                                    <input type="hidden" name="redirect_to" value="{{ $redirectRouteName }}">
                                    <input type="hidden" name="party_category" value="investors">
                                    <input type="hidden" name="status_id" value="{{ $operation['status_id'] }}">
                                    <input type="hidden" name="active_operation" value="{{ $key }}">
                                    <input type="hidden" name="active_mode" value="single">
                                    <input type="hidden" name="bank_account_id" value="{{ $singleBankId }}" class="js-bank-hidden">
                                    <input type="hidden" name="safe_id" value="{{ $singleSafeId }}" class="js-safe-hidden">

                                    <div class="col-md-4">
                                        <label class="form-label" for="investor-{{ $key }}-single">المستثمر</label>
                                        <div
                                            class="js-investor-field"
                                            data-liquidity-url="{{ route('ajax.investors.liquidity', ['investor' => '__ID__']) }}">
                                            <select
                                                name="investor_id"
                                                id="investor-{{ $key }}-single"
                                                class="form-select js-investor-select"
                                                required>
                                                <option value="" disabled {{ $investorSingleId ? '' : 'selected' }}>اختر المستثمر</option>
                                                @foreach ($investors as $investor)
                                                    <option
                                                        value="{{ $investor->id }}"
                                                        {{ (int) $investorSingleId === (int) $investor->id ? 'selected' : '' }}>
                                                        {{ $investor->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="form-text mt-1">
                                                <span class="text-muted">سيولة المستثمر المتاحة: </span>
                                                <strong class="js-investor-liquidity">—</strong>
                                                <span class="spinner-border spinner-border-sm align-middle d-none js-investor-loading" role="status" aria-hidden="true"></span>
                                            </div>
                                        </div>
                                        @if ($singleActive)
                                            @error('investor_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        @endif
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label" for="account-{{ $key }}">الحساب</label>
                                        <select
                                            id="account-{{ $key }}"
                                            class="form-select js-account-picker"
                                            required>
                                            <option value="" disabled {{ $singleAccountValue ? '' : 'selected' }}>اختر حسابًا</option>
                                            <optgroup label="الحسابات البنكية">
                                                @foreach ($banks as $bank)
                                                    <option value="bank:{{ $bank->id }}" {{ $singleAccountValue === 'bank:' . $bank->id ? 'selected' : '' }}>
                                                        {{ $bank->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="الخزن">
                                                @foreach ($safes as $safe)
                                                    <option value="safe:{{ $safe->id }}" {{ $singleAccountValue === 'safe:' . $safe->id ? 'selected' : '' }}>
                                                        {{ $safe->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                        <div class="form-text mt-1">
                                            <span class="text-muted">المتاح في الحساب:</span>
                                            <strong class="js-account-availability">—</strong>
                                            <span class="spinner-border spinner-border-sm align-middle d-none js-account-loading" role="status" aria-hidden="true"></span>
                                        </div>
                                        @if ($singleActive)
                                            @error('bank_account_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                            @error('safe_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        @endif
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label" for="amount-{{ $key }}">المبلغ</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            name="amount"
                                            id="amount-{{ $key }}"
                                            class="form-control js-amount-input"
                                            value="{{ $singleAmount }}"
                                            required>
                                        <div class="invalid-feedback">المبلغ يتجاوز المتاح في الحساب.</div>
                                        @if ($singleActive)
                                            @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        @endif
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label" for="date-{{ $key }}">تاريخ العملية</label>
                                        <input
                                            type="date"
                                            name="transaction_date"
                                            id="date-{{ $key }}"
                                            class="form-control js-date"
                                            value="{{ $singleDate }}"
                                            required>
                                        @if ($singleActive)
                                            @error('transaction_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        @endif
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label" for="notes-{{ $key }}">ملاحظات</label>
                                        <textarea
                                            name="notes"
                                            id="notes-{{ $key }}"
                                            rows="3"
                                            class="form-control"
                                            maxlength="1000">{{ $singleNotes }}</textarea>
                                        @if ($singleActive)
                                            @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        @endif
                                    </div>

                                    <div class="col-12 d-flex gap-2 mt-2">
                                        <x-button.action type="submit" variant="success" :outline="true" class="js-submit-btn">
                                            <span class="spinner-border spinner-border-sm me-1 d-none js-submit-spinner" role="status" aria-hidden="true"></span>
                                            <span class="d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-check2-circle"></i>
                                                <span>حفظ</span>
                                            </span>
                                        </x-button.action>
                                        <button type="reset" class="btn btn-outline-secondary">إعادة تعيين</button>
                                    </div>
                                </form>
                            </div>

                            <div
                                class="tab-pane fade {{ $splitActive ? 'show active' : '' }}"
                                id="investor-operation-{{ $key }}-split"
                                role="tabpanel"
                                aria-labelledby="investor-operation-{{ $key }}-split-tab">
                                <form
                                    action="{{ $splitAction }}"
                                    method="POST"
                                    class="row g-3 mt-1 js-split-form"
                                    data-status-type="{{ $operation['transaction_type_id'] }}">
                                    @csrf
                                    <input type="hidden" name="redirect_to" value="{{ $redirectRouteName }}">
                                    <input type="hidden" name="party_category" value="investors">
                                    <input type="hidden" name="status_id" value="{{ $operation['status_id'] }}">
                                    <input type="hidden" name="active_operation" value="{{ $key }}">
                                    <input type="hidden" name="active_mode" value="split">

                                    <div class="col-md-3">
                                        <label class="form-label" for="total-{{ $key }}">إجمالي المبلغ</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            name="amount"
                                            id="total-{{ $key }}"
                                            class="form-control js-total-input"
                                            value="{{ $splitAmount }}"
                                            required>
                                        <div class="invalid-feedback">يرجى إدخال إجمالي صالح.</div>
                                        @if ($splitActive)
                                            @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        @endif
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label" for="bank-share-{{ $key }}">جزء البنك</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            name="bank_share"
                                            id="bank-share-{{ $key }}"
                                            class="form-control js-bank-share"
                                            value="{{ $splitBankShare }}">
                                        <div class="invalid-feedback">المبلغ يتجاوز المتاح في الحساب البنكي.</div>
                                        @if ($splitActive)
                                            @error('bank_share') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        @endif
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label" for="safe-share-{{ $key }}">جزء الخزنة</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            name="safe_share"
                                            id="safe-share-{{ $key }}"
                                            class="form-control js-safe-share"
                                            value="{{ $splitSafeShare }}">
                                        <div class="invalid-feedback">المبلغ يتجاوز المتاح في الخزنة.</div>
                                        @if ($splitActive)
                                            @error('safe_share') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        @endif
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label" for="date-split-{{ $key }}">تاريخ العملية</label>
                                        <input
                                            type="date"
                                            name="transaction_date"
                                            id="date-split-{{ $key }}"
                                            class="form-control js-date"
                                            value="{{ $splitDate }}"
                                            required>
                                        @if ($splitActive)
                                            @error('transaction_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        @endif
                                    </div>

                                    <div class="col-12">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="border rounded p-3 h-100">
                                                    <h6 class="mb-3">تفاصيل البنك</h6>
                                                    <label class="form-label" for="bank-select-{{ $key }}">الحساب البنكي</label>
                                                    <select
                                                        name="bank_account_id"
                                                        id="bank-select-{{ $key }}"
                                                        class="form-select js-bank-select">
                                                        <option value="" disabled {{ $splitBankId ? '' : 'selected' }}>اختر الحساب البنكي</option>
                                                        @foreach ($banks as $bank)
                                                            <option value="{{ $bank->id }}" {{ (int) $splitBankId === (int) $bank->id ? 'selected' : '' }}>
                                                                {{ $bank->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="form-text mt-1">
                                                        <span class="text-muted">المتاح: </span>
                                                        <strong class="js-bank-availability">—</strong>
                                                        <span class="spinner-border spinner-border-sm align-middle d-none js-bank-loading" role="status" aria-hidden="true"></span>
                                                    </div>
                                                    @if ($splitActive)
                                                        @error('bank_account_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="border rounded p-3 h-100">
                                                    <h6 class="mb-3">تفاصيل الخزنة</h6>
                                                    <label class="form-label" for="safe-select-{{ $key }}">الخزنة</label>
                                                    <select
                                                        name="safe_id"
                                                        id="safe-select-{{ $key }}"
                                                        class="form-select js-safe-select">
                                                        <option value="" disabled {{ $splitSafeId ? '' : 'selected' }}>اختر الخزنة</option>
                                                        @foreach ($safes as $safe)
                                                            <option value="{{ $safe->id }}" {{ (int) $splitSafeId === (int) $safe->id ? 'selected' : '' }}>
                                                                {{ $safe->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="form-text mt-1">
                                                        <span class="text-muted">المتاح: </span>
                                                        <strong class="js-safe-availability">—</strong>
                                                        <span class="spinner-border spinner-border-sm align-middle d-none js-safe-loading" role="status" aria-hidden="true"></span>
                                                    </div>
                                                    @if ($splitActive)
                                                        @error('safe_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="small">
                                            <span class="text-muted js-sum-hint">مجموع البنك + الخزنة يجب أن يساوي إجمالي المبلغ.</span>
                                            <span class="ms-2 text-muted js-ratio-hint"></span>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div
                                            class="js-investor-field"
                                            data-liquidity-url="{{ route('ajax.investors.liquidity', ['investor' => '__ID__']) }}">
                                            <label class="form-label" for="investor-{{ $key }}-split">المستثمر</label>
                                            <select
                                                name="investor_id"
                                                id="investor-{{ $key }}-split"
                                                class="form-select js-investor-select"
                                                required>
                                                <option value="" disabled {{ $investorSplitId ? '' : 'selected' }}>اختر المستثمر</option>
                                                @foreach ($investors as $investor)
                                                    <option value="{{ $investor->id }}" {{ (int) $investorSplitId === (int) $investor->id ? 'selected' : '' }}>
                                                        {{ $investor->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="form-text mt-1">
                                                <span class="text-muted">سيولة المستثمر المتاحة: </span>
                                                <strong class="js-investor-liquidity">—</strong>
                                                <span class="spinner-border spinner-border-sm align-middle d-none js-investor-loading" role="status" aria-hidden="true"></span>
                                            </div>
                                        </div>
                                        @if ($splitActive)
                                            @error('investor_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        @endif
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label" for="notes-split-{{ $key }}">ملاحظات</label>
                                        <textarea
                                            name="notes"
                                            id="notes-split-{{ $key }}"
                                            rows="3"
                                            class="form-control"
                                            maxlength="1000">{{ $splitNotes }}</textarea>
                                        @if ($splitActive)
                                            @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        @endif
                                    </div>

                                    <div class="col-12 d-flex gap-2 mt-2">
                                        <x-button.action type="submit" variant="success" :outline="true" class="js-submit-btn">
                                            <span class="spinner-border spinner-border-sm me-1 d-none js-submit-spinner" role="status" aria-hidden="true"></span>
                                            <span class="d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-check2-circle"></i>
                                                <span>حفظ</span>
                                            </span>
                                        </x-button.action>
                                        <button type="reset" class="btn btn-outline-secondary">إعادة تعيين</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ACCOUNT_AVAIL_URL = @json(route('ajax.accounts.availability'));
            const INVESTOR_LIQ_TEMPLATE = @json(route('ajax.investors.liquidity', ['investor' => '__ID__']));
            const USE_OLD = {{ $hasOldInput ? 'true' : 'false' }};
            const OPERATION_STORAGE_KEY = 'investorLedgerShortcuts.activeOperation';
            const MODE_STORAGE_PREFIX = 'investorLedgerShortcuts.activeMode.';

            const formatCurrency = (value) => {
                const num = Number(value);
                if (!Number.isFinite(num)) {
                    return '0.00';
                }
                return num.toLocaleString('ar-EG', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            };

            const parseDecimal = (value) => {
                if (value == null) {
                    return null;
                }
                const str = String(value).trim().replace(',', '.');
                if (!str) {
                    return null;
                }
                const num = Number(str);
                return Number.isFinite(num) ? num : null;
            };

            const parseAccountValue = (value) => {
                if (!value) {
                    return null;
                }
                const [type, id] = String(value).split(':');
                if (!type || !id) {
                    return null;
                }
                const idNum = Number(id);
                if (!Number.isFinite(idNum)) {
                    return null;
                }
                if (type !== 'bank' && type !== 'safe') {
                    return null;
                }
                return { type, id: idNum };
            };

            const fetchAccountAvailability = async (type, id) => {
                if (!type || !id) {
                    return null;
                }
                const params = new URLSearchParams({
                    account_type: type,
                    account_id: String(id),
                });
                try {
                    const response = await fetch(`${ACCOUNT_AVAIL_URL}?${params.toString()}`, {
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

            const fetchInvestorLiquidity = async (url) => {
                if (!url) {
                    return { success: false, message: 'missing-url' };
                }
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

            const attachSubmitHandler = (form) => {
                if (!form) {
                    return;
                }
                const submitBtn = form.querySelector('.js-submit-btn');
                const spinner = form.querySelector('.js-submit-spinner');
                form.addEventListener('submit', () => {
                    if (submitBtn) {
                        submitBtn.setAttribute('disabled', 'disabled');
                    }
                    if (spinner) {
                        spinner.classList.remove('d-none');
                    }
                });
                form.addEventListener('reset', () => {
                    if (submitBtn) {
                        submitBtn.removeAttribute('disabled');
                    }
                    if (spinner) {
                        spinner.classList.add('d-none');
                    }
                });
            };

            const initInvestorFields = () => {
                document.querySelectorAll('.js-investor-field').forEach((wrapper) => {
                    const select = wrapper.querySelector('.js-investor-select');
                    const valueSpan = wrapper.querySelector('.js-investor-liquidity');
                    const loading = wrapper.querySelector('.js-investor-loading');
                    const urlTemplate = wrapper.dataset.liquidityUrl || INVESTOR_LIQ_TEMPLATE;
                    if (!select || !valueSpan) {
                        return;
                    }

                    const refresh = async () => {
                        valueSpan.classList.remove('text-danger');
                        const investorId = select.value;
                        if (!investorId) {
                            valueSpan.textContent = '—';
                            return;
                        }
                        if (loading) {
                            loading.classList.remove('d-none');
                        }
                        const url = urlTemplate.replace('__ID__', encodeURIComponent(investorId));
                        const payload = await fetchInvestorLiquidity(url);
                        if (loading) {
                            loading.classList.add('d-none');
                        }
                        if (!payload || payload.success !== true) {
                            valueSpan.textContent = payload && payload.message ? `خطأ: ${payload.message}` : 'تعذّر الجلب';
                            valueSpan.classList.add('text-danger');
                            return;
                        }
                        const numeric = Number(payload.cash ?? payload.liquidity ?? payload.balance ?? 0);
                        const formatted = payload.formatted
                            ?? payload.cash_formatted
                            ?? payload.liquidity_formatted
                            ?? formatCurrency(numeric);
                        valueSpan.textContent = formatted;
                    };

                    select.addEventListener('change', refresh);
                    if (select.value) {
                        refresh();
                    }
                });
            };

            const initSingleForms = () => {
                document.querySelectorAll('.js-single-form').forEach((form) => {
                    const statusType = Number(form.dataset.statusType || 2);
                    const accountPicker = form.querySelector('.js-account-picker');
                    const bankHidden = form.querySelector('.js-bank-hidden');
                    const safeHidden = form.querySelector('.js-safe-hidden');
                    const availabilityValue = form.querySelector('.js-account-availability');
                    const availabilityLoading = form.querySelector('.js-account-loading');
                    const amountInput = form.querySelector('.js-amount-input');
                    let accountAvail = null;

                    const applyHidden = (account) => {
                        if (bankHidden) {
                            bankHidden.value = account && account.type === 'bank' ? account.id : '';
                        }
                        if (safeHidden) {
                            safeHidden.value = account && account.type === 'safe' ? account.id : '';
                        }
                    };

                    const applyAmountConstraints = () => {
                        if (!amountInput) {
                            return;
                        }
                        if (statusType === 2 && accountAvail !== null) {
                            amountInput.setAttribute('max', String(accountAvail));
                        } else {
                            amountInput.removeAttribute('max');
                        }
                    };

                    const validateAmount = () => {
                        if (!amountInput) {
                            return;
                        }
                        const value = parseDecimal(amountInput.value);
                        if (statusType === 2 && accountAvail !== null && value !== null && value > accountAvail + 1e-9) {
                            amountInput.setCustomValidity('المبلغ يتجاوز المتاح في الحساب.');
                        } else {
                            amountInput.setCustomValidity('');
                        }
                        amountInput.classList.toggle('is-invalid', amountInput.validationMessage !== '');
                    };

                    const refreshAvailability = async () => {
                        if (!availabilityValue) {
                            return;
                        }
                        availabilityValue.classList.remove('text-danger');
                        const account = parseAccountValue(accountPicker ? accountPicker.value : '');
                        applyHidden(account);
                        if (!account) {
                            availabilityValue.textContent = '—';
                            accountAvail = null;
                            applyAmountConstraints();
                            validateAmount();
                            return;
                        }
                        if (availabilityLoading) {
                            availabilityLoading.classList.remove('d-none');
                        }
                        const payload = await fetchAccountAvailability(account.type, account.id);
                        if (availabilityLoading) {
                            availabilityLoading.classList.add('d-none');
                        }
                        if (!payload || payload.success !== true) {
                            availabilityValue.textContent = payload && payload.message ? `خطأ: ${payload.message}` : 'تعذّر الجلب';
                            availabilityValue.classList.add('text-danger');
                            accountAvail = null;
                        } else {
                            const numeric = Number(payload.balance ?? payload.available ?? payload.cash ?? 0);
                            const formatted = payload.formatted
                                ?? payload.balance_formatted
                                ?? payload.available_formatted
                                ?? formatCurrency(numeric);
                            availabilityValue.textContent = formatted;
                            accountAvail = Number.isFinite(numeric) ? numeric : null;
                        }
                        applyAmountConstraints();
                        validateAmount();
                    };

                    if (accountPicker) {
                        accountPicker.addEventListener('change', refreshAvailability);
                        refreshAvailability();
                    }

                    if (amountInput) {
                        amountInput.addEventListener('input', validateAmount);
                        validateAmount();
                    }

                    attachSubmitHandler(form);
                });
            };

            const initSplitForms = () => {
                const round2 = (value) => Math.round((value ?? 0) * 100) / 100;

                document.querySelectorAll('.js-split-form').forEach((form) => {
                    const statusType = Number(form.dataset.statusType || 2);
                    const totalInput = form.querySelector('.js-total-input');
                    const bankShareInput = form.querySelector('.js-bank-share');
                    const safeShareInput = form.querySelector('.js-safe-share');
                    const bankSelect = form.querySelector('.js-bank-select');
                    const safeSelect = form.querySelector('.js-safe-select');
                    const bankAvailability = form.querySelector('.js-bank-availability');
                    const bankLoading = form.querySelector('.js-bank-loading');
                    const safeAvailability = form.querySelector('.js-safe-availability');
                    const safeLoading = form.querySelector('.js-safe-loading');
                    const sumHint = form.querySelector('.js-sum-hint');
                    const ratioHint = form.querySelector('.js-ratio-hint');
                    let bankAvail = null;
                    let safeAvail = null;

                    const validateShare = (input, avail) => {
                        if (!input) {
                            return;
                        }
                        const value = parseDecimal(input.value);
                        if (statusType === 2 && avail !== null && value !== null && value > avail + 1e-9) {
                            input.setCustomValidity('المبلغ يتجاوز المتاح في الحساب.');
                        } else {
                            input.setCustomValidity('');
                        }
                        input.classList.toggle('is-invalid', input.validationMessage !== '');
                    };

                    const updateShareConstraints = () => {
                        if (statusType === 2) {
                            if (bankShareInput) {
                                if (bankAvail !== null) {
                                    bankShareInput.setAttribute('max', String(bankAvail));
                                } else {
                                    bankShareInput.removeAttribute('max');
                                }
                            }
                            if (safeShareInput) {
                                if (safeAvail !== null) {
                                    safeShareInput.setAttribute('max', String(safeAvail));
                                } else {
                                    safeShareInput.removeAttribute('max');
                                }
                            }
                        } else {
                            if (bankShareInput) {
                                bankShareInput.removeAttribute('max');
                            }
                            if (safeShareInput) {
                                safeShareInput.removeAttribute('max');
                            }
                        }
                        validateShare(bankShareInput, bankAvail);
                        validateShare(safeShareInput, safeAvail);
                    };

                    const updateSumHint = () => {
                        if (!sumHint) {
                            return;
                        }
                        sumHint.classList.remove('text-danger');
                        const total = parseDecimal(totalInput ? totalInput.value : null);
                        const bankVal = parseDecimal(bankShareInput ? bankShareInput.value : null) || 0;
                        const safeVal = parseDecimal(safeShareInput ? safeShareInput.value : null) || 0;
                        const sum = round2(bankVal + safeVal);

                        if (total == null) {
                            sumHint.textContent = 'أدخل إجمالي المبلغ لتوزيعه على البنك والخزنة.';
                            if (ratioHint) {
                                ratioHint.textContent = '';
                            }
                            return;
                        }

                        if (Math.abs(sum - round2(total)) > 0.01) {
                            sumHint.textContent = 'يجب أن يساوي مجموع البنك + الخزنة إجمالي المبلغ.';
                            sumHint.classList.add('text-danger');
                        } else {
                            sumHint.textContent = 'مجموع البنك + الخزنة يساوي إجمالي المبلغ.';
                        }

                        if (ratioHint) {
                            if (total > 0 && Math.abs(sum - round2(total)) <= 0.01) {
                                const bankPct = (bankVal / total) * 100;
                                const safePct = (safeVal / total) * 100;
                                ratioHint.textContent = `البنك ${bankPct.toFixed(2)}% — الخزنة ${safePct.toFixed(2)}%`;
                            } else {
                                ratioHint.textContent = '';
                            }
                        }
                    };

                    const enforceShareState = () => {
                        const bankChosen = !!(bankSelect && bankSelect.value);
                        if (bankShareInput) {
                            bankShareInput.readOnly = !bankChosen;
                            bankShareInput.classList.toggle('bg-light', !bankChosen);
                            if (!bankChosen) {
                                bankShareInput.value = '0.00';
                                bankAvail = null;
                            }
                        }

                        const safeChosen = !!(safeSelect && safeSelect.value);
                        if (safeShareInput) {
                            safeShareInput.readOnly = !safeChosen;
                            safeShareInput.classList.toggle('bg-light', !safeChosen);
                            if (!safeChosen) {
                                safeShareInput.value = '0.00';
                                safeAvail = null;
                            }
                        }

                        updateShareConstraints();
                        updateSumHint();
                    };

                    const refreshBankAvailability = async () => {
                        if (!bankAvailability) {
                            return;
                        }
                        bankAvailability.classList.remove('text-danger');
                        const bankId = bankSelect ? bankSelect.value : '';
                        if (!bankId) {
                            bankAvailability.textContent = '—';
                            bankAvail = null;
                            updateShareConstraints();
                            return;
                        }
                        if (bankLoading) {
                            bankLoading.classList.remove('d-none');
                        }
                        const payload = await fetchAccountAvailability('bank', bankId);
                        if (bankLoading) {
                            bankLoading.classList.add('d-none');
                        }
                        if (!payload || payload.success !== true) {
                            bankAvailability.textContent = payload && payload.message ? `خطأ: ${payload.message}` : 'تعذّر الجلب';
                            bankAvailability.classList.add('text-danger');
                            bankAvail = null;
                        } else {
                            const numeric = Number(payload.balance ?? payload.available ?? payload.cash ?? 0);
                            const formatted = payload.formatted
                                ?? payload.balance_formatted
                                ?? payload.available_formatted
                                ?? formatCurrency(numeric);
                            bankAvailability.textContent = formatted;
                            bankAvail = Number.isFinite(numeric) ? numeric : null;
                        }
                        updateShareConstraints();
                    };

                    const refreshSafeAvailability = async () => {
                        if (!safeAvailability) {
                            return;
                        }
                        safeAvailability.classList.remove('text-danger');
                        const safeId = safeSelect ? safeSelect.value : '';
                        if (!safeId) {
                            safeAvailability.textContent = '—';
                            safeAvail = null;
                            updateShareConstraints();
                            return;
                        }
                        if (safeLoading) {
                            safeLoading.classList.remove('d-none');
                        }
                        const payload = await fetchAccountAvailability('safe', safeId);
                        if (safeLoading) {
                            safeLoading.classList.add('d-none');
                        }
                        if (!payload || payload.success !== true) {
                            safeAvailability.textContent = payload && payload.message ? `خطأ: ${payload.message}` : 'تعذّر الجلب';
                            safeAvailability.classList.add('text-danger');
                            safeAvail = null;
                        } else {
                            const numeric = Number(payload.balance ?? payload.available ?? payload.cash ?? 0);
                            const formatted = payload.formatted
                                ?? payload.balance_formatted
                                ?? payload.available_formatted
                                ?? formatCurrency(numeric);
                            safeAvailability.textContent = formatted;
                            safeAvail = Number.isFinite(numeric) ? numeric : null;
                        }
                        updateShareConstraints();
                    };

                    if (bankSelect) {
                        bankSelect.addEventListener('change', () => {
                            enforceShareState();
                            refreshBankAvailability();
                        });
                    }

                    if (safeSelect) {
                        safeSelect.addEventListener('change', () => {
                            enforceShareState();
                            refreshSafeAvailability();
                        });
                    }

                    if (totalInput) {
                        totalInput.addEventListener('input', updateSumHint);
                    }
                    if (bankShareInput) {
                        bankShareInput.addEventListener('input', () => {
                            validateShare(bankShareInput, bankAvail);
                            updateSumHint();
                        });
                    }
                    if (safeShareInput) {
                        safeShareInput.addEventListener('input', () => {
                            validateShare(safeShareInput, safeAvail);
                            updateSumHint();
                        });
                    }

                    enforceShareState();
                    if (bankSelect && bankSelect.value) {
                        refreshBankAvailability();
                    }
                    if (safeSelect && safeSelect.value) {
                        refreshSafeAvailability();
                    }
                    updateSumHint();

                    attachSubmitHandler(form);
                });
            };

            const initTabPersistence = () => {
                const { Tab } = window.bootstrap || {};
                document.querySelectorAll('#investorOperationsTabs button[data-operation]').forEach((button) => {
                    button.addEventListener('shown.bs.tab', (event) => {
                        const op = event.target?.dataset?.operation;
                        if (op) {
                            try {
                                localStorage.setItem(OPERATION_STORAGE_KEY, op);
                            } catch (error) {
                                // ignore storage errors
                            }
                            if (!USE_OLD && Tab) {
                                const storedMode = (() => {
                                    try {
                                        return localStorage.getItem(`${MODE_STORAGE_PREFIX}${op}`);
                                    } catch (error) {
                                        return null;
                                    }
                                })();
                                if (storedMode) {
                                    const modeButton = document.querySelector(`#investor-operation-${op}-${storedMode}-tab`);
                                    if (modeButton) {
                                        new Tab(modeButton).show();
                                    }
                                }
                            }
                        }
                    });
                });

                document.querySelectorAll('.js-mode-tab').forEach((button) => {
                    button.addEventListener('shown.bs.tab', (event) => {
                        const op = event.target?.dataset?.operation;
                        const mode = event.target?.dataset?.mode;
                        if (op && mode) {
                            try {
                                localStorage.setItem(`${MODE_STORAGE_PREFIX}${op}`, mode);
                            } catch (error) {
                                // ignore
                            }
                        }
                    });
                });

                if (USE_OLD || !Tab) {
                    return;
                }

                try {
                    const storedOperation = localStorage.getItem(OPERATION_STORAGE_KEY);
                    if (storedOperation) {
                        const opButton = document.querySelector(`#investorOperationsTabs button[data-operation="${storedOperation}"]`);
                        if (opButton) {
                            new Tab(opButton).show();
                        }
                    }
                    const activeOpButton = document.querySelector('#investorOperationsTabs .nav-link.active');
                    const currentOperation = activeOpButton?.dataset?.operation
                        ?? localStorage.getItem(OPERATION_STORAGE_KEY);
                    if (currentOperation) {
                        const storedMode = localStorage.getItem(`${MODE_STORAGE_PREFIX}${currentOperation}`);
                        if (storedMode) {
                            const modeButton = document.querySelector(`#investor-operation-${currentOperation}-${storedMode}-tab`);
                            if (modeButton) {
                                new Tab(modeButton).show();
                            }
                        }
                    }
                } catch (error) {
                    // ignore storage errors
                }
            };

            initTabPersistence();
            initInvestorFields();
            initSingleForms();
            initSplitForms();
        });
    </script>
@endpush
