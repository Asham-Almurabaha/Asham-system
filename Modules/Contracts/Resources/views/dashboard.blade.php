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

    $selectedInvestorName = $selectedInvestor->name ?? __('All Investors');

    $statusIcon = function ($name) {
        $normalize = fn($arr) => array_map(fn($s) => mb_strtolower(trim((string) $s), 'UTF-8'), $arr);
        $n = mb_strtolower(trim((string) $name), 'UTF-8');

        $groups = [
            'active'            => ['نشط','active','open','ساري','جاري','effective'],
            'pending'           => ['معلق','pending','قيد الانتظار','قيد الإنتظار','on hold','paused','موقوف مؤقتاً','موقوف'],
            'ended'             => ['منتهي','انتهى','مغلق','closed','ended','complete','completed','تم الانتهاء'],
            'canceled'          => ['ملغي','مرفوض','canceled','cancelled','rejected','void','باطل'],
            'late'              => ['متأخر','متاخر','late','overdue','delinquent'],
            'review'            => ['قيد المراجعة','under review','review','verification','مراجعة'],
            'draft'             => ['مسودة','draft'],
            'early_settlement'  => ['سداد مبكر','early settlement','paid off','مقفلة بالسداد'],
            'rescheduled'       => ['معاد جدولته','rescheduled','جدولة','إعادة جدولة'],
            'suspended'         => ['موقوف','suspended','حظر'],
            'renewed'           => ['مجدد','renewed','تم التجديد'],
            'in_progress'       => ['قيد التنفيذ','in progress','processing','جار العمل','جاري التنفيذ'],
            'archived'          => ['مؤرشف','archived'],
            'deferred'          => ['مؤجل','deferred','تأجيل'],
            'apologized'        => ['معتذر','apologized','اعتذار'],
            'collection'        => ['تحصيل','collection','under collection'],
            'dispute'           => ['نزاع','dispute','متنازع'],
            'partial'           => ['مدفوع جزئياً','partial','partial paid','جزئي'],
        ];

        foreach ($groups as $key => $values) {
            if (in_array($n, $normalize($values), true)) {
                return match ($key) {
                    'active'           => ['bi-check2-circle',        'text-success'],
                    'pending'          => ['bi-hourglass-split',      'text-warning'],
                    'ended'            => ['bi-flag-fill',            'text-secondary'],
                    'canceled'         => ['bi-slash-circle',         'text-danger'],
                    'late'             => ['bi-exclamation-triangle', 'text-danger'],
                    'review'           => ['bi-eye',                  'text-info'],
                    'draft'            => ['bi-file-earmark',         'text-muted'],
                    'early_settlement' => ['bi-cash-coin',            'text-success'],
                    'rescheduled'      => ['bi-arrow-repeat',         'text-primary'],
                    'suspended'        => ['bi-pause-circle',         'text-warning'],
                    'renewed'          => ['bi-arrow-clockwise',      'text-primary'],
                    'in_progress'      => ['bi-gear-wide-connected',  'text-primary'],
                    'archived'         => ['bi-archive',              'text-muted'],
                    'deferred'         => ['bi-calendar-minus',       'text-warning'],
                    'apologized'       => ['bi-emoji-neutral',        'text-muted'],
                    'collection'       => ['bi-piggy-bank',           'text-info'],
                    'dispute'          => ['bi-exclamation-octagon',  'text-danger'],
                    'partial'          => ['bi-pie-chart',            'text-info'],
                    default            => ['bi-circle',               'text-primary'],
                };
            }
        }

        return ['bi-circle', 'text-primary'];
    };
@endphp

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <p class="text-muted mb-0">{{ __('Review the overall performance of contracts and quickly reach the detailed reports.') }}</p>
        <span class="badge bg-light text-dark">{{ __('Current Investor:') }} {{ $selectedInvestorName }}</span>
    </div>
    <div class="btn-group" role="group">
        <a href="{{ route('contracts.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-table"></i> {{ __('Manage Contracts') }}
        </a>

        <div class="d-flex gap-2">
            <div class="dropdown">
                <button class="btn btn-outline-dark dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    📊 {{ __('Status Reports') }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end text-end shadow mt-2">
                    @foreach($contractStatuses as $status)
                        @php
                            $name = (string) ($status->name ?? '-');
                            [$ic, $cls] = $statusIcon($name);
                        @endphp
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('reports.contracts.status', $status->id) }}">
                                <i class="bi {{ $ic }} {{ $cls }}"></i>
                                <span>{{ $name }}</span>
                            </a>
                        </li>
                    @endforeach
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('reports.contracts.office_outstanding') }}">
                            <i class="bi bi-cash-coin text-warning"></i>
                            <span>{{ __('Office Outstanding Report') }}</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('reports.contracts.without_investor') }}">
                            <i class="bi bi-people text-danger"></i>
                            <span>{{ __('Contracts Without Investor') }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

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
            <div class="col-12 col-md-3">
                <div class="kpi-card p-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon"><i class="bi bi-journal-check fs-4 text-primary"></i></div>
                        <div>
                            <div class="subnote">{{ __('Number of Due Installments') }}</div>
                            <div class="kpi-value fw-bold">{{ number_format($dueCount) }}</div>
                            <div class="subnote">{{ __('This Month') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-5">
                <div class="kpi-card p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="kpi-icon"><i class="bi bi-cash-coin fs-4 text-success"></i></div>
                            <div>
                                <div class="subnote">{{ __('Total Due') }}</div>
                                <div class="kpi-value fw-bold">
                                    {{ number_format($dueSum, 2) }}
                                    <span class="fs-6 text-muted">{{ $currencySymbol }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="subnote">{{ __('Paid') }}</div>
                            <div class="fw-bold">{{ number_format($paidSum, 2) }}</div>
                        </div>
                    </div>
                    <div class="progress bar-8" title="{{ __('Paid Percentage') }}">
                        <div class="progress-bar" style="width: {{ $paidPct2 }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between subnote mt-1">
                        <span>{{ __('Percentage:') }} {{ number_format($paidPct2, 1) }}%</span>
                        <span>{{ __('Remaining:') }} {{ number_format($remainSum, 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="kpi-card p-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon"><i class="bi bi-wallet2 fs-4 text-warning"></i></div>
                        <div>
                            <div class="subnote">{{ __('Remaining to Pay') }}</div>
                            <div class="kpi-value fw-bold">
                                {{ number_format($remainSum, 2) }}
                                <span class="fs-6 text-muted">{{ $currencySymbol }}</span>
                            </div>
                            <div class="subnote">{{ __('Within the Specified Period') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4" dir="rtl">
    <div class="col-12 col-md-2">
        <div class="kpi-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-journal-text fs-4 text-primary"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">{{ __('Total Contracts — Entire System') }}</div>
                    <div class="kpi-value fw-bold">{{ number_format($counts['total'] ?? 0) }}</div>
                    <div class="subnote">{{ __('Not affected by filters') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-2">
        <div class="kpi-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-check2-circle fs-4 text-success"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">
                        {{ __('Active Contracts') }}
                        <span class="hint" data-bs-toggle="tooltip" title="{{ __('Active = All statuses except :ended and :pending', ['ended' => $labels['ended'] ?? '—', 'pending' => $labels['pending'] ?? '—']) }}">
                            <i class="bi bi-info-circle"></i>
                        </span>
                    </div>
                    <div class="kpi-value fw-bold">{{ number_format($counts['active'] ?? 0) }}</div>
                    <div class="subnote">{{ __('Percentage:') }} {{ number_format($pct['active'] ?? 0, 1) }}%</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="progress bar-8">
                    <div class="progress-bar" style="width: {{ $pct['active'] ?? 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-2">
        <div class="kpi-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-hourglass-split fs-4 text-warning"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">
                        {{ __('Pending Contracts') }}
                        <span class="hint" data-bs-toggle="tooltip" title="{{ __('Includes only:') }} {{ $labels['pending'] ?? '—' }}">
                            <i class="bi bi-info-circle"></i>
                        </span>
                    </div>
                    <div class="kpi-value fw-bold">{{ number_format($counts['pending'] ?? 0) }}</div>
                    <div class="subnote">{{ __('Percentage:') }} {{ number_format($pct['pending'] ?? 0, 1) }}%</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="progress bar-8">
                    <div class="progress-bar bg-warning" style="width: {{ $pct['pending'] ?? 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-2">
        <div class="kpi-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-people fs-4 text-danger"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">
                        {{ __('Without Investor') }}
                        <span class="hint" data-bs-toggle="tooltip" title="{{ __('Contracts that are not linked to any investor (from the relationship)') }}">
                            <i class="bi bi-info-circle"></i>
                        </span>
                    </div>
                    <div class="kpi-value fw-bold">{{ number_format($counts['noInvestor'] ?? 0) }}</div>
                    <div class="subnote">{{ __('Percentage:') }} {{ number_format($pct['noInvestor'] ?? 0, 1) }}%</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="progress bar-8">
                    <div class="progress-bar bg-danger" style="width: {{ $pct['noInvestor'] ?? 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-2">
        <div class="kpi-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-flag-fill fs-4 text-secondary"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">
                        {{ __('Ended Contracts') }}
                        <span class="hint" data-bs-toggle="tooltip" title="{{ __('Includes:') }} {{ $labels['ended'] ?? '—' }}">
                            <i class="bi bi-info-circle"></i>
                        </span>
                    </div>
                    <div class="kpi-value fw-bold">{{ number_format($counts['ended'] ?? 0) }}</div>
                    <div class="subnote">{{ __('Percentage:') }} {{ number_format($pct['ended'] ?? 0, 1) }}%</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="progress bar-8">
                    <div class="progress-bar bg-secondary" style="width: {{ $pct['ended'] ?? 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el, {container: 'body'}));
    });
</script>
@endpush
