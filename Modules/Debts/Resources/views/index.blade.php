@extends('layouts.master')

@php
    use Illuminate\Support\Arr;
    use Illuminate\Support\Str;

    $filterTypes = Arr::only(__('debts::messages.types'), ['other']);
@endphp

@section('title', __('debts::messages.page_title'))

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ __('debts::messages.page_title') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ __('debts::messages.page_title') }}</li>
        </ol>
    </nav>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-2 p-2">
        @can('debts.create')
            <x-button.action href="{{ route('debts.create') }}" variant="success">
                <i class="bi bi-plus-lg me-1"></i>{{ __('debts::messages.buttons.create') }}
            </x-button.action>
        @endcan
    </div>
</div>

<div class="card-body">
    <div class="row g-3" dir="rtl">
        <div class="col-12 col-md-4">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon"><i class="bi bi-pie-chart fs-4 text-primary"></i></div>
                    <div class="flex-grow-1">
                        <div class="subnote">{{ __('debts::messages.totals.principal') }}</div>
                        <div class="kpi-value fw-bold">{{ number_format($totals['principal'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon"><i class="bi bi-cash-coin fs-4 text-success"></i></div>
                    <div class="flex-grow-1">
                        <div class="subnote">{{ __('debts::messages.totals.paid') }}</div>
                        <div class="kpi-value fw-bold">{{ number_format($totals['paid'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon"><i class="bi bi-wallet2 fs-4 text-warning"></i></div>
                    <div class="flex-grow-1">
                        <div class="subnote">{{ __('debts::messages.totals.outstanding') }}</div>
                        <div class="kpi-value fw-bold">{{ number_format($totals['outstanding'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span class="fw-semibold">{{ __('debts::messages.filters.title') }}</span>
        <span class="small text-muted">{{ __('debts::messages.filters.results', ['count' => $debts->total()]) }}</span>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('debts.index') }}" id="filtersForm" class="row gy-3 gx-3 align-items-end">
            <div class="col-12 col-md-4 col-xl-3">
                <label class="form-label small text-muted" for="filterParty">{{ __('debts::messages.filters.party_type') }}</label>
                <select id="filterParty" name="party_type" class="form-select form-select-sm">
                    <option value="">{{ __('debts::messages.filters.all') }}</option>
                    @foreach($filterTypes as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['party_type'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-xl-3">
                <label class="form-label small text-muted" for="filterStatus">{{ __('debts::messages.filters.status') }}</label>
                <select id="filterStatus" name="status" class="form-select form-select-sm">
                    <option value="">{{ __('debts::messages.filters.all') }}</option>
                    @foreach(__('debts::messages.statuses') as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-xl-3">
                <label class="form-label small text-muted" for="filterSearch">{{ __('debts::messages.filters.search') }}</label>
                <input id="filterSearch" type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control form-control-sm" placeholder="{{ __('debts::messages.filters.search_placeholder') }}">
            </div>
            <div class="col-12 col-xl-3">
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <x-button.action type="submit" variant="primary" size="sm" class="w-100">
                        <i class="bi bi-funnel"></i>
                        <span class="ms-1">{{ __('debts::messages.buttons.filter') }}</span>
                    </x-button.action>
                    <x-button.action href="{{ route('debts.index') }}" variant="secondary" :outline="true" size="sm" class="w-100">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span class="ms-1">{{ __('debts::messages.buttons.reset') }}</span>
                    </x-button.action>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <x-table head-class="table-light" class="text-center">
            <x-slot name="head">
                <tr>
                    <th style="width:60px">#</th>
                    <th>{{ __('debts::messages.table.name') }}</th>
                    <th>{{ __('debts::messages.table.type') }}</th>
                    <th>{{ __('debts::messages.table.account') }}</th>
                    <th>{{ __('debts::messages.table.principal') }}</th>
                    <th>{{ __('debts::messages.table.paid') }}</th>
                    <th>{{ __('debts::messages.table.outstanding') }}</th>
                    <th>{{ __('debts::messages.table.issued_at') }}</th>
                    <th>{{ __('debts::messages.table.due_at') }}</th>
                    <th>{{ __('debts::messages.table.status') }}</th>
                    <th>{{ __('debts::messages.table.actions') }}</th>
                </tr>
            </x-slot>

            @php
                $oldContextDebtId = (string) old('context_debt_id');
                $todayDate = now()->format('Y-m-d');
                $banksCollection = $banks->values();
                $safesCollection = $safes->values();
            @endphp

            @forelse($debts as $debt)
                    @php
                        $rowNumber = $loop->iteration + ($debts->currentPage() - 1) * $debts->perPage();
                        $paymentsCollapseId = 'debt-payments-'.$debt->id;
                        $outstanding = max($debt->outstanding_amount, 0);
                        $accountName = optional($debt->bankAccount)->name ?? optional($debt->safe)->name ?? '—';
                        $accountLabel = $debt->bankAccount
                            ? __('debts::messages.fields.bank_account')
                            : ($debt->safe ? __('debts::messages.fields.safe') : null);
                        $paymentAction = route('debts.payments.store', $debt);
                        $queryString = request()->getQueryString();
                        if ($queryString) {
                            $paymentAction .= '?'.$queryString;
                        }
                        $isCurrentDebt = $oldContextDebtId === (string) $debt->id;
                        $debtName = $debt->counterparty_name ?? ($debt->customer->name ?? $debt->investor->name ?? '-');
                        $defaultAmount = number_format($outstanding, 2, '.', '');
                        $amountValue = $isCurrentDebt ? old('amount', $defaultAmount) : $defaultAmount;
                        $defaultPaidAt = $todayDate;
                        $paidAtValue = $isCurrentDebt ? old('paid_at', $defaultPaidAt) : $defaultPaidAt;
                        $oldBank = $isCurrentDebt ? old('bank_account_id') : null;
                        $oldSafe = $isCurrentDebt ? old('safe_id') : null;
                        $oldNotes = $isCurrentDebt ? old('notes') : '';
                        $collapseShowClass = $isCurrentDebt ? 'show' : '';
                    @endphp

                    <tr>
                        <td class="text-muted">{{ $rowNumber }}</td>
                        <td class="text-start">
                            <div class="fw-semibold">{{ $debtName }}</div>
                            @if($debt->notes)
                                <div class="small text-muted" title="{{ $debt->notes }}">{{ Str::limit($debt->notes, 80) }}</div>
                            @endif
                        </td>
                        <td class="text-start">{{ __('debts::messages.types.'.$debt->party_type) }}</td>
                        <td class="text-start">
                            <div>{{ $accountName }}</div>
                            @if($accountLabel)
                                <div class="text-muted small">{{ $accountLabel }}</div>
                            @endif
                        </td>
                        <td>{{ number_format($debt->principal_amount, 2) }}</td>
                        <td>{{ number_format($debt->paid_amount, 2) }}</td>
                        <td>{{ number_format($outstanding, 2) }}</td>
                        <td>{{ optional($debt->issued_at)->format('Y-m-d') }}</td>
                        <td>{{ optional($debt->due_at)->format('Y-m-d') ?? '-' }}</td>
                        <td>
                            @if($debt->status === 'settled')
                                <span class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2">{{ __('debts::messages.statuses.settled') }}</span>
                            @else
                                <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis px-3 py-2">{{ __('debts::messages.statuses.open') }}</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            <div class="d-flex flex-wrap justify-content-center gap-2">
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
                                    {{ __('debts::messages.payments.actions.view') }}
                                </x-button.action>

                                @can('debts.edit')
                                    @if($outstanding > 0)
                                        <x-button.action
                                            type="button"
                                            variant="dark"
                                            size="sm"
                                            :outline="true"
                                            data-bs-toggle="modal"
                                            data-bs-target="#debtPaymentModal"
                                            data-debt-payment-trigger="1"
                                            data-payment-action="{{ $paymentAction }}"
                                            data-debt-id="{{ $debt->id }}"
                                            data-debt-name="{{ e($debtName) }}"
                                            data-outstanding="{{ $defaultAmount }}"
                                            data-outstanding-formatted="{{ number_format($outstanding, 2) }}"
                                            data-amount-default="{{ $defaultAmount }}"
                                            data-old-amount="{{ $isCurrentDebt ? $amountValue : '' }}"
                                            data-paid-at-default="{{ $defaultPaidAt }}"
                                            data-old-paid-at="{{ $isCurrentDebt ? $paidAtValue : '' }}"
                                            data-old-bank="{{ $oldBank ?? '' }}"
                                            data-old-safe="{{ $oldSafe ?? '' }}"
                                            data-old-notes="{{ e($oldNotes) }}"
                                            data-account-default="{{ $debt->bankAccount ? 'bank:'.$debt->bankAccount->id : ($debt->safe ? 'safe:'.$debt->safe->id : '') }}"
                                        >
                                            {{ __('debts::messages.payments.actions.pay') }}
                                        </x-button.action>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    <tr class="table-light">
                        <td colspan="11" class="text-start">
                            <div class="collapse {{ $collapseShowClass }}" id="{{ $paymentsCollapseId }}">
                                <div class="px-3 py-3">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                                        <div class="fw-semibold text-muted">{{ __('debts::messages.payments.title') }}</div>
                                        <div class="d-flex flex-wrap gap-2 small">
                                            <span class="badge bg-light text-dark border">{{ __('debts::messages.totals.principal') }}: {{ number_format($debt->principal_amount, 2) }}</span>
                                            <span class="badge bg-light text-dark border">{{ __('debts::messages.totals.paid') }}: {{ number_format($debt->paid_amount, 2) }}</span>
                                            <span class="badge {{ $outstanding > 0 ? 'bg-warning text-dark' : 'bg-success' }}">{{ __('debts::messages.totals.outstanding') }}: {{ number_format($outstanding, 2) }}</span>
                                        </div>
                                    </div>

                                    @if($debt->payments->isNotEmpty())
                                        <x-table small bordered head-class="table-secondary">
                                            <x-slot name="head">
                                                <tr>
                                                    <th class="text-center" style="width: 60px;">#</th>
                                                    <th class="text-end" style="width: 140px;">{{ __('debts::messages.payments.fields.amount') }}</th>
                                                    <th style="width: 160px;">{{ __('debts::messages.payments.fields.paid_at') }}</th>
                                                    <th style="width: 220px;" class="text-start">{{ __('debts::messages.payments.fields.account') }}</th>
                                                    <th class="text-start">{{ __('debts::messages.payments.fields.notes') }}</th>
                                                </tr>
                                            </x-slot>

                                            @foreach($debt->payments as $index => $payment)
                                                <tr>
                                                    <td class="text-center">{{ $index + 1 }}</td>
                                                    <td class="text-end">{{ number_format($payment->amount, 2) }}</td>
                                                    <td>{{ optional($payment->paid_at)->format('Y-m-d') }}</td>
                                                    <td class="text-start">{{ $payment->bankAccount->name ?? $payment->safe->name ?? '-' }}</td>
                                                    <td class="text-start">{{ $payment->notes ?: '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </x-table>
                                    @else
                                        <div class="text-muted small">{{ __('debts::messages.payments.empty') }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="py-5 text-center text-muted">{{ __('debts::messages.table.empty') }}</td>
                    </tr>
            @endforelse
        </x-table>
    </div>

    @if($debts->hasPages())
        <div class="card-footer bg-white">
            {{ $debts->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

<div class="modal fade" id="debtPaymentModal" tabindex="-1" aria-labelledby="debtPaymentModalLabel" aria-hidden="true" data-reopen-id="{{ $oldContextDebtId }}" >
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="text-start">
                @csrf
                <input type="hidden" name="context_debt_id" value="{{ $oldContextDebtId }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="debtPaymentModalLabel">{{ __('debts::messages.payments.actions.pay') }}</h5>
                    <x-button.action type="button" :unstyled="true" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></x-button.action>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="modal-debt-amount">{{ __('debts::messages.payments.fields.amount') }}</label>
                        <input
                            id="modal-debt-amount"
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
                            {{ __('debts::messages.totals.outstanding') }}:
                            <span data-role="outstanding-amount">—</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="modal-debt-paid-at">{{ __('debts::messages.payments.fields.paid_at') }}</label>
                        <input id="modal-debt-paid-at" type="text" name="paid_at" class="form-control js-date" value="{{ old('paid_at') }}" required >
                        @error('paid_at')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="modal-debt-account">{{ __('debts::messages.table.account') }}</label>
                        @php($selectedAccount = old('bank_account_id') ? 'bank:' . old('bank_account_id') : (old('safe_id') ? 'safe:' . old('safe_id') : ''))
                        <select
                            id="modal-debt-account"
                            class="form-select"
                            data-debt-account-picker="1"
                            data-bank-input="modal-debt-bank"
                            data-safe-input="modal-debt-safe"
                            @if ($banksCollection->isEmpty() && $safesCollection->isEmpty()) disabled @endif
                        >
                            <option value="" @selected($selectedAccount === '')>{{ __('debts::messages.placeholders.select_bank') }} / {{ __('debts::messages.placeholders.select_safe') }}</option>
                            @if ($banksCollection->isNotEmpty())
                                <optgroup label="{{ __('debts::messages.fields.bank_account') }}">
                                    @foreach($banksCollection as $bank)
                                        <option value="bank:{{ $bank->id }}" @selected($selectedAccount === 'bank:' . $bank->id)>{{ $bank->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if ($safesCollection->isNotEmpty())
                                <optgroup label="{{ __('debts::messages.fields.safe') }}">
                                    @foreach($safesCollection as $safe)
                                        <option value="safe:{{ $safe->id }}" @selected($selectedAccount === 'safe:' . $safe->id)>{{ $safe->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        <input type="hidden" name="bank_account_id" id="modal-debt-bank" value="{{ old('bank_account_id') }}">
                        <input type="hidden" name="safe_id" id="modal-debt-safe" value="{{ old('safe_id') }}">
                        <div class="form-text">{{ __('debts::messages.hints.account_choice') }}</div>
                        @if ($banksCollection->isEmpty() && $safesCollection->isEmpty())
                            <div class="text-danger small mt-1">{{ __('debts::messages.payments.empty') }}</div>
                        @endif
                        @error('bank_account_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        @error('safe_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <label class="form-label" for="modal-debt-notes">{{ __('debts::messages.payments.fields.notes') }}</label>
                        <textarea
                            id="modal-debt-notes"
                            name="notes"
                            class="form-control"
                            rows="2"
                            placeholder="{{ __('debts::messages.payments.placeholders.notes') }}"
                        >{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <x-button.action type="button" variant="light" data-bs-dismiss="modal">{{ __('debts::messages.buttons.cancel') }}</x-button.action>
                    <x-button.action type="submit" variant="dark">
                        {{ __('debts::messages.payments.actions.pay') }}
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
            var modalElement = document.getElementById('debtPaymentModal');
            if (!modalElement) {
                return;
            }

            var form = modalElement.querySelector('form');
            var amountInput = modalElement.querySelector('input[name="amount"]');
            var paidAtInput = modalElement.querySelector('input[name="paid_at"]');
            var bankInput = modalElement.querySelector('input[name="bank_account_id"]');
            var safeInput = modalElement.querySelector('input[name="safe_id"]');
            var accountPicker = modalElement.querySelector('[data-debt-account-picker]');
            var notesInput = modalElement.querySelector('textarea[name="notes"]');
            var contextInput = modalElement.querySelector('input[name="context_debt_id"]');
            var debtNameTarget = modalElement.querySelector('[data-role="debt-name"]');
            var outstandingTarget = modalElement.querySelector('[data-role="outstanding-amount"]');

            if (!form || !amountInput || !paidAtInput || !notesInput || !contextInput) {
                return;
            }

            var cloneDataset = function (dataset) {
                return dataset ? Object.assign({}, dataset) : {};
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

            if (accountPicker) {
                accountPicker.addEventListener('change', function () {
                    syncAccountInputs(accountPicker.value || '');
                });
                syncAccountInputs(accountPicker.value || '');
            }

            var applyDataset = function (data) {
                var amount = data.oldAmount && data.oldAmount.length ? data.oldAmount : (data.amountDefault || '');
                amountInput.value = amount;

                if (data.outstanding && data.outstanding.length) {
                    amountInput.setAttribute('max', data.outstanding);
                } else {
                    amountInput.removeAttribute('max');
                }

                var paidAt = data.oldPaidAt && data.oldPaidAt.length ? data.oldPaidAt : (data.paidAtDefault || '');
                paidAtInput.value = paidAt;

                var accountValue = '';
                if (data.oldBank && data.oldBank.length) {
                    accountValue = 'bank:' + data.oldBank;
                } else if (data.oldSafe && data.oldSafe.length) {
                    accountValue = 'safe:' + data.oldSafe;
                } else if (data.accountDefault && data.accountDefault.length) {
                    accountValue = data.accountDefault;
                }

                if (accountPicker) {
                    accountPicker.value = accountValue;
                }
                syncAccountInputs(accountValue);

                notesInput.value = data.oldNotes && data.oldNotes.length ? data.oldNotes : '';
                contextInput.value = data.debtId || '';

                if (debtNameTarget) {
                    debtNameTarget.textContent = data.debtName || '—';
                }

                if (outstandingTarget) {
                    outstandingTarget.textContent = data.outstandingFormatted || data.outstanding || '—';
                }

                if (form && data.paymentAction) {
                    form.setAttribute('action', data.paymentAction);
                }
            };

            var modalInstance = window.bootstrap ? window.bootstrap.Modal.getOrCreateInstance(modalElement) : null;
            var pendingDataset = null;

            modalElement.addEventListener('show.bs.modal', function (event) {
                var dataset = null;

                if (event.relatedTarget && event.relatedTarget.dataset && event.relatedTarget.dataset.debtPaymentTrigger !== undefined) {
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
                var trigger = document.querySelector('[data-debt-payment-trigger][data-debt-id="' + reopenId + '"]');
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
