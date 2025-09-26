@extends('layouts.print-portrait')

@section('title', __('customers::messages.Monthly Payments Report'))
@section('report_title', __('customers::messages.Monthly Payments Report'))

@php
    $reportData   = (array) ($report ?? []);
    $contracts    = collect($reportData['contracts'] ?? []);
    $contractsCount = (int) ($reportData['contracts_count'] ?? $contracts->count());
    $installmentsCount = (int) ($reportData['installments_count'] ?? $contracts->sum(fn ($row) => (int) ($row['installment_count'] ?? 0)));
    $totalDue     = (float) ($reportData['total_due'] ?? $contracts->sum(fn ($row) => (float) ($row['due_sum'] ?? 0.0)));
    $totalPaid    = (float) ($reportData['total_paid'] ?? $contracts->sum(fn ($row) => (float) ($row['paid_sum'] ?? 0.0)));
    $totalRemaining = (float) ($reportData['total_remaining'] ?? max($totalDue - $totalPaid, 0));
    $paidPercentage = $totalDue > 0 ? round(($totalPaid / max($totalDue, 0.0001)) * 100, 1) : 0.0;

    $formatDate = function ($value) {
        if (empty($value)) {
            return '—';
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('Y-m-d');
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $periodStart = $reportData['period_start'] ?? ($periodContext['start'] ?? null);
    $periodEnd   = $reportData['period_end'] ?? ($periodContext['end'] ?? null);
    $periodStartLabel = $formatDate($periodStart);
    $periodEndLabel   = $formatDate($periodEnd);
    $periodLabel      = (string) ($reportData['period_label'] ?? ($periodContext['label'] ?? ''));

    if ($periodLabel === '' && $periodStartLabel !== '—' && $periodEndLabel !== '—') {
        $periodLabel = $periodStartLabel . ' — ' . $periodEndLabel;
    }

    $selectedMonth = (int) ($periodContext['month'] ?? ($reportData['month'] ?? now()->month));
    $selectedYear  = (int) ($periodContext['year'] ?? ($reportData['year'] ?? now()->year));
    $periodMonthsOptions = (array) ($periodMonths ?? []);
    $periodYearsOptions  = (array) ($periodYears ?? []);

    $formatNumber = fn ($value, $decimals = 2) => number_format((float) $value, $decimals);
@endphp

@push('styles')
    <style>
        .kpi-card .small-muted {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .kpi-card .fs-4 {
            font-size: 1.65rem !important;
        }
    </style>
@endpush

@section('content')
    <div class="no-print card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('customers.reports.monthly.print', $customer) }}" method="GET" class="row g-3 align-items-end">
                @foreach(request()->except(['period_month', 'period_year']) as $key => $value)
                    @if(is_array($value))
                        @foreach($value as $single)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $single }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label small mb-1" for="period_month">{{ __('customers::messages.Month') }}</label>
                    <select name="period_month" id="period_month" class="form-select form-select-sm">
                        @foreach($periodMonthsOptions as $value => $label)
                            <option value="{{ $value }}" @selected((int) $value === $selectedMonth)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label small mb-1" for="period_year">{{ __('customers::messages.Year') }}</label>
                    <select name="period_year" id="period_year" class="form-select form-select-sm">
                        @foreach($periodYearsOptions as $value => $label)
                            <option value="{{ $value }}" @selected((int) $value === $selectedYear)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-auto d-flex gap-2 align-items-end">
                    <x-button.action type="submit" variant="primary" size="sm">
                        <i class="bi bi-search me-1"></i> {{ __('customers::messages.Filter') }}
                    </x-button.action>
                    <x-button.action href="{{ route('customers.reports.monthly.print', $customer) }}" variant="secondary" :outline="true" size="sm">
                        {{ __('customers::messages.Clear') }}
                    </x-button.action>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 bg-light-subtle mb-4">
        <div class="card-body">
            <div class="small-muted mb-1">{{ __('customers::messages.Customer Name') }}</div>
            <div class="fw-bold fs-5 mb-0">{{ $customer->name ?? '—' }}</div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card kpi-card h-100">
                <div class="card-body text-center">
                    <div class="small-muted">{{ __('customers::messages.Contracts Count') }}</div>
                    <div class="fs-4 fw-bold">{{ number_format($contractsCount) }}</div>
                    <div class="small-muted">{{ __('customers::messages.Contracts With Payments In Period') }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card kpi-card h-100">
                <div class="card-body text-center">
                    <div class="small-muted">{{ __('customers::messages.Installments Count') }}</div>
                    <div class="fs-4 fw-bold">{{ number_format($installmentsCount) }}</div>
                    <div class="small-muted">{{ __('customers::messages.Installments Recorded') }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between small-muted mb-1">
                        <span>{{ __('customers::messages.Total Due') }}</span>
                        <span class="fw-semibold">{{ $formatNumber($totalDue) }}</span>
                    </div>
                    <div class="d-flex justify-content-between small-muted mb-1">
                        <span>{{ __('customers::messages.Total Paid') }}</span>
                        <span class="fw-semibold text-success">{{ $formatNumber($totalPaid) }}</span>
                    </div>
                    <div class="d-flex justify-content-between small-muted">
                        <span>{{ __('customers::messages.Total Remaining') }}</span>
                        <span class="fw-semibold">{{ $formatNumber($totalRemaining) }}</span>
                    </div>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar" role="progressbar" style="width: {{ $paidPercentage }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between small-muted mt-1">
                        <span>{{ __('customers::messages.Paid Percentage') }}: {{ $formatNumber($paidPercentage, 1) }}%</span>
                        <span>{{ __('customers::messages.Remaining') }}: {{ $formatNumber($totalRemaining) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-table head-class="table-light" bordered striped>
        <x-slot name="head">
            <tr>
                <th style="width: 160px;">{{ __('customers::messages.Contract Number') }}</th>
                <th class="text-end" style="width: 140px;">{{ __('customers::messages.Due Amount') }}</th>
                <th class="text-end" style="width: 140px;">{{ __('customers::messages.Paid Amount') }}</th>
                <th class="text-end" style="width: 140px;">{{ __('customers::messages.Remaining Amount') }}</th>
                <th class="text-end" style="width: 140px;">{{ __('customers::messages.Last Payment Amount') }}</th>
                <th style="width: 160px;">{{ __('customers::messages.Last Payment Date') }}</th>
            </tr>
        </x-slot>

        @forelse($contracts as $row)
            @php
                $contractNumber  = (string) ($row['contract_number'] ?? ($row['contract_id'] ?? '—'));
                $dueSum          = (float) ($row['due_sum'] ?? 0.0);
                $paidSum         = (float) ($row['paid_sum'] ?? 0.0);
                $remainingSum    = (float) ($row['remaining_sum'] ?? max($dueSum - $paidSum, 0));
                $lastPaymentDate = $row['last_payment_date'] ?? null;
                $lastPaymentAmount = $row['last_payment_amount'] ?? null;
            @endphp
            <tr>
                <td>{{ $contractNumber }}</td>
                <td class="text-end">{{ $formatNumber($dueSum) }}</td>
                <td class="text-end text-success">{{ $formatNumber($paidSum) }}</td>
                <td class="text-end">{{ $formatNumber($remainingSum) }}</td>
                <td class="text-end">{{ $lastPaymentAmount !== null ? $formatNumber($lastPaymentAmount) : '—' }}</td>
                <td>{{ $lastPaymentDate ?: '—' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="py-5 text-center text-muted">{{ __('customers::messages.No payments recorded in period') }}</td>
            </tr>
        @endforelse
    </x-table>
@endsection

@section('actions')
    <x-button.action href="{{ route('customers.show', $customer) }}" variant="secondary" :outline="true">
        <i class="bi bi-person-lines-fill me-1"></i> {{ __('customers::messages.View Customer Data') }}
    </x-button.action>
@endsection
