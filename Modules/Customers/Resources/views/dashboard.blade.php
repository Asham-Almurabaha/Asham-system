@extends('layouts.master')

@section('title', __('customers::messages.Customers Dashboard'))

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ __('customers::messages.Customers Dashboard') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">{{ __('Customers') }}</li>
        </ol>
    </nav>
</div>

@php
    $totals               = (array) ($totals ?? []);
    $percentages          = (array) ($percentages ?? []);
    $statusBreakdown      = collect($statusBreakdown ?? []);
    $statusChartLabels    = (array) ($statusChartLabels ?? []);
    $statusChartData      = (array) ($statusChartData ?? []);
    $monthlyRegistrations = (array) ($monthlyRegistrations ?? ['labels' => [], 'values' => [], 'range' => ['from' => null, 'to' => null]]);
    $topContractCustomers = collect($topContractCustomers ?? []);
    $topOutstanding       = collect($topOutstanding ?? []);
    $topNationalities     = collect($topNationalities ?? []);
    $recentCustomers      = collect($recentCustomers ?? []);

    $rangeFrom   = $monthlyRegistrations['range']['from'] ?? '—';
    $rangeTo     = $monthlyRegistrations['range']['to'] ?? '—';
    $statusTotal = $statusBreakdown->sum(fn ($row) => (int) ($row['count'] ?? 0));

    $summaryCards = [
        [
            'col_class'  => 'col-12 col-sm-6 col-xl-4',
            'icon'       => 'bi bi-people',
            'icon_class' => 'fs-4 text-primary',
            'title'      => __('customers::messages.Total Customers'),
            'value'      => number_format($totals['total'] ?? 0),
            'meta'       => [
                ['text' => __('customers::messages.Not affected by filters')],
            ],
        ],
        [
            'col_class'  => 'col-12 col-sm-6 col-xl-4',
            'icon'       => 'bi bi-person-check',
            'icon_class' => 'fs-4 text-success',
            'title'      => __('customers::messages.Active Customers'),
            'value'      => number_format($totals['active'] ?? 0),
            'meta'       => [
                ['text' => __('customers::messages.Percentage') . ': ' . number_format($percentages['active'] ?? 0, 1) . '%'],
            ],
            'aside'      => [
                'title' => __('customers::messages.Inactive Customers'),
                'value' => number_format($totals['inactive'] ?? 0),
                'meta'  => [
                    ['text' => __('customers::messages.Percentage') . ': ' . number_format($percentages['inactive'] ?? 0, 1) . '%'],
                ],
            ],
            'progress' => [
                'value' => $percentages['active'] ?? 0,
            ],
        ],
        [
            'col_class'  => 'col-12 col-sm-6 col-xl-4',
            'icon'       => 'bi bi-diagram-3',
            'icon_class' => 'fs-4 text-info',
            'title'      => __('customers::messages.Customers With Contracts'),
            'value'      => number_format($totals['withContracts'] ?? 0),
            'meta'       => [
                ['text' => __('customers::messages.Percentage') . ': ' . number_format($percentages['withContracts'] ?? 0, 1) . '%'],
            ],
            'aside'      => [
                'title' => __('customers::messages.Customers Without Contracts'),
                'value' => number_format($totals['withoutContracts'] ?? 0),
                'meta'  => [
                    ['text' => __('customers::messages.Percentage') . ': ' . number_format($percentages['withoutContracts'] ?? 0, 1) . '%'],
                ],
            ],
        ],
    ];

    $engagementCards = [
        [
            'col_class'  => 'col-12 col-lg-6',
            'icon'       => 'bi bi-calendar2-plus',
            'icon_class' => 'fs-4 text-primary',
            'title'      => __('customers::messages.New Customers This Month'),
            'value'      => number_format($totals['newMonth'] ?? 0),
            'meta'       => [
                ['text' => __('customers::messages.This Week') . ': ' . number_format($totals['newWeek'] ?? 0)],
            ],
        ],
        [
            'col_class'    => 'col-12 col-lg-6',
            'icon'         => 'bi bi-exclamation-triangle',
            'icon_class'   => 'fs-4 text-danger',
            'title'        => __('customers::messages.Customers With Overdue Installments'),
            'value'        => number_format($totals['overdue'] ?? 0),
            'value_class'  => 'text-danger fw-bold',
            'meta'         => [
                ['text' => __('customers::messages.Percentage') . ': ' . number_format($percentages['overdue'] ?? 0, 1) . '%'],
            ],
            'aside'        => [
                'title' => __('customers::messages.Due This Month'),
                'value' => number_format($totals['dueThisMonth'] ?? 0),
            ],
        ],
    ];
@endphp

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4" dir="rtl">
    <div>
        <p class="text-muted mb-0">{{ __('customers::messages.Dashboard Intro') }}</p>
        <span class="badge bg-light text-dark">{{ __('customers::messages.Total Customers') }}: {{ number_format($totals['total'] ?? 0) }}</span>
    </div>
    <div class="btn-group" role="group">
        <a href="{{ route('customers.index') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 px-3">
            <i class="bi bi-table"></i>
            <span>{{ __('sidebar.Manage Customers') }}</span>
        </a>

        <button class="btn btn-outline-dark dropdown-toggle d-inline-flex align-items-center gap-2 px-3" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
            <span class="fs-5">📊</span>
            <span>{{ __('customers::messages.Reports') }}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end text-end shadow mt-2">
            <li>
                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('reports.customers.delinquent') }}">
                    <i class="bi bi-exclamation-triangle text-warning"></i>
                    <span>{{ __('customers::messages.Delinquent Customers') }}</span>
                </a>
            </li>
            <li>
                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('reports.customers.active') }}">
                    <i class="bi bi-person-check text-success"></i>
                    <span>{{ __('customers::messages.Customers With Active Contracts') }}</span>
                </a>
            </li>
            <li>
                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('reports.customers.unpaid') }}">
                    <i class="bi bi-cash-coin text-primary"></i>
                    <span>{{ __('customers::messages.Unpaid This Month') }}</span>
                </a>
            </li>
            <li>
                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('reports.customers.contracts') }}">
                    <i class="bi bi-list-check"></i>
                    <span>{{ __('customers::messages.Customers & Contracts Report') }}</span>
                </a>
            </li>
        </ul>
    </div>
</div>

@if(!empty($summaryCards))
    <div class="card shadow-sm mb-4" dir="rtl">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <h6 class="mb-1">{{ __('customers::messages.Customers Overview') }}</h6>
                <div class="small text-muted">{{ __('customers::messages.Customers Overview Hint') }}</div>
            </div>
            <div class="small text-muted text-nowrap">{{ __('customers::messages.Total Customers') }}: {{ number_format($totals['total'] ?? 0) }}</div>
        </div>
        <div class="card-body p-20">
            <div class="row g-3">
                @foreach($summaryCards as $card)
                    <div class="{{ $card['col_class'] }}">
                        @include('contracts::partials.kpi-card', array_merge(['dir' => 'rtl'], $card))
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

@if(!empty($engagementCards))
    <div class="card shadow-sm mb-4" dir="rtl">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <h6 class="mb-1">{{ __('customers::messages.Engagement Snapshot') }}</h6>
                <div class="small text-muted">{{ __('customers::messages.Engagement Snapshot Hint') }}</div>
            </div>
        </div>
        <div class="card-body p-20">
            <div class="row g-3">
                @foreach($engagementCards as $card)
                    <div class="{{ $card['col_class'] }}">
                        @include('contracts::partials.kpi-card', array_merge(['dir' => 'rtl'], $card))
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

<div class="row g-3 mb-4" dir="rtl">
    <div class="col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>{{ __('customers::messages.Customer Status Distribution') }}</span>
                <span class="small text-muted">
                    <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="{{ __('customers::messages.Status Overview Hint') }}"></i>
                </span>
            </div>
            <div class="card-body p-0">
                @if($statusBreakdown->isNotEmpty())
                    <div class="list-group list-group-flush">
                        @foreach($statusBreakdown as $status)
                            @php
                                $label = $status['name'] ?? '—';
                                $count = (int) ($status['count'] ?? 0);
                                $pct   = (float) ($status['pct'] ?? 0);
                            @endphp
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="fw-semibold">{{ $label }}</div>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="badge bg-secondary">{{ number_format($count) }}</span>
                                        <span class="text-muted small">{{ number_format($pct, 1) }}%</span>
                                    </div>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-3 text-muted">{{ __('customers::messages.No data available') }}</div>
                @endif
            </div>
            <div class="card-footer bg-white text-end small text-muted">
                {{ __('customers::messages.Total Statuses') }}: {{ number_format($statusTotal) }}
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>{{ __('customers::messages.Statuses Chart (Doughnut)') }}</span>
                <span class="small text-muted">
                    <i class="bi bi-graph-up" data-bs-toggle="tooltip" title="{{ __('customers::messages.Status Chart Hint') }}"></i>
                </span>
            </div>
            <div class="card-body">
                <canvas id="customerStatusChart" height="220"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4" dir="rtl">
    <div class="col-xl-7">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>{{ __('customers::messages.New Customers (Last 12 Months)') }}</span>
                <span class="small text-muted">{{ __('customers::messages.Date Range') }}: {{ $rangeFrom }} — {{ $rangeTo }}</span>
            </div>
            <div class="card-body">
                <div class="ratio ratio-21x9">
                    <canvas id="customerMonthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>{{ __('customers::messages.Top Nationalities') }}</span>
                <span class="small text-muted">{{ __('customers::messages.Total Customers') }}: {{ number_format($totals['total'] ?? 0) }}</span>
            </div>
            <div class="card-body p-0">
                @if($topNationalities->isNotEmpty())
                    <div class="list-group list-group-flush">
                        @foreach($topNationalities as $item)
                            @php
                                $count = (int) ($item['count'] ?? 0);
                                $pct   = (float) ($item['pct'] ?? 0);
                            @endphp
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="fw-semibold">{{ $item['name'] }}</div>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="badge bg-secondary">{{ number_format($count) }}</span>
                                        <span class="text-muted small">{{ number_format($pct, 1) }}%</span>
                                    </div>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-3 text-muted">{{ __('customers::messages.No data available') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4" dir="rtl">
    <div class="col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>{{ __('customers::messages.Top Customers by Active Contracts') }}</span>
                <span class="small text-muted">{{ __('customers::messages.Active Contracts') }}</span>
            </div>
            <div class="card-body p-0">
                @if($topContractCustomers->isNotEmpty())
                    <x-table head-class="table-light" small>
                        <x-slot name="head">
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('customers::messages.Customer Status') }}</th>
                                <th>{{ __('Active Contracts') }}</th>
                                <th>{{ __('Total Contracts') }}</th>
                            </tr>
                        </x-slot>
                        @foreach($topContractCustomers as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="text-start">
                                    <a href="{{ route('customers.show', $item['id']) }}" class="text-decoration-none fw-bold">{{ $item['name'] }}</a>
                                </td>
                                <td>{{ $item['status'] ?? __('customers::messages.Undefined') }}</td>
                                <td>{{ number_format($item['active_contracts'] ?? 0) }}</td>
                                <td>{{ number_format($item['total_contracts'] ?? 0) }}</td>
                            </tr>
                        @endforeach
                    </x-table>
                @else
                    <div class="p-3 text-muted">{{ __('customers::messages.No data available') }}</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>{{ __('customers::messages.Top Outstanding Balances') }}</span>
                <span class="small text-muted">{{ __('customers::messages.Outstanding Amount') }}</span>
            </div>
            <div class="card-body p-0">
                @if($topOutstanding->isNotEmpty())
                    <x-table head-class="table-light" small>
                        <x-slot name="head">
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('customers::messages.Outstanding Amount') }}</th>
                                <th>{{ __('customers::messages.Overdue Amount') }}</th>
                                <th>{{ __('customers::messages.Due This Month') }}</th>
                            </tr>
                        </x-slot>
                        @foreach($topOutstanding as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="text-start">
                                    <a href="{{ route('customers.show', $item['id']) }}" class="text-decoration-none fw-bold">{{ $item['name'] }}</a>
                                </td>
                                <td>{{ number_format($item['unpaid_total'] ?? 0, 2) }}</td>
                                <td>{{ number_format($item['overdue_total'] ?? 0, 2) }}</td>
                                <td>{{ number_format($item['due_this_month_total'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </x-table>
                @else
                    <div class="p-3 text-muted">{{ __('customers::messages.No data available') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm" dir="rtl">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span>{{ __('customers::messages.Recently Added Customers') }}</span>
        <span class="small text-muted">{{ __('customers::messages.Date Range') }}: {{ $rangeFrom }} — {{ now()->format('Y-m-d') }}</span>
    </div>
    <div class="card-body p-0">
        @if($recentCustomers->isNotEmpty())
            <ul class="list-group list-group-flush">
                @foreach($recentCustomers as $item)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('customers.show', $item['id']) }}" class="fw-bold text-decoration-none">{{ $item['name'] }}</a>
                            <div class="small text-muted">{{ $item['status'] ?? __('customers::messages.Undefined') }}</div>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">{{ __('customers::messages.Joined At') }}</div>
                            <div class="fw-semibold">{{ $item['created_at'] ?? '—' }}</div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="p-3 text-muted">{{ __('customers::messages.No data available') }}</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ChartJS = window.Chart;

        if (!ChartJS) {
            console.error('Chart.js failed to load');
            return;
        }

        const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el, {container: 'body'}));

        (function () {
            const el = document.getElementById('customerStatusChart');
            if (!el) {
                return;
            }

            const labels = @json(array_values($statusChartLabels));
            const data   = @json(array_values($statusChartData));

            const ctx = el.getContext('2d');
            if (!ctx) {
                console.warn('Unable to initialise the customer status chart context.');
                return;
            }

            if (!labels.length || !data.length) {
                el.parentElement.innerHTML = '<div class="text-muted">{{ __('customers::messages.No data available') }}</div>';
                return;
            }

            new ChartJS(ctx, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data,
                        borderWidth: 1,
                        backgroundColor: [
                            '#0d6efd', '#198754', '#ffc107', '#dc3545', '#6610f2', '#20c997', '#fd7e14', '#6c757d'
                        ],
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '58%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { usePointStyle: true, boxWidth: 10 }
                        },
                        tooltip: { rtl: true }
                    },
                    animation: { animateScale: true, animateRotate: true }
                }
            });
        })();

        (function () {
            const el = document.getElementById('customerMonthlyChart');
            if (!el) {
                return;
            }

            const labels = @json($monthlyRegistrations['labels'] ?? []);
            const data   = @json($monthlyRegistrations['values'] ?? []);

            const ctx = el.getContext('2d');
            if (!ctx) {
                console.warn('Unable to initialise the customer monthly chart context.');
                return;
            }

            if (!labels.length || !data.length) {
                el.parentElement.innerHTML = '<div class="text-muted">{{ __('customers::messages.No data available') }}</div>';
                return;
            }

            new ChartJS(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: '{{ __('customers::messages.New Customers This Month') }}',
                        data,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13,110,253,0.15)',
                        tension: 0.3,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { rtl: true }
                    },
                    scales: {
                        x: {
                            ticks: { autoSkip: true, maxTicksLimit: 6 }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });
        })();
    });
</script>
@endpush
