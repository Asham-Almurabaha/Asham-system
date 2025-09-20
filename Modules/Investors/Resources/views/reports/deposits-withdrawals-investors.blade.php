@extends('layouts.print-landscape')

@section('title', __('reports.Investors Deposits Withdrawals Report'))
@section('report_title', __('reports.Investors Deposits Withdrawals Report'))

@php
    $cs = $currencySymbol ?? 'ر.س';

    $filters = (array) ($filters ?? []);
    $q = (string) ($filters['q'] ?? '');
    $perPage = (int) ($filters['per_page'] ?? 25);

    $isPaginated = $rows instanceof \Illuminate\Pagination\LengthAwarePaginator;
    $items = $isPaginated ? $rows->items() : (is_iterable($rows) ? $rows : []);
    $items = collect($items);

    $countAll = $isPaginated ? $rows->total() : $items->count();

    $pageTotals = (array) ($pageTotals ?? ['in' => 0.0, 'out' => 0.0, 'net' => 0.0]);
    $overallTotals = (array) ($overallTotals ?? ['in' => 0.0, 'out' => 0.0, 'net' => 0.0]);

    $perPageOptions = [10, 25, 50, 100];
@endphp

@push('styles')
    <style>
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
    </style>
@endpush

@section('content')
    <div class="small-muted mb-2">
        @lang('reports.Based on ledger totals for deposits and withdrawals per investor.')
    </div>

    <div class="toolbar soft p-3 mb-3 no-print">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
                <label class="form-label mb-1 small">@lang('reports.Search by name')</label>
                <input type="text" name="q" class="form-control" value="{{ e($q) }}" placeholder="@lang('investors::investors.Type investor name...')">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small">@lang('reports.Per Page')</label>
                <select name="per_page" class="form-select">
                    <?php foreach ($perPageOptions as $n) { ?>
                        <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-6 col-md-4 d-flex gap-2">
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
        <?php if ($items->isNotEmpty()) { ?>
            <?php foreach ($items as $i => $investor) { ?>
                @php
                    $totalIn = (float) ($investor->total_in ?? 0);
                    $totalOut = (float) ($investor->total_out ?? 0);
                    $net = (float) ($investor->net_liquidity ?? 0);
                @endphp
                <tr>
                    <td>{{ $isPaginated ? ($rows->firstItem() + $i) : ($i + 1) }}</td>
                    <td class="text-start">
                        @if(Route::has('investors.show'))
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
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="5" class="py-5 text-muted">@lang('reports.No matching data.')</td>
            </tr>
        <?php } ?>
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
            @if($isPaginated)
                <tr>
                    <th colspan="5" class="bg-white">
                        <div class="no-print d-flex justify-content-center p-2">
                            {{ $rows->withQueryString()->links('pagination::bootstrap-5') }}
                        </div>
                    </th>
                </tr>
            @endif
        </x-slot>
    </x-table>
@endsection
