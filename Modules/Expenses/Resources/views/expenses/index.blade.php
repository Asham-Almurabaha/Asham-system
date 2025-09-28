@extends('layouts.master')

@section('title', __('expenses::expenses.index_title'))

@section('content')
    <div class="pagetitle mb-3">
        <h1 class="h3 mb-1">@lang('expenses::expenses.index_title')</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                <li class="breadcrumb-item active">@lang('expenses::expenses.index_title')</li>
            </ol>
        </nav>
    </div>

    {{-- زر إنشاء --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">
            <x-button.action href="{{ route('expenses.expenses.create') }}" variant="success">
                <i class="bi bi-plus-lg me-1"></i>@lang('expenses::expenses.actions.create')
            </x-button.action>
        </div>
    </div>

    {{-- KPI --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="kpi-card p-3 h-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="kpi-icon"><i class="bi bi-calendar-event fs-4 text-primary"></i></div>
                            <div class="flex-grow-1">
                                <div class="subnote">@lang('expenses::expenses.filters.upcoming')</div>
                                <div class="kpi-value fw-bold">{{ number_format($stats['upcoming'], 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="kpi-card p-3 h-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="kpi-icon"><i class="bi bi-exclamation-octagon fs-4 text-danger"></i></div>
                            <div class="flex-grow-1">
                                <div class="subnote">@lang('expenses::expenses.filters.overdue')</div>
                                <div class="kpi-value fw-bold text-danger">{{ number_format($stats['overdue'], 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="kpi-card p-3 h-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="kpi-icon"><i class="bi bi-collection fs-4 text-success"></i></div>
                            <div class="flex-grow-1">
                                <div class="subnote">@lang('expenses::expenses.filters.total')</div>
                                <div class="kpi-value fw-bold text-success">{{ number_format($stats['total'], 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- /.row -->
        </div><!-- /.card-body -->
    </div><!-- /.card -->

    {{-- الجدول --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <x-table head-class="table-light" class="text-center">
                @php
                    $today = \Illuminate\Support\Carbon::today();
                    $oldContextExpenseId = (string) old('context_expense_id');
                    $todayDate = now()->format('Y-m-d');
                    $banksCollection = $banks->values();
                    $safesCollection = $safes->values();
                @endphp

                <x-slot name="head">
                    <tr>
                        <th scope="col" style="width:60px" class="text-center">#</th>
                        <th scope="col" class="text-start">@lang('expenses::expenses.fields.title')</th>
                        <th scope="col" class="text-start">@lang('expenses::expenses.fields.expense_type_id')</th>
                        <th scope="col" class="text-end">@lang('expenses::expenses.fields.amount')</th>
                        <th scope="col" class="text-end">@lang('expenses::expenses.fields.paid_amount')</th>
                        <th scope="col" class="text-end">@lang('expenses::expenses.fields.outstanding_amount')</th>
                        <th scope="col">@lang('expenses::expenses.fields.due_date')</th>
                        <th scope="col" style="width:150px">@lang('expenses::expenses.fields.status')</th>
                        <th scope="col" class="text-end" style="width:260px">@lang('expenses::expenses.actions.manage')</th>
                    </tr>
                </x-slot>

                @forelse($expenses as $expense)
                    @php
                        $rowNumber = $expenses->firstItem() + $loop->index;
                        $paymentsCollapseId = 'expense-payments-' . $expense->id;
                        $outstanding = max($expense->outstanding_amount, 0);
                        $paymentAction = route('expenses.payments.store', $expense);
                        $queryString = request()->getQueryString();
                        if ($queryString) {
                            $paymentAction .= '?' . $queryString;
                        }
                        $isCurrentExpense = $oldContextExpenseId === (string) $expense->id;
                        $defaultAmount = number_format($outstanding, 2, '.', '');
                        $amountValue = $isCurrentExpense ? old('amount', $defaultAmount) : $defaultAmount;
                        $defaultPaidAt = $todayDate;
                        $paidAtValue = $isCurrentExpense ? old('paid_at', $defaultPaidAt) : $defaultPaidAt;
                        $oldBank = $isCurrentExpense ? old('bank_account_id') : null;
                        $oldSafe = $isCurrentExpense ? old('safe_id') : null;
                        $oldNotes = $isCurrentExpense ? old('notes') : '';
                        $collapseShowClass = $isCurrentExpense ? 'show' : '';
                    @endphp

                    <tr>
                        <td class="text-center">{{ $rowNumber }}</td>
                        <td class="text-start fw-semibold">{{ $expense->title }}</td>
                        <td class="text-start">{{ optional($expense->type)->name ?? __('expenses::expenses.fields.not_available') }}</td>
                        <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                        <td class="text-end">{{ number_format($expense->paid_amount, 2) }}</td>
                        <td class="text-end">{{ number_format($expense->outstanding_amount, 2) }}</td>
                        <td>{{ optional($expense->due_date)->toDateString() }}</td>
                        <td>
                            @if ($expense->outstanding_amount <= 0)
                                <span class="badge bg-success-subtle text-success">@lang('expenses::expenses.status_labels.settled')</span>
                            @elseif ($expense->due_date && $expense->due_date->lt($today))
                                <span class="badge bg-danger-subtle text-danger">@lang('expenses::expenses.status_labels.overdue')</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning">@lang('expenses::expenses.status_labels.upcoming')</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex flex-wrap justify-content-end gap-2">
                                <x-button.action
                                    type="button"
                                    variant="secondary"
                                    :outline="true"
                                    size="sm"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#{{ $paymentsCollapseId }}"
                                    aria-expanded="{{ $collapseShowClass ? 'true' : 'false' }}"
                                    aria-controls="{{ $paymentsCollapseId }}"
                                >
                                    @lang('expenses::expenses.actions.view_payments')
                                </x-button.action>

                                @if ($outstanding > 0)
                                    <x-button.action
                                        type="button"
                                        variant="dark"
                                        size="sm"
                                        :outline="true"
                                        data-bs-toggle="modal"
                                        data-bs-target="#expensePaymentModal"
                                        data-expense-payment-trigger="1"
                                        data-payment-action="{{ $paymentAction }}"
                                        data-expense-id="{{ $expense->id }}"
                                        data-expense-title="{{ e($expense->title) }}"
                                        data-outstanding="{{ $defaultAmount }}"
                                        data-outstanding-formatted="{{ number_format($outstanding, 2) }}"
                                        data-amount-default="{{ $defaultAmount }}"
                                        data-old-amount="{{ $isCurrentExpense ? $amountValue : '' }}"
                                        data-paid-at-default="{{ $defaultPaidAt }}"
                                        data-old-paid-at="{{ $isCurrentExpense ? $paidAtValue : '' }}"
                                        data-old-bank="{{ $oldBank ?? '' }}"
                                        data-old-safe="{{ $oldSafe ?? '' }}"
                                        data-old-notes="{{ e($oldNotes) }}"
                                    >
                                        <i class="bi bi-wallet2 me-1"></i>@lang('expenses::expenses.actions.record_payment')
                                    </x-button.action>
                                @endif

                                <x-button.action href="{{ route('expenses.expenses.edit', $expense) }}" variant="primary" :outline="true" size="sm">
                                    @lang('expenses::expenses.actions.edit')
                                </x-button.action>
                                @include('lookups::components.delete-button', [
                                    'action' => route('expenses.expenses.destroy', $expense),
                                    'confirm' => __('expenses::expenses.actions.confirm_delete'),
                                    'label' => __('expenses::expenses.actions.delete'),
                                ])
                            </div>
                        </td>
                    </tr>
                    <tr class="table-light">
                        <td colspan="9" class="text-start">
                            <div class="collapse {{ $collapseShowClass }}" id="{{ $paymentsCollapseId }}">
                                <div class="px-3 py-3">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                                        <div class="fw-semibold text-muted">@lang('expenses::payments.history.heading')</div>
                                        <div class="d-flex flex-wrap gap-2 small">
                                            <span class="badge bg-light text-dark border">@lang('expenses::payments.summary.amount'): {{ number_format($expense->amount, 2) }}</span>
                                            <span class="badge bg-light text-dark border">@lang('expenses::payments.summary.paid'): {{ number_format($expense->paid_amount, 2) }}</span>
                                            <span class="badge {{ $outstanding > 0 ? 'bg-warning text-dark' : 'bg-success' }}">@lang('expenses::payments.summary.outstanding'): {{ number_format($outstanding, 2) }}</span>
                                        </div>
                                    </div>

                                    @if ($expense->payments->isNotEmpty())
                                        <x-table small bordered head-class="table-secondary">
                                            <x-slot name="head">
                                                <tr>
                                                    <th class="text-center" style="width:60px">#</th>
                                                    <th class="text-end" style="width:140px">@lang('expenses::payments.history.table.amount')</th>
                                                    <th style="width:160px;">@lang('expenses::payments.history.table.paid_at')</th>
                                                    <th class="text-start" style="width:220px;">@lang('expenses::payments.history.table.account')</th>
                                                    <th class="text-start">@lang('expenses::payments.history.table.notes')</th>
                                                </tr>
                                            </x-slot>

                                            @foreach($expense->payments as $index => $payment)
                                                @php
                                                    $accountName = $payment->bankAccount->name ?? $payment->safe->name ?? '—';
                                                @endphp
                                                <tr>
                                                    <td class="text-center">{{ $index + 1 }}</td>
                                                    <td class="text-end">{{ number_format($payment->amount, 2) }}</td>
                                                    <td>{{ optional($payment->paid_at)->format('Y-m-d') }}</td>
                                                    <td class="text-start">{{ $accountName }}</td>
                                                    <td class="text-start">{{ $payment->notes ?: '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </x-table>
                                    @else
                                        <div class="text-muted small">@lang('expenses::payments.history.empty')</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">@lang('expenses::expenses.empty')</td>
                    </tr>
                @endforelse
            </x-table>
        </div>

        @if ($expenses->hasPages())
            <div class="card-footer bg-white">
                {{ $expenses->links() }}
            </div>
        @endif
    </div>

    <div class="modal fade" id="expensePaymentModal" tabindex="-1" aria-labelledby="expensePaymentModalLabel" aria-hidden="true" data-reopen-id="{{ $oldContextExpenseId }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" class="text-start">
                    @csrf
                    <input type="hidden" name="context_expense_id" value="{{ $oldContextExpenseId }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="expensePaymentModalLabel">@lang('expenses::expenses.actions.record_payment')</h5>
                        <x-button.action type="button" :unstyled="true" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('Close')"></x-button.action>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <div class="small text-muted">@lang('expenses::expenses.fields.title')</div>
                            <div class="fw-semibold" data-role="expense-title">—</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="modal-expense-amount">@lang('expenses::payments.fields.amount')</label>
                            <input
                                id="modal-expense-amount"
                                type="number"
                                name="amount"
                                class="form-control"
                                min="0.01"
                                step="0.01"
                                value="{{ old('amount') }}"
                                required
                            >
                            @error('amount')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                @lang('expenses::payments.summary.outstanding'):
                                <span data-role="outstanding-amount">—</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="modal-expense-paid-at">@lang('expenses::payments.fields.paid_at')</label>
                            <input id="modal-expense-paid-at" type="date" name="paid_at" class="form-control" value="{{ old('paid_at') }}" required>
                            @error('paid_at')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="modal-expense-account">@lang('expenses::payments.history.table.account')</label>
                            @php($selectedAccount = old('bank_account_id') ? 'bank:' . old('bank_account_id') : (old('safe_id') ? 'safe:' . old('safe_id') : ''))
                            <select
                                id="modal-expense-account"
                                class="form-select"
                                data-expense-account-picker="1"
                                data-bank-input="modal-expense-bank"
                                data-safe-input="modal-expense-safe"
                                @if ($banksCollection->isEmpty() && $safesCollection->isEmpty()) disabled @endif
                            >
                                <option value="" @selected($selectedAccount === '')>@lang('expenses::payments.fields.account_placeholder') / @lang('expenses::payments.fields.safe_placeholder')</option>
                                @if ($banksCollection->isNotEmpty())
                                    <optgroup label="@lang('expenses::payments.fields.bank_account_id')">
                                        @foreach($banksCollection as $bank)
                                            <option value="bank:{{ $bank->id }}" @selected($selectedAccount === 'bank:' . $bank->id)>{{ $bank->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                @if ($safesCollection->isNotEmpty())
                                    <optgroup label="@lang('expenses::payments.fields.safe_id')">
                                        @foreach($safesCollection as $safe)
                                            <option value="safe:{{ $safe->id }}" @selected($selectedAccount === 'safe:' . $safe->id)>{{ $safe->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                            <input type="hidden" name="bank_account_id" id="modal-expense-bank" value="{{ old('bank_account_id') }}">
                            <input type="hidden" name="safe_id" id="modal-expense-safe" value="{{ old('safe_id') }}">
                            <div class="form-text">@lang('expenses::payments.hints.account_choice')</div>
                            <div class="form-text mt-2" data-role="account-availability">
                                <span class="text-muted">@lang('expenses::payments.hints.account_available')</span>
                                <strong data-role="account-availability-value">—</strong>
                                <span class="spinner-border spinner-border-sm align-middle d-none" role="status" aria-hidden="true" data-role="account-availability-spinner"></span>
                            </div>
                            @if ($banksCollection->isEmpty() && $safesCollection->isEmpty())
                                <div class="text-danger small mt-1">@lang('expenses::payments.history.empty')</div>
                            @endif
                            @error('bank_account_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            @error('safe_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label" for="modal-expense-notes">@lang('expenses::payments.fields.notes')</label>
                            <textarea
                                id="modal-expense-notes"
                                name="notes"
                                class="form-control"
                                rows="2"
                                placeholder="@lang('expenses::payments.placeholders.notes')"
                            >{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <x-button.action type="button" variant="light" data-bs-dismiss="modal">@lang('expenses::expenses.actions.cancel')</x-button.action>
                        <x-button.action type="submit" variant="dark">
                            @lang('expenses::expenses.actions.record_payment')
                        </x-button.action>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalElement = document.getElementById('expensePaymentModal');
            if (!modalElement) {
                return;
            }

            var form = modalElement.querySelector('form');
            var amountInput = modalElement.querySelector('input[name="amount"]');
            var paidAtInput = modalElement.querySelector('input[name="paid_at"]');
            var bankInput = modalElement.querySelector('input[name="bank_account_id"]');
            var safeInput = modalElement.querySelector('input[name="safe_id"]');
            var accountPicker = modalElement.querySelector('[data-expense-account-picker]');
            var notesInput = modalElement.querySelector('textarea[name="notes"]');
            var contextInput = modalElement.querySelector('input[name="context_expense_id"]');
            var expenseTitleTarget = modalElement.querySelector('[data-role="expense-title"]');
            var outstandingTarget = modalElement.querySelector('[data-role="outstanding-amount"]');
            var availabilityValue = modalElement.querySelector('[data-role="account-availability-value"]');
            var availabilitySpinner = modalElement.querySelector('[data-role="account-availability-spinner"]');
            var availabilityUrl = @json(route('ajax.accounts.availability'));
            var amountLimitMessageTemplate = @json(__('expenses::payments.validation.amount_exceeds_available', ['available' => ':available']));
            var currentAvailability = null;
            var currentAvailabilityFormatted = '—';
            var outstandingLimit = null;

            if (!form || !amountInput || !paidAtInput || !notesInput || !contextInput) {
                return;
            }

            var cloneDataset = function (dataset) {
                return dataset ? Object.assign({}, dataset) : {};
            };

            var updateAmountMax = function () {
                if (!amountInput) {
                    return;
                }

                var limit = null;

                if (Number.isFinite(outstandingLimit)) {
                    limit = outstandingLimit;
                }

                if (Number.isFinite(currentAvailability)) {
                    limit = limit === null ? currentAvailability : Math.min(limit, currentAvailability);
                }

                if (Number.isFinite(limit)) {
                    amountInput.max = limit.toFixed(2);
                } else if (Number.isFinite(outstandingLimit)) {
                    amountInput.max = outstandingLimit.toFixed(2);
                } else {
                    amountInput.removeAttribute('max');
                }
            };

            var setOutstandingLimit = function (value) {
                if (!amountInput) {
                    return;
                }

                var numeric = null;

                if (typeof value === 'number') {
                    numeric = value;
                } else if (typeof value === 'string' && value.length) {
                    var parsed = Number(value);
                    if (Number.isFinite(parsed)) {
                        numeric = parsed;
                    }
                }

                outstandingLimit = Number.isFinite(numeric) ? numeric : null;

                updateAmountMax();
            };

            var enforceAmountLimit = function () {
                if (!amountInput) {
                    return;
                }

                var value = Number(amountInput.value);

                if (Number.isFinite(currentAvailability) && Number.isFinite(value) && value > currentAvailability + 0.00001) {
                    var formatted = currentAvailabilityFormatted && currentAvailabilityFormatted.length
                        ? currentAvailabilityFormatted
                        : currentAvailability.toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        });

                    amountInput.setCustomValidity(amountLimitMessageTemplate.replace(':available', formatted));
                } else {
                    amountInput.setCustomValidity('');
                }
            };

            var setCurrentAvailability = function (value, formatted) {
                if (!amountInput) {
                    currentAvailability = null;
                    currentAvailabilityFormatted = '—';
                    return;
                }

                var numeric = null;

                if (typeof value === 'number') {
                    numeric = value;
                } else if (typeof value === 'string' && value.length) {
                    var parsed = Number(value);
                    if (Number.isFinite(parsed)) {
                        numeric = parsed;
                    }
                }

                if (Number.isFinite(numeric) && numeric >= 0) {
                    currentAvailability = numeric;
                    currentAvailabilityFormatted = formatted && formatted.length
                        ? formatted
                        : numeric.toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        });
                } else {
                    currentAvailability = null;
                    currentAvailabilityFormatted = '—';
                }

                updateAmountMax();
                enforceAmountLimit();
            };

            var syncAccountInputs = function (value) {
                if (bankInput) {
                    bankInput.value = '';
                }

                if (safeInput) {
                    safeInput.value = '';
                }

                if (!value || typeof value !== 'string') {
                    return;
                }

                var parts = value.split(':');
                if (parts.length !== 2) {
                    return;
                }

                if (parts[0] === 'bank' && bankInput) {
                    bankInput.value = parts[1];
                }

                if (parts[0] === 'safe' && safeInput) {
                    safeInput.value = parts[1];
                }
            };

            var refreshAccountAvailability = function (value) {
                if (!availabilityValue || !availabilitySpinner || !availabilityUrl) {
                    setCurrentAvailability(null);
                    if (availabilityValue) {
                        availabilityValue.textContent = '—';
                    }
                    return;
                }

                availabilityValue.textContent = '—';
                setCurrentAvailability(null);

                if (!value || typeof value !== 'string') {
                    return;
                }

                var parts = value.split(':');
                if (parts.length !== 2) {
                    return;
                }

                var accountType = parts[0];
                var accountId = parts[1];

                if (!accountType || !accountId) {
                    return;
                }

                availabilitySpinner.classList.remove('d-none');

                var params = new URLSearchParams({
                    account_type: accountType,
                    account_id: accountId,
                });

                fetch(availabilityUrl + '?' + params.toString(), {
                    headers: {
                        'Accept': 'application/json',
                    },
                }).then(function (response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }

                    return response.json();
                }).then(function (data) {
                    if (data && data.success) {
                        var availableRaw = (typeof data.available !== 'undefined' && data.available !== null)
                            ? Number(data.available)
                            : 0;

                        var formatted = data.available_formatted && data.available_formatted.length
                            ? data.available_formatted
                            : availableRaw.toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2,
                            });

                        availabilityValue.textContent = formatted;
                        setCurrentAvailability(availableRaw, formatted);
                    } else {
                        availabilityValue.textContent = '—';
                        setCurrentAvailability(null);
                    }
                }).catch(function () {
                    availabilityValue.textContent = '—';
                    setCurrentAvailability(null);
                }).finally(function () {
                    availabilitySpinner.classList.add('d-none');
                });
            };

            if (accountPicker) {
                accountPicker.addEventListener('change', function () {
                    syncAccountInputs(accountPicker.value || '');
                    refreshAccountAvailability(accountPicker.value || '');
                    enforceAmountLimit();
                });
                syncAccountInputs(accountPicker.value || '');
            }

            var applyDataset = function (data) {
                var amount = data.oldAmount && data.oldAmount.length ? data.oldAmount : (data.amountDefault || '');
                amountInput.value = amount;
                setOutstandingLimit(data.outstanding || '');
                setCurrentAvailability(null);

                var paidAt = data.oldPaidAt && data.oldPaidAt.length ? data.oldPaidAt : (data.paidAtDefault || '');
                paidAtInput.value = paidAt;

                var accountValue = '';
                if (data.oldBank && data.oldBank.length) {
                    accountValue = 'bank:' + data.oldBank;
                } else if (data.oldSafe && data.oldSafe.length) {
                    accountValue = 'safe:' + data.oldSafe;
                }

                if (accountPicker) {
                    accountPicker.value = accountValue;
                }
                syncAccountInputs(accountValue);
                if (availabilityValue) {
                    availabilityValue.textContent = '—';
                }
                refreshAccountAvailability(accountValue);

                notesInput.value = data.oldNotes && data.oldNotes.length ? data.oldNotes : '';
                contextInput.value = data.expenseId || '';

                if (expenseTitleTarget) {
                    expenseTitleTarget.textContent = data.expenseTitle || '—';
                }

                if (outstandingTarget) {
                    outstandingTarget.textContent = data.outstandingFormatted || data.outstanding || '—';
                }

                if (form && data.paymentAction) {
                    form.setAttribute('action', data.paymentAction);
                }
            };

            if (amountInput) {
                amountInput.addEventListener('input', enforceAmountLimit);
            }

            var modalInstance = window.bootstrap ? window.bootstrap.Modal.getOrCreateInstance(modalElement) : null;
            var pendingDataset = null;

            modalElement.addEventListener('show.bs.modal', function (event) {
                var dataset = null;

                if (event.relatedTarget && event.relatedTarget.dataset && event.relatedTarget.dataset.expensePaymentTrigger !== undefined) {
                    dataset = cloneDataset(event.relatedTarget.dataset);
                } else if (pendingDataset) {
                    dataset = cloneDataset(pendingDataset);
                    pendingDataset = null;
                }

                if (!dataset) {
                    return;
                }

                applyDataset(dataset);
            });

            var reopenId = modalElement.getAttribute('data-reopen-id');
            if (reopenId) {
                var trigger = document.querySelector('[data-expense-payment-trigger][data-expense-id="' + reopenId + '"]');
                if (trigger) {
                    pendingDataset = cloneDataset(trigger.dataset);
                    if (modalInstance) {
                        modalInstance.show();
                    }
                }
            }
        });
    </script>
@endpush
