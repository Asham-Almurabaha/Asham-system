@extends('layouts.master')

@section('title', __('dashboard.Dashboard'))

@section('content')
<div class="container py-4" dir="rtl">

    {{-- Bootstrap Icons (لو مش محمّل في الـ layout) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    

    {{-- ====== شريط اختيار النطاق الزمني ====== --}}
    @php
        $qsBase = request()->except(['from','to','page']);
        $buildUrl = function (?string $from = null, ?string $to = null) use ($qsBase) {
            $q = $qsBase; if ($from) $q['from'] = $from; if ($to) $q['to'] = $to;
            return url()->current() . (empty($q) ? '' : ('?' . http_build_query($q)));
        };

        $todayFrom = \Carbon\Carbon::today()->toDateString();
        $todayTo   = \Carbon\Carbon::today()->toDateString();
        $now       = \Carbon\Carbon::now();
        $monthFrom = $now->copy()->startOfMonth()->toDateString();
        $monthTo   = $now->copy()->endOfMonth()->toDateString();
        $yearFrom  = $now->copy()->startOfYear()->toDateString();
        $yearTo    = $now->copy()->endOfYear()->toDateString();

        $rFrom = request('from'); $rTo = request('to');
        $isToday = ($rFrom === $todayFrom && $rTo === $todayTo);
        $isMonth = ($rFrom === $monthFrom && $rTo === $monthTo);
        $isYear  = ($rFrom === $yearFrom  && $rTo === $yearTo);
        $isAll   = (!request()->filled('from') && !request()->filled('to'));
    @endphp

    <div class="dr-toolbar p-3 mb-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ $buildUrl($todayFrom,$todayTo) }}" class="btn btn-outline-secondary btn-sm btn-range {{ $isToday ? 'active' : '' }}">
                    <i class="bi bi-calendar-day me-1"></i> {{ __('dashboard.Today') }} {!! $isToday ? '<span class="dot"></span>' : '' !!}
                </a>
                <a href="{{ $buildUrl($monthFrom,$monthTo) }}" class="btn btn-outline-secondary btn-sm btn-range {{ $isMonth ? 'active' : '' }}">
                    <i class="bi bi-calendar3 me-1"></i> {{ __('dashboard.This Month') }} {!! $isMonth ? '<span class="dot"></span>' : '' !!}
                </a>
                <a href="{{ $buildUrl($yearFrom,$yearTo) }}" class="btn btn-outline-secondary btn-sm btn-range {{ $isYear ? 'active' : '' }}">
                    <i class="bi bi-calendar2-week me-1"></i> {{ __('dashboard.This Year') }} {!! $isYear ? '<span class="dot"></span>' : '' !!}
                </a>
                <a href="{{ $buildUrl(null,null) }}" class="btn btn-outline-secondary btn-sm btn-range {{ $isAll ? 'active' : '' }}">
                    <i class="bi bi-infinity me-1"></i> {{ __('dashboard.All') }} {!! $isAll ? '<span class="dot"></span>' : '' !!}
                </a>
            </div>

            <div class="sep mx-2 d-none d-md-block"></div>

            {{-- مدى مُخصّص --}}
            <form method="GET" action="{{ url()->current() }}" class="d-flex flex-wrap align-items-end gap-2">
                @foreach($qsBase as $k => $v)
                    @if(is_array($v))
                        @foreach($v as $vv) <input type="hidden" name="{{ $k }}[]" value="{{ e($vv) }}"> @endforeach
                    @else
                        <input type="hidden" name="{{ $k }}" value="{{ e($v) }}">
                    @endif
                @endforeach

                <div class="label">{{ __('dashboard.Custom') }}:</div>
                <div><label class="form-label mb-1 small">{{ __('dashboard.From') }}</label><input type="date" class="form-control form-control-sm js-date" name="from" value="{{ e($rFrom) }}"></div>
                <div><label class="form-label mb-1 small">{{ __('dashboard.To') }}</label><input type="date" class="form-control form-control-sm js-date" name="to" value="{{ e($rTo) }}"></div>
                <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i> {{ __('dashboard.Apply') }}</button>
                <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle me-1"></i> {{ __('dashboard.Clear') }}</a>
            </form>

            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="chip"><i class="bi bi-clock me-1"></i> {{ __('dashboard.Current Range') }}:
                    <strong>{{ $rFrom ? e($rFrom) : '—' }} — {{ $rTo ? e($rTo) : '—' }}</strong>
                </span>
                <span class="text-muted small d-none d-md-inline">{{ __('dashboard.Last Update') }}: {{ now()->format('Y-m-d H:i') }}</span>
            </div>
        </div>
    </div>

    {{-- ====== تطبيع المتغيّرات ====== --}}
    @php
        $invTotals       = (object) ($invTotals      ?? ['net' => 0]);
        $officeTotals    = (object) ($officeTotals   ?? ['net' => 0]);

        $invByInvestor   = collect($invByInvestor    ?? []);
        $statuses        = collect($statuses         ?? []);

        $timeSeries      = (array)  ($timeSeries     ?? ['labels'=>[], 'in'=>[], 'out'=>[], 'net'=>[]]);
        $monthlySeries   = (array)  ($monthlySeries  ?? ['labels'=>[], 'in'=>[], 'out'=>[]]);
        $distribution    = (array)  ($distribution   ?? ['labels'=>['بنوك','خزن'], 'data'=>[0,0]]);

        $banksWithOpen   = collect($banksWithOpen    ?? []);
        $safesWithOpen   = collect($safesWithOpen    ?? []);

        // Totals
        $banksTotal  = isset($distribution['data'][0]) ? (float)$distribution['data'][0] : (float)$banksWithOpen->sum('balance');
        $safesTotal  = isset($distribution['data'][1]) ? (float)$distribution['data'][1] : (float)$safesWithOpen->sum('balance');
        $totalAll    = $banksTotal + $safesTotal;

        // عدد البطاقات المتاح (من الكنترولر إن وُجِد، وإلا 0)
        $cardsAvailable = (int) ($cardsAvailable ?? 0);

        // صافي دخل المكتب = ربح المكتب + فرق البيع + المكاتبة (لو متوفر من الكنترولر أو من officeMetrics، وإلا fallback لصافي القيود)
        $officeNet = isset($officeNet)
            ? (float) $officeNet
            : (isset($officeMetrics)
                ? (float) (
                    ($officeMetrics['profit']['total']   ?? 0) +
                    ($officeMetrics['sales']['total']    ?? 0) +
                    ($officeMetrics['mukataba']['total'] ?? 0)
                  )
                : (float) ($officeTotals->net ?? 0)
              );
    @endphp

    {{-- ====== HERO ====== --}}
    <div class="dashboard-hero mb-3">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-speedometer2 fs-4 text-primary"></i></div>
                <div>
                    <h3 class="mb-1">{{ __('dashboard.Dashboard') }}</h3>
                    <div class="text-muted small">
                        {{ __('dashboard.Data Range') }}:
                        {{--
                        {{ request('from') ? e(request('from')) : '—' }} —
                        {{ request('to') ? e(request('to')) : '—' }}
                        --}}
                        @if(!request()->filled('from') && !request()->filled('to'))
                            {{ __('dashboard.All') }}
                        @else
                            {{ request('from') ? e(request('from')) : __('dashboard.From').': -' }}
                            →
                            {{ request('to') ? e(request('to')) : __('dashboard.To').': -' }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge-chip" data-bs-toggle="tooltip" title="{{ __('dashboard.Total Contracts') }}">
                    <i class="bi bi-files me-1"></i> {{ __('dashboard.Total Contracts') }}: {{ number_format($contractsTotal ?? 0) }}
                </span>
                <span class="badge-chip" data-bs-toggle="tooltip" title="{{ __('dashboard.Net = In - Out') }}">
                    <i class="bi bi-people me-1"></i> {{ __('dashboard.Investors Liquidity Net') }}: {{ number_format(($invTotals->net ?? 0), 2) }}
                </span>
                <span class="badge-chip" data-bs-toggle="tooltip" title="{{ __('dashboard.Profit + Sales Difference + Mukataba') }}">
                    <i class="bi bi-building me-1"></i> {{ __('dashboard.Office Income Net') }}: {{ number_format($officeNet, 2) }}
                </span>
            </div>
        </div>
    </div>

    {{-- ====== KPIs الأساسية الأربعة ====== --}}
    <div class="row g-3">
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-credit-card-2-front fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">{{ __('dashboard.Available Cards Count') }}</div>
                </div>
                <div class="kpi-value fw-bold">{{ number_format($cardsAvailable) }}</div>
                <div class="small text-muted mt-2">{{ __('dashboard.Total Stock Available') }}</div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-bank fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">{{ __('dashboard.Total Banks') }}</div>
                </div>
                <div class="kpi-value fw-bold text-pos">{{ number_format($banksTotal, 2) }}</div>
                <div class="small text-muted mt-2">{{ __('dashboard.Estimated Balance') }}</div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-safe2 fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">{{ __('dashboard.Total Cash') }}</div>
                </div>
                <div class="kpi-value fw-bold text-pos">{{ number_format($safesTotal, 2) }}</div>
                <div class="small text-muted mt-2">{{ __('dashboard.Estimated Balance') }}</div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-graph-up-arrow fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">{{ __('dashboard.Grand Total') }}</div>
                </div>
                @php $totalClass = $totalAll >= 0 ? 'text-pos' : 'text-neg'; @endphp
                <div class="kpi-value fw-bold {{ $totalClass }}">{{ number_format($totalAll, 2) }}</div>
                <div class="small text-muted mt-2">{{ __('dashboard.Banks + Cash') }}</div>
            </div>
        </div>
    </div>

    {{-- ====== KPIs إضافية (بدون المساس بالأساسية) ====== --}}
    @php
        // مفاتيح دخل المكتب التفصيلية (ربح/فرق بيع/مكاتبة)
        $officeProfit   = (float)($officeMetrics['profit']['total']   ?? 0);
        $salesDiff      = (float)($officeMetrics['sales']['total']    ?? 0);
        $mukatabaTotal  = (float)($officeMetrics['mukataba']['total'] ?? 0);

        // إجماليات المستثمرين
        $invIn          = (float)($invTotals->inflow  ?? 0);
        $invOut         = (float)($invTotals->outflow ?? 0);

        // أعداد الحسابات
        $banksCount     = ($banksWithOpen ?? collect())->count();
        $safesCount     = ($safesWithOpen ?? collect())->count();

        // صافي الحركة للحسابات
        $banksIn        = (float)($banksWithOpen->sum('in')  ?? 0);
        $banksOut       = (float)($banksWithOpen->sum('out') ?? 0);
        $banksNet       = $banksIn - $banksOut;

        $safesIn        = (float)($safesWithOpen->sum('in')  ?? 0);
        $safesOut       = (float)($safesWithOpen->sum('out') ?? 0);
        $safesNet       = $safesIn - $safesOut;

        // إجمالي الافتتاحي
        $openingBanks   = (float)($banksWithOpen->sum('opening_balance') ?? 0);
        $openingSafes   = (float)($safesWithOpen->sum('opening_balance') ?? 0);
        $openingTotal   = $openingBanks + $openingSafes;

        // إجمالي داخل/خارج للفترة الحالية (تتأثر بالنطاق)
        $periodIn       = (float)array_sum($timeSeries['in']  ?? []);
        $periodOut      = (float)array_sum($timeSeries['out'] ?? []);
        $periodNet      = $periodIn - $periodOut;
    @endphp

    <div class="row g-3 mt-1">
        {{-- صافي دخل المكتب + تفصيله --}}
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-building fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">{{ __('dashboard.Office Income Net') }}</div>
                </div>
                <div class="kpi-value fw-bold {{ $officeNet>=0?'text-pos':'text-neg' }}">{{ number_format($officeNet, 2) }}</div>
                <div class="small text-muted mt-2">{{ __('dashboard.Profit + Sales Difference + Mukataba') }}</div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-cash-coin fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">{{ __('dashboard.Office Profit') }}</div>
                </div>
                <div class="kpi-value fw-bold {{ $officeProfit>=0?'text-pos':'text-neg' }}">{{ number_format($officeProfit, 2) }}</div>
                <div class="small text-muted mt-2">{{ __('dashboard.Total Profit') }}</div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-arrow-left-right fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">{{ __('dashboard.Sales Difference') }}</div>
                </div>
                <div class="kpi-value fw-bold {{ $salesDiff>=0?'text-pos':'text-neg' }}">{{ number_format($salesDiff, 2) }}</div>
                <div class="small text-muted mt-2">{{ __('dashboard.Total Sales Difference') }}</div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-journal-text fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">{{ __('dashboard.Mukataba') }}</div>
                </div>
                <div class="kpi-value fw-bold {{ $mukatabaTotal>=0?'text-pos':'text-neg' }}">{{ number_format($mukatabaTotal, 2) }}</div>
                <div class="small text-muted mt-2">{{ __('dashboard.Total Mukataba') }}</div>
            </div>
        </div>
    </div>

    
    
    {{-- ====== الحالات + توزيع ====== --}}
    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="section-card card h-100 border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('dashboard.Contract Statuses Distribution') }}</span>
                    <span class="small text-muted"><i class="bi bi-info-circle" data-bs-toggle="tooltip"
                        title="{{ __('dashboard.Percentages calculated from current total contracts') }}"></i></span>
                </div>
                <div class="card-body p-0">
                    @if(($statuses->count() ?? 0) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($statuses as $s)
                                <div class="list-group-item status-row">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="fw-semibold">{{ $s['name'] }}</div>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="badge bg-secondary">{{ number_format($s['count']) }}</span>
                                            <span class="text-muted small">{{ $s['pct'] }}%</span>
                                        </div>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $s['pct'] }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-3 text-muted">{{ __('dashboard.No data for statuses.') }}</div>
                    @endif
                </div>
                <div class="card-footer text-end small text-muted">
                    {{ __('dashboard.Total Statuses') }}: {{ number_format($contractsTotal ?? 0) }}
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="section-card card h-100 border-0 chart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('dashboard.Statuses Chart (Doughnut)') }}</span>
                    <span class="small text-muted"><i class="bi bi-graph-up" data-bs-toggle="tooltip"
                        title="{{ __('dashboard.The chart reflects the same distribution shown on the right') }}"></i></span>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ====== تحليلات إضافية ====== --}}
    @php
        // أفضل الأرصدة (أعلى 7) باستخدام الحقول الجديدة
        $topBalances = $banksWithOpen->map(function($b){
            $b = (object) $b;
            $opening=(float)($b->opening_balance ?? 0);
            $in=(float)($b->in ?? 0);
            $out=(float)($b->out ?? 0);
            return ['label'=>$b->name ?? ('#'.$b->id), 'bal'=>$opening + ($in-$out)];
        })->merge(
            $safesWithOpen->map(function($s){
                $s = (object) $s;
                $opening=(float)($s->opening_balance ?? 0);
                $in=(float)($s->in ?? 0);
                $out=(float)($s->out ?? 0);
                return ['label'=>$s->name ?? ('#'.$s->id), 'bal'=>$opening + ($in-$out)];
            })
        )->sortByDesc('bal')->take(7)->values();
        $topBalLabels = $topBalances->pluck('label');
        $topBalData   = $topBalances->pluck('bal');
    @endphp

    <div class="row g-3 mt-1">
        <div class="col-xl-6">
            <div class="section-card card border-0 chart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('dashboard.Daily Cash Flow') }}</span>
                    <span class="small text-muted"><i class="bi bi-calendar-range" data-bs-toggle="tooltip"
                        title="{{ __('dashboard.Daily view: in/out/net') }}"></i></span>
                </div>
                <div class="card-body">
                    <canvas id="cashLineChart" height="240"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="section-card card border-0 chart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('dashboard.Monthly Flow (In/Out)') }}</span>
                    <span class="small text-muted"><i class="bi bi-bar-chart-steps" data-bs-toggle="tooltip"
                        title="{{ __('dashboard.Stacked values for each month') }}"></i></span>
                </div>
                <div class="card-body">
                    <canvas id="monthlyBarChart" height="240"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-xl-4">
            <div class="section-card card border-0 chart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('dashboard.Balance Distribution (Banks/Safes)') }}</span>
                    <span class="small text-muted"><i class="bi bi-pie-chart" data-bs-toggle="tooltip"
                        title="{{ __('dashboard.Distribution of estimated total balances') }}"></i></span>
                </div>
                <div class="card-body">
                    <canvas id="acctDistChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="section-card card border-0 chart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('dashboard.Top Balances on Accounts') }}</span>
                    <span class="small text-muted"><i class="bi bi-arrow-up-right-circle" data-bs-toggle="tooltip"
                        title="{{ __('dashboard.Top 7 balances from banks and safes') }}"></i></span>
                </div>
                <div class="card-body">
                    <canvas id="topBalancesChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ====== أعلى المستثمرين (سيولة فقط) ====== --}}
    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="section-card card border-0">
                @php
                    $rowsRaw = collect($invByInvestor ?? []);

                    $liquid = $rowsRaw
                        ->map(function ($r) {
                            $in  = (float) ($r->inflow  ?? 0);
                            $out = (float) ($r->outflow ?? 0);
                            $net = $in - $out; // الصافي = داخل − خارج
                            $r->inflow  = $in;
                            $r->outflow = $out;
                            $r->net     = $net;
                            return $r;
                        })
                        ->filter(fn($r) => ($r->inflow > 0 || $r->outflow > 0) && $r->net > 0) // فقط سيولة موجبة
                        ->sortByDesc('net')
                        ->take(10)
                        ->values();

                    $liquidTotalNet = (float) $liquid->sum('net');
                @endphp

                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('dashboard.Top 10 Investors (Positive Net Liquidity)') }}</span>
                    <span class="text-muted small" data-bs-toggle="tooltip"
                        title="{{ __('dashboard.Net = In - Out. Internal transfers are neutral and do not affect the net.') }}">
                        {{ __('dashboard.Total Net Displayed') }}: {{ number_format($liquidTotalNet, 2) }}
                    </span>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-center">
                                    <th style="width:60px;">#</th>
                                    <th class="text-start">{{ __('dashboard.Investor') }}</th>
                                    <th>{{ __('dashboard.In') }}</th>
                                    <th>{{ __('dashboard.Out') }}</th>
                                    <th>{{ __('dashboard.Net') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($liquid as $idx => $row)
                                @php
                                    $in  = (float) ($row->inflow  ?? 0);
                                    $out = (float) ($row->outflow ?? 0);
                                    $net = (float) ($row->net     ?? ($in - $out));
                                @endphp
                                <tr class="text-center">
                                    <td>{{ $idx + 1 }}</td>
                                    <td class="text-start fw-semibold">{{ $row->name ?? ('#'.($row->id ?? '')) }}</td>
                                    <td class="text-pos fw-semibold">{{ number_format($in, 2) }}</td>
                                    <td class="text-neg fw-semibold">{{ number_format($out, 2) }}</td>
                                    <td class="fw-bold text-pos">{{ number_format($net, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted text-center py-4">
                                        {{ __('dashboard.No data for investors with positive liquidity in the current range.') }}
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if(($liquid->count() ?? 0) > 0)
                <div class="card-footer small text-muted">
                    {{ __('dashboard.Net = In - Out. Internal transfers are neutral and do not affect the net.') }}
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ====== حالة كل حساب (بنوك + خزن) ====== --}}
    <div class="row g-3 mt-1">
        {{-- البنوك --}}
        <div class="col-lg-6">
            <div class="section-card card border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('dashboard.Bank Accounts Status') }}</span>
                    <span class="small text-muted" data-bs-toggle="tooltip" title="{{ __('dashboard.Estimated Balance = Opening Balance + In - Out') }}">
                        <i class="bi bi-calculator"></i>
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-center">
                                    <th class="text-start">{{ __('dashboard.Account') }}</th>
                                    <th>{{ __('dashboard.Opening') }}</th>
                                    <th>{{ __('dashboard.In') }}</th>
                                    <th>{{ __('dashboard.Out') }}</th>
                                    <th>{{ __('dashboard.Movement Net') }}</th>
                                    <th>{{ __('dashboard.Estimated Balance') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @php $banks = ($banksWithOpen ?? collect())->map(fn($b) => (object)$b); @endphp
                            @forelse($banks as $b)
                                @php
                                    $opening = (float)($b->opening_balance ?? 0);
                                    $in      = (float)($b->in ?? 0);
                                    $out     = (float)($b->out ?? 0);
                                    $net     = $in - $out;
                                    $bal     = $opening + $net;
                                    $netClass = $net >= 0 ? 'text-pos' : 'text-neg';
                                    $balClass = $bal >= 0 ? 'text-pos' : 'text-neg';
                                @endphp
                                <tr class="text-center">
                                    <td class="text-start"><i class="bi bi-bank"></i> {{ $b->name ?? ('#'.$b->id) }}</td>
                                    <td>{{ number_format($opening, 2) }}</td>
                                    <td class="text-pos fw-semibold">{{ number_format($in, 2) }}</td>
                                    <td class="text-neg fw-semibold">{{ number_format($out, 2) }}</td>
                                    <td class="fw-bold {{ $netClass }}">{{ number_format($net, 2) }}</td>
                                    <td class="fw-bold {{ $balClass }}">{{ number_format($bal, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted text-center py-4">{{ __('dashboard.No bank accounts.') }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer small text-muted">
                    {{ __('dashboard.The numbers are calculated from the ledger, and include internal transfers according to their direction (in/out).') }}
                </div>
            </div>
        </div>

        {{-- الخزن --}}
        <div class="col-lg-6">
            <div class="section-card card border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('dashboard.Safes Status') }}</span>
                    <span class="small text-muted" data-bs-toggle="tooltip" title="{{ __('dashboard.Estimated Balance = Opening Balance + In - Out') }}">
                        <i class="bi bi-safe2"></i>
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-center">
                                    <th class="text-start">{{ __('dashboard.Safe') }}</th>
                                    <th>{{ __('dashboard.Opening') }}</th>
                                    <th>{{ __('dashboard.In') }}</th>
                                    <th>{{ __('dashboard.Out') }}</th>
                                    <th>{{ __('dashboard.Movement Net') }}</th>
                                    <th>{{ __('dashboard.Estimated Balance') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @php $safes = ($safesWithOpen ?? collect())->map(fn($s) => (object)$s); @endphp
                            @forelse($safes as $s)
                                @php
                                    $opening = (float)($s->opening_balance ?? 0);
                                    $in      = (float)($s->in ?? 0);
                                    $out     = (float)($s->out ?? 0);
                                    $net     = $in - $out;
                                    $bal     = $opening + $net;
                                    $netClass = $net >= 0 ? 'text-pos' : 'text-neg';
                                    $balClass = $bal >= 0 ? 'text-pos' : 'text-neg';
                                @endphp
                                <tr class="text-center">
                                    <td class="text-start"><i class="bi bi-safe2"></i> {{ $s->name ?? ('#'.$s->id) }}</td>
                                    <td>{{ number_format($opening, 2) }}</td>
                                    <td class="text-pos fw-semibold">{{ number_format($in, 2) }}</td>
                                    <td class="text-neg fw-semibold">{{ number_format($out, 2) }}</td>
                                    <td class="fw-bold {{ $netClass }}">{{ number_format($net, 2) }}</td>
                                    <td class="fw-bold {{ $balClass }}">{{ number_format($bal, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted text-center py-4">{{ __('dashboard.No safes.') }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer small text-muted">
                    {{ __('dashboard.The numbers are calculated from the ledger, and include internal transfers according to their direction (in/out).') }}
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ====== Scripts ====== --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Translation variables for charts
    const chartTranslations = {
        in: '{{ __("dashboard.In") }}',
        out: '{{ __("dashboard.Out") }}',
        net: '{{ __("dashboard.Net") }}',
        estimatedBalance: '{{ __("dashboard.Estimated Balance") }}',
        banks: '{{ __("dashboard.Banks") }}',
        safes: '{{ __("dashboard.Safes") }}',
        noDailyData: '{{ __("dashboard.No daily data.") }}',
        noMonthlyData: '{{ __("dashboard.No monthly data.") }}',
        noDistributionData: '{{ __("dashboard.No distribution data.") }}',
        noSufficientBalances: '{{ __("dashboard.No sufficient balances to display.") }}',
        noDataForChart: '{{ __("dashboard.No data for the chart.") }}'
    };
</script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    // Tooltips
    const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el, {container: 'body'}));

    // Doughnut (statuses)
    (function(){
        const el = document.getElementById('statusChart');
        if (!el) return;
        const labels = @json(($chartLabels ?? collect())->values());
        const data   = @json(($chartData ?? collect())->values());
        if (!labels.length || !data.length) {
            el.parentElement.innerHTML = '<div class="text-muted">' + chartTranslations.noDataForChart + '</div>';
            return;
        }
        new Chart(el, {
            type: 'doughnut',
            data: { labels, datasets: [{ data, borderWidth: 1 }] },
            options: {
                responsive: true, cutout: '58%',
                plugins: { legend: { position:'bottom', labels:{ usePointStyle:true, boxWidth:10 } }, tooltip:{ rtl:true } },
                animation: { animateScale:true, animateRotate:true }
            }
        });
    })();

    // Line: Daily cashflow
    (function(){
        const el = document.getElementById('cashLineChart');
        if (!el) return;
        const labels = @json(($timeSeries['labels'] ?? ($timeSeries['labels'] ?? [])));
        const inflow = @json(($timeSeries['in']     ?? ($timeSeries['in']     ?? [])));
        const outflow= @json(($timeSeries['out']    ?? ($timeSeries['out']    ?? [])));
        const net    = @json(($timeSeries['net']    ?? ($timeSeries['net']    ?? [])));
        if (!labels.length) { el.parentElement.innerHTML = '<div class="text-muted">' + chartTranslations.noDailyData + '</div>'; return; }
        new Chart(el, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: chartTranslations.in, data: inflow, tension:.3, borderWidth:2, fill:false },
                    { label: chartTranslations.out, data: outflow, tension:.3, borderWidth:2, fill:false },
                    { label: chartTranslations.net, data: net, tension:.3, borderWidth:2, fill:false }
                ]
            },
            options: {
                responsive:true,
                interaction:{ mode:'index', intersect:false },
                plugins:{ legend:{ position:'bottom' }, tooltip:{ rtl:true } },
                scales:{ y:{ beginAtZero:true } }
            }
        });
    })();

    // Bar (stacked): Monthly in/out
    (function(){
        const el = document.getElementById('monthlyBarChart');
        if (!el) return;
        const labels = @json(($monthlySeries['labels'] ?? ($monthlySeries['labels'] ?? [])));
        const inflow = @json(($monthlySeries['in']     ?? ($monthlySeries['in']     ?? [])));
        const outflow= @json(($monthlySeries['out']    ?? ($monthlySeries['out']    ?? [])));
        if (!labels.length) { el.parentElement.innerHTML = '<div class="text-muted">' + chartTranslations.noMonthlyData + '</div>'; return; }
        new Chart(el, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: chartTranslations.in, data: inflow, borderWidth:1, stack:'s' },
                    { label: chartTranslations.out, data: outflow, borderWidth:1, stack:'s' }
                ]
            },
            options: {
                responsive:true,
                plugins:{ legend:{ position:'bottom' }, tooltip:{ rtl:true } },
                scales:{ x:{ stacked:true }, y:{ stacked:true, beginAtZero:true } }
            }
        });
    })();

    // Doughnut: Account distribution (banks vs safes)
    (function(){
        const el = document.getElementById('acctDistChart');
        if (!el) return;
        let labels = @json(($distribution['labels'] ?? []));
        if (!labels.length) { labels = ['{{ __('dashboard.Banks') }}','{{ __('dashboard.Safes') }}']; }
        const data   = @json(($distribution['data']   ?? ($distribution['data']   ?? [0,0])));
        if (!data.length) { el.parentElement.innerHTML = '<div class="text-muted">{{ __('dashboard.No distribution data.') }}</div>'; return; }
        new Chart(el, {
            type: 'doughnut',
            data: { labels, datasets:[{ data, borderWidth:1 }] },
            options: { responsive:true, cutout:'58%', plugins:{ legend:{ position:'bottom' }, tooltip:{ rtl:true } } }
        });
    })();

    // Horizontal Bar: Top balances
    (function(){
        const el = document.getElementById('topBalancesChart');
        if (!el) return;
        const labels = @json($topBalLabels ?? []);
        const data   = @json($topBalData ?? []);
        if (!labels.length) { el.parentElement.innerHTML = '<div class="text-muted">{{ __('dashboard.No sufficient balances to display.') }}</div>'; return; }
        new Chart(el, {
            type: 'bar',
            data: { labels, datasets: [{ label:'{{ __('dashboard.Estimated Balance') }}', data, borderWidth:1 }] },
            options: {
                indexAxis: 'y',
                responsive:true,
                plugins:{ legend:{ display:false }, tooltip:{ rtl:true } },
                scales:{ x:{ beginAtZero:true } }
            }
        });
    })();
});
</script>
@endsection

