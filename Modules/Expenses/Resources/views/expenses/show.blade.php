@extends('layouts.master')

@section('title', __('expenses::expenses.show_title'))

@section('content')
    <div class="container py-3">
        <div class="pagetitle mb-3">
            <h1 class="h3 mb-1">@lang('expenses::expenses.show_title')</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('expenses.expenses.index') }}">@lang('expenses::expenses.index_title')</a></li>
                    <li class="breadcrumb-item active">{{ $expense->title }}</li>
                </ol>
            </nav>
        </div>

        @php
            $today = \Illuminate\Support\Carbon::today();
            $outstanding = max($expense->outstanding_amount, 0);
            $isManuallySettled = $expense->manual_outstanding_amount !== null && (float) $expense->manual_outstanding_amount <= 0;
            $dueDateValue = $expense->due_date;
            $dueDateLabel = $isManuallySettled ? '—' : optional($dueDateValue)->toDateString();
            $amountLabel = $isManuallySettled ? '—' : number_format((float) $expense->amount, 2);

            $statusKey = 'upcoming';
            $statusParams = [];

            if ($isManuallySettled) {
                $statusKey = 'completed';
            } elseif ($expense->outstanding_amount <= 0) {
                $statusKey = 'settled';
            } elseif ($expense->due_date instanceof \Illuminate\Support\Carbon) {
                if ($expense->due_date->lt($today)) {
                    $statusKey = 'overdue';
                } else {
                    $daysRemaining = $today->diffInDays($expense->due_date, false);

                    if ($daysRemaining === 0) {
                        $statusKey = 'due_today';
                    } elseif ($daysRemaining > 0) {
                        $statusKey = 'due_in_days';
                        $statusParams = ['days' => $daysRemaining];
                    } else {
                        $statusKey = 'overdue';
                    }
                }
            }

            $statusBadgeClasses = [
                'completed' => 'badge bg-success-subtle text-success',
                'settled' => 'badge bg-success-subtle text-success',
                'overdue' => 'badge bg-danger-subtle text-danger',
                'due_today' => 'badge bg-warning-subtle text-warning',
                'due_in_days' => 'badge bg-warning-subtle text-warning',
                'upcoming' => 'badge bg-warning-subtle text-warning',
            ];

            $statusBadgeClass = $statusBadgeClasses[$statusKey] ?? 'badge bg-light text-dark';
            $statusLabel = __('expenses::expenses.status_labels.' . $statusKey, $statusParams);

            $lastPayment = $expense->payments->first();
            $lastPaymentLabel = optional($lastPayment?->paid_at)->format('Y-m-d');
            $lastPaymentLabel = $lastPaymentLabel ?: '—';
        @endphp

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3 justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-muted small mb-1">@lang('expenses::expenses.fields.title')</div>
                        <h2 class="h4 mb-1">{{ $expense->title }}</h2>
                        <div class="text-muted">{{ optional($expense->type)->name ?? __('expenses::expenses.fields.not_available') }}</div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="{{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                        <x-button.action href="{{ route('expenses.expenses.edit', $expense) }}" variant="primary" :outline="true" size="sm">
                            @lang('expenses::expenses.actions.edit')
                        </x-button.action>
                        @include('lookups::components.delete-button', [
                            'action' => route('expenses.expenses.destroy', $expense),
                            'confirm' => __('expenses::expenses.actions.confirm_delete'),
                            'label' => __('expenses::expenses.actions.delete'),
                            'buttonClass' => 'px-3 rounded-pill',
                        ])
                    </div>
                </div>

                @if ($isManuallySettled)
                    <div class="alert alert-success d-flex align-items-center gap-2 py-2 px-3 mb-0">
                        <i class="bi bi-check2-circle fs-4"></i>
                        <div>
                            <div class="fw-semibold mb-1">@lang('expenses::expenses.completion.indicator')</div>
                            @if (filled($expense->notes))
                                <div class="mb-0 small text-muted">@lang('expenses::expenses.completion.notes_prefix')</div>
                            @endif
                        </div>
                    </div>
                @endif

                <h3 class="h6 text-uppercase text-muted mt-4 mb-3">@lang('expenses::expenses.sections.overview')</h3>
                <div class="row g-3">
                    <div class="col-sm-6 col-lg-3">
                        <div class="text-muted small">@lang('expenses::expenses.fields.expense_type_id')</div>
                        <div class="fw-semibold">{{ optional($expense->type)->name ?? __('expenses::expenses.fields.not_available') }}</div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="text-muted small">@lang('expenses::expenses.fields.due_date')</div>
                        <div class="fw-semibold">{{ $dueDateLabel ?: '—' }}</div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="text-muted small">@lang('expenses::expenses.fields.status')</div>
                        <div><span class="{{ $statusBadgeClass }}">{{ $statusLabel }}</span></div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="text-muted small">@lang('expenses::payments.history.table.paid_at')</div>
                        <div class="fw-semibold">{{ $lastPaymentLabel }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h5 mb-3">@lang('expenses::expenses.sections.payment_summary')</h2>
                <div class="row g-3 text-center text-md-start">
                    <div class="col-md-4">
                        <div class="text-muted small">@lang('expenses::expenses.fields.amount')</div>
                        <div class="fs-4 fw-semibold">{{ $amountLabel }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">@lang('expenses::expenses.fields.paid_amount')</div>
                        <div class="fs-4 fw-semibold">{{ number_format((float) $expense->paid_amount, 2) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">@lang('expenses::expenses.fields.outstanding_amount')</div>
                        <div class="fs-4 fw-semibold">{{ number_format($outstanding, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h5 mb-3">@lang('expenses::expenses.sections.history')</h2>

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

                        @foreach ($expense->payments as $index => $payment)
                            @php
                                $accountName = $payment->bankAccount->name ?? $payment->safe->name ?? '—';
                            @endphp
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td class="text-end">{{ number_format((float) $payment->amount, 2) }}</td>
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

        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">@lang('expenses::expenses.sections.notes')</h2>

                @if (filled($expense->notes))
                    <div class="text-body" style="white-space: pre-line;">{{ $expense->notes }}</div>
                @else
                    <div class="text-muted small">@lang('expenses::expenses.notes_empty')</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
@endpush
