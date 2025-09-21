@extends('layouts.print-landscape')

@section('title', __('reports.Investors Deposits Withdrawals Report'))
@section('report_title', __('reports.Investors Deposits Withdrawals Report'))

@php
    $cs = $currencySymbol ?? 'ر.س';

    $filters = (array) ($filters ?? []);
    $selectedInvestor = (string) ($filters['investor_id'] ?? '');

    $items = collect(is_iterable($rows ?? []) ? $rows : []);
    $countAll = $items->count();

    $pageTotals = (array) ($pageTotals ?? ['in' => 0.0, 'out' => 0.0, 'net' => 0.0]);
    $overallTotals = (array) ($overallTotals ?? ['in' => 0.0, 'out' => 0.0, 'net' => 0.0]);
    $investorOptions = collect($investors ?? []);
@endphp

@push('styles')
    <style>
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
    </style>
@endpush

@section('content')
    <div class="small-muted mb-2">
        @lang('reports.Based on investor transactions totals for deposits and withdrawals per investor.')
    </div>

    <div class="toolbar soft p-3 mb-3 no-print">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-6 col-lg-4">
                <label class="form-label mb-1 small">@lang('reports.Search by name')</label>
                <select name="investor_id" class="form-select">
                    <option value="">@lang('reports.All Investors')</option>
                    <?php foreach ($investorOptions as $investor) { ?>
                        <option value="{{ $investor->id }}" @selected((string) $selectedInvestor === (string) $investor->id)>
                            {{ $investor->name }}
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-4 d-flex gap-2">
                <x-button.action type="submit" variant="primary" class="flex-fill">
                    <i class="bi bi-search"></i> {{ __('Search') }}
                </x-button.action>
                <x-button.action href="{{ url()->current() }}" variant="secondary" :outline="true" class="flex-fill">
                    {{ __('Clear') }}
                </x-button.action>
            </div>
        </form>
    </div>

    <div class="row g-3 kpi mb-4">
        <div class="col-12 col-md-3">
            <div class="card"><div class="card-body text-center">
                <div class="small-muted">@lang('reports.Total Investors (All)')</div>
                <div class="fs-5 fw-bold">{{ number_format($countAll) }}</div>
            </div></div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card"><div class="card-body text-center">
                <div class="small-muted">@lang('reports.Total Deposits (Matching Investors)')</div>
                <div class="fs-5 fw-bold text-success">
                    {{ number_format((float) ($overallTotals['in'] ?? 0), 2) }} <span class="small-muted">{{ $cs }}</span>
                </div>
            </div></div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card"><div class="card-body text-center">
                <div class="small-muted">@lang('reports.Total Withdrawals (Matching Investors)')</div>
                <div class="fs-5 fw-bold text-danger">
                    {{ number_format((float) ($overallTotals['out'] ?? 0), 2) }} <span class="small-muted">{{ $cs }}</span>
                </div>
            </div></div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card"><div class="card-body text-center">
                <div class="small-muted">@lang('reports.Net Liquidity (Matching Investors)')</div>
                @php($overallNet = (float) ($overallTotals['net'] ?? 0))
                <div class="fs-5 fw-bold {{ $overallNet >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($overallNet, 2) }} <span class="small-muted">{{ $cs }}</span>
                </div>
            </div></div>
        </div>
    </div>

    <x-table head-class="table-light" striped bordered class="text-center" :hover="false">
        <x-slot name="head">
            <tr>
                <th style="width:56px">#</th>
                <th class="text-start">@lang('app.Investor')</th>
                <th>@lang('investors::investors.Total Deposits')</th>
                <th>@lang('investors::investors.Total Withdrawals')</th>
                <th>@lang('investors::investors.Net Liquidity')</th>
            </tr>
        </x-slot>
        @if ($items->isNotEmpty())
            <?php
                $rowIndex = 0;
                foreach ($items as $investor) {
                    $rowIndex++;
                    $totalIn = (float) ($investor->total_in ?? 0);
                    $totalOut = (float) ($investor->total_out ?? 0);
                    $net = (float) ($investor->net_liquidity ?? 0);
            ?>
                <tr>
                    <td>{{ $rowIndex }}</td>
                    <td class="text-start">
                        @if (Route::has('investors.show'))
                            <a href="{{ route('investors.show', $investor->id) }}" class="fw-bold text-dark text-decoration-none hover-primary">
                                {{ $investor->name }}
                            </a>
                        @else
                            <span class="fw-bold text-dark">{{ $investor->name }}</span>
                        @endif
                    </td>
                    <td class="text-success fw-semibold">
                        {{ number_format($totalIn, 2) }} <span class="small-muted">{{ $cs }}</span>
                    </td>
                    <td class="text-danger fw-semibold">
                        {{ number_format($totalOut, 2) }} <span class="small-muted">{{ $cs }}</span>
                    </td>
                    <td class="fw-bold {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($net, 2) }} <span class="small-muted">{{ $cs }}</span>
                    </td>
                </tr>
            <?php
                }
            ?>
        @else
            <tr>
                <td colspan="5" class="py-5 text-muted">@lang('reports.No matching data.')</td>
            </tr>
        @endif
        <x-slot name="footer">
            <tr class="table-light fw-semibold">
                <td colspan="2" class="text-start">@lang('reports.Page Totals')</td>
                <td class="text-success">
                    {{ number_format((float) ($pageTotals['in'] ?? 0), 2) }} <span class="small-muted">{{ $cs }}</span>
                </td>
                <td class="text-danger">
                    {{ number_format((float) ($pageTotals['out'] ?? 0), 2) }} <span class="small-muted">{{ $cs }}</span>
                </td>
                <td class="fw-bold {{ ((float) ($pageTotals['net'] ?? 0)) >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format((float) ($pageTotals['net'] ?? 0), 2) }} <span class="small-muted">{{ $cs }}</span>
                </td>
            </tr>
        </x-slot>
    </x-table>
@endsection
