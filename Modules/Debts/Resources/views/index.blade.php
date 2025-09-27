@extends('layouts.master')

@php
    use Illuminate\Support\Str;
@endphp

@section('title', __('debts::messages.page_title'))

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ __('debts::messages.page_heading') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">@lang('sidebar.Accounts')</li>
            <li class="breadcrumb-item active">{{ __('debts::messages.page_title') }}</li>
        </ol>
    </nav>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">
        @routecan('debts.create')
        <x-button.action href="{{ route('debts.create') }}" variant="success">
            <i class="bi bi-plus-circle"></i> {{ __('debts::messages.buttons.create') }}
        </x-button.action>
        @endroutecan

        <div class="ms-auto d-flex flex-wrap gap-3 align-items-center small">
            <span>{{ __('debts::messages.totals.principal') }}: <strong>{{ number_format($totals['principal'], 2) }}</strong></span>
            <span>{{ __('debts::messages.totals.paid') }}: <strong>{{ number_format($totals['paid'], 2) }}</strong></span>
            <span>{{ __('debts::messages.totals.outstanding') }}: <strong>{{ number_format($totals['outstanding'], 2) }}</strong></span>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="fw-semibold">{{ __('debts::messages.filters.title') }}</div>
        <div class="small text-muted">{{ __('debts::messages.filters.results', ['count' => $debts->total()]) }}</div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('debts.index') }}" class="row gy-2 gx-2 align-items-end" id="filtersForm">
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label mb-1">{{ __('debts::messages.filters.party_type') }}</label>
                <select name="party_type" class="form-select form-select-sm">
                    <option value="">{{ __('debts::messages.filters.all') }}</option>
                    @foreach(__('debts::messages.types') as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['party_type'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label mb-1">{{ __('debts::messages.filters.status') }}</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">{{ __('debts::messages.filters.all') }}</option>
                    @foreach(__('debts::messages.statuses') as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label mb-1" for="debts-filter-search">{{ __('debts::messages.filters.search') }}</label>
                <input id="debts-filter-search" type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control form-control-sm" placeholder="{{ __('debts::messages.filters.search_placeholder') }}">
            </div>
            <div class="col-12 col-lg-3 d-flex flex-column flex-sm-row gap-2">
                <x-button.action type="submit" variant="primary" size="sm" class="w-100">{{ __('debts::messages.buttons.filter') }}</x-button.action>
                <x-button.action href="{{ route('debts.index') }}" variant="secondary" :outline="true" size="sm" class="w-100">{{ __('debts::messages.buttons.reset') }}</x-button.action>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <x-table head-class="table-light align-middle" class="text-center">
            <x-slot name="head">
                <tr>
                    <th style="width: 60px;">#</th>
                    <th class="text-start">{{ __('debts::messages.table.name') }}</th>
                    <th class="text-start">{{ __('debts::messages.table.type') }}</th>
                    <th class="text-start">{{ __('debts::messages.table.account') }}</th>
                    <th class="text-end">{{ __('debts::messages.table.principal') }}</th>
                    <th class="text-end">{{ __('debts::messages.table.paid') }}</th>
                    <th class="text-end">{{ __('debts::messages.table.outstanding') }}</th>
                    <th>{{ __('debts::messages.table.issued_at') }}</th>
                    <th>{{ __('debts::messages.table.due_at') }}</th>
                    <th>{{ __('debts::messages.table.status') }}</th>
                    <th>{{ __('debts::messages.table.actions') }}</th>
                </tr>
            </x-slot>

        @php($oldContextDebtId = (string) old('context_debt_id'))

        @forelse($debts as $debt)
            @php
                $rowNumber = $loop->iteration + ($debts->currentPage() - 1) * $debts->perPage();
                $paymentsCollapseId = 'debt-payments-'.$debt->id;
                $paymentFormId = $paymentsCollapseId.'-form';
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
                $defaultAmount = number_format($outstanding, 2, '.', '');
                $amountValue = $isCurrentDebt ? old('amount', $defaultAmount) : $defaultAmount;
                $paidAtValue = $isCurrentDebt ? old('paid_at', now()->format('Y-m-d')) : now()->format('Y-m-d');
                $oldBank = $isCurrentDebt ? old('bank_account_id') : null;
                $oldSafe = $isCurrentDebt ? old('safe_id') : null;
                $oldNotes = $isCurrentDebt ? old('notes') : '';
            @endphp
            <tr>
                <td class="text-muted">{{ $rowNumber }}</td>
                <td class="text-start">
                    <div class="fw-semibold">{{ $debt->counterparty_name ?? ($debt->customer->name ?? $debt->investor->name ?? '-') }}</div>
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
                <td class="text-end">{{ number_format($debt->principal_amount, 2) }}</td>
                <td class="text-end">{{ number_format($debt->paid_amount, 2) }}</td>
                <td class="text-end">{{ number_format($debt->outstanding_amount, 2) }}</td>
                <td>{{ $debt->issued_at?->format('Y-m-d') }}</td>
                <td>{{ $debt->due_at?->format('Y-m-d') ?? '-' }}</td>
                <td>
                    @if($debt->status === 'settled')
                        <span class="badge bg-success">{{ __('debts::messages.statuses.settled') }}</span>
                    @else
                        <span class="badge bg-warning text-dark">{{ __('debts::messages.statuses.open') }}</span>
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
                                aria-expanded="false"
                                aria-controls="{{ $paymentsCollapseId }}"
                            >
                                {{ __('debts::messages.payments.actions.view') }}
                            </x-button.action>

                            @routecan('debts.edit')
                                @if($outstanding > 0)
                                    <x-button.action
                                        type="button"
                                        variant="success"
                                        :outline="true"
                                        size="sm"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#{{ $paymentsCollapseId }}"
                                        aria-expanded="false"
                                        aria-controls="{{ $paymentsCollapseId }}"
                                    >
                                        {{ __('debts::messages.payments.actions.pay') }}
                                    </x-button.action>
                                @endif
                            @endroutecan

                            @routecan('debts.destroy')
                                <form action="{{ route('debts.destroy', $debt) }}" method="POST" onsubmit="return confirm('{{ __('debts::messages.confirm_delete') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <x-button.action type="submit" size="sm" variant="danger" :outline="true">
                                        <i class="bi bi-trash"></i>
                                    </x-button.action>
                                </form>
                            @endroutecan
                        </div>
                    </td>
                </tr>
                <tr class="table-light">
                    <td colspan="11" class="text-start">
                        <div class="collapse" id="{{ $paymentsCollapseId }}">
                            <div class="px-3 py-3">
                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                                    <h6 class="fw-semibold mb-0">{{ __('debts::messages.payments.title') }}</h6>
                                    <div class="small text-muted">{{ __('debts::messages.table.outstanding') }}: <strong>{{ number_format($outstanding, 2) }}</strong></div>
                                </div>

                                <div class="table-responsive mb-3">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center" style="width: 60px;">#</th>
                                                <th class="text-end" style="width: 160px;">{{ __('debts::messages.payments.fields.amount') }}</th>
                                                <th style="width: 160px;">{{ __('debts::messages.payments.fields.paid_at') }}</th>
                                                <th style="width: 200px;">{{ __('debts::messages.payments.fields.account') }}</th>
                                                <th class="text-start">{{ __('debts::messages.payments.fields.notes') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($debt->payments as $index => $payment)
                                                <tr>
                                                    <td class="text-center">{{ $index + 1 }}</td>
                                                    <td class="text-end">{{ number_format($payment->amount, 2) }}</td>
                                                    <td>{{ optional($payment->paid_at)->format('Y-m-d') }}</td>
                                                    <td class="text-start">{{ $payment->bankAccount->name ?? $payment->safe->name ?? '-' }}</td>
                                                    <td class="text-start">{{ $payment->notes ?: '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-3">{{ __('debts::messages.payments.empty') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                @routecan('debts.edit')
                                    @if($outstanding > 0)
                                        <form action="{{ $paymentAction }}" method="POST" class="row g-2 align-items-end" id="{{ $paymentFormId }}">
                                            @csrf
                                            <input type="hidden" name="context_debt_id" value="{{ $debt->id }}">
                                            <div class="col-12 col-md-3">
                                                <label class="form-label small text-muted">{{ __('debts::messages.payments.fields.amount') }}</label>
                                                <input type="number" name="amount" class="form-control form-control-sm" min="0.01" step="0.01" max="{{ $outstanding }}" value="{{ $amountValue }}" required>
                                                @if($isCurrentDebt)
                                                    @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                                @endif
                                            </div>
                                            <div class="col-12 col-md-3">
                                                <label class="form-label small text-muted">{{ __('debts::messages.payments.fields.paid_at') }}</label>
                                                <input type="date" name="paid_at" class="form-control form-control-sm" value="{{ $paidAtValue }}" required>
                                                @if($isCurrentDebt)
                                                    @error('paid_at') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                                @endif
                                            </div>
                                            <div class="col-12 col-md-3">
                                                <label class="form-label small text-muted">{{ __('debts::messages.payments.fields.bank_account') }}</label>
                                                <select name="bank_account_id" class="form-select form-select-sm" {{ $banks->count() ? '' : 'disabled' }}>
                                                    <option value="">{{ __('debts::messages.placeholders.select_bank') }}</option>
                                                    @foreach($banks as $bank)
                                                        <option value="{{ $bank->id }}" @selected($oldBank == $bank->id)>{{ $bank->name }}</option>
                                                    @endforeach
                                                </select>
                                                @if($isCurrentDebt)
                                                    @error('bank_account_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                                @endif
                                            </div>
                                            <div class="col-12 col-md-3">
                                                <label class="form-label small text-muted">{{ __('debts::messages.payments.fields.safe') }}</label>
                                                <select name="safe_id" class="form-select form-select-sm" {{ $safes->count() ? '' : 'disabled' }}>
                                                    <option value="">{{ __('debts::messages.placeholders.select_safe') }}</option>
                                                    @foreach($safes as $safe)
                                                        <option value="{{ $safe->id }}" @selected($oldSafe == $safe->id)>{{ $safe->name }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="form-text">{{ __('debts::messages.hints.account_choice') }}</div>
                                                @if($isCurrentDebt)
                                                    @error('safe_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                                @endif
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="form-label small text-muted">{{ __('debts::messages.payments.fields.notes') }}</label>
                                                <input type="text" name="notes" class="form-control form-control-sm" value="{{ $oldNotes }}" placeholder="{{ __('debts::messages.payments.placeholders.notes') }}">
                                                @if($isCurrentDebt)
                                                    @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                                @endif
                                            </div>
                                            <div class="col-12 col-md-2 d-grid">
                                                <x-button.action type="submit" variant="success" size="sm">
                                                    <i class="bi bi-cash-stack"></i> {{ __('debts::messages.payments.actions.pay') }}
                                                </x-button.action>
                                            </div>
                                        </form>
                                    @else
                                        <div class="alert alert-success mb-0" role="alert">
                                            {{ __('debts::messages.payments.settled') }}
                                        </div>
                                    @endif
                                @endroutecan
                            </div>
                        </div>
                    </td>
            </tr>
        @empty
            <tr>
                <td colspan="11" class="py-5 text-muted">{{ __('debts::messages.table.empty') }}</td>
            </tr>
        @endforelse
        </x-table>

        @if($debts->hasPages())
            <div class="mt-3 p-3">
                {{ $debts->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
@endpush
