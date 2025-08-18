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
    </style>

    {{-- ====== فلاتر التاريخ (اختياري) ====== --}}
    <div class="card border-0 shadow-sm mb-3">
        <form method="GET" action="{{ url()->current() }}" class="card-body row gy-2 gx-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label mb-1">من</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm js-date">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1">إلى</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm js-date">
            </div>
            <div class="col-12 col-md-3">
                <button class="btn btn-primary btn-sm">تحديث</button>
                <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm">مسح</a>
            </div>
            <div class="col-12 col-md-5 text-end small text-muted">
                آخر تحديث: {{ now()->format('Y-m-d H:i') }}
            </div>
        </form>
    </div>

    {{-- ====== حسابات تفصيلية + KPIs إضافية ====== --}}
    @php
        use Illuminate\Support\Facades\DB;
        use Illuminate\Support\Facades\Schema;

        $from = request('from'); $to = request('to');

        // ===== معاملات المكتب =====
        $statusMap = \App\Models\TransactionStatus::whereIn('name', ['ربح المكتب','فرق البيع','المكاتبة'])
            ->pluck('id','name');

        $officeProfitId = $statusMap['ربح المكتب']    ?? null;
        $saleDiffId     = $statusMap['فرق البيع'] ?? null;
        $mukatabaId     = $statusMap['المكاتبة']      ?? null;

        $baseOT = \App\Models\OfficeTransaction::query();
        if ($from) $baseOT->whereDate('transaction_date', '>=', $from);
        if ($to)   $baseOT->whereDate('transaction_date', '<=', $to);

        $officeProfitTotal = $officeProfitId ? (clone $baseOT)->where('status_id', $officeProfitId)->sum('amount') : 0.0;
        $saleDiffTotal     = $saleDiffId     ? (clone $baseOT)->where('status_id', $saleDiffId)->sum('amount') : 0.0;
        $mukatabaTotal     = $mukatabaId     ? (clone $baseOT)->where('status_id', $mukatabaId)->sum('amount') : 0.0;

        $officeRecent = (clone $baseOT)->with('investor')
            ->orderByDesc('transaction_date')->orderByDesc('id')
            ->limit(10)->get();

        $monthRows = (clone $baseOT)
            ->when($officeProfitId || $saleDiffId, function($q) use($officeProfitId, $saleDiffId){
                $ids = array_values(array_filter([$officeProfitId, $saleDiffId], fn($v)=>!is_null($v)));
                return $q->whereIn('status_id', $ids);
            })
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as ym, status_id, SUM(amount) as total")
            ->groupBy('ym','status_id')
            ->orderBy('ym')
            ->get();

        $months = $monthRows->pluck('ym')->unique()->values()->all();
        $seriesOffice = []; $seriesSaleDiff = [];
        foreach ($months as $m) {
            $rowOffice = $monthRows->firstWhere(fn($r)=>$r->ym===$m && (int)$r->status_id === (int)$officeProfitId);
            $rowSale   = $monthRows->firstWhere(fn($r)=>$r->ym===$m && (int)$r->status_id === (int)$saleDiffId);
            $seriesOffice[] = (float)($rowOffice->total ?? 0);
            $seriesSaleDiff[] = (float)($rowSale->total ?? 0);
        }

        $topOfficeByInvestor = $officeProfitId
            ? (clone $baseOT)->where('status_id', $officeProfitId)
                ->selectRaw('investor_id, SUM(amount) as total')
                ->groupBy('investor_id')
                ->orderByDesc('total')->limit(10)->get()
            : collect();

        $invNames = \App\Models\Investor::whereIn('id', $topOfficeByInvestor->pluck('investor_id')->filter()->values())
            ->pluck('name','id');

        $topOfficeLabels = $topOfficeByInvestor->map(fn($r)=> $invNames[$r->investor_id] ?? ('#'.$r->investor_id));
        $topOfficeData   = $topOfficeByInvestor->pluck('total')->map(fn($v)=> (float)$v);

        // ===== دفتر القيود العام =====
        $baseLE = \App\Models\LedgerEntry::query();
        if ($from) $baseLE->whereDate('entry_date', '>=', $from);
        if ($to)   $baseLE->whereDate('entry_date', '<=', $to);

        $invIn   = (float) (clone $baseLE)->where('is_office', false)->where('direction', 'in')->sum('amount');
        $invOut  = (float) (clone $baseLE)->where('is_office', false)->where('direction', 'out')->sum('amount');
        $invNetK = $invIn - $invOut;

        $offIn   = (float) (clone $baseLE)->where('is_office', true)->where('direction', 'in')->sum('amount');
        $offOut  = (float) (clone $baseLE)->where('is_office', true)->where('direction', 'out')->sum('amount');
        $offNetK = $offIn - $offOut;

        $transfersCount = (int) (clone $baseLE)->where('transaction_type_id', 3)->count();
        $avgTicketAll   = (float) (clone $baseLE)->avg('amount'); // (لم يعد مستخدمًا في العرض)

        $recentLedger = (clone $baseLE)->with(['investor','bankAccount','safe','status'])
            ->orderByDesc('entry_date')->orderByDesc('id')->limit(10)->get();

        // ===== البضائع (اختياري) =====
        $goodsEnabled = Schema::hasTable('product_transactions') && Schema::hasTable('products');
        $topProducts = collect(); $goodsBuyQty = 0; $goodsSellQty = 0; $goodsMonths = []; $goodsBuySeries = []; $goodsSellSeries = [];

        if ($goodsEnabled) {
            $buyStatusId  = DB::table('transaction_statuses')->where('name','شراء بضائع')->value('id');
            $sellStatusId = DB::table('transaction_statuses')->where('name','بيع بضائع')->value('id');

            $goodsBase = DB::table('product_transactions as pt')
                ->join('ledger_entries as le', 'le.id', '=', 'pt.ledger_entry_id');

            if ($from) $goodsBase->whereDate('le.entry_date', '>=', $from);
            if ($to)   $goodsBase->whereDate('le.entry_date', '<=', $to);

            $goodsBuyQty  = $buyStatusId  ? (clone $goodsBase)->where('le.transaction_status_id', $buyStatusId)->sum('pt.quantity')  : 0;
            $goodsSellQty = $sellStatusId ? (clone $goodsBase)->where('le.transaction_status_id', $sellStatusId)->sum('pt.quantity') : 0;

            $topProducts = (clone $goodsBase)
                ->join('products as p','p.id','=','pt.product_id')
                ->selectRaw('p.name as name, SUM(pt.quantity) as qty')
                ->groupBy('p.name')
                ->orderByDesc('qty')
                ->limit(10)->get();

            $goodsMonthRows = (clone $goodsBase)
                ->selectRaw("DATE_FORMAT(le.entry_date, '%Y-%m') as ym, le.transaction_status_id as sid, SUM(pt.quantity) as qty")
                ->when($buyStatusId || $sellStatusId, function($q) use($buyStatusId, $sellStatusId){
                    $ids = array_values(array_filter([$buyStatusId, $sellStatusId], fn($v)=>!is_null($v)));
                    return $q->whereIn('le.transaction_status_id', $ids);
                })
                ->groupBy('ym','sid')->orderBy('ym')->get();

            $goodsMonths = $goodsMonthRows->pluck('ym')->unique()->values()->all();
            foreach ($goodsMonths as $m) {
                $rb = $goodsMonthRows->firstWhere(fn($r)=>$r->ym === $m && (int)$r->sid === (int)$buyStatusId);
                $rs = $goodsMonthRows->firstWhere(fn($r)=>$r->ym === $m && (int)$r->sid === (int)$sellStatusId);
                $goodsBuySeries[]  = (int)($rb->qty ?? 0);
                $goodsSellSeries[] = (int)($rs->qty ?? 0);
            }
        }

        // ===== مخزون متاح حسب نوع البضاعة (غير خاضع للفترة) =====
        $inventoryByType = collect();
        $totalAvailableUnits = 0;

        if ($goodsEnabled && Schema::hasTable('product_types')) {
            $buyId  = DB::table('transaction_statuses')->where('name','شراء بضائع')->value('id');
            $sellId = DB::table('transaction_statuses')->where('name','بيع بضائع')->value('id');

            $invRows = DB::table('product_transactions as pt')
                ->join('ledger_entries as le','le.id','=','pt.ledger_entry_id')
                ->join('products as p','p.id','=','pt.product_id')
                ->leftJoin('product_types as t','t.id','=','p.product_type_id')
                ->selectRaw("
                    COALESCE(t.id, 0) as type_id,
                    COALESCE(t.name, 'غير محدد') as type_name,
                    SUM(CASE WHEN le.transaction_status_id = ? THEN pt.quantity ELSE 0 END) as buy_qty,
                    SUM(CASE WHEN le.transaction_status_id = ? THEN pt.quantity ELSE 0 END) as sell_qty
                ", [$buyId ?: 0, $sellId ?: 0])
                ->groupBy('type_id','type_name')
                ->orderBy('type_name')
                ->get();

            $inventoryByType = $invRows->map(function($r){
                $buy  = (int)($r->buy_qty ?? 0);
                $sell = (int)($r->sell_qty ?? 0);
                return (object)[
                    'type_id'   => (int)$r->type_id,
                    'type_name' => $r->type_name,
                    'bought'    => $buy,
                    'sold'      => $sell,
                    'available' => $buy - $sell,
                ];
            });

            $totalAvailableUnits = $inventoryByType->sum(fn($x) => max(0, (int)$x->available));
        }

        // ===== ملخصات جاهزة للعرض =====
        $invNet  = (float)($invTotals->net ?? 0);
        $offNet  = (float)($officeTotals->net ?? 0);
        $invCls  = $invNet >= 0 ? 'text-pos' : 'text-neg';
        $offCls  = $offNet >= 0 ? 'text-pos' : 'text-neg';

        $kpi = (object) [
            'entries_count'        => (int)($entriesCount ?? ($contractsTotal ?? 0)),
            'active_investors'     => (int)($activeInvestors ?? ($invByInvestor->count() ?? 0)),
            'stock_available_total'=> (int)$totalAvailableUnits,
        ];
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
                {{-- إضافات المكتب --}}
                <span class="badge-chip" data-bs-toggle="tooltip" title="إجمالي ربح المكتب ضمن الفترة">
                    <i class="bi bi-briefcase me-1"></i> ربح المكتب: {{ number_format($officeProfitTotal, 2) }}
                </span>
                <span class="badge-chip" data-bs-toggle="tooltip" title="إجمالي فرق البيع ضمن الفترة">
                    <i class="bi bi-percent me-1"></i> فرق البيع: {{ number_format($saleDiffTotal, 2) }}
                </span>
                @if(($mukatabaId ?? null))
                <span class="badge-chip" data-bs-toggle="tooltip" title="إجمالي المكاتبة ضمن الفترة">
                    <i class="bi bi-journal-text me-1"></i> المكاتبة: {{ number_format($mukatabaTotal, 2) }}
                </span>
                @endif
            </div>
        </div>
    </div>

    {{-- ====== KPIs عامة (أعدنا ترتيبها: شِلنا متوسط القيد، أضفنا المخزون المتاح) ====== --}}
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
                    <div class="kpi-icon"><i class="bi bi-boxes fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">المخزون المتاح</div>
                </div>
                <div class="kpi-value fw-bold">{{ number_format($kpi->stock_available_total) }}</div>
                <div class="small text-muted mt-2">إجمالي الوحدات (كل الأنواع)</div>
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

    {{-- ====== بطاقات إضافية للمستثمرين والمكتب ====== --}}
    <div class="row g-3 mt-1">
        <div class="col-12 col-md-6">
            <div class="kpi-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="kpi-icon"><i class="bi bi-person-lines-fill fs-5 text-primary"></i></div>
                        <div class="fw-bold text-muted">حركة المستثمرين</div>
                    </div>
                    <span class="badge bg-light text-dark">ضمن الفترة</span>
                </div>
                <div class="row text-center">
                    <div class="col-4">
                        <div class="small text-muted">داخل</div>
                        <div class="fw-bold text-pos">{{ number_format($invIn, 2) }}</div>
                    </div>
                    <div class="col-4">
                        <div class="small text-muted">خارج</div>
                        <div class="fw-bold text-neg">{{ number_format($invOut, 2) }}</div>
                    </div>
                    <div class="col-4">
                        <div class="small text-muted">صافي</div>
                        <div class="fw-bold {{ $invNetK>=0 ? 'text-pos':'text-neg' }}">{{ number_format($invNetK, 2) }}</div>
                    </div>
                </div>
                <div class="small text-muted mt-2">اعتمادًا على اتجاه القيود للمستثمرين فقط.</div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="kpi-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="kpi-icon"><i class="bi bi-building fs-5 text-primary"></i></div>
                        <div class="fw-bold text-muted">حركة المكتب</div>
                    </div>
                    <span class="badge bg-light text-dark">ضمن الفترة</span>
                </div>
                <div class="row text-center">
                    <div class="col-4">
                        <div class="small text-muted">داخل</div>
                        <div class="fw-bold text-pos">{{ number_format($offIn, 2) }}</div>
                    </div>
                    <div class="col-4">
                        <div class="small text-muted">خارج</div>
                        <div class="fw-bold text-neg">{{ number_format($offOut, 2) }}</div>
                    </div>
                    <div class="col-4">
                        <div class="small text-muted">صافي</div>
                        <div class="fw-bold {{ $offNetK>=0 ? 'text-pos':'text-neg' }}">{{ number_format($offNetK, 2) }}</div>
                    </div>
                </div>
                <div class="d-flex justify-content-between small text-muted mt-2">
                    <span><i class="bi bi-arrow-left-right"></i> التحويلات الداخلية: <strong>{{ number_format($transfersCount) }}</strong></span>
                    <span><i class="bi bi-cash-stack"></i> متوسط القيد: <strong>{{ number_format($avgTicketAll, 2) }}</strong></span>
                </div>
            </div>
        </div>
    </div>

    {{-- ====== توزيع حالات العقود + مخططها ====== --}}
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

    {{-- ====== ملخص المكتب التفصيلي ====== --}}
    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="section-card card border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>ملخص معاملات المكتب</span>
                    <span class="text-muted small">
                        <i class="bi bi-funnel"></i>
                        نطاق: {{ $from ?: 'الكل' }} — {{ $to ?: 'الكل' }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <div class="kpi-card p-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="kpi-icon"><i class="bi bi-briefcase fs-5 text-primary"></i></div>
                                    <div class="fw-bold text-muted">إجمالي ربح المكتب</div>
                                </div>
                                <div class="kpi-value fw-bold">{{ number_format($officeProfitTotal, 2) }}</div>
                                <div class="small text-muted mt-2">تحصيلات حالة "ربح المكتب"</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="kpi-card p-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="kpi-icon"><i class="bi bi-percent fs-5 text-primary"></i></div>
                                    <div class="fw-bold text-muted">إجمالي فرق البيع</div>
                                </div>
                                <div class="kpi-value fw-bold">{{ number_format($saleDiffTotal, 2) }}</div>
                                <div class="small text-muted mt-2">حالة "فرق البيع"</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="kpi-card p-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="kpi-icon"><i class="bi bi-journal-text fs-5 text-primary"></i></div>
                                    <div class="fw-bold text-muted">إجمالي المكاتبة</div>
                                </div>
                                <div class="kpi-value fw-bold">{{ number_format($mukatabaTotal, 2) }}</div>
                                <div class="small text-muted mt-2">إن وُجدت حالة "المكاتبة"</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-lg-6">
                            <div class="section-card card border-0 chart-card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>شهريًا: ربح المكتب مقابل فرق البيع</span>
                                    <span class="small text-muted"><i class="bi bi-bar-chart"></i></span>
                                </div>
                                <div class="card-body">
                                    <canvas id="officeMonthlyChart" height="240"
                                        data-months='@json($months)'
                                        data-office='@json($seriesOffice)'
                                        data-sale='@json($seriesSaleDiff)'></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="section-card card border-0 chart-card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>أعلى المستثمرين مساهمة في ربح المكتب</span>
                                    <span class="small text-muted"><i class="bi bi-graph-up-arrow"></i></span>
                                </div>
                                <div class="card-body">
                                    <canvas id="officeTopInvestorsChart" height="240"
                                        data-labels='@json($topOfficeLabels)'
                                        data-data='@json($topOfficeData)'></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- أحدث 10 معاملات مكتب --}}
                    <div class="row g-3 mt-1">
                        <div class="col-12">
                            <div class="section-card card border-0">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>أحدث 10 معاملات مكتب</span>
                                    <span class="small text-muted"><i class="bi bi-clock"></i></span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead>
                                                <tr class="text-center">
                                                    <th class="text-start">التاريخ</th>
                                                    <th>الحالة</th>
                                                    <th>المستثمر</th>
                                                    <th>العقد</th>
                                                    <th>القسط</th>
                                                    <th>المبلغ</th>
                                                    <th class="text-start">ملاحظات</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($officeRecent as $r)
                                                    <tr class="text-center">
                                                        <td class="text-start">{{ \Illuminate\Support\Carbon::parse($r->transaction_date)->format('Y-m-d') }}</td>
                                                        <td>{{ optional(\App\Models\TransactionStatus::find($r->status_id))->name ?? '-' }}</td>
                                                        <td>{{ optional($r->investor)->name ?? '—' }}</td>
                                                        <td>{{ $r->contract_id ? '#'.$r->contract_id : '—' }}</td>
                                                        <td>{{ $r->installment_id ? '#'.$r->installment_id : '—' }}</td>
                                                        <td class="fw-semibold">{{ number_format((float)$r->amount, 2) }}</td>
                                                        <td class="text-start">{{ $r->notes }}</td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="7" class="text-muted text-center py-4">لا توجد معاملات مكتب حديثة.</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer small text-muted">
                                    * الجدول يعرض آخر 10 قيود على معاملات المكتب ضمن نطاق التاريخ المحدد.
                                </div>
                            </div>
                        </div>
                    </div>

                </div> {{-- /card-body --}}
            </div>
        </div>
    </div>

    {{-- ====== أحدث 10 قيود عامة (دفتر القيود) ====== --}}
    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="section-card card border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>أحدث 10 قيود في دفتر القيود</span>
                    <span class="small text-muted"><i class="bi bi-clock-history"></i></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-center">
                                    <th class="text-start">التاريخ</th>
                                    <th>الجهة</th>
                                    <th>الحالة</th>
                                    <th>الحساب</th>
                                    <th>الاتجاه</th>
                                    <th>المبلغ</th>
                                    <th class="text-start">ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentLedger as $e)
                                    <tr class="text-center">
                                        <td class="text-start">{{ \Illuminate\Support\Carbon::parse($e->entry_date)->format('Y-m-d') }}</td>
                                        <td>{{ $e->is_office ? 'المكتب' : (optional($e->investor)->name ?? '—') }}</td>
                                        <td>{{ optional($e->status)->name ?? '—' }}</td>
                                        <td>
                                            @if($e->bankAccount) <i class="bi bi-bank"></i> {{ $e->bankAccount->name }}
                                            @elseif($e->safe)   <i class="bi bi-safe2"></i> {{ $e->safe->name }}
                                            @else — @endif
                                        </td>
                                        <td>
                                            @if($e->direction === 'in') <span class="badge bg-success">داخل</span>
                                            @elseif($e->direction === 'out') <span class="badge bg-danger">خارج</span>
                                            @else <span class="badge bg-secondary">—</span>@endif
                                        </td>
                                        <td class="fw-semibold">{{ number_format((float)$e->amount, 2) }}</td>
                                        <td class="text-start">{{ $e->notes }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-muted text-center py-4">لا توجد قيود حديثة.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer small text-muted">
                    * يشمل قيود المستثمرين والمكتب (حسب نطاق التاريخ).
                </div>
            </div>
        </div>
    </div>

    {{-- ====== تحليلات إضافية (كما كانت) ====== --}}
    @php
        $ts = (object)($timeSeries ?? ['labels'=>[], 'in'=>[], 'out'=>[], 'net'=>[]]);
        $ms = (object)($monthlySeries ?? ['labels'=>[], 'in'=>[], 'out'=>[]]);
        $dist = (object)($distribution ?? ['labels'=>['بنوك','خزن'], 'data'=>[(float)($banksTotal ?? 0), (float)($safesTotal ?? 0)]]);
        $banksColl = collect($bankAccountsSummary ?? []);
        $safesColl = collect($safesSummary ?? []);
        $topBalances = $banksColl->map(function($b){
            $opening=(float)($b->opening_balance ?? 0); $in=(float)($b->inflow ?? 0); $out=(float)($b->outflow ?? 0);
            return ['label'=>$b->name ?? ('#'.$b->id), 'bal'=>$opening + ($in-$out)];
        })->merge(
            $safesColl->map(function($s){
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

    {{-- ====== (اختياري) قسم البضائع لو الجداول متوفرة ====== --}}
    @if($goodsEnabled)
    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="section-card card border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>ملخص البضائع</span>
                    <span class="text-muted small"><i class="bi bi-box-seam"></i> إجمالي الكميات ضمن الفترة</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <div class="kpi-card p-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="kpi-icon"><i class="bi bi-cart-plus fs-5 text-primary"></i></div>
                                    <div class="fw-bold text-muted">كميات مُشتراة</div>
                                </div>
                                <div class="kpi-value fw-bold">{{ number_format((int)$goodsBuyQty) }}</div>
                                <div class="small text-muted mt-2">حالة "شراء بضائع"</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="kpi-card p-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="kpi-icon"><i class="bi bi-cart-dash fs-5 text-primary"></i></div>
                                    <div class="fw-bold text-muted">كميات مُباعة</div>
                                </div>
                                <div class="kpi-value fw-bold">{{ number_format((int)$goodsSellQty) }}</div>
                                <div class="small text-muted mt-2">حالة "بيع بضائع"</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="kpi-card p-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="kpi-icon"><i class="bi bi-boxes fs-5 text-primary"></i></div>
                                    <div class="fw-bold text-muted">صافي حركة الكميات</div>
                                </div>
                                <div class="kpi-value fw-bold {{ ($goodsBuyQty-$goodsSellQty)>=0 ? 'text-pos':'text-neg' }}">
                                    {{ number_format((int)($goodsBuyQty - $goodsSellQty)) }}
                                </div>
                                <div class="small text-muted mt-2">مُشتراة − مُباعة</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-lg-6">
                            <div class="section-card card border-0 chart-card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>شهريًا: شراء vs بيع (كميات)</span>
                                    <span class="small text-muted"><i class="bi bi-bar-chart-steps"></i></span>
                                </div>
                                <div class="card-body">
                                    <canvas id="goodsInOutChart" height="220"
                                        data-months='@json($goodsMonths)'
                                        data-buy='@json($goodsBuySeries)'
                                        data-sell='@json($goodsSellSeries)'></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="section-card card border-0 chart-card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>أفضل الأصناف (بالكمية)</span>
                                    <span class="small text-muted"><i class="bi bi-bar-chart"></i></span>
                                </div>
                                <div class="card-body">
                                    <canvas id="topProductsChart" height="220"
                                        data-labels='@json($topProducts->pluck("name"))'
                                        data-data='@json($topProducts->pluck("qty")->map(fn($q)=>(int)$q))'></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ====== المخزون المتاح حسب نوع البضاعة (تفاصيل كل نوع) ====== --}}
                    <div class="row g-3 mt-1">
                        <div class="col-12">
                            <div class="section-card card border-0">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>المخزون المتاح حسب نوع البضاعة</span>
                                    <span class="text-muted small"><i class="bi bi-info-circle"></i> الأرقام إجمالية (غير خاضعة لفلاتر التاريخ)</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead>
                                                <tr class="text-center">
                                                    <th class="text-start">نوع البضاعة</th>
                                                    <th>مُشتراة</th>
                                                    <th>مُباعة</th>
                                                    <th>المتاح</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($inventoryByType as $row)
                                                    @php
                                                        $avail = (int)$row->available;
                                                        $cls   = $avail >= 0 ? 'text-pos' : 'text-neg';
                                                    @endphp
                                                    <tr class="text-center">
                                                        <td class="text-start">{{ $row->type_name }}</td>
                                                        <td class="text-pos fw-semibold">{{ number_format((int)$row->bought) }}</td>
                                                        <td class="text-neg fw-semibold">{{ number_format((int)$row->sold) }}</td>
                                                        <td class="fw-bold {{ $cls }}">{{ number_format($avail) }}</td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="4" class="text-muted text-center py-4">لا توجد بيانات مخزون حسب النوع.</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer small text-muted d-flex justify-content-between align-items-center">
                                    <span>* المتاح = مُشتراة − مُباعة</span>
                                    <span><strong>إجمالي المخزون المتاح:</strong> {{ number_format($totalAvailableUnits) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> {{-- /card-body --}}
            </div>
        </div>
    </div>
    @endif

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
        const labels = @json(($timeSeries['labels'] ?? ($timeSeries->labels ?? [])));
        const inflow = @json(($timeSeries['in']     ?? ($timeSeries->in     ?? [])));
        const outflow= @json(($timeSeries['out']    ?? ($timeSeries->out    ?? [])));
        const net    = @json(($timeSeries['net']    ?? ($timeSeries->net    ?? [])));
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
        const labels = @json(($monthlySeries['labels'] ?? ($monthlySeries->labels ?? [])));
        const inflow = @json(($monthlySeries['in']     ?? ($monthlySeries->in     ?? [])));
        const outflow= @json(($monthlySeries['out']    ?? ($monthlySeries->out    ?? [])));
        if (!labels.length) { el.parentElement.innerHTML = '<div class="text-muted">لا توجد بيانات شهرية.</div>'; return; }
        new Chart(el, {
            type: 'bar',
            data: { labels,
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
        const labels = @json(($distribution['labels'] ?? ($distribution->labels ?? ['بنوك','خزن'])));
        const data   = @json(($distribution['data']   ?? ($distribution->data   ?? [0,0])));
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

    // Line: Office profit vs Sale difference (monthly)
    (function(){
        const el = document.getElementById('officeMonthlyChart');
        if (!el) return;
        const labels = JSON.parse(el.dataset.months || '[]');
        const office = JSON.parse(el.dataset.office || '[]');
        const sale   = JSON.parse(el.dataset.sale || '[]');
        if (!labels.length) { el.parentElement.innerHTML = '<div class="text-muted">لا توجد بيانات شهرية للمكتب.</div>'; return; }
        new Chart(el, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: 'ربح المكتب', data: office, tension:.3, borderWidth:2, fill:false },
                    { label: 'فرق البيع',  data: sale,   tension:.3, borderWidth:2, fill:false }
                ]
            },
            options: {
                responsive:true,
                plugins:{ legend:{ position:'bottom' }, tooltip:{ rtl:true } },
                interaction:{ mode:'index', intersect:false },
                scales:{ y:{ beginAtZero:true } }
            }
        });
    })();

    // Horizontal Bar: Top Investors by office profit
    (function(){
        const el = document.getElementById('officeTopInvestorsChart');
        if (!el) return;
        const labels = JSON.parse(el.dataset.labels || '[]');
        const data   = JSON.parse(el.dataset.data   || '[]');
        if (!labels.length) { el.parentElement.innerHTML = '<div class="text-muted">لا توجد بيانات كافية.</div>'; return; }
        new Chart(el, {
            type: 'bar',
            data: { labels, datasets: [{ label:'ربح المكتب', data, borderWidth:1 }] },
            options: {
                indexAxis: 'y',
                responsive:true,
                plugins:{ legend:{ position:'bottom' }, tooltip:{ rtl:true } },
                scales:{ x:{ beginAtZero:true } }
            }
        });
    })();

    // ===== (اختياري) مخططات البضائع =====
    (function(){
        const el1 = document.getElementById('goodsInOutChart');
        if (el1) {
            const labels = JSON.parse(el1.dataset.months || '[]');
            const buy    = JSON.parse(el1.dataset.buy    || '[]');
            const sell   = JSON.parse(el1.dataset.sell   || '[]');
            if (!labels.length) {
                el1.parentElement.innerHTML = '<div class="text-muted">لا توجد بيانات كافية.</div>';
            } else {
                new Chart(el1, {
                    type: 'bar',
                    data: { labels,
                        datasets: [
                            { label:'شراء', data: buy,  borderWidth:1, stack:'g' },
                            { label:'بيع',  data: sell, borderWidth:1, stack:'g' }
                        ]
                    },
                    options: {
                        responsive:true,
                        plugins:{ legend:{ position:'bottom' }, tooltip:{ rtl:true } },
                        scales:{ x:{ stacked:true }, y:{ stacked:true, beginAtZero:true } }
                    }
                });
            }
        }
        const el2 = document.getElementById('topProductsChart');
        if (el2) {
            const labels = JSON.parse(el2.dataset.labels || '[]');
            const data   = JSON.parse(el2.dataset.data   || '[]');
            if (!labels.length) {
                el2.parentElement.innerHTML = '<div class="text-muted">لا توجد بيانات أصناف.</div>';
            } else {
                new Chart(el2, {
                    type: 'bar',
                    data: { labels, datasets: [{ label:'كميات', data, borderWidth:1 }] },
                    options: {
                        indexAxis:'y',
                        responsive:true,
                        plugins:{ legend:{ display:false }, tooltip:{ rtl:true } },
                        scales:{ x:{ beginAtZero:true } }
                    }
                });
            }
        }
    })();
});
</script>
@endsection
