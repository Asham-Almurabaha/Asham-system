@extends('layouts.master')

@section('title', 'عرض بيانات المستثمر')

@push('styles')
<style>
    .investor-reports-dropdown .dropdown-submenu + .dropdown-submenu {
        margin-top: .25rem;
    }

    .investor-reports-dropdown .report-submenu-toggle {
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
    }

    .investor-reports-dropdown .report-submenu-toggle .report-submenu-chevron {
        transition: transform .2s ease;
    }

    .investor-reports-dropdown .report-submenu-toggle[aria-expanded="true"] .report-submenu-chevron {
        transform: rotate(180deg);
    }

    .investor-reports-dropdown .dropdown-submenu-menu {
        padding: .25rem 0 .5rem;
    }

    .investor-reports-dropdown .dropdown-submenu-menu .dropdown-item {
        font-weight: 500;
        font-size: .95rem;
        padding-top: .35rem;
        padding-bottom: .35rem;
        padding-inline-start: 1.75rem;
        padding-inline-end: 1rem;
    }

    .investor-reports-dropdown .dropdown-divider {
        margin: .35rem 0;
    }
</style>
@endpush

@section('content')
<div class="container py-3" dir="rtl">

    {{-- Bootstrap Icons (لو مش مضافة في الـ layout) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    @php
        // ====== Fallbacks آمنة ======
        $currencySymbol    = $currencySymbol    ?? 'ر.س';

        $contractsTotal    = (int)($contractsTotal  ?? 0);
        $contractsEnded    = (int)($contractsEnded  ?? 0);
        $contractsActive   = (int)($contractsActive ?? max($contractsTotal - $contractsEnded, 0));

        $activePct         = isset($activePct) ? (float)$activePct : ($contractsTotal ? round($contractsActive/$contractsTotal*100,1) : 0);
        $endedPct          = isset($endedPct)  ? (float)$endedPct  : ($contractsTotal ? round($contractsEnded/$contractsTotal*100,1)  : 0);

        // مجاميع "نشِط"
        $totalCapitalShare = (float)($totalCapitalShare ?? 0);
        $totalProfitGross  = (float)($totalProfitGross  ?? 0);
        $totalOfficeCut    = (float)($totalOfficeCut    ?? 0);
        $totalProfitNet    = (float)($totalProfitNet    ?? ($totalProfitGross - $totalOfficeCut));

        // نصيب المستثمر من مدفوعات العميل تناسبياً (Pro-Rata)
        $totalPaidPortionToInvestor = (float)($totalPaidPortionToInvestor ?? 0);

        // المتبقي على العملاء لصالح المستثمر
        $totalRemainingOnCustomers  = (float)($totalRemainingOnCustomers  ?? round(($totalCapitalShare + $totalProfitNet) - $totalPaidPortionToInvestor, 2));

        // مجاميع "كل العقود" (نشِط + منتهي)
        $totalCapitalShareAll = (float)($totalCapitalShareAll ?? 0);
        $totalProfitGrossAll  = (float)($totalProfitGrossAll  ?? 0);
        $totalOfficeCutAll    = (float)($totalOfficeCutAll    ?? 0);
        $totalProfitNetAll    = (float)($totalProfitNetAll    ?? ($totalProfitGrossAll - $totalOfficeCutAll));

        $officeProfitCollectedAll = (float) ($officeProfitCollectedAll ?? 0);
        $officeProfitCollectedActive = (float) ($officeProfitCollectedActive ?? 0);
        $officeProfitRemainingAll = (float) ($officeProfitRemainingAll ?? max(0, round($totalOfficeCutAll - $officeProfitCollectedAll, 2)));
        $officeProfitCollectionPct = (float) ($officeProfitCollectionPct ?? ($totalOfficeCutAll > 0 ? round(($officeProfitCollectedAll / $totalOfficeCutAll) * 100, 2) : 0));

        $contractBreakdown = $contractBreakdown ?? [];
        $liquidity         = isset($liquidity) ? (float)$liquidity : 0.0;
        $liquiditySummaryData = (array) ($liquiditySummary ?? []);
        $liquidityTotalIn  = (float) ($liquiditySummaryData['in'] ?? 0);
        $liquidityTotalOut = (float) ($liquiditySummaryData['out'] ?? 0);

        $statusMetricsCollection = collect($contractStatusMetrics ?? [])
            ->map(function ($row) {
                return [
                    'id'    => (int) ($row['id'] ?? 0),
                    'name'  => (string) ($row['name'] ?? '—'),
                    'count' => (int) ($row['count'] ?? 0),
                    'pct'   => isset($row['pct']) ? (float) $row['pct'] : 0.0,
                ];
            })
            ->filter(fn ($row) => $row['count'] > 0)
            ->sortByDesc('count');

        $statusTotal = (int) ($contractStatusTotal ?? $contractsTotal ?? 0);
        $investorId = (int) ($investor->id ?? 0);

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
                    'منتهي بمطالبة'  => ['bi-file-earmark-exclamation','text-danger'],
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
                logger()->warning('Unknown contract status icon mapping (investor view)', ['status' => $name]);
            }

            return ['bi-question-circle', 'text-muted'];
        };

        $statusKpiCards = $statusMetricsCollection
            ->map(function ($statusRow) use ($statusIcon, $investorId) {
                $statusId = (int) ($statusRow['id'] ?? 0);
                $name = (string) ($statusRow['name'] ?? '—');
                [$iconName, $colorClass] = $statusIcon($name);
                $count = (int) ($statusRow['count'] ?? 0);
                $pct = (float) ($statusRow['pct'] ?? 0.0);

                if ($count <= 0) {
                    return null;
                }

                $barClass = 'bg-primary';
                if (str_contains($colorClass, 'text-success')) {
                    $barClass = 'bg-success';
                } elseif (str_contains($colorClass, 'text-danger')) {
                    $barClass = 'bg-danger';
                } elseif (str_contains($colorClass, 'text-warning')) {
                    $barClass = 'bg-warning';
                } elseif (str_contains($colorClass, 'text-secondary')) {
                    $barClass = 'bg-secondary';
                } elseif (str_contains($colorClass, 'text-info')) {
                    $barClass = 'bg-info';
                }

                $card = [
                    'col_class'  => 'col-12 col-sm-6 col-lg-4 col-xl-3',
                    'icon'       => 'bi ' . $iconName,
                    'icon_class' => trim('fs-4 ' . $colorClass),
                    'title'      => $name,
                    'value'      => number_format($count),
                    'value_class'=> trim('fw-bold ' . $colorClass),
                    'meta'       => [
                        ['text' => 'النسبة: ' . number_format($pct, 2) . '%'],
                    ],
                    'progress'   => [
                        'value'     => $pct,
                        'bar_class' => $barClass,
                    ],
                ];

                if ($statusId > 0 && $investorId > 0) {
                    $card['actions'] = [
                        [
                            'url'    => route('contracts.index', [
                                'status'      => $statusId,
                                'investor_id' => $investorId,
                            ]),
                            'icon'   => 'bi bi-arrow-up-right-square',
                            'title'  => 'عرض العقود',
                            'target' => '_blank',
                            'rel'    => 'noopener noreferrer',
                        ],
                    ];
                }

                return $card;
            })
            ->filter()
            ->values()
            ->all();

        $zakatData = is_array($zakat ?? null) ? $zakat : [];
        $zakatAmount = (float)($zakatData['amount'] ?? 0);
        $zakatBase = isset($zakatData['base']) ? (float)$zakatData['base'] : ($liquidity + $totalRemainingOnCustomers);
        $zakatRatePct = isset($zakatData['rate_pct']) ? (float)$zakatData['rate_pct'] : 2.5;
        $zakatStartDate = $zakatData['start_date'] ?? null;
        $zakatLastEntryDate = $zakatData['last_entry_date'] ?? null;
        $zakatDaysSince = $zakatData['days_since'] ?? null;
        $zakatStartSource = $zakatData['start_source'] ?? null;
        $zakatBreakdown = is_array($zakatData['base_breakdown'] ?? null) ? $zakatData['base_breakdown'] : [];
        $zakatBreakdown['liquidity'] = (float)($zakatBreakdown['liquidity'] ?? $liquidity);
        $zakatBreakdown['remaining'] = (float)($zakatBreakdown['remaining'] ?? $totalRemainingOnCustomers);
        $zakatCycleDays = (int)($zakatData['cycle_days'] ?? 354);
        $zakatStartDateFormatted = $zakatStartDate instanceof \Illuminate\Support\Carbon
            ? $zakatStartDate->format('Y-m-d')
            : ($zakatStartDate ? (string) $zakatStartDate : null);
        $zakatLastEntryDateFormatted = $zakatLastEntryDate instanceof \Illuminate\Support\Carbon
            ? $zakatLastEntryDate->format('Y-m-d')
            : ($zakatLastEntryDate ? (string) $zakatLastEntryDate : null);
        $zakatDueDate = $zakatData['due_date'] ?? null;
        $zakatDueDateFormatted = $zakatDueDate instanceof \Illuminate\Support\Carbon
            ? $zakatDueDate->format('Y-m-d')
            : ($zakatDueDate ? (string) $zakatDueDate : null);
        $zakatDaysUntilDueRaw = $zakatData['days_until_due'] ?? null;
        $zakatDaysUntilDueRaw = is_numeric($zakatDaysUntilDueRaw) ? (int) $zakatDaysUntilDueRaw : null;
        $zakatIsDue = (bool) ($zakatData['is_due'] ?? ($zakatDaysUntilDueRaw !== null && $zakatDaysUntilDueRaw <= 0));
        $zakatDaysRemaining = (!is_null($zakatDaysUntilDueRaw) && $zakatDaysUntilDueRaw > 0)
            ? $zakatDaysUntilDueRaw
            : null;
        $zakatDaysOverdue = $zakatData['days_overdue'] ?? null;
        if (!is_null($zakatDaysOverdue)) {
            $zakatDaysOverdue = is_numeric($zakatDaysOverdue) ? (int) $zakatDaysOverdue : null;
        }
        if (is_null($zakatDaysOverdue) && !is_null($zakatDaysUntilDueRaw) && $zakatDaysUntilDueRaw < 0) {
            $zakatDaysOverdue = abs((int) $zakatDaysUntilDueRaw);
        }

        // ====== ملخص الأقساط الشهري لهذا المستثمر ======
        $monthly   = (array)($installmentsMonthly ?? []);
        $totals    = (array)($monthly['totals'] ?? []);
        $dueSum    = (float)($totals['due'] ?? 0);
        $paidSum   = (float)($totals['paid'] ?? 0);
        $remainSum = (float)($totals['remaining'] ?? max($dueSum - $paidSum, 0));
        $dueCount  = (int)  ($totals['count'] ?? 0);
        $paidPct2  = $dueSum > 0 ? round(($paidSum / $dueSum) * 100, 1) : 0;

        $monthLabel       = (string)($monthly['month_label'] ?? now()->format('Y-m'));
        $excludedStatuses = (array)($monthly['excluded_status_names'] ?? ['مؤجل','معتذر']);
        $excludedStatusesTx = count($excludedStatuses) ? implode('، ', $excludedStatuses) : '—';

        $mVal = (int)($monthly['month'] ?? now()->month);
        $yVal = (int)($monthly['year']  ?? now()->year);

        // لعرض الصور داخل التفاصيل
        $hasIdCard   = !empty($investor->id_card_image);
        $hasContract = !empty($investor->contract_image);
    @endphp

    

    {{-- ====== HERO ====== --}}
    <div class="profile-hero mb-3">
        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar">
                    {{ mb_strtoupper(mb_substr($investor->name ?? '؟', 0, 1)) }}
                </div>
                <div>
                    <h3 class="mb-0 fw-bold fs-2 text-dark hover-primary">{{ $investor->name }}</h3>
                    <div class="small text-muted-2 mt-1 d-flex flex-wrap gap-1">
                        <span class="chip"><i class="bi bi-badge-ad me-1"></i>{{ optional($investor->title)->name ?? '—' }}</span>
                        <span class="chip"><i class="bi bi-flag me-1"></i>{{ optional($investor->nationality)->name ?? '—' }}</span>
                        <span class="chip"><i class="bi bi-hash me-1"></i>{{ __('ID:') }} {{ $investor->id }}</span>
                    </div>
                </div>
            </div>
            <div class="mini-actions d-flex flex-wrap gap-2">
                {{-- <x-button.action href="{{ route('investors.edit', $investor) }}" variant="primary">
                    <i class="bi bi-pencil-square me-1"></i> تعديل
                </x-button.action> --}}
                
                 {{-- ✅ Dropdown للتقارير --}}
                <div class="btn-group" data-bs-auto-close="outside">
                    <x-button.action type="button" variant="dark" :outline="true" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        📊 التقارير
                    </x-button.action>
                    <ul class="dropdown-menu dropdown-menu-end text-end investor-reports-dropdown" id="investorReportsDropdown">
                        <li>
                            <a class="dropdown-item" href="{{ route('investors.statement.statement', $investor) }}" target="_blank" rel="noopener">
                                📄 تقرير جرد المستثمر
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="dropdown-submenu">
                            <x-button.action
                                type="button"
                                :unstyled="true"
                                class="dropdown-item report-submenu-toggle"
                                data-report-submenu-toggle="true"
                                data-bs-target="#investorReportsDeposits"
                                aria-expanded="false"
                                aria-controls="investorReportsDeposits"
                            >
                                <span>💰 تقارير الإيداعات</span>
                                <i class="bi bi-chevron-down report-submenu-chevron"></i>
                            </x-button.action>
                            <div class="collapse dropdown-submenu-menu" id="investorReportsDeposits">
                                <a class="dropdown-item ps-4" href="{{ route('investors.deposits.deposits', $investor) }}" target="_blank" rel="noopener">
                                    💰 جرد الإيداعات
                                </a>
                                <a class="dropdown-item ps-4" href="{{ route('investors.deposits.ledger', $investor) }}" target="_blank" rel="noopener">
                                    📥 جرد الإيداعات (قيود المستثمر)
                                </a>
                                <a class="dropdown-item ps-4" href="{{ route('investors.deposits.installments', $investor) }}" target="_blank" rel="noopener">
                                    💳 جرد إيداعات سداد قسط
                                </a>
                            </div>
                        </li>
                        <li class="dropdown-submenu">
                            <x-button.action
                                type="button"
                                :unstyled="true"
                                class="dropdown-item report-submenu-toggle"
                                data-report-submenu-toggle="true"
                                data-bs-target="#investorReportsWithdrawals"
                                aria-expanded="false"
                                aria-controls="investorReportsWithdrawals"
                            >
                                <span>💸 تقارير المسحوبات</span>
                                <i class="bi bi-chevron-down report-submenu-chevron"></i>
                            </x-button.action>
                            <div class="collapse dropdown-submenu-menu" id="investorReportsWithdrawals">
                                <a class="dropdown-item ps-4" href="{{ route('investors.withdrawals.withdrawals', $investor) }}" target="_blank" rel="noopener">
                                    💸 جرد المسحوبات
                                </a>
                                <a class="dropdown-item ps-4" href="{{ route('investors.withdrawals.ledger', $investor) }}" target="_blank" rel="noopener">
                                    🏧 جرد المسحوبات (قيود المستثمر)
                                </a>
                                <a class="dropdown-item ps-4" href="{{ route('investors.withdrawals.add-contract', $investor) }}" target="_blank" rel="noopener">
                                    🧾 جرد المسحوبات لحالة إضافة عقد
                                </a>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('investors.transactions.transactions', $investor) }}" target="_blank" rel="noopener">
                                🔄 جرد إيداعات / مسحوبات
                            </a>
                        </li>
                    </ul>
                </div>

                <x-button.action href="{{ route('investors.index') }}" variant="secondary" :outline="true">
                    <i class="bi bi-arrow-right-circle me-1"></i> العودة للقائمة
                </x-button.action>

            </div>
        </div>
    </div>

    {{-- ====== KPIs العقود الأساسية ====== --}}
    {{-- <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-files fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">إجمالي العقود المشاركة</div>
                </div>
                <div class="fs-2 fw-bold">{{ number_format($contractsTotal) }}</div>
                <div class="stat-sub">جميع العقود المرتبطة بالمستثمر</div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-person-check fs-5 text-success"></i></div>
                    <div class="fw-bold text-muted">العقود النشطة</div>
                </div>
                <div class="fs-2 fw-bold text-pos">{{ number_format($contractsActive) }}</div>
                <div class="stat-sub">النسبة: {{ number_format($activePct,1) }}%</div>
                <div class="progress bar-8 mt-2"><div class="progress-bar" style="width: {{ $activePct }}%"></div></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-archive fs-5 text-danger"></i></div>
                    <div class="fw-bold text-muted">العقود المنتهية</div>
                </div>
                <div class="fs-2 fw-bold text-neg">{{ number_format($contractsEnded) }}</div>
                <div class="stat-sub">النسبة: {{ number_format($endedPct,1) }}%</div>
                <div class="progress bar-8 mt-2"><div class="progress-bar bg-danger" style="width: {{ $endedPct }}%"></div></div>
            </div>
        </div>
    </div> --}}

     @if(!empty($statusKpiCards))
        <div class="card shadow-sm mb-4" dir="rtl">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <h6 class="mb-1">توزيع حالات عقود المستثمر</h6>
                    <div class="small text-muted">النسب محسوبة من إجمالي العقود المرتبطة بهذا المستثمر</div>
                </div>
                <div class="small text-muted text-nowrap">إجمالي العقود: {{ number_format($statusTotal) }}</div>
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


    {{-- ====== ملخص أقساط هذا الشهر (لـ {{ $investor->name }}) ====== --}}
    <div class="card shadow-soft mb-4">
        <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="section-title">@lang('Monthly Installments Summary') <span class="text-muted">({{ $monthLabel }})</span></div>
                <span class="stat-sub"><i class="bi bi-filter"></i> @lang('Excludes statuses:') {{ $excludedStatusesTx }}</span>
            </div>
            {{-- اختيار سريع للشهر/السنة (يحافظ على الـquerystring) --}}
            <form action="{{ route('investors.show', $investor) }}" method="GET" class="d-flex align-items-center gap-2">
                @foreach(request()->except(['m','y','page']) as $k => $v)
                    @if(is_array($v))
                        @foreach($v as $vv)
                            <input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endif
                @endforeach
                <input type="number" name="m" min="1" max="12" class="form-control form-control-sm" style="width:86px" value="{{ request('m', $mVal) }}" placeholder="شهر">
                <input type="number" name="y" min="2000" max="2100" class="form-control form-control-sm" style="width:92px" value="{{ request('y', $yVal) }}" placeholder="سنة">
                <x-button.action type="submit" variant="primary" :outline="true" size="sm">تحديث</x-button.action>
            </form>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <div class="kpi-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="kpi-icon"><i class="bi bi-journal-check fs-4 text-primary"></i></div>
                            <div>
                                <div class="stat-sub">@lang('Number of Due Installments')</div>
                                <div class="fs-2 fw-bold">{{ number_format($dueCount) }}</div>
                                <div class="stat-sub">@lang('Count')</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-5">
                    <div class="kpi-card p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="kpi-icon"><i class="bi bi-cash-coin fs-4 text-success"></i></div>
                                <div>
                                    <div class="stat-sub">@lang('Unpaid Amount This Month')</div>
                                    <div class="fs-2 fw-bold">{{ number_format($dueSum, 2) }} <span class="fs-6 text-muted">{{ $currencySymbol }}</span></div>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="stat-sub">مدفوع</div>
                                <div class="fw-bold">{{ number_format($paidSum,2) }}</div>
                            </div>
                        </div>
                        <div class="progress bar-8" title="نسبة المدفوع">
                            <div class="progress-bar" style="width: {{ $paidPct2 }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between stat-sub mt-1">
                            <span>@lang('Paid Percentage'): {{ number_format($paidPct2,1) }}%</span>
                            <span>@lang('Remaining:') {{ number_format($remainSum,2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="kpi-card p-3 h-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="kpi-icon"><i class="bi bi-wallet2 fs-4 text-warning"></i></div>
                            <div>
                                <div class="stat-sub">@lang('Remaining to Pay')</div>
                                <div class="fs-2 fw-bold">{{ number_format($remainSum, 2) }} <span class="fs-6 text-muted">{{ $currencySymbol }}</span></div>
                                <div class="stat-sub">ضمن الفترة المحددة</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    

   

    {{-- ====== كروت "المتبقي على العملاء" + "سيولة المستثمر" ====== --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-cash-coin fs-5 text-success"></i></div>
                    <div class="fw-bold text-muted">سيولة المستثمر</div>
                </div>
                <div class="fs-2 fw-bold {{ $liquidity >= 0 ? 'text-pos' : 'text-neg' }}">
                    {{ number_format($liquidity, 2) }} <span class="fs-6 text-muted">{{ $currencySymbol }}</span>
                </div>
                <div class="stat-sub">{{ $liquidity >= 0 ? 'صافي الرصيد المتاح' : 'صافي الرصيد المستحق' }}</div>
                <div class="stat-sub small text-muted">
                    {{ __('investors::investors.Total Deposits') }}:
                    <span class="fw-semibold">{{ number_format($liquidityTotalIn, 2) }}</span>
                    <span class="text-muted">{{ $currencySymbol }}</span>
                </div>
                <div class="stat-sub small text-muted">
                    {{ __('investors::investors.Total Withdrawals') }}:
                    <span class="fw-semibold">{{ number_format($liquidityTotalOut, 2) }}</span>
                    <span class="text-muted">{{ $currencySymbol }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-cash-stack fs-5 text-warning"></i></div>
                    <div class="fw-bold text-muted">المتبقي على العملاء</div>
                </div>
                <div class="fs-2 fw-bold {{ $totalRemainingOnCustomers >= 0 ? 'text-pos' : 'text-neg' }}">
                    {{ number_format($totalRemainingOnCustomers, 2) }} <span class="fs-6 text-muted">{{ $currencySymbol }}</span>
                </div>
                <div class="stat-sub">
                    = رأس المال + (ربح المستثمر − نصيب المكتب) −
                    <span title="نصيب المستثمر من مدفوعات العميل تناسبياً">المدفوع</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ====== بطاقة زكاة المال ====== --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-6 col-xl-5">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-moon-stars fs-5 text-info"></i></div>
                    <div class="fw-bold text-muted">زكاة المال التقديرية ({{ number_format($zakatRatePct, 2) }}%)</div>
                </div>
                <div class="fs-2 fw-bold text-primary">
                    {{ number_format($zakatAmount, 2) }} <span class="fs-6 text-muted">{{ $currencySymbol }}</span>
                </div>
                <div class="stat-sub">
                    الأصول الخاضعة للزكاة = {{ number_format($zakatBreakdown['liquidity'], 2) }} + {{ number_format($zakatBreakdown['remaining'], 2) }} = {{ number_format($zakatBase, 2) }} {{ $currencySymbol }}
                </div>
                <div class="small text-muted mt-2">نسبة الزكاة المطبقة: {{ number_format($zakatRatePct, 2) }}%</div>
                <div class="small text-muted mt-3">
                    @if($zakatStartSource === 'ledger' && $zakatLastEntryDateFormatted)
                        آخر قيد زكاة المال: {{ $zakatLastEntryDateFormatted }}
                    @elseif($zakatStartSource === 'investment_start' && $zakatStartDateFormatted)
                        لم تُسجل قيود زكاة بعد — يُحتسب من بدء الاستثمار {{ $zakatStartDateFormatted }}
                    @elseif($zakatStartSource === 'created_at' && $zakatStartDateFormatted)
                        لم تُسجل قيود زكاة بعد — يُحتسب من تاريخ إنشاء المستثمر {{ $zakatStartDateFormatted }}
                    @elseif($zakatStartDateFormatted)
                        يُحتسب اعتباراً من {{ $zakatStartDateFormatted }}
                    @else
                        لا تتوفر بيانات تاريخ لاحتساب الزكاة.
                    @endif
                    @if(!is_null($zakatDaysSince))
                        <span class="d-block">عدد الأيام منذ ذلك التاريخ: {{ number_format($zakatDaysSince) }} يوم</span>
                    @endif
                    @if($zakatIsDue)
                        <span class="d-block text-danger fw-bold mt-1">
                            @if(!is_null($zakatDaysOverdue) && $zakatDaysOverdue > 0)
                                حان موعد إخراج الزكاة منذ {{ number_format($zakatDaysOverdue) }} يوم
                            @else
                                اليوم هو موعد إخراج الزكاة
                            @endif
                            @if($zakatDueDateFormatted)
                                ({{ $zakatDueDateFormatted }})
                            @endif
                        </span>
                    @elseif(!is_null($zakatDaysRemaining))
                        <span class="d-block mt-1">
                            المتبقي على موعد الزكاة: {{ number_format($zakatDaysRemaining) }} يوم
                            @if($zakatDueDateFormatted)
                                ({{ $zakatDueDateFormatted }})
                            @endif
                        </span>
                    @elseif($zakatDueDateFormatted)
                        <span class="d-block mt-1">موعد الزكاة القادم: {{ $zakatDueDateFormatted }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ====== إجماليات كل العقود (نشِط + منتهي) ====== --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="kpi-icon"><i class="bi bi-wallet2 fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">حصة رأس المال (كل العقود)</div>
                </div>
                <div class="fs-2 fw-bold">
                    {{ number_format($totalCapitalShareAll, 2) }} <span class="fs-6 text-muted">{{ $currencySymbol }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="kpi-icon"><i class="bi bi-graph-up fs-5 text-success"></i></div>
                    <div class="fw-bold text-muted">ربح المستثمر (كل العقود)</div>
                </div>
                <div class="fs-2 fw-bold">
                    {{ number_format($totalProfitGrossAll, 2) }} <span class="fs-6 text-muted">{{ $currencySymbol }}</span>
                </div>
                <div class="stat-sub">قبل خصم نسبة المكتب</div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="kpi-icon"><i class="bi bi-building fs-5 text-danger"></i></div>
                    <div class="fw-bold text-muted">نصيب المكتب (كل العقود)</div>
                </div>
                <div class="fs-2 fw-bold text-neg">
                    {{ number_format($totalOfficeCutAll, 2) }} <span class="fs-6 text-muted">{{ $currencySymbol }}</span>
                </div>
                <div class="stat-sub">
                    {{ __('reports.Paid from Office Share') }}:
                    {{ number_format($officeProfitCollectedAll, 2) }}
                    <span class="text-muted">{{ $currencySymbol }}</span>
                    <span class="badge bg-light text-dark ms-1">{{ number_format($officeProfitCollectionPct, 2) }}%</span>
                </div>
                <div class="stat-sub">
                    {{ __('reports.Office Share Portion Pending') }}:
                    {{ number_format($officeProfitRemainingAll, 2) }}
                    <span class="text-muted">{{ $currencySymbol }}</span>
                </div>
                @if($officeProfitCollectedActive > 0 && abs($officeProfitCollectedAll - $officeProfitCollectedActive) > 0.005)
                    <div class="stat-sub small text-muted">
                        {{ __('reports.Paid from Office Share') }} ({{ trans('investors::investors.Active Contracts') }}):
                        {{ number_format($officeProfitCollectedActive, 2) }}
                        <span class="text-muted">{{ $currencySymbol }}</span>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="kpi-icon"><i class="bi bi-cash fs-5 text-warning"></i></div>
                    <div class="fw-bold text-muted">ربح المستثمر (كل العقود)</div>
                </div>
                <div class="fs-2 fw-bold">
                    {{ number_format($totalProfitNetAll, 2) }} <span class="fs-6 text-muted">{{ $currencySymbol }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ====== جدول تفصيلي للعقود النشطة ====== --}}
    @if(!empty($contractBreakdown))
        <div class="card border-0 shadow-soft">
            <div class="card-header bg-white fw-bold">تفصيل العقود النشطة</div>
            <div class="card-body p-0">
                <x-table head-class="table-light position-sticky top-0" foot-class="table-light" class="text-center">
                    <x-slot name="head">
                        <tr>
                            <th style="width:60px">#</th>
                            <th>رقم/مُعرّف العقد</th>
                            <th>العميل</th>
                            <th>النسبة %</th>
                            <th>رأس المال</th>
                            <th>ربح المستثمر (إجمالي)</th>
                            <th>نصيب المكتب</th>
                            <th>الربح الصافي</th>
                            <th title="نصيب المستثمر من مدفوعات العميل تناسبياً">المدفوع</th>
                            <th>المتبقي على العملاء</th>
                        </tr>
                    </x-slot>
                    @foreach($contractBreakdown as $i => $r)
                        <tr>
                            <td class="text-muted">{{ $i+1 }}</td>
                            <td>
                                @php
                                    $cid = $r['contract_id'] ?? null;
                                    $cno = $r['contract_number'] ?? null;
                                @endphp
                                @if(!empty($cid))
                                    <a href="{{ route('contracts.show', $cid) }}" class="text-decoration-none text-dark hover-primary fw-bold">
                                        {{ $cno ?: ('#'.$cid) }}
                                    </a>
                                @else
                                    {{ $cno ?: '?' }}
                                @endif
                            </td>
                            <td class="text-start">
                                @php $custId = $r['customer_id'] ?? null; @endphp
                                @if(!empty($custId))
                                    <a href="{{ route('customers.show', $custId) }}" class="text-decoration-none text-dark hover-primary">
                                        {{ $r['customer'] ?? '?' }}
                                    </a>
                                @else
                                    {{ $r['customer'] ?? '?' }}
                                @endif
                            </td>
                            <td dir="ltr">{{ number_format($r['share_percentage'] ?? 0,2) }}</td>
                            <td dir="ltr">{{ number_format($r['share_value'],2) }}</td>
                            <td dir="ltr">{{ number_format($r['profit_gross'],2) }}</td>
                            <td class="text-neg" dir="ltr">
                                {{ number_format($r['office_cut'],2) }}
                                @if(array_key_exists('office_cut_paid', $r))
                                    <div class="small text-muted" dir="ltr">
                                        {{ __('reports.Paid from Office Share') }}:
                                        <span class="fw-semibold">{{ number_format($r['office_cut_paid'], 2) }}</span>
                                    </div>
                                @endif
                            </td>
                            <td dir="ltr">{{ number_format($r['profit_net'],2) }}</td>
                            @php
                                $normalizedStatus = mb_strtolower((string)($r['status_name'] ?? ''), 'UTF-8');
                                $isRaisedStatus = $normalizedStatus === mb_strtolower('مرفوع فيه', 'UTF-8');
                                $paidInstallments = (float)($r['paid_to_investor_from_installments'] ?? 0);
                                $paidClaims = (float)($r['paid_to_investor_from_claims'] ?? 0);
                            @endphp
                            <td dir="ltr">
                                {{ number_format($r['paid_to_investor_from_customer'] ?? 0,2) }}
                                @if($isRaisedStatus)
                                    <div class="small text-muted" dir="ltr">
                                        {{ __('reports.Paid via Installments') }}:
                                        <span class="fw-semibold">{{ number_format($paidInstallments, 2) }}</span>
                                    </div>
                                    <div class="small text-muted" dir="ltr">
                                        {{ __('reports.Paid via Claims') }}:
                                        <span class="fw-semibold">{{ number_format($paidClaims, 2) }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="fw-semibold {{ ($r['remaining_on_customers'] ?? 0) >= 0 ? 'text-pos' : 'text-neg' }}" dir="ltr">
                                {{ number_format($r['remaining_on_customers'] ?? 0,2) }}
                            </td>
                        </tr>
                    @endforeach
                    <x-slot name="footer">
                        <tr>
                            <th colspan="4" class="text-end">الإجماليات:</th>
                            <th dir="ltr">{{ number_format($totalCapitalShare,2) }}</th>
                            <th dir="ltr">{{ number_format($totalProfitGross,2) }}</th>
                            <th class="text-neg" dir="ltr">{{ number_format($totalOfficeCut,2) }}</th>
                            <th dir="ltr">{{ number_format($totalProfitNet,2) }}</th>
                            <th dir="ltr">{{ number_format($totalPaidPortionToInvestor,2) }}</th>
                            <th class="fw-bold {{ $totalRemainingOnCustomers >= 0 ? 'text-pos' : 'text-neg' }}" dir="ltr">
                                {{ number_format($totalRemainingOnCustomers,2) }}
                            </th>
                        </tr>
                    </x-slot>
                </x-table>
            </div>
        </div>
    @endif

    {{-- ====== تفاصيل المستثمر (مع الصور داخل التفاصيل) ====== --}}
    <div class="card shadow-sm mb-3 kpi-card">
        <div class="card-header bg-white fw-bold">بيانات أساسية</div>
        <div class="card-body pt-2">
            <div class="row g-3">

                <div class="col-md-6">
                    <div class="row"><div class="col-5 label-col">الاسم</div><div class="col-7 value-col">{{ $investor->name }}</div></div>
                    <div class="row mt-2">
                        <div class="col-5 label-col">رقم الهوية</div>
                        <div class="col-7 value-col">
                            @if($investor->national_id)
                                <span dir="ltr">{{ $investor->national_id }}</span>
                            @else <span class="text-muted">—</span> @endif
                        </div>
                    </div>
                        <div class="row mt-2"><div class="col-5 label-col">الجنسية</div><div class="col-7 value-col">{{ optional($investor->nationality)->name ?? '—' }}</div></div>
                        <div class="row mt-2"><div class="col-5 label-col">الوظيفة</div><div class="col-7 value-col">{{ optional($investor->title)->name ?? '—' }}</div></div>
                        <div class="row mt-2"><div class="col-5 label-col">{{ __('investors::investors.Office Share %') }}</div><div class="col-7 value-col">{{ number_format((float) ($investor->office_share_percentage ?? 0), 2) }}%</div></div>
                        <div class="row mt-2">
                            <div class="col-5 label-col">{{ __('investors::investors.Investment Start Date') }}</div>
                            <div class="col-7 value-col">
                                @if($investor->investment_start_date)
                                    <span dir="ltr">{{ $investor->investment_start_date->format('Y-m-d') }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                        </div>
                </div>
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-5 label-col">الهاتف</div>
                        <div class="col-7 value-col">
                            @if($investor->phone)
                                <a href="tel:{{ $investor->phone }}" class="text-decoration-none text-dark"><i class="bi bi-telephone me-1"></i>{{ $investor->phone }}</a>
                            @else <span class="text-muted">—</span> @endif
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-5 label-col">البريد الإلكتروني</div>
                        <div class="col-7 value-col">
                            @if($investor->email) <a href="mailto:{{ $investor->email }}" class="text-decoration-none text-dark"><i class="bi bi-envelope me-1"></i>{{ $investor->email }}</a>
                            @else <span class="text-muted">—</span> @endif
                        </div>
                    </div>
                    <div class="row mt-2"><div class="col-5 label-col">العنوان</div><div class="col-7 value-col">{{ $investor->address ?? '—' }}</div></div>
                </div>
            </div>

            {{-- الصور داخل التفاصيل --}}
            <div class="row g-3 mt-2">
                <div class="col-12 col-md-6">
                    <div class="row align-items-start">
                        <div class="col-5 label-col">صورة الهوية</div>
                        <div class="col-7 value-col">
                            @if($hasIdCard)
                                <a href="{{ asset('storage/'.$investor->id_card_image) }}" target="_blank" title="عرض بالحجم الكامل">
                                    <img class="img-thumb" src="{{ asset('storage/'.$investor->id_card_image) }}" alt="صورة الهوية">
                                </a>
                                <div class="small text-muted mt-1">انقر لفتح الصورة في نافذة جديدة</div>
                            @else
                                <span class="text-muted">لا توجد صورة هوية مرفوعة.</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="row align-items-start">
                        <div class="col-5 label-col">صورة العقد</div>
                        <div class="col-7 value-col">
                            @if($hasContract)
                                <a href="{{ asset('storage/'.$investor->contract_image) }}" target="_blank" title="عرض بالحجم الكامل">
                                    <img class="img-thumb" src="{{ asset('storage/'.$investor->contract_image) }}" alt="صورة العقد">
                                </a>
                                <div class="small text-muted mt-1">انقر لفتح الصورة في نافذة جديدة</div>
                            @else
                                <span class="text-muted">لا توجد صورة عقد مرفوعة.</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            {{-- /الصور داخل التفاصيل --}}
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>

// تفعيل Bootstrap Tooltip لو موجود
document.addEventListener('DOMContentLoaded', function () {
    if (window.bootstrap && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el, {container: 'body'});
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const dropdownMenu = document.getElementById('investorReportsDropdown');
    if (!dropdownMenu || !window.bootstrap || !bootstrap.Collapse || !bootstrap.Dropdown) {
        return;
    }

    dropdownMenu.querySelectorAll('[data-report-submenu-toggle]').forEach(function (toggle) {
        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const targetSelector = toggle.getAttribute('data-bs-target');
            if (!targetSelector) {
                return;
            }

            const target = document.querySelector(targetSelector);
            if (!target) {
                return;
            }

            const collapseInstance = bootstrap.Collapse.getOrCreateInstance(target, { toggle: false });
            collapseInstance.toggle();
        });
    });

    dropdownMenu.querySelectorAll('.dropdown-submenu-menu').forEach(function (submenu) {
        submenu.addEventListener('shown.bs.collapse', function () {
            const toggle = dropdownMenu.querySelector('[data-bs-target="#' + submenu.id + '"]');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'true');
            }
        });

        submenu.addEventListener('hidden.bs.collapse', function () {
            const toggle = dropdownMenu.querySelector('[data-bs-target="#' + submenu.id + '"]');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    });

    dropdownMenu.querySelectorAll('a.dropdown-item').forEach(function (link) {
        link.addEventListener('click', function () {
            const btnGroup = dropdownMenu.closest('.btn-group');
            const toggleButton = btnGroup ? btnGroup.querySelector('[data-bs-toggle="dropdown"]') : null;

            if (!toggleButton) {
                return;
            }

            let dropdownInstance = bootstrap.Dropdown.getInstance(toggleButton);
            if (!dropdownInstance) {
                dropdownInstance = new bootstrap.Dropdown(toggleButton);
            }

            dropdownInstance.hide();
        });
    });
});

// إخفاء أي alert تلقائياً
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.style.transition = 'opacity .5s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);
</script>
@endpush
