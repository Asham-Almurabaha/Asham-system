@extends('layouts.print-portrait')

@section('title', __('reports.Daily Ledger Report'))
@section('report_title', __('reports.Daily Ledger Report'))

@php
    $reportDay = ($reportDay ?? now())->copy();
    $reportDay->locale(app()->getLocale());
    $reportDateLabel = $reportDay->format('Y-m-d');
    $reportDateHuman = $reportDay->translatedFormat('l d F Y');

    $bankReport = collect($bankReport ?? []);
    $safeReport = collect($safeReport ?? []);
    $grandTotals = collect($grandTotals ?? []);

    $bankAccounts = collect($bankReport->get('accounts', []));
    $safeAccounts = collect($safeReport->get('accounts', []));

    $formatAmount = fn ($value) => number_format((float) $value, 2);
@endphp

@push('styles')
    <style>
        .kpi-card {
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 0.5rem;
            padding: 1rem;
            background-color: #f8f9fa;
        }
        .kpi-card .label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .kpi-card .value {
            font-size: 1.4rem;
            font-weight: 600;
        }
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        .account-header {
            background-color: #f1f3f5;
            font-weight: 600;
        }
        .table td, .table th {
            vertical-align: middle;
        }
        .table thead th {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .table-ledger td,
        .table-ledger th {
            text-align: center;
        }
        .table-ledger .account-column,
        .table-ledger th.account-column {
            text-align: start;
        }
        .badge-soft {
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 999px;
            padding: 0.35rem 0.65rem;
            display: inline-block;
        }
        .badge-soft-neutral {
            background-color: #f1f3f5;
            color: #495057;
            border: 1px solid rgba(73, 80, 87, 0.15);
        }
        .badge-soft-primary {
            background-color: rgba(13, 110, 253, 0.12);
            color: #0d6efd;
            border: 1px solid rgba(13, 110, 253, 0.2);
        }
    </style>
@endpush

@section('actions')
    <x-button.action href="{{ route('dashboard') }}" variant="secondary" :outline="true">
        <i class="bi bi-arrow-90deg-left me-1"></i> @lang('app.Back')
    </x-button.action>
@endsection

@section('content')
    <div class="no-print card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('dashboard.daily-ledger.print') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-auto">
                    <label for="report_date" class="form-label small mb-1">@lang('app.Date')</label>
                    <input type="date" class="form-control form-control-sm" id="report_date" name="date" value="{{ $reportDateLabel }}">
                </div>
                <div class="col-auto d-flex gap-2 align-items-end">
                    <x-button.action type="submit" variant="primary" size="sm">
                        <i class="bi bi-search me-1"></i> {{ __('dashboard.Apply') }}
                    </x-button.action>
                    <x-button.action href="{{ route('dashboard.daily-ledger.print') }}" variant="secondary" :outline="true" size="sm">
                        {{ __('dashboard.Today') }}
                    </x-button.action>
                </div>
                <div class="col-12 col-lg-auto ms-lg-auto text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    {{ __('reports.Daily Ledger Report Description', ['date' => $reportDateHuman]) }}
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="kpi-card h-100">
                <div class="label mb-1">{{ __('dashboard.Banks') }}</div>
                <div class="value text-primary">{{ $formatAmount($bankReport->get('net', $bankReport->get('total_in', 0) - $bankReport->get('total_out', 0))) }}</div>
                <ul class="list-unstyled mb-0 small">
                    <li><span class="text-muted">{{ __('dashboard.In') }}:</span> <strong class="text-success">{{ $formatAmount($bankReport->get('total_in', 0)) }}</strong></li>
                    <li><span class="text-muted">{{ __('dashboard.Out') }}:</span> <strong class="text-danger">{{ $formatAmount($bankReport->get('total_out', 0)) }}</strong></li>
                    <li><span class="text-muted">{{ __('reports.Accounts Count') }}:</span> <strong>{{ $bankReport->get('accounts_count', 0) }}</strong></li>
                    <li><span class="text-muted">{{ __('reports.Entries Count') }}:</span> <strong>{{ $bankReport->get('entries_count', 0) }}</strong></li>
                </ul>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="kpi-card h-100">
                <div class="label mb-1">{{ __('dashboard.Safes') }}</div>
                <div class="value text-primary">{{ $formatAmount($safeReport->get('net', $safeReport->get('total_in', 0) - $safeReport->get('total_out', 0))) }}</div>
                <ul class="list-unstyled mb-0 small">
                    <li><span class="text-muted">{{ __('dashboard.In') }}:</span> <strong class="text-success">{{ $formatAmount($safeReport->get('total_in', 0)) }}</strong></li>
                    <li><span class="text-muted">{{ __('dashboard.Out') }}:</span> <strong class="text-danger">{{ $formatAmount($safeReport->get('total_out', 0)) }}</strong></li>
                    <li><span class="text-muted">{{ __('reports.Accounts Count') }}:</span> <strong>{{ $safeReport->get('accounts_count', 0) }}</strong></li>
                    <li><span class="text-muted">{{ __('reports.Entries Count') }}:</span> <strong>{{ $safeReport->get('entries_count', 0) }}</strong></li>
                </ul>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="kpi-card h-100">
                <div class="label mb-1">{{ __('reports.Daily Total') }}</div>
                <div class="value text-primary">{{ $formatAmount($grandTotals->get('net', $grandTotals->get('total_in', 0) - $grandTotals->get('total_out', 0))) }}</div>
                <ul class="list-unstyled mb-0 small">
                    <li><span class="text-muted">{{ __('dashboard.In') }}:</span> <strong class="text-success">{{ $formatAmount($grandTotals->get('total_in', 0)) }}</strong></li>
                    <li><span class="text-muted">{{ __('dashboard.Out') }}:</span> <strong class="text-danger">{{ $formatAmount($grandTotals->get('total_out', 0)) }}</strong></li>
                    <li><span class="text-muted">{{ __('reports.Entries Count') }}:</span> <strong>{{ $grandTotals->get('entries_count', 0) }}</strong></li>
                </ul>
            </div>
        </div>
    </div>

    @if(!$hasEntries)
        <div class="alert alert-warning" role="alert">
            {{ __('reports.No ledger entries for the selected day.') }}
        </div>
    @endif

    @if($bankAccounts->isNotEmpty())
        <div class="mb-4">
            <div class="section-title">{{ __('reports.Bank Ledger Entries') }}</div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle table-ledger">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px">#</th>
                            <th class="account-column">{{ __('dashboard.Account') }}</th>
                            <th>@lang('app.Amount')</th>
                            <th>@lang('app.Status')</th>
                            <th>@lang('app.Type')</th>
                            <th>@lang('app.Party')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $rowNumber = 1; @endphp
                        @foreach($bankAccounts as $account)
                            <tr class="account-header">
                                <td colspan="6" class="text-start">
                                    {{ $account['name'] }}
                                    <span class="ms-2 text-success">{{ __('dashboard.In') }}: {{ $formatAmount($account['total_in'] ?? 0) }}</span>
                                    <span class="ms-2 text-danger">{{ __('dashboard.Out') }}: {{ $formatAmount($account['total_out'] ?? 0) }}</span>
                                    <span class="ms-2">{{ __('dashboard.Net') }}: {{ $formatAmount(($account['net'] ?? 0)) }}</span>
                                </td>
                            </tr>
                            @foreach($account['entries'] as $entry)
                                <tr>
                                    <td>{{ $rowNumber++ }}</td>
                                    <td class="account-column">{{ $entry['account_name'] ?? $account['name'] }}</td>
                                    <td class="fw-semibold">{{ $formatAmount($entry['amount'] ?? 0) }}</td>
                                    <td>
                                        @php $status = $entry['status'] ?? null; @endphp
                                        {{ $status ?: '—' }}
                                    </td>
                                    <td>
                                        @php $type = $entry['type'] ?? null; @endphp
                                        {{ $type ?: '—' }}
                                    </td>
                                    <td>
                                        @php
                                            $party = ($entry['is_office'] ?? false)
                                                ? __('dashboard.Office')
                                                : ($entry['investor'] ?? null);
                                        @endphp
                                        {{ $party ?: '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($safeAccounts->isNotEmpty())
        <div>
            <div class="section-title">{{ __('reports.Safe Ledger Entries') }}</div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle table-ledger">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px">#</th>
                            <th class="account-column">{{ __('dashboard.Account') }}</th>
                            <th>@lang('app.Amount')</th>
                            <th>@lang('app.Status')</th>
                            <th>@lang('app.Type')</th>
                            <th>@lang('app.Party')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $rowNumber = 1; @endphp
                        @foreach($safeAccounts as $account)
                            <tr class="account-header">
                                <td colspan="6" class="text-start">
                                    {{ $account['name'] }}
                                    <span class="ms-2 text-success">{{ __('dashboard.In') }}: {{ $formatAmount($account['total_in'] ?? 0) }}</span>
                                    <span class="ms-2 text-danger">{{ __('dashboard.Out') }}: {{ $formatAmount($account['total_out'] ?? 0) }}</span>
                                    <span class="ms-2">{{ __('dashboard.Net') }}: {{ $formatAmount(($account['net'] ?? 0)) }}</span>
                                </td>
                            </tr>
                            @foreach($account['entries'] as $entry)
                                <tr>
                                    <td>{{ $rowNumber++ }}</td>
                                    <td class="account-column">{{ $entry['account_name'] ?? $account['name'] }}</td>
                                    <td class="fw-semibold">{{ $formatAmount($entry['amount'] ?? 0) }}</td>
                                    <td>
                                        @php $status = $entry['status'] ?? null; @endphp
                                        {{ $status ?: '—' }}
                                    </td>
                                    <td>
                                        @php $type = $entry['type'] ?? null; @endphp
                                        {{ $type ?: '—' }}
                                    </td>
                                    <td>
                                        @php
                                            $party = ($entry['is_office'] ?? false)
                                                ? __('dashboard.Office')
                                                : ($entry['investor'] ?? null);
                                        @endphp
                                        {{ $party ?: '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
