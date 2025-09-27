@extends('layouts.master')

@section('title', 'داش بورد الحسابات')

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">داش بورد الحسابات</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">@lang('sidebar.Accounts')</li>
            <li class="breadcrumb-item"><a href="{{ route('ledger.index') }}">@lang('sidebar.Ledger')</a></li>
            <li class="breadcrumb-item active">داش بورد الحسابات</li>
        </ol>
    </nav>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4" dir="rtl">
    <div class="flex-grow-1"></div>
    <div class="btn-group" role="group">
        <x-button.action href="{{ route('ledger.index') }}" variant="primary" class="d-inline-flex align-items-center gap-2 px-3">
            <i class="bi bi-journal-text"></i>
            <span>تفاصيل القيود</span>
        </x-button.action>
    </div>
</div>

@php
    /**
     * تهيئة بيانات الحسابات من الخدمة مع fallback للتجميع اليدوي القديم
     */
    // ==== BANKS ====
    $banksFromSvc = collect($banks ?? []);
    $bankAgg = $banksFromSvc->map(function($b){
        $name = is_array($b) ? ($b['name'] ?? '') : ($b->name ?? '');
        $in   = (float)(is_array($b) ? ($b['in']   ?? $b['total_in']  ?? 0) : ($b->in   ?? 0));
        $out  = (float)(is_array($b) ? ($b['out']  ?? $b['total_out'] ?? 0) : ($b->out  ?? 0));
        $flow = max($in + $out, 0.00001);
        return [
            'name'    => $name,
            'in'      => $in,
            'out'     => $out,
            'net'     => $in - $out,
            'in_pct'  => round($in  / $flow * 100, 1),
            'out_pct' => round($out / $flow * 100, 1),
        ];
    });

    if ($bankAgg->isEmpty()) {
        // Fallback من القيود
        $bankAgg = collect();
        $bankBucket = [];
        $entriesCollection = collect();
        if (isset($entries)) {
            $entriesCollection = $entries instanceof \Illuminate\Pagination\LengthAwarePaginator ? $entries->getCollection() : collect($entries);
        }
        foreach ($entriesCollection as $e) {
            if (!$e->bankAccount) continue;
            $key = (string)$e->bank_account_id;
            $bankBucket[$key] ??= ['name'=>$e->bankAccount->name ?? ('#'.$key), 'in'=>0.0, 'out'=>0.0];
            if ($e->direction === 'in')  $bankBucket[$key]['in']  += (float)$e->amount;
            if ($e->direction === 'out') $bankBucket[$key]['out'] += (float)$e->amount;
        }
        foreach ($bankBucket as $b) {
            $flow = max(($b['in']+$b['out']), 0.00001);
            $bankAgg->push([
                'name'=>$b['name'],
                'in'=>$b['in'],
                'out'=>$b['out'],
                'net'=>$b['in']-$b['out'],
                'in_pct'=>round($b['in']/$flow*100,1),
                'out_pct'=>round($b['out']/$flow*100,1),
            ]);
        }
        $bankAgg = $bankAgg->sortBy('name', SORT_NATURAL|SORT_FLAG_CASE)->values();
    }

    $bankTotalIn  = (float)($bankTotals['in']  ?? $bankAgg->sum('in'));
    $bankTotalOut = (float)($bankTotals['out'] ?? $bankAgg->sum('out'));
    $bankNet      = $bankTotalIn - $bankTotalOut;

    // ==== SAFES ====
    $safesFromSvc = collect($safes ?? []);
    $safeAgg = $safesFromSvc->map(function($s){
        $name = is_array($s) ? ($s['name'] ?? '') : ($s->name ?? '');
        $in   = (float)(is_array($s) ? ($s['in']   ?? $s['total_in']  ?? 0) : ($s->in   ?? 0));
        $out  = (float)(is_array($s) ? ($s['out']  ?? $s['total_out'] ?? 0) : ($s->out  ?? 0));
        $flow = max($in + $out, 0.00001);
        return [
            'name'    => $name,
            'in'      => $in,
            'out'     => $out,
            'net'     => $in - $out,
            'in_pct'  => round($in  / $flow * 100, 1),
            'out_pct' => round($out / $flow * 100, 1),
        ];
    });

    if ($safeAgg->isEmpty()) {
        // Fallback من القيود
        $safeAgg = collect();
        $safeBucket = [];
        $entriesCollection = collect();
        if (isset($entries)) {
            $entriesCollection = $entries instanceof \Illuminate\Pagination\LengthAwarePaginator ? $entries->getCollection() : collect($entries);
        }
        foreach ($entriesCollection as $e) {
            if (!$e->safe) continue;
            $key = (string)$e->safe_id;
            $safeBucket[$key] ??= ['name'=>$e->safe->name ?? ('#'.$key), 'in'=>0.0, 'out'=>0.0];
            if ($e->direction === 'in')  $safeBucket[$key]['in']  += (float)$e->amount;
            if ($e->direction === 'out') $safeBucket[$key]['out'] += (float)$e->amount;
        }
        foreach ($safeBucket as $s) {
            $flow = max(($s['in']+$s['out']), 0.00001);
            $safeAgg->push([
                'name'=>$s['name'],
                'in'=>$s['in'],
                'out'=>$s['out'],
                'net'=>$s['in']-$s['out'],
                'in_pct'=>round($s['in']/$flow*100,1),
                'out_pct'=>round($s['out']/$flow*100,1),
            ]);
        }
        $safeAgg = $safeAgg->sortBy('name', SORT_NATURAL|SORT_FLAG_CASE)->values();
    }

    $safeTotalIn  = (float)($safeTotals['in']  ?? $safeAgg->sum('in'));
    $safeTotalOut = (float)($safeTotals['out'] ?? $safeAgg->sum('out'));
    $safeNet      = $safeTotalIn - $safeTotalOut;

    // ==== KPIs المكتب ====
    $mukatabaTotal = (float)($officeKpis['mukataba']['total'] ?? 0);
    $profitTotal   = (float)($officeKpis['profit']['total']   ?? 0);
    $salesDisplay  = (float)($officeKpis['sales']['total']    ?? 0);
    $legalTotal    = (float)($officeKpis['legal']['total']    ?? 0);
@endphp

{{-- كروت: الحسابات البنكية + الخزن --}}
<div class="row g-3 mb-3" dir="rtl">
    {{-- الحسابات البنكية --}}
    <div class="col-12 col-xl-6">
        <div class="kpi-card p-0 h-100">
            <div class="card-head bank-grad p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="kpi-icon"><i class="bi bi-bank fs-4 text-success"></i></div>
                    <div>
                        <div class="fw-bold">الحسابات البنكية</div>
                        <div class="subnote">ضمن النتائج الحالية</div>
                    </div>
                </div>
                <span class="chip soft">عدد الحسابات: <strong>{{ $bankAgg->count() }}</strong></span>
            </div>

            <div class="p-3 pt-2">
                <div class="stat-box mb-2">
                    <div class="d-flex justify-content-between mini"><span>إجمالي داخل</span><strong class="text-success">{{ number_format($bankTotalIn,2) }}</strong></div>
                    <div class="d-flex justify-content-between mini"><span>إجمالي خارج</span><strong class="text-danger">{{ number_format($bankTotalOut,2) }}</strong></div>
                    <div class="d-flex justify-content-between mini">
                        <span>الصافي</span>
                        <strong class="{{ $bankNet>=0?'text-success':'text-danger' }}">{{ number_format($bankNet,2) }}</strong>
                    </div>
                </div>

                @if($bankAgg->isNotEmpty())
                    <x-table head-class="table-light sticky-top" foot-class="table-light" small>
                        <x-slot name="head">
                            <tr>
                                <th>الحساب</th>
                                <th style="width:34%"></th>
                                <th class="text-end" style="width:12%">داخل</th>
                                <th class="text-end" style="width:12%">خارج</th>
                                <th class="text-end" style="width:12%">صافي</th>
                            </tr>
                        </x-slot>
                        @foreach($bankAgg as $b)
                            <tr>
                                <td class="text-truncate" style="max-width:220px">
                                    <i class="bi bi-building-fill-check text-success me-1"></i>{{ $b['name'] }}
                                </td>
                                <td>
                                    <div class="stacked-bar" title="داخل {{ $b['in_pct'] }}% / خارج {{ $b['out_pct'] }}%">
                                        <span class="in"  style="width: {{ $b['in_pct'] }}%"></span>
                                        <span class="out" style="width: {{ $b['out_pct'] }}%"></span>
                                    </div>
                                </td>
                                <td class="text-end text-success">{{ number_format($b['in'],2) }}</td>
                                <td class="text-end text-danger">{{ number_format($b['out'],2) }}</td>
                                <td class="text-end fw-semibold {{ ($b['net']??0)>=0?'text-success':'text-danger' }}">{{ number_format($b['net']??0,2) }}</td>
                            </tr>
                        @endforeach
                        <x-slot name="footer">
                            <tr>
                                <th colspan="2" class="text-end">الإجمالي</th>
                                <th class="text-end text-success">{{ number_format($bankTotalIn,2) }}</th>
                                <th class="text-end text-danger">{{ number_format($bankTotalOut,2) }}</th>
                                <th class="text-end fw-semibold {{ $bankNet>=0?'text-success':'text-danger' }}">{{ number_format($bankNet,2) }}</th>
                            </tr>
                        </x-slot>
                    </x-table>
                @else
                    <div class="text-muted mini">لا توجد حركات بنكية ضمن النتائج.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- الخزن --}}
    <div class="col-12 col-xl-6">
        <div class="kpi-card p-0 h-100">
            <div class="card-head safe-grad p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="kpi-icon"><i class="bi bi-safe2 fs-4 text-warning"></i></div>
                    <div>
                        <div class="fw-bold">الخزن</div>
                        <div class="subnote">ضمن النتائج الحالية</div>
                    </div>
                </div>
                <span class="chip soft">عدد الخزن: <strong>{{ $safeAgg->count() }}</strong></span>
            </div>

            <div class="p-3 pt-2">
                <div class="stat-box mb-2">
                    <div class="d-flex justify-content-between mini"><span>إجمالي داخل</span><strong class="text-success">{{ number_format($safeTotalIn,2) }}</strong></div>
                    <div class="d-flex justify-content-between mini"><span>إجمالي خارج</span><strong class="text-danger">{{ number_format($safeTotalOut,2) }}</strong></div>
                    <div class="d-flex justify-content-between mini">
                        <span>الصافي</span>
                        <strong class="{{ $safeNet>=0?'text-success':'text-danger' }}">{{ number_format($safeNet,2) }}</strong>
                    </div>
                </div>

                @if($safeAgg->isNotEmpty())
                    <x-table head-class="table-light sticky-top" foot-class="table-light" small>
                        <x-slot name="head">
                            <tr>
                                <th>الخزنة</th>
                                <th style="width:34%"></th>
                                <th class="text-end" style="width:12%">داخل</th>
                                <th class="text-end" style="width:12%">خارج</th>
                                <th class="text-end" style="width:12%">صافي</th>
                            </tr>
                        </x-slot>
                        @foreach($safeAgg as $s)
                            <tr>
                                <td class="text-truncate" style="max-width:220px">
                                    <i class="bi bi-archive-fill text-warning me-1"></i>{{ $s['name'] }}
                                </td>
                                <td>
                                    <div class="stacked-bar" title="داخل {{ $s['in_pct'] }}% / خارج {{ $s['out_pct'] }}%">
                                        <span class="in"  style="width: {{ $s['in_pct'] }}%"></span>
                                        <span class="out" style="width: {{ $s['out_pct'] }}%"></span>
                                    </div>
                                </td>
                                <td class="text-end text-success">{{ number_format($s['in'],2) }}</td>
                                <td class="text-end text-danger">{{ number_format($s['out'],2) }}</td>
                                <td class="text-end fw-semibold {{ ($s['net']??0)>=0?'text-success':'text-danger' }}">{{ number_format($s['net']??0,2) }}</td>
                            </tr>
                        @endforeach
                        <x-slot name="footer">
                            <tr>
                                <th colspan="2" class="text-end">الإجمالي</th>
                                <th class="text-end text-success">{{ number_format($safeTotalIn,2) }}</th>
                                <th class="text-end text-danger">{{ number_format($safeTotalOut,2) }}</th>
                                <th class="text-end fw-semibold {{ $safeNet>=0?'text-success':'text-danger' }}">{{ number_format($safeNet,2) }}</th>
                            </tr>
                        </x-slot>
                    </x-table>
                @else
                    <div class="text-muted mini">لا توجد حركات خزنة ضمن النتائج.</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- كروت إضافية: المكاتبة / فرق البيع / ربح المكتب (داخل فقط) --}}
<div class="row g-3 mb-3" dir="rtl">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="kpi-card pretty p-3">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="kpi-icon"><i class="bi bi-journal-text fs-4 text-primary"></i></div>
                <div>
                    <div class="fw-bold">المكاتبة</div>
                    <div class="subnote">مجمّعة من خدمة المكتب</div>
                </div>
            </div>
            <div class="kpi-value fw-bold text-success">{{ number_format($mukatabaTotal,2) }}</div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="kpi-card pretty p-3">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="kpi-icon"><i class="bi bi-bag-check fs-4 text-success"></i></div>
                <div>
                    <div class="fw-bold">فرق البيع</div>
                    <div class="subnote">مجمّعة من خدمة المكتب</div>
                </div>
            </div>
            <div class="kpi-value fw-bold text-success">{{ number_format($salesDisplay,2) }}</div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="kpi-card pretty p-3">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="kpi-icon"><i class="bi bi-briefcase fs-4 text-warning"></i></div>
                <div>
                    <div class="fw-bold">ربح المكتب</div>
                    <div class="subnote">مجمّعة من خدمة المكتب</div>
                </div>
            </div>
            <div class="kpi-value fw-bold text-success">{{ number_format($profitTotal,2) }}</div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="kpi-card pretty p-3">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="kpi-icon"><i class="bi bi-shield-check fs-4 text-info"></i></div>
                <div>
                    <div class="fw-bold">محاماة المكتب</div>
                    <div class="subnote">مجمّعة من خدمة المكتب</div>
                </div>
            </div>
            <div class="kpi-value fw-bold text-success">{{ number_format($legalTotal,2) }}</div>
        </div>
    </div>
</div>

{{-- ========= (جديد) كروت "المتاح من البضائع لكل نوع" ========= --}}
@php
    $pa   = $productsAvailability ?? $goodsAvailability ?? null;
    $list = collect($pa['items'] ?? []);
    $lowThreshold = (int)($pa['totals']['low_threshold'] ?? 5);
    $totalAvailable = (int)($pa['totals']['available'] ?? $list->sum('available'));
    $totalTypes = $list->count();
@endphp

@if(!empty($pa) && $list->isNotEmpty())
<div class="kpi-card p-0 mb-3">
    <div class="card-head goods-grad p-3 d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <div class="kpi-icon"><i class="bi bi-box-seam fs-4"></i></div>
            <div>
                <div class="fw-bold">المتاح من البضائع حسب النوع</div>
                <div class="subnote">يعرض المتاح فقط — بدون تفاصيل الحركات</div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="chip soft">عدد الأنواع: <strong>{{ $totalTypes }}</strong></span>
            <span class="chip soft">إجمالي المتاح: <strong>{{ number_format($totalAvailable) }}</strong></span>
        </div>
    </div>

    <div class="p-3 pt-2">
        <div class="row g-3">
            @foreach($list as $row)
                @php
                    $name = (string)($row['name'] ?? '-');
                    $av   = (int)($row['available'] ?? 0);
                    $formatted = (string)($row['formatted'] ?? number_format($av));
                    $isLow = (bool)($row['is_low'] ?? ($av <= $lowThreshold && $av > 0));
                    $status = $av === 0 ? 'zero' : ($isLow ? 'low' : 'ok');
                    $pillClass = $status === 'zero' ? 'pill-zero' : ($status === 'low' ? 'pill-low' : 'pill-ok');
                    $pillIcon  = $status === 'zero' ? 'bi-x-circle' : ($status === 'low' ? 'bi-exclamation-circle' : 'bi-check-circle');
                    $pillText  = $status === 'zero' ? 'نافد' : ($status === 'low' ? 'منخفض' : 'جيد');
                @endphp
                <div class="col-12 col-sm-6 col-lg-4 col-xxl-3">
                    <div class="goods-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="fw-semibold text-truncate" title="{{ $name }}">
                                <i class="bi bi-tag me-1"></i>{{ $name }}
                            </div>
                            <span class="pill {{ $pillClass }}"><i class="bi {{ $pillIcon }}"></i> {{ $pillText }}</span>
                        </div>

                        <div class="avail-badge" title="المتاح">
                            <div class="avail-number">{{ $formatted }}</div>
                            <div class="avail-label">المتاح</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
{{-- ========= نهاية كروت البضائع ========= --}}
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
@endpush

@include('ledger::ledger.partials.filter-script')
