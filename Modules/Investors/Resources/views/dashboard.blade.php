@extends('layouts.master')

@section('title', __('investors::investors.Investors Dashboard'))

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ __('investors::investors.Investors Dashboard') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">{{ __('investors::investors.Investors') }}</li>
        </ol>
    </nav>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

@php
    $percentages = (array) ($percentages ?? []);
    $liquidityTotals = (array) ($liquidityTotals ?? []);
    $contractStats = (array) ($contractStats ?? []);
    $topLiquidityCollection = collect($topLiquidity ?? []);
    $topLiquidityTotalNet = (float) ($topLiquidityTotalNet ?? $topLiquidityCollection->sum('net'));

    $totalInvestors   = (int) ($investorsTotalAll ?? 0);
    $activeInvestors  = (int) ($activeInvestorsTotalAll ?? 0);
    $inactiveInvestors = (int) ($inactiveInvestorsTotalAll ?? max($totalInvestors - $activeInvestors, 0));
    $coveragePct      = (float) ($contractStats['coverage_pct'] ?? 0);
    $totalContracts   = (int) ($contractStats['total'] ?? 0);
    $activeContracts  = (int) ($contractStats['active'] ?? 0);
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4" dir="rtl">
    <div>
        <p class="text-muted mb-1">{{ __('investors::investors.Dashboard Intro') }}</p>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-light text-dark">{{ __('investors::investors.Total Investors — All System') }}: {{ number_format($totalInvestors) }}</span>
            <span class="badge bg-light text-dark">{{ __('investors::investors.Active Investors') }}: {{ number_format($activeInvestors) }}</span>
        </div>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-3">
        <span class="badge-chip" data-bs-toggle="tooltip" title="{{ __('dashboard.Net = In - Out') }}">
            <i class="bi bi-people me-1"></i>
            {{ __('dashboard.Investors Liquidity Net') }}: {{ number_format($liquidityTotals['net'] ?? 0, 2) }}
        </span>
        <div class="btn-group" role="group">
            <a href="{{ route('investors.index') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 px-3">
                <i class="bi bi-table"></i>
                <span>{{ __('investors::investors.Manage Investors') }}</span>
            </a>
            <button class="btn btn-outline-dark dropdown-toggle d-inline-flex align-items-center gap-2 px-3" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                <span class="fs-5">📊</span>
                <span>{{ __('investors::investors.Reports') }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end text-end shadow mt-2">
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('reports.investors.Allliquidity') }}">
                        <i class="bi bi-cash-coin text-success"></i>
                        <span>{{ __('investors::investors.Investors Liquidity Report') }}</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('reports.investors.outstanding') }}">
                        <i class="bi bi-clipboard-data text-primary"></i>
                        <span>{{ __('investors::investors.Investors Outstanding Report') }}</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="row g-3 mb-4" dir="rtl">
    <div class="col-12 col-md-3">
        <div class="kpi-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-people fs-4 text-primary"></i></div>
                <div>
                    <div class="subnote">{{ __('investors::investors.Total Investors — All System') }}</div>
                    <div class="kpi-value fw-bold">{{ number_format($totalInvestors) }}</div>
                    <div class="subnote">{{ __('investors::investors.Not affected by filters') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="kpi-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-person-check fs-4 text-success"></i></div>
                <div>
                    <div class="subnote">{{ __('investors::investors.Active Investors') }}</div>
                    <div class="kpi-value fw-bold">{{ number_format($activeInvestors) }}</div>
                    <div class="subnote">{{ __('investors::investors.Active Percentage') }}: {{ number_format($percentages['active'] ?? 0, 1) }}%</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="progress bar-8">
                    <div class="progress-bar" style="width: {{ $percentages['active'] ?? 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="kpi-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-person-x fs-4 text-danger"></i></div>
                <div>
                    <div class="subnote">{{ __('investors::investors.Inactive Investors') }}</div>
                    <div class="kpi-value fw-bold">{{ number_format($inactiveInvestors) }}</div>
                    <div class="subnote">{{ __('investors::investors.Percentage') }}: {{ number_format($percentages['inactive'] ?? 0, 1) }}%</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="progress bar-8">
                    <div class="progress-bar bg-danger" style="width: {{ $percentages['inactive'] ?? 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="kpi-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-calendar2-plus fs-4 text-primary"></i></div>
                <div>
                    <div class="subnote">{{ __('investors::investors.New Investors This Month') }}</div>
                    <div class="kpi-value fw-bold">{{ number_format($newInvestorsThisMonthAll ?? 0) }}</div>
                    <div class="subnote">{{ __('investors::investors.This Week') }}: {{ number_format($newInvestorsThisWeekAll ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4" dir="rtl">
    <div class="col-12 col-md-4">
        <div class="kpi-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-graph-down-arrow fs-4 text-info"></i></div>
                <div>
                    <div class="subnote">{{ __('investors::investors.Net Liquidity') }}</div>
                    <div class="kpi-value fw-bold">{{ number_format($liquidityTotals['net'] ?? 0, 2) }}</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="subnote">{{ __('investors::investors.Total Deposits') }}: {{ number_format($liquidityTotals['in'] ?? 0, 2) }}</div>
                <div class="subnote">{{ __('investors::investors.Total Withdrawals') }}: {{ number_format($liquidityTotals['out'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="kpi-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-diagram-3 fs-4 text-warning"></i></div>
                <div>
                    <div class="subnote">{{ __('investors::investors.Investors With Contracts') }}</div>
                    <div class="kpi-value fw-bold">{{ number_format($investorsWithContracts ?? 0) }}</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="subnote">{{ __('investors::investors.Investors Without Contracts') }}: {{ number_format($investorsWithoutContracts ?? 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="kpi-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-percent fs-4 text-secondary"></i></div>
                <div>
                    <div class="subnote">{{ __('investors::investors.Average Office Share') }}</div>
                    <div class="kpi-value fw-bold">{{ number_format($avgOfficeShare ?? 0, 2) }}%</div>
                    <div class="subnote">{{ __('investors::investors.Across All Investors') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4" dir="rtl">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="card-title mb-1">{{ __('investors::investors.Contracts Coverage') }}</h5>
                <div class="small text-muted">{{ __('investors::investors.Contracts Coverage Hint') }}</div>
            </div>
            <span class="badge bg-primary-subtle text-primary fs-6">{{ __('investors::investors.Coverage Rate') }}: {{ number_format($coveragePct, 1) }}%</span>
        </div>
        <div class="progress bar-12 mt-3">
            <div class="progress-bar" role="progressbar" style="width: {{ $coveragePct }}%" aria-valuenow="{{ $coveragePct }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div class="row mt-3 text-center">
            <div class="col-12 col-md-4">
                <div class="fw-bold">{{ number_format($activeContracts) }}</div>
                <div class="subnote">{{ __('investors::investors.Active Contracts Linked') }}</div>
            </div>
            <div class="col-12 col-md-4">
                <div class="fw-bold">{{ number_format($totalContracts) }}</div>
                <div class="subnote">{{ __('investors::investors.Total Contracts Linked') }}</div>
            </div>
            <div class="col-12 col-md-4">
                <div class="fw-bold">{{ number_format($investorsWithContracts ?? 0) }}</div>
                <div class="subnote">{{ __('investors::investors.Investors With Contracts') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3" dir="rtl">
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h6 class="mb-0">{{ __('dashboard.Top 10 Investors (Positive Net Liquidity)') }}</h6>
                    <div class="small text-muted">{{ __('dashboard.Net = In - Out. Internal transfers are neutral and do not affect the net.') }}</div>
                </div>
                <div class="text-end">
                    <span class="badge bg-light text-dark">{{ __('investors::investors.Results') }}: {{ number_format($topLiquidityCollection->count()) }}</span>
                    <div class="small text-muted mt-1">{{ __('dashboard.Total Net Displayed') }}: {{ number_format($topLiquidityTotalNet, 2) }}</div>
                </div>
            </div>
            <div class="card-body p-0">
                <x-table head-class="table-light" class="text-center">
                    <x-slot name="head">
                        <tr>
                            <th style="width:60px">#</th>
                            <th>{{ __('investors::investors.Name') }}</th>
                            <th>{{ __('investors::investors.Total Deposits') }}</th>
                            <th>{{ __('investors::investors.Total Withdrawals') }}</th>
                            <th>{{ __('investors::investors.Net Liquidity') }}</th>
                            <th>{{ __('investors::investors.Actions') }}</th>
                        </tr>
                    </x-slot>
                    @forelse($topLiquidityCollection as $index => $row)
                        <tr>
                            <td class="text-muted">{{ $index + 1 }}</td>
                            <td class="text-start">
                                <a href="{{ route('investors.show', $row['id']) }}" class="text-decoration-none fw-bold text-dark hover-primary">
                                    {{ $row['name'] }}
                                </a>
                            </td>
                            <td>{{ number_format($row['in'] ?? 0, 2) }}</td>
                            <td>{{ number_format($row['out'] ?? 0, 2) }}</td>
                            <td>{{ number_format($row['net'] ?? 0, 2) }}</td>
                            <td>
                                <a href="{{ route('investors.show', $row['id']) }}" class="btn btn-sm btn-outline-primary">{{ __('investors::investors.View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-muted">{{ __('investors::investors.No data available') }}</td>
                        </tr>
                    @endforelse
                </x-table>
            </div>
            @if($topLiquidityCollection->count() > 0)
                <div class="card-footer small text-muted">
                    {{ __('dashboard.Net = In - Out. Internal transfers are neutral and do not affect the net.') }}
                </div>
            @endif
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ __('investors::investors.Recently Added Investors') }}</h6>
                <span class="badge bg-light text-dark">{{ __('investors::investors.Results') }}: {{ number_format(($recentInvestors ?? collect())->count()) }}</span>
            </div>
            <div class="card-body p-0">
                <x-table head-class="table-light" class="text-center">
                    <x-slot name="head">
                        <tr>
                            <th style="width:60px">#</th>
                            <th>{{ __('investors::investors.Name') }}</th>
                            <th>{{ __('investors::investors.Created At') }}</th>
                            <th>{{ __('investors::investors.Investment Start Date') }}</th>
                            <th>{{ __('investors::investors.Contracts Count') }}</th>
                            <th>{{ __('investors::investors.Office Share %') }}</th>
                        </tr>
                    </x-slot>
                    @forelse(($recentInvestors ?? collect()) as $index => $investor)
                        <tr>
                            <td class="text-muted">{{ $index + 1 }}</td>
                            <td class="text-start">
                                <a href="{{ route('investors.show', $investor) }}" class="text-decoration-none fw-bold text-dark hover-primary">
                                    {{ $investor->name }}
                                </a>
                            </td>
                            <td>
                                <span dir="ltr">{{ optional($investor->created_at)->format('Y-m-d') }}</span>
                            </td>
                            <td>
                                @if($investor->investment_start_date)
                                    <span dir="ltr">{{ $investor->investment_start_date->format('Y-m-d') }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ number_format($investor->contracts_count ?? 0) }}</td>
                            <td>{{ number_format((float) ($investor->office_share_percentage ?? 0), 2) }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-muted">{{ __('investors::investors.No data available') }}</td>
                        </tr>
                    @endforelse
                </x-table>
            </div>
        </div>
    </div>
</div>
@endsection
