@extends('layouts.master')

@section('title', __('companies::companies.Company Dashboard'))

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ __('companies::companies.Company Dashboard') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">{{ __('companies::companies.Companies') }}</li>
        </ol>
    </nav>
</div>

@php
    $companySummaries = collect($companySummaries ?? []);
    $overallTotals = (array) ($overallTotals ?? [
        'companies' => 0,
        'active' => 0,
        'inactive' => 0,
        'bank_amount' => 0,
        'safe_amount' => 0,
        'final_balance' => 0,
    ]);
@endphp

<div class="card shadow-sm mb-4" dir="rtl">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2 p-20">
        <div>
            <p class="text-muted mb-1">{{ __('companies::companies.Company Dashboard Intro') }}</p>
            <span class="badge bg-light text-dark border">{{ __('companies::companies.Total Companies') }}: {{ number_format($overallTotals['companies'] ?? 0) }}</span>
            <span class="badge bg-success-subtle text-success border ms-1">{{ __('companies::companies.Active Companies') }}: {{ number_format($overallTotals['active'] ?? 0) }}</span>
            <span class="badge bg-danger-subtle text-danger border ms-1">{{ __('companies::companies.Inactive Companies') }}: {{ number_format($overallTotals['inactive'] ?? 0) }}</span>
        </div>
        <div class="text-end">
            <div class="fw-semibold text-muted small">{{ __('companies::companies.Bank Total') }}: {{ number_format($overallTotals['bank_amount'] ?? 0, 2) }}</div>
            <div class="fw-semibold text-muted small">{{ __('companies::companies.Safe Total') }}: {{ number_format($overallTotals['safe_amount'] ?? 0, 2) }}</div>
            <div class="fs-5 fw-bold {{ ($overallTotals['final_balance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                {{ __('companies::companies.Net Balance') }}: {{ number_format($overallTotals['final_balance'] ?? 0, 2) }}
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4" dir="rtl">
    <div class="col-12 col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">{{ __('companies::companies.Total Companies') }}</div>
                    <div class="fs-4 fw-bold">{{ number_format($overallTotals['companies'] ?? 0) }}</div>
                </div>
                <span class="fs-2" aria-hidden="true">🏢</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">{{ __('companies::companies.Bank Total') }}</div>
                    <div class="fs-4 fw-bold">{{ number_format($overallTotals['bank_amount'] ?? 0, 2) }}</div>
                    <div class="text-muted small">{{ __('companies::companies.Safe Total') }}: {{ number_format($overallTotals['safe_amount'] ?? 0, 2) }}</div>
                </div>
                <span class="fs-2" aria-hidden="true">🏦</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">{{ __('companies::companies.Net Balance') }}</div>
                    <div class="fs-4 fw-bold {{ ($overallTotals['final_balance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($overallTotals['final_balance'] ?? 0, 2) }}</div>
                </div>
                <span class="fs-2" aria-hidden="true">⚖️</span>
            </div>
        </div>
    </div>
</div>

@forelse($companySummaries as $summary)
    @php
        /** @var \Modules\Companies\Entities\Company $company */
        $company = $summary['company'];
        $totals = (array) ($summary['totals'] ?? []);
        $statuses = collect($summary['statuses'] ?? []);
        $netClass = ($totals['final_balance'] ?? 0) >= 0 ? 'text-success' : 'text-danger';
    @endphp
    <div class="card shadow-sm mb-3" dir="rtl">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <h6 class="mb-1">{{ $company->name }}</h6>
                <span class="badge {{ $company->is_active ? 'bg-success-subtle text-success border' : 'bg-secondary-subtle text-secondary border' }}">
                    {{ $company->is_active ? __('companies::companies.Active') : __('companies::companies.Inactive') }}
                </span>
            </div>
            <div class="text-end">
                <div class="fw-semibold {{ $netClass }}">{{ __('companies::companies.Net Balance') }}: {{ number_format($totals['final_balance'] ?? 0, 2) }}</div>
                <div class="small text-muted">{{ __('companies::companies.Bank Total') }}: {{ number_format($totals['bank_amount'] ?? 0, 2) }} • {{ __('companies::companies.Safe Total') }}: {{ number_format($totals['safe_amount'] ?? 0, 2) }}</div>
                <div class="small text-muted">{{ __('companies::companies.Transactions Count') }}: {{ number_format($totals['transactions'] ?? 0) }}</div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($statuses->isEmpty())
                <div class="p-3 text-center text-muted">{{ __('companies::companies.No Dashboard Data') }}</div>
            @else
                <div class="table-responsive">
                    <div class="px-3 pt-3 small text-muted">
                        {{ __('companies::companies.Accounts Breakdown Hint') }}
                    </div>
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">{{ __('companies::companies.Status') }}</th>
                                <th scope="col" class="text-center">{{ __('companies::companies.Transactions Count') }}</th>
                                <th scope="col" class="text-end">{{ __('companies::companies.Bank Total') }}</th>
                                <th scope="col" class="text-end">{{ __('companies::companies.Safe Total') }}</th>
                                <th scope="col" class="text-end">{{ __('companies::companies.Net Balance') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($statuses as $status)
                                @php
                                    $balanceClass = ($status['final_balance'] ?? 0) >= 0 ? 'text-success' : 'text-danger';
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $status['status_name'] }}</td>
                                    <td class="text-center">{{ number_format($status['transaction_count'] ?? 0) }}</td>
                                    <td class="text-end">
                                        {{ number_format($status['bank_amount'] ?? 0, 2) }}
                                        @if(!empty($status['bank_accounts']))
                                            <div class="small text-muted mt-1 text-start text-md-end">
                                                @foreach($status['bank_accounts'] as $account)
                                                    @php
                                                        $net = $account['net'] ?? 0;
                                                        $netClass = $net >= 0 ? 'text-success' : 'text-danger';
                                                        $sign = $net >= 0 ? '+' : '-';
                                                    @endphp
                                                    <div>
                                                        <span class="fw-semibold">{{ $account['name'] }}</span>
                                                        <span class="badge bg-light border text-body-secondary ms-1">{{ __('companies::companies.Transactions Count') }}: {{ number_format($account['transaction_count'] ?? 0) }}</span>
                                                        <span class="ms-1 {{ $netClass }}">{{ $sign }}{{ number_format(abs($net), 2) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($status['safe_amount'] ?? 0, 2) }}
                                        @if(!empty($status['safes']))
                                            <div class="small text-muted mt-1 text-start text-md-end">
                                                @foreach($status['safes'] as $safe)
                                                    @php
                                                        $net = $safe['net'] ?? 0;
                                                        $netClass = $net >= 0 ? 'text-success' : 'text-danger';
                                                        $sign = $net >= 0 ? '+' : '-';
                                                    @endphp
                                                    <div>
                                                        <span class="fw-semibold">{{ $safe['name'] }}</span>
                                                        <span class="badge bg-light border text-body-secondary ms-1">{{ __('companies::companies.Transactions Count') }}: {{ number_format($safe['transaction_count'] ?? 0) }}</span>
                                                        <span class="ms-1 {{ $netClass }}">{{ $sign }}{{ number_format(abs($net), 2) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end {{ $balanceClass }}">{{ number_format($status['final_balance'] ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@empty
    <div class="alert alert-info" dir="rtl">{{ __('companies::companies.No Dashboard Data') }}</div>
@endforelse
@endsection
