@extends('layouts.master')

@section('title', 'لوحة التحكم')

@section('content')
<div class="container py-4" dir="rtl">

    {{-- Bootstrap Icons (لو مش محمّل في الـ layout) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        :root {
            --card-radius: 1.2rem;
            --soft-shadow: 0 6px 18px rgba(0,0,0,.06);
            --soft-shadow-hover: 0 10px 24px rgba(0,0,0,.08);
        }
        .dashboard-hero {
            border-radius: var(--card-radius);
            background: linear-gradient(135deg, #e9f5ff 0%, #f7faff 100%);
            padding: 1.25rem 1.5rem;
            border: 1px solid #eef2f7;
        }
        .kpi-card {
            border: 1px solid #eef2f7;
            border-radius: var(--card-radius);
            box-shadow: var(--soft-shadow);
            transition: .2s ease;
            height: 100%;
        }
        .kpi-card:hover { box-shadow: var(--soft-shadow-hover); transform: translateY(-2px); }
        .kpi-icon {
            width: 48px; height: 48px;
            border-radius: .85rem;
            display: grid; place-items: center;
            background: #f4f6fb;
        }
        .kpi-value { font-size: 2rem; line-height: 1; }
        .section-card {
            border: 1px solid #eef2f7;
            border-radius: var(--card-radius);
            box-shadow: var(--soft-shadow);
            overflow: hidden;
        }
        .section-card .card-header {
            background: #fbfcfe; border-bottom: 1px solid #eef2f7;
            font-weight: 700;
        }
        .status-row + .status-row { border-top: 1px dashed #eef2f7; }
        .badge-chip {
            background: #f1f4f9; color: #3c4a5d; border-radius: 999px; padding: .35rem .7rem; font-weight: 600;
        }
        .text-pos { color: #16a34a !important; }
        .text-neg { color: #dc2626 !important; }
        .help-item { margin-bottom: .35rem; }
        .help-item i { width: 1.2rem; display: inline-block; }
        .table thead th { position: sticky; top: 0; background: #f8f9fb; z-index: 1; }
        .chart-card canvas { max-height: 280px; }

        /* ===== شريط النطاق الزمني الاحترافي ===== */
        .dr-toolbar{border:1px solid #eef2f7;border-radius:1.1rem;box-shadow:var(--soft-shadow);background:#fff}
        .dr-toolbar .btn-range{border-radius:999px}
        .dr-toolbar .btn-range.active{background:#0d6efd;color:#fff;border-color:#0d6efd}
        .dr-toolbar .btn-range .dot{display:inline-block;width:.5rem;height:.5rem;border-radius:50%;margin-inline-start:.4rem;background:currentColor;opacity:.6}
        .dr-toolbar .chip{background:#f1f4f9;border-radius:999px;padding:.35rem .7rem;font-weight:600;color:#475569}
        .dr-toolbar .label{font-size:.9rem;color:#6b7280}
        .dr-toolbar .sep{width:1px;background:#eef2f7;height:32px}
    </style>

    {{-- ====== شريط اختيار النطاق الزمني (بديل عن كار্ড التاريخ) ====== --}}
    @php
        // هنحافظ على كل البرامترات ما عدا from/to/page
        $qsBase = request()->except(['from','to','page']);

        $buildUrl = function (?string $from = null, ?string $to = null) use ($qsBase) {
            $q = $qsBase;
            if ($from) $q['from'] = $from;
            if ($to)   $q['to']   = $to;
            return url()->current() . (empty($q) ? '' : ('?' . http_build_query($q)));
        };

        $todayFrom = \Carbon\Carbon::today()->toDateString();
        $todayTo   = \Carbon\Carbon::today()->toDateString();

        $now       = \Carbon\Carbon::now();
        $monthFrom = $now->copy()->startOfMonth()->toDateString();
        $monthTo   = $now->copy()->endOfMonth()->toDateString();

        $yearFrom  = $now->copy()->startOfYear()->toDateString();
        $yearTo    = $now->copy()->endOfYear()->toDateString();

        $rFrom = request('from');
        $rTo   = request('to');

        $isToday = ($rFrom === $todayFrom && $rTo === $todayTo);
        $isMonth = ($rFrom === $monthFrom && $rTo === $monthTo);
        $isYear  = ($rFrom === $yearFrom  && $rTo === $yearTo);
        $isAll   = (!request()->filled('from') && !request()->filled('to'));
    @endphp

    <div class="dr-toolbar p-3 mb-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ $buildUrl($todayFrom,$todayTo) }}"
                   class="btn btn-outline-secondary btn-sm btn-range {{ $isToday ? 'active' : '' }}">
                    <i class="bi bi-calendar-day me-1"></i> اليوم {!! $isToday ? '<span class="dot"></span>' : '' !!}
                </a>

                <a href="{{ $buildUrl($monthFrom,$monthTo) }}"
                   class="btn btn-outline-secondary btn-sm btn-range {{ $isMonth ? 'active' : '' }}">
                    <i class="bi bi-calendar3 me-1"></i> هذا الشهر {!! $isMonth ? '<span class="dot"></span>' : '' !!}
                </a>

                <a href="{{ $buildUrl($yearFrom,$yearTo) }}"
                   class="btn btn-outline-secondary btn-sm btn-range {{ $isYear ? 'active' : '' }}">
                    <i class="bi bi-calendar2-week me-1"></i> هذه السنة {!! $isYear ? '<span class="dot"></span>' : '' !!}
                </a>

                <a href="{{ $buildUrl(null,null) }}"
                   class="btn btn-outline-secondary btn-sm btn-range {{ $isAll ? 'active' : '' }}">
                    <i class="bi bi-infinity me-1"></i> الكل {!! $isAll ? '<span class="dot"></span>' : '' !!}
                </a>
            </div>

            <div class="sep mx-2 d-none d-md-block"></div>

            {{-- مدى مُخصّص --}}
            <form method="GET" action="{{ url()->current() }}" class="d-flex flex-wrap align-items-end gap-2">
                {{-- الحفاظ على باقي معلمات الرابط --}}
                @foreach($qsBase as $k => $v)
                    @if(is_array($v))
                        @foreach($v as $vv)
                            <input type="hidden" name="{{ $k }}[]" value="{{ e($vv) }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $k }}" value="{{ e($v) }}">
                    @endif
                @endforeach

                <div class="label">مُخصّص:</div>
                <div>
                    <label class="form-label mb-1 small">من</label>
                    <input type="date" class="form-control form-control-sm js-date" name="from" value="{{ e($rFrom) }}">
                </div>
                <div>
                    <label class="form-label mb-1 small">إلى</label>
                    <input type="date" class="form-control form-control-sm js-date" name="to" value="{{ e($rTo) }}">
                </div>
                <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i> تطبيق</button>
                <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle me-1"></i> مسح</a>
            </form>

            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="chip">
                    <i class="bi bi-clock me-1"></i>
                    النطاق الحالي:
                    <strong>{{ $rFrom ? e($rFrom) : '—' }} — {{ $rTo ? e($rTo) : '—' }}</strong>
                </span>
                <span class="text-muted small d-none d-md-inline">آخر تحديث: {{ now()->format('Y-m-d H:i') }}</span>
            </div>
        </div>
    </div>

    {{-- ====== تطبيع المتغيّرات لتفادي أخطاء null ====== --}}
    @php
        $invTotals       = (object) ($invTotals      ?? ['net' => 0]);
        $officeTotals    = (object) ($officeTotals   ?? ['net' => 0]);

        $invByInvestor   = collect($invByInvestor    ?? []);
        $statuses        = collect($statuses         ?? []);

        $timeSeries      = (array)  ($timeSeries     ?? ['labels'=>[], 'in'=>[], 'out'=>[], 'net'=>[]]);
        $monthlySeries   = (array)  ($monthlySeries  ?? ['labels'=>[], 'in'=>[], 'out'=>[]]);
        $distribution    = (array)  ($distribution   ?? ['labels'=>['بنوك','خزن'], 'data'=>[0,0]]);

        $bankAccountsSummary = collect($bankAccountsSummary ?? []);
        $safesSummary        = collect($safesSummary       ?? []);
    @endphp

    {{-- ====== HERO ====== --}}
    <div class="dashboard-hero mb-3">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-speedometer2 fs-4 text-primary"></i></div>
                <div>
                    <h3 class="mb-1">لوحة التحكم</h3>
                    <div class="text-muted small">
                        نطاق البيانات:
                        {{ request('from') ? e(request('from')) : '—' }}
                        —
                        {{ request('to') ? e(request('to')) : '—' }}
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge-chip" data-bs-toggle="tooltip" title="إجمالي عدد العقود في النظام">
                    <i class="bi bi-files me-1"></i> إجمالي العقود: {{ number_format($contractsTotal ?? 0) }}
                </span>
                <span class="badge-chip" data-bs-toggle="tooltip" title="الصافي = داخل − خارج">
                    <i class="bi bi-people me-1"></i> صافي سيولة المستثمرين: {{ number_format(($invTotals->net ?? 0), 2) }}
                </span>
                <span class="badge-chip" data-bs-toggle="tooltip" title="الصافي = داخل − خارج">
                    <i class="bi bi-building me-1"></i> صافي سيولة المكتب: {{ number_format(($officeTotals->net ?? 0), 2) }}
                </span>
            </div>
        </div>
    </div>

    {{-- ====== KPIs إضافية ====== --}}
    @php
        $invNet  = (float)($invTotals->net ?? 0);
        $offNet  = (float)($officeTotals->net ?? 0);

        // تقديرات آمنة لو مش متوفرة من الكنترولر:
        $kpi = (object) [
            'entries_count'    => (int)($entriesCount ?? ($contractsTotal ?? 0)),
            'avg_amount'       => (float)($avgAmount ?? 0),
            'active_investors' => (int)($activeInvestors ?? $invByInvestor->count()),
        ];
    @endphp

    <div class="row g-3">
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-collection fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">عدد القيود</div>
                </div>
                <div class="kpi-value fw-bold">{{ number_format($kpi->entries_count) }}</div>
                <div class="small text-muted mt-2">إجمالي قيود دفتر القيود</div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-cash-coin fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">متوسط القيد</div>
                </div>
                <div class="kpi-value fw-bold">{{ number_format($kpi->avg_amount, 2) }}</div>
                <div class="small text-muted mt-2">قيمة متوسّطة لكل عملية</div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-people fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">مستثمرون نشطون</div>
                </div>
                <div class="kpi-value fw-bold">{{ number_format($kpi->active_investors) }}</div>
                <div class="small text-muted mt-2">عدد المستثمرين ذوي الحركة</div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-graph-up-arrow fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">صافي إجمالي</div>
                </div>
                <div class="kpi-value fw-bold {{ ($invNet+$offNet)>=0 ? 'text-pos':'text-neg' }}">{{ number_format($invNet + $offNet, 2) }}</div>
                <div class="small text-muted mt-2">مستثمرين + مكتب</div>
            </div>
        </div>
    </div>

    {{-- ====== الحالات + توزيع ====== --}}
    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="section-card card h-100 border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>توزيع حالات العقود</span>
                    <span class="small text-muted"><i class="bi bi-info-circle" data-bs-toggle="tooltip"
                        title="النِّسب محسوبة من إجمالي العقود الحالي"></i></span>
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
                        <div class="p-3 text-muted">لا توجد بيانات للحالات.</div>
                    @endif
                </div>
                <div class="card-footer text-end small text-muted">
                    إجمالي الحالات: {{ number_format($contractsTotal ?? 0) }}
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="section-card card h-100 border-0 chart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>مخطط الحالات (Doughnut)</span>
                    <span class="small text-muted"><i class="bi bi-graph-up" data-bs-toggle="tooltip"
                        title="المخطط يعكس نفس التوزيع المعروض يمينًا"></i></span>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ====== تحليلات إضافية ====== --}}
    @php
        // أفضل الأرصدة من ملخص البنوك والخزن (أعلى 7)
        $topBalances = $bankAccountsSummary->map(function($b){
            $opening=(float)($b->opening_balance ?? 0); $in=(float)($b->inflow ?? 0); $out=(float)($b->outflow ?? 0);
            return ['label'=>$b->name ?? ('#'.$b->id), 'bal'=>$opening + ($in-$out)];
        })->merge(
            $safesSummary->map(function($s){
                $opening=(float)($s->opening_balance ?? 0); $in=(float)($s->inflow ?? 0); $out=(float)($s->outflow ?? 0);
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
                    <span>التدفق النقدي اليومي</span>
                    <span class="small text-muted"><i class="bi bi-calendar-range" data-bs-toggle="tooltip"
                        title="عرض يومي: داخل/خارج/صافي"></i></span>
                </div>
                <div class="card-body">
                    <canvas id="cashLineChart" height="240"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="section-card card border-0 chart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>التدفق الشهري (داخل/خارج)</span>
                    <span class="small text-muted"><i class="bi bi-bar-chart-steps" data-bs-toggle="tooltip"
                        title="قِيَم مكدّسة لكل شهر"></i></span>
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
                    <span>توزيع الرصيد (بنوك/خزن)</span>
                    <span class="small text-muted"><i class="bi bi-pie-chart" data-bs-toggle="tooltip"
                        title="توزيع إجمالي الأرصدة التقديرية"></i></span>
                </div>
                <div class="card-body">
                    <canvas id="acctDistChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="section-card card border-0 chart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>أفضل الأرصدة على الحسابات</span>
                    <span class="small text-muted"><i class="bi bi-arrow-up-right-circle" data-bs-toggle="tooltip"
                        title="أعلى 7 أرصدة من البنوك والخزن"></i></span>
                </div>
                <div class="card-body">
                    <canvas id="topBalancesChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ====== أعلى المستثمرين ====== --}}
    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="section-card card border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>أعلى 10 مستثمرين حسب الصافي</span>
                    <span class="text-muted small" data-bs-toggle="tooltip"
                          title="الصافي لكل مستثمر = داخل − خارج (التحويلات محايدة)">
                        إجمالي صافي: {{ number_format(($invTotals->net ?? 0), 2) }}
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-center">
                                    <th style="width:60px;">#</th>
                                    <th class="text-start">المستثمر</th>
                                    <th>داخل</th>
                                    <th>خارج</th>
                                    <th>صافي</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($invByInvestor as $idx => $row)
                                @php
                                    $rowNet = (float)($row->net ?? 0);
                                    $rowClass = $rowNet >= 0 ? 'text-pos' : 'text-neg';
                                @endphp
                                <tr class="text-center">
                                    <td>{{ $idx + 1 }}</td>
                                    <td class="text-start fw-semibold">{{ $row->name }}</td>
                                    <td>{{ number_format(($row->inflow ?? 0), 2) }}</td>
                                    <td>{{ number_format(($row->outflow ?? 0), 2) }}</td>
                                    <td class="fw-bold {{ $rowClass }}">{{ number_format($rowNet, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center py-4">لا توجد بيانات للمستثمرين.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if(($invByInvestor->count() ?? 0) > 0)
                <div class="card-footer small text-muted">
                    * الأرقام أعلاه مبنية على اتجاه القيود (داخل/خارج). التحويلات الداخلية محايدة في الصافي العام.
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
                    <span>حالة الحسابات البنكية</span>
                    <span class="small text-muted" data-bs-toggle="tooltip"
                          title="الرصيد التقديري = رصيد افتتاحي + داخل − خارج">
                        <i class="bi bi-calculator"></i>
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-center">
                                    <th class="text-start">الحساب</th>
                                    <th>افتتاحي</th>
                                    <th>داخل</th>
                                    <th>خارج</th>
                                    <th>صافي حركة</th>
                                    <th>رصيد تقديري</th>
                                </tr>
                            </thead>
                            <tbody>
                            @php $banks = $bankAccountsSummary ?? collect(); @endphp
                            @forelse($banks as $b)
                                @php
                                    $opening = (float)($b->opening_balance ?? 0);
                                    $in      = (float)($b->inflow ?? 0);
                                    $out     = (float)($b->outflow ?? 0);
                                    $net     = $in - $out;
                                    $bal     = $opening + $net;
                                    $netClass = $net >= 0 ? 'text-pos' : 'text-neg';
                                    $balClass = $bal >= 0 ? 'text-pos' : 'text-neg';
                                @endphp
                                <tr class="text-center">
                                    <td class="text-start">
                                        <i class="bi bi-bank"></i>
                                        {{ $b->name ?? ('#'.$b->id) }}
                                    </td>
                                    <td>{{ number_format($opening, 2) }}</td>
                                    <td class="text-pos fw-semibold">{{ number_format($in, 2) }}</td>
                                    <td class="text-neg fw-semibold">{{ number_format($out, 2) }}</td>
                                    <td class="fw-bold {{ $netClass }}">{{ number_format($net, 2) }}</td>
                                    <td class="fw-bold {{ $balClass }}">{{ number_format($bal, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted text-center py-4">لا توجد حسابات بنكية.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer small text-muted">
                    * الأرقام محسوبة من دفتر القيود، وتشمل التحويلات الداخلية حسب اتجاهها (داخل/خارج).
                </div>
            </div>
        </div>

        {{-- الخزن --}}
        <div class="col-lg-6">
            <div class="section-card card border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>حالة الخزن</span>
                    <span class="small text-muted" data-bs-toggle="tooltip"
                          title="الرصيد التقديري = رصيد افتتاحي + داخل − خارج">
                        <i class="bi bi-safe2"></i>
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-center">
                                    <th class="text-start">الخزنة</th>
                                    <th>افتتاحي</th>
                                    <th>داخل</th>
                                    <th>خارج</th>
                                    <th>صافي حركة</th>
                                    <th>رصيد تقديري</th>
                                </tr>
                            </thead>
                            <tbody>
                            @php $safes = $safesSummary ?? collect(); @endphp
                            @forelse($safes as $s)
                                @php
                                    $opening = (float)($s->opening_balance ?? 0);
                                    $in      = (float)($s->inflow ?? 0);
                                    $out     = (float)($s->outflow ?? 0);
                                    $net     = $in - $out;
                                    $bal     = $opening + $net;
                                    $netClass = $net >= 0 ? 'text-pos' : 'text-neg';
                                    $balClass = $bal >= 0 ? 'text-pos' : 'text-neg';
                                @endphp
                                <tr class="text-center">
                                    <td class="text-start">
                                        <i class="bi bi-safe2"></i>
                                        {{ $s->name ?? ('#'.$s->id) }}
                                    </td>
                                    <td>{{ number_format($opening, 2) }}</td>
                                    <td class="text-pos fw-semibold">{{ number_format($in, 2) }}</td>
                                    <td class="text-neg fw-semibold">{{ number_format($out, 2) }}</td>
                                    <td class="fw-bold {{ $netClass }}">{{ number_format($net, 2) }}</td>
                                    <td class="fw-bold {{ $balClass }}">{{ number_format($bal, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted text-center py-4">لا توجد خزن.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer small text-muted">
                    * الأرقام محسوبة من دفتر القيود، وتشمل التحويلات الداخلية حسب اتجاهها (داخل/خارج).
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ====== Scripts ====== --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            el.parentElement.innerHTML = '<div class="text-muted">لا توجد بيانات للمخطط.</div>';
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
        if (!labels.length) { el.parentElement.innerHTML = '<div class="text-muted">لا توجد بيانات يومية.</div>'; return; }
        new Chart(el, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: 'داخل', data: inflow, tension:.3, borderWidth:2, fill:false },
                    { label: 'خارج', data: outflow, tension:.3, borderWidth:2, fill:false },
                    { label: 'صافي', data: net, tension:.3, borderWidth:2, fill:false }
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
        if (!labels.length) { el.parentElement.innerHTML = '<div class="text-muted">لا توجد بيانات شهرية.</div>'; return; }
        new Chart(el, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label:'داخل', data: inflow, borderWidth:1, stack:'s' },
                    { label:'خارج', data: outflow, borderWidth:1, stack:'s' }
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
        const labels = @json(($distribution['labels'] ?? ($distribution['labels'] ?? ['بنوك','خزن'])));
        const data   = @json(($distribution['data']   ?? ($distribution['data']   ?? [0,0])));
        if (!data.length) { el.parentElement.innerHTML = '<div class="text-muted">لا توجد بيانات توزيع.</div>'; return; }
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
        if (!labels.length) { el.parentElement.innerHTML = '<div class="text-muted">لا توجد أرصدة كافية للعرض.</div>'; return; }
        new Chart(el, {
            type: 'bar',
            data: { labels, datasets: [{ label:'رصيد تقديري', data, borderWidth:1 }] },
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
