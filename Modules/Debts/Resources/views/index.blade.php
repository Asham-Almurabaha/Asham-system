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
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
            <li class="breadcrumb-item active">{{ __('debts::messages.page_title') }}</li>
        </ol>
    </nav>
</div>

<div class="row g-3 mb-3" dir="rtl">
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

<div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">
        @can('debts.create')
            <x-button.action href="{{ route('debts.create') }}" variant="success">
                <i class="bi bi-plus-circle"></i>
                <span class="ms-1">{{ __('debts::messages.buttons.create') }}</span>
            </x-button.action>
        @endcan
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-light border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-semibold">{{ __('debts::messages.filters.title') }}</span>
        <span class="small text-muted">{{ __('debts::messages.filters.results', ['count' => $debts->total()]) }}</span>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('debts.index') }}" id="filtersForm" class="row gy-3 gx-3 align-items-end">
            <div class="col-12 col-md-4 col-xl-3">
                <label class="form-label small text-muted" for="filterParty">{{ __('debts::messages.filters.party_type') }}</label>
                <select id="filterParty" name="party_type" class="form-select form-select-sm">
                    <option value="">{{ __('debts::messages.filters.all') }}</option>
                    @foreach(__('debts::messages.types') as $key => $label)
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

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <x-table head-class="table-light align-middle" class="table table-hover align-middle mb-0">
                <x-slot name="head">
                    <tr>
                        <th style="width: 60px;" class="text-center">#</th>
                        <th class="text-start">{{ __('debts::messages.table.name') }}</th>
                        <th class="text-start">{{ __('debts::messages.table.type') }}</th>
                        <th class="text-start">{{ __('debts::messages.table.account') }}</th>
                        <th class="text-end">{{ __('debts::messages.table.principal') }}</th>
                        <th class="text-end">{{ __('debts::messages.table.paid') }}</th>
                        <th class="text-end">{{ __('debts::messages.table.outstanding') }}</th>
                        <th>{{ __('debts::messages.table.issued_at') }}</th>
                        <th>{{ __('debts::messages.table.due_at') }}</th>
                        <th>{{ __('debts::messages.table.status') }}</th>
                        <th class="text-center">{{ __('debts::messages.table.actions') }}</th>
                    </tr>
                </x-slot>

                @php
                    $oldContextDebtId = (string) old('context_debt_id');
                @endphp

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
                        $collapseShowClass = $isCurrentDebt ? 'show' : '';
                    @endphp

                    <tr>
                        <td class="text-muted text-center">{{ $rowNumber }}</td>
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
                        <td class="text-end">{{ number_format($outstanding, 2) }}</td>
                        <td>{{ optional($debt->issued_at)->format('Y-m-d') }}</td>
                        <td>{{ optional($debt->due_at)->format('Y-m-d') ?? '-' }}</td>
                        <td>
                            @if($debt->status === 'settled')
                                <span class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2">{{ __('debts::messages.statuses.settled') }}</span>
                            @else
                                <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis px-3 py-2">{{ __('debts::messages.statuses.open') }}</span>
                            @endif
                        </td>
                        <td class="text-center">
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
                                    <i class="bi bi-list"></i>
                                    <span class="ms-1">{{ __('debts::messages.payments.actions.view') }}</span>
                                </x-button.action>

                                @can('debts.edit')
                                    @if($outstanding > 0)
                                        <x-button.action
                                            type="button"
                                            variant="success"
                                            size="sm"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#{{ $paymentsCollapseId }}"
                                            aria-expanded="{{ $collapseShowClass ? 'true' : 'false' }}"
                                            aria-controls="{{ $paymentsCollapseId }}"
                                        >
                                            <i class="bi bi-cash-coin"></i>
                                            <span class="ms-1">{{ __('debts::messages.payments.actions.pay') }}</span>
                                        </x-button.action>
                                    @endif
                                @endcan

                                @can('debts.destroy')
                                    <form action="{{ route('debts.destroy', $debt) }}" method="POST" onsubmit="return confirm('{{ __('debts::messages.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <x-button.action type="submit" size="sm" variant="danger" :outline="true">
                                            <i class="bi bi-trash"></i>
                                        </x-button.action>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    <tr class="bg-body-tertiary">
                        <td colspan="11" class="p-0">
                            <div class="collapse {{ $collapseShowClass }}" id="{{ $paymentsCollapseId }}">
                                <div class="px-4 py-4">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                                        <h6 class="fw-semibold mb-0">{{ __('debts::messages.payments.title') }}</h6>
                                        <div class="small text-muted">{{ __('debts::messages.table.outstanding') }}:
                                            <strong>{{ number_format($outstanding, 2) }}</strong>
                                        </div>
                                    </div>

                                    <div class="table-responsive mb-4">
                                        <table class="table table-sm table-striped align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-center" style="width: 60px;">#</th>
                                                    <th class="text-end" style="width: 140px;">{{ __('debts::messages.payments.fields.amount') }}</th>
                                                    <th style="width: 160px;">{{ __('debts::messages.payments.fields.paid_at') }}</th>
                                                    <th style="width: 220px;">{{ __('debts::messages.payments.fields.account') }}</th>
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

                                    @can('debts.edit')
                                        @if($outstanding > 0)
                                            <form action="{{ $paymentAction }}" method="POST" class="row g-3 align-items-end" id="{{ $paymentFormId }}">
                                                @csrf
                                                <input type="hidden" name="context_debt_id" value="{{ $debt->id }}">
                                                <div class="col-12 col-md-3">
                                                    <label class="form-label small text-muted" for="amount-{{ $debt->id }}">{{ __('debts::messages.payments.fields.amount') }}</label>
                                                    <input id="amount-{{ $debt->id }}" type="number" name="amount" class="form-control form-control-sm" min="0.01" step="0.01" max="{{ $outstanding }}" value="{{ $amountValue }}" required>
                                                    @if($isCurrentDebt)
                                                        @error('amount')
                                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                                        @enderror
                                                    @endif
                                                </div>
                                                <div class="col-12 col-md-3">
                                                    <label class="form-label small text-muted" for="paid-at-{{ $debt->id }}">{{ __('debts::messages.payments.fields.paid_at') }}</label>
                                                    <input id="paid-at-{{ $debt->id }}" type="date" name="paid_at" class="form-control form-control-sm" value="{{ $paidAtValue }}" required>
                                                    @if($isCurrentDebt)
                                                        @error('paid_at')
                                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                                        @enderror
                                                    @endif
                                                </div>
                                                <div class="col-12 col-md-3">
                                                    <label class="form-label small text-muted" for="bank-{{ $debt->id }}">{{ __('debts::messages.payments.fields.bank_account') }}</label>
                                                    <select id="bank-{{ $debt->id }}" name="bank_account_id" class="form-select form-select-sm" {{ $banks->count() ? '' : 'disabled' }}>
                                                        <option value="">{{ __('debts::messages.placeholders.select_bank') }}</option>
                                                        @foreach($banks as $bank)
                                                            <option value="{{ $bank->id }}" @selected($oldBank == $bank->id)>{{ $bank->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @if($isCurrentDebt)
                                                        @error('bank_account_id')
                                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                                        @enderror
                                                    @endif
                                                </div>
                                                <div class="col-12 col-md-3">
                                                    <label class="form-label small text-muted" for="safe-{{ $debt->id }}">{{ __('debts::messages.payments.fields.safe') }}</label>
                                                    <select id="safe-{{ $debt->id }}" name="safe_id" class="form-select form-select-sm" {{ $safes->count() ? '' : 'disabled' }}>
                                                        <option value="">{{ __('debts::messages.placeholders.select_safe') }}</option>
                                                        @foreach($safes as $safe)
                                                            <option value="{{ $safe->id }}" @selected($oldSafe == $safe->id)>{{ $safe->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <div class="form-text">{{ __('debts::messages.hints.account_choice') }}</div>
                                                    @if($isCurrentDebt)
                                                        @error('safe_id')
                                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                                        @enderror
                                                    @endif
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label small text-muted" for="notes-{{ $debt->id }}">{{ __('debts::messages.payments.fields.notes') }}</label>
                                                    <input id="notes-{{ $debt->id }}" type="text" name="notes" class="form-control form-control-sm" value="{{ $oldNotes }}" placeholder="{{ __('debts::messages.payments.placeholders.notes') }}">
                                                    @if($isCurrentDebt)
                                                        @error('notes')
                                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                                        @enderror
                                                    @endif
                                                </div>
                                                <div class="col-12 col-md-2 ms-auto d-grid">
                                                    <x-button.action type="submit" variant="success" size="sm">
                                                        <i class="bi bi-cash-stack"></i>
                                                        <span class="ms-1">{{ __('debts::messages.payments.actions.pay') }}</span>
                                                    </x-button.action>
                                                </div>
                                            </form>
                                        @endif
                                    @endcan

                                    @if($outstanding <= 0)
                                        <div class="alert alert-success mt-4 mb-0" role="alert">
                                            {{ __('debts::messages.payments.settled') }}
                                        </div>
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
            <div class="p-3">
                {{ $debts->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
@endpush
