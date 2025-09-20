@extends('layouts.master')

@section('title', __('Contracts Dashboard'))

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ __('Contracts Dashboard') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">{{ __('Contracts') }}</li>
        </ol>
    </nav>
</div>

@php
    $currencySymbol = $currencySymbol ?? 'ر.س';

    $monthly     = (array) ($installmentsMonthly ?? []);
    $totals      = (array) ($monthly['totals'] ?? []);
    $dueSum      = (float) ($totals['due'] ?? 0);
    $paidSum     = (float) ($totals['paid'] ?? 0);
    $remainSum   = (float) ($totals['remaining'] ?? max($dueSum - $paidSum, 0));
    $dueCount    = (int)   ($totals['count'] ?? 0);
    $paidPct2    = $dueSum > 0 ? round(($paidSum / $dueSum) * 100, 1) : 0;

    $monthLabel  = (string) ($monthly['month_label'] ?? now()->format('Y-m'));
    $excludedStatuses = (array) ($monthly['excluded_status_names'] ?? ['مؤجل', 'معتذر']);
    $excludedStatusesTx = count($excludedStatuses) ? implode('، ', $excludedStatuses) : '—';

    $mVal = (int) ($monthly['month'] ?? now()->month);
    $yVal = (int) ($monthly['year'] ?? now()->year);

    $counts = (array) ($dashboardStats['counts'] ?? []);
    $pct    = (array) ($dashboardStats['percentages'] ?? []);
    $labels = (array) ($dashboardStats['labels'] ?? []);

    $statusMetrics      = collect($contractStatusMetrics ?? []);
    $statusChartLabels  = (array) ($contractStatusChartLabels ?? []);
    $statusChartData    = (array) ($contractStatusChartData ?? []);
    $statusTotal        = (int) ($contractStatusTotal ?? ($counts['total'] ?? 0));
    $raisedCount        = (int) ($raisedContractsCount ?? 0);
    $requiredCount      = (int) ($requiredContractsCount ?? 0);

    $selectedInvestorName = $selectedInvestor->name ?? __('All Investors');

    $statusIcon = function ($name) {
        $normalize = fn($value) => mb_strtolower(trim((string) $value), 'UTF-8');
        $normalizedName = $normalize($name);

        static $normalizedStatusMap = null;
        static $aliasMap = null;

        if ($normalizedStatusMap === null) {
            $statusMap = [
                'بدون مستثمر'    => ['bi-person-dash',             'text-secondary'],
                'معلق'           => ['bi-hourglass-split',         'text-warning'],
                'جديد'           => ['bi-stars',                   'text-primary'],
                'منتهي'          => ['bi-flag-fill',               'text-secondary'],
                'سداد مبكر'      => ['bi-cash-stack',              'text-success'],
                'مطلوب'          => ['bi-exclamation-diamond',     'text-danger'],
                'منتظم'          => ['bi-check2-circle',           'text-success'],
                'غير منتظم'      => ['bi-slash-circle',            'text-warning'],
                'متأخر'          => ['bi-clock-history',           'text-warning'],
                'متعثر'          => ['bi-exclamation-triangle',    'text-danger'],
                'مرفوع فيه'      => ['bi-exclamation-octagon',     'text-danger'],
                'منتهي بمطالبة'  => ['bi-file-earmark-exclamation', 'text-danger'],
            ];

            $normalizedStatusMap = [];
            foreach ($statusMap as $label => $iconData) {
                $normalizedStatusMap[$normalize($label)] = $iconData;
            }

            $aliases = [
                'without investor'   => 'بدون مستثمر',
                'no investor'        => 'بدون مستثمر',
                'pending'            => 'معلق',
                'on hold'            => 'معلق',
                'waiting'            => 'معلق',
                'new'                => 'جديد',
                'fresh'              => 'جديد',
                'ended'              => 'منتهي',
                'closed'             => 'منتهي',
                'complete'           => 'منتهي',
                'completed'          => 'منتهي',
                'early settlement'   => 'سداد مبكر',
                'paid off'           => 'سداد مبكر',
                'required'           => 'مطلوب',
                'demand'             => 'مطلوب',
                'active'             => 'منتظم',
                'regular'            => 'منتظم',
                'irregular'          => 'غير منتظم',
                'non-regular'        => 'غير منتظم',
                'late'               => 'متأخر',
                'overdue'            => 'متأخر',
                'delayed'            => 'متأخر',
                'delinquent'         => 'متعثر',
                'defaulted'          => 'متعثر',
                'raised'             => 'مرفوع فيه',
                'ended with claim'   => 'منتهي بمطالبة',
                'under claim'        => 'منتهي بمطالبة',
                'claim closed'       => 'منتهي بمطالبة',
            ];

            $aliasMap = [];
            foreach ($aliases as $alias => $canonical) {
                $canonicalKey = $normalize($canonical);
                if (isset($normalizedStatusMap[$canonicalKey])) {
                    $aliasMap[$normalize($alias)] = $canonicalKey;
                }
            }
        }

        if (isset($normalizedStatusMap[$normalizedName])) {
            return $normalizedStatusMap[$normalizedName];
        }

        if (isset($aliasMap[$normalizedName])) {
            return $normalizedStatusMap[$aliasMap[$normalizedName]];
        }

        if ($normalizedName !== '') {
            logger()->warning('Unknown contract status icon mapping', ['status' => $name]);
        }

        return ['bi-question-circle', 'text-muted'];
    };

    $topKpiCards = [
        [
            'col_class'  => 'col-12 col-md-2',
            'icon'       => 'bi bi-journal-text',
            'icon_class' => 'fs-4 text-primary',
            'title'      => __('Total Contracts — Entire System'),
            'value'      => number_format($counts['total'] ?? 0),
            'meta'       => [
                ['text' => __('Not affected by filters')],
            ],
        ],
        [
            'col_class'  => 'col-12 col-md-2',
            'icon'       => 'bi bi-check2-circle',
            'icon_class' => 'fs-4 text-success',
            'title'      => __('Active Contracts'),
            'hint'       => __('Active = All statuses except :ended and :pending', [
                'ended'   => $labels['ended'] ?? '—',
                'pending' => $labels['pending'] ?? '—',
            ]),
            'value'      => number_format($counts['active'] ?? 0),
            'meta'       => [
                ['text' => __('Percentage: :value%', ['value' => number_format($pct['active'] ?? 0, 1)])],
            ],
            'progress'   => [
                'value' => $pct['active'] ?? 0,
            ],
        ],
        [
            'col_class'  => 'col-12 col-md-2',
            'icon'       => 'bi bi-hourglass-split',
            'icon_class' => 'fs-4 text-warning',
            'title'      => __('Pending Contracts'),
            'hint'       => __('Includes only:') . ' ' . ($labels['pending'] ?? '—'),
            'value'      => number_format($counts['pending'] ?? 0),
            'meta'       => [
                ['text' => __('Percentage: :value%', ['value' => number_format($pct['pending'] ?? 0, 1)])],
            ],
            'progress'   => [
                'value'     => $pct['pending'] ?? 0,
                'bar_class' => 'bg-warning',
            ],
        ],
        [
            'col_class'  => 'col-12 col-md-2',
            'icon'       => 'bi bi-people',
            'icon_class' => 'fs-4 text-danger',
            'title'      => __('Without Investor'),
            'hint'       => __('Contracts that are not linked to any investor (from the relationship)'),
            'value'      => number_format($counts['noInvestor'] ?? 0),
            'meta'       => [
                ['text' => __('Percentage: :value%', ['value' => number_format($pct['noInvestor'] ?? 0, 1)])],
            ],
            'progress'   => [
                'value'     => $pct['noInvestor'] ?? 0,
                'bar_class' => 'bg-danger',
            ],
        ],
        [
            'col_class'  => 'col-12 col-md-2',
            'icon'       => 'bi bi-flag-fill',
            'icon_class' => 'fs-4 text-secondary',
            'title'      => __('Ended Contracts'),
            'hint'       => __('Includes:') . ' ' . ($labels['ended'] ?? '—'),
            'value'      => number_format($counts['ended'] ?? 0),
            'meta'       => [
                ['text' => __('Percentage: :value%', ['value' => number_format($pct['ended'] ?? 0, 1)])],
            ],
            'progress'   => [
                'value'     => $pct['ended'] ?? 0,
                'bar_class' => 'bg-secondary',
            ],
        ],
        [
            'col_class'    => 'col-12 col-md-2',
            'icon'         => 'bi bi-exclamation-octagon',
            'icon_class'   => 'fs-4 text-danger',
            'title'        => __('contracts::contracts.Raised Status Contracts'),
            'value'        => number_format($raisedCount),
            'value_class'  => 'text-danger',
            'meta'         => [
                ['text' => __('contracts::contracts.Contracts in Raised Status')],
            ],
        ],
        [
            'col_class'    => 'col-12 col-md-2',
            'icon'         => 'bi bi-exclamation-triangle',
            'icon_class'   => 'fs-4 text-warning',
            'title'        => __('contracts::contracts.Required Status Contracts'),
            'value'        => number_format($requiredCount),
            'value_class'  => 'text-warning',
            'meta'         => [
                ['text' => __('contracts::contracts.Contracts in Required Status')],
            ],
        ],
    ];

    $statusMetricsMap = $statusMetrics
        ->filter(fn($row) => is_array($row) && isset($row['id']))
        ->mapWithKeys(fn($row) => [
            (int) ($row['id'] ?? 0) => [
                'count' => (int) ($row['count'] ?? 0),
                'pct'   => isset($row['pct']) ? (float) $row['pct'] : null,
            ],
        ]);

    $statusKpiCards = collect($contractStatuses ?? [])
        ->map(function ($status) use ($statusMetricsMap, $statusIcon) {
            $statusId = (int) ($status->id ?? 0);
            if ($statusId <= 0) {
                return null;
            }

            $metrics = $statusMetricsMap->get($statusId, ['count' => 0, 'pct' => null]);
            $count   = (int) ($metrics['count'] ?? 0);

            if ($count <= 0) {
                return null;
            }

            $name = (string) ($status->name ?? '—');
            [$iconName, $colorClass] = $statusIcon($name);
            $reportUrl = route('reports.contracts.status', $statusId);

            $card = [
                '__count'     => $count,
                'col_class'   => 'col-12 col-sm-6 col-lg-4 col-xl-3',
                'icon'        => 'bi ' . $iconName,
                'icon_class'  => trim('fs-4 ' . $colorClass),
                'title'       => $name,
                'value'       => number_format($count),
                'value_class' => trim('fw-bold ' . $colorClass),
                'actions'     => [
                    [
                        'url'    => $reportUrl,
                        'icon'   => 'bi bi-file-earmark-text',
                        'title'  => __('contracts::contracts.View Status Report'),
                        'target' => '_blank',
                        'rel'    => 'noopener noreferrer',
                        'attrs'  => [
                            'aria-label' => __('contracts::contracts.View Status Report'),
                        ],
                    ],
                ],
            ];

            $pct = $metrics['pct'] ?? null;
            if ($pct !== null) {
                $card['meta'] = [
                    [
                        'text' => __('contracts::contracts.Percentage: :value%', [
                            'value' => number_format((float) $pct, 2),
                        ]),
                    ],
                ];
            }

            return $card;
        })
        ->filter()
        ->sortByDesc('__count')
        ->map(function ($card) {
            unset($card['__count']);
            return $card;
        })
        ->values()
        ->all();

    $monthlyInstallmentCards = [
        [
            'col_class'  => 'col-12 col-md-3',
            'icon'       => 'bi bi-journal-check',
            'icon_class' => 'fs-4 text-primary',
            'title'      => __('Number of Due Installments'),
            'value'      => number_format($dueCount),
            'meta'       => [
                ['text' => __('This Month')],
            ],
        ],
        [
            'col_class'           => 'col-12 col-md-5',
            'icon'                => 'bi bi-cash-coin',
            'icon_class'          => 'fs-4 text-success',
            'title'               => __('Total Due'),
            'value'               => number_format($dueSum, 2),
            'value_suffix'        => $currencySymbol,
            'value_suffix_class'  => 'fs-6 text-muted',
            'aside'               => [
                'title'       => __('Paid'),
                'title_class' => 'subnote',
                'value'       => number_format($paidSum, 2),
                'value_class' => 'fw-bold',
            ],
            'progress'            => [
                'value' => $paidPct2,
                'title' => __('Paid Percentage'),
            ],
            'footer'              => [
                'class' => 'mt-1',
                'split' => true,
                'items' => [
                    ['text' => __('Percentage: :value%', ['value' => number_format($paidPct2, 1)])],
                    ['text' => __('Remaining:') . ' ' . number_format($remainSum, 2)],
                ],
            ],
        ],
        [
            'col_class'          => 'col-12 col-md-4',
            'icon'               => 'bi bi-wallet2',
            'icon_class'         => 'fs-4 text-warning',
            'title'              => __('Remaining to Pay'),
            'value'              => number_format($remainSum, 2),
            'value_suffix'       => $currencySymbol,
            'value_suffix_class' => 'fs-6 text-muted',
            'meta'               => [
                ['text' => __('Within the Specified Period')],
            ],
        ],
    ];
@endphp

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">



<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <p class="text-muted mb-0">{{ __('Review the overall performance of contracts and quickly reach the detailed reports.') }}</p>
        <span class="badge bg-light text-dark">{{ __('Current Investor:') }} {{ $selectedInvestorName }}</span>
    </div>
    <div class="btn-group" role="group">
        <a href="{{ route('contracts.index') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 px-3">
            <i class="bi bi-table"></i>
            <span>{{ __('Manage Contracts') }}</span>
        </a>

        <button class="btn btn-outline-dark dropdown-toggle d-inline-flex align-items-center gap-2 px-3" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
            <span class="fs-5">📊</span>
            <span>{{ __('Reports') }}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end text-end shadow mt-2">
            <li>
                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('reports.contracts.office_outstanding') }}">
                    <i class="bi bi-cash-coin text-warning"></i>
                    <span>{{ __('Office Outstanding Report') }}</span>
                </a>
            </li>
        </ul>
    </div>
</div>

{{-- <div class="row g-3 mb-4" dir="rtl">
    @foreach($topKpiCards as $card)
        <div class="{{ $card['col_class'] }}">
            @include('contracts::partials.kpi-card', array_merge(['dir' => 'rtl'], $card))
        </div>
    @endforeach
</div> --}}

@if(!empty($statusKpiCards))
    <div class="card shadow-sm mb-4" dir="rtl">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <h6 class="mb-1">{{ __('contracts::contracts.Contract Statuses Overview') }}</h6>
                <div class="small text-muted">{{ __('contracts::contracts.Percentages calculated from current total contracts') }}</div>
            </div>
            <div class="small text-muted text-nowrap">{{ __('contracts::contracts.Total Statuses') }}: {{ number_format($statusTotal) }}</div>
        </div>
        <div class="card-body p-20">
            <div class="row g-3">
                @foreach($statusKpiCards as $card)
                    <div class="{{ $card['col_class'] }}">
                        @include('contracts::partials.kpi-card', array_merge(['dir' => 'rtl'], $card))
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

<div class="card shadow-sm mb-4" dir="rtl">
    <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h6 class="mb-1">{{ __('Monthly Installments Summary') }} <span class="text-muted">({{ $monthLabel }})</span></h6>
            <div class="small text-muted">
                <i class="bi bi-filter"></i>
                {{ __('Excludes statuses:') }} {{ $excludedStatusesTx }}
            </div>
        </div>
        <form action="{{ route('contracts.dashboard') }}" method="GET" class="row g-2 align-items-end flex-grow-1 flex-md-grow-0" dir="rtl">
            <div class="col-12 col-md-auto">
                <label class="form-label small mb-1" for="investor_id">{{ __('Investor') }}</label>
                <select name="investor_id" id="investor_id" class="form-select form-select-sm">
                    <option value="">{{ __('All Investors') }}</option>
                    @foreach($investors as $investor)
                        <option value="{{ $investor->id }}" {{ (string) request('investor_id') === (string) $investor->id ? 'selected' : '' }}>
                            {{ $investor->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-auto">
                <label class="form-label small mb-1" for="month">{{ __('Month') }}</label>
                <input type="number" name="m" id="month" min="1" max="12" class="form-control form-control-sm" value="{{ request('m', $mVal) }}">
            </div>
            <div class="col-6 col-md-auto">
                <label class="form-label small mb-1" for="year">{{ __('Year') }}</label>
                <input type="number" name="y" id="year" min="2000" max="2100" class="form-control form-control-sm" value="{{ request('y', $yVal) }}">
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button class="btn btn-primary btn-sm">{{ __('Update') }}</button>
                <a href="{{ route('contracts.dashboard') }}" class="btn btn-outline-secondary btn-sm">{{ __('Clear') }}</a>
            </div>
        </form>
    </div>
    <div class="card-body p-20">
        <div class="row g-3">
            @foreach($monthlyInstallmentCards as $card)
                <div class="{{ $card['col_class'] }}">
                    @include('contracts::partials.kpi-card', array_merge(['dir' => 'rtl'], $card))
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-3 mb-4" dir="rtl">
    <div class="col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>{{ __('contracts::contracts.Contract Statuses Distribution') }}</span>
                <span class="small text-muted">
                    <i class="bi bi-info-circle" data-bs-toggle="tooltip"
                       title="{{ __('contracts::contracts.Percentages calculated from current total contracts') }}"></i>
                </span>
            </div>
            <div class="card-body p-0">
                @if(($statusMetrics->count() ?? 0) > 0)
                    <div class="list-group list-group-flush">
                        @foreach($statusMetrics as $statusRow)
                            @php
                                $label = $statusRow['name'] ?? '—';
                                $count = (int) ($statusRow['count'] ?? 0);
                                $pct   = (float) ($statusRow['pct'] ?? 0);
                            @endphp
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="fw-semibold">{{ $label }}</div>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="badge bg-secondary">{{ number_format($count) }}</span>
                                        <span class="text-muted small">{{ number_format($pct, 2) }}%</span>
                                    </div>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-3 text-muted">{{ __('contracts::contracts.No data for statuses.') }}</div>
                @endif
            </div>
            <div class="card-footer bg-white text-end small text-muted">
                {{ __('contracts::contracts.Total Statuses') }}: {{ number_format($statusTotal) }}
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>{{ __('contracts::contracts.Statuses Chart (Doughnut)') }}</span>
                <span class="small text-muted">
                    <i class="bi bi-graph-up" data-bs-toggle="tooltip"
                       title="{{ __('contracts::contracts.The chart reflects the same distribution shown on the right') }}"></i>
                </span>
            </div>
            <div class="card-body">
                <canvas id="contractStatusChart" height="220"></canvas>
            </div>
        </div>
    </div>
</div>





@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el, {container: 'body'}));

        (function () {
            const el = document.getElementById('contractStatusChart');
            if (!el) {
                return;
            }

            const labels = @json(array_values($statusChartLabels ?? []));
            const data   = @json(array_values($statusChartData ?? []));

            if (!labels.length || !data.length) {
                el.parentElement.innerHTML = '<div class="text-muted">{{ __('contracts::contracts.No data for the chart.') }}</div>';
                return;
            }

            new Chart(el, {
                type: 'doughnut',
                data: { labels, datasets: [{ data, borderWidth: 1 }] },
                options: {
                    responsive: true,
                    cutout: '58%',
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 10 } },
                        tooltip: { rtl: true }
                    },
                    animation: { animateScale: true, animateRotate: true }
                }
            });
        })();
    });
</script>
@endpush
