@extends('layouts.master')

@section('title', 'دفتر القيود')

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">دفتر القيود</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">الحسابات</li>
            <li class="breadcrumb-item active">دفتر القيود</li>
        </ol>
    </nav>
</div>

{{-- شريط أدوات سريع --}}
<div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">
        <a href="{{ route('ledger.create') }}" class="btn btn-outline-success">+ إضافة قيد</a>
        <a href="{{ route('ledger.transfer.create') }}" class="btn btn-outline-primary">تحويل داخلي (مكتب)</a>
        <a href="{{ route('ledger.split.create') }}" class="btn btn-outline-secondary">قيد مُجزّأ (بنك + خزنة)</a>

        <span class="ms-auto small text-muted">
            النتائج: <strong>{{ $entries->total() }}</strong>
        </span>

        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#filterBar" aria-expanded="false">
            تصفية متقدمة
        </button>
    </div>

    <div class="collapse @if(($filters['party_category'] ?? '') || ($filters['investor_id'] ?? '') || ($filters['status_id'] ?? '') || ($filters['account_type'] ?? '') || ($filters['from'] ?? '') || ($filters['to'] ?? '')) show @endif border-top" id="filterBar">
        <div class="card-body">
            <form method="GET" action="{{ route('ledger.index') }}" class="row gy-2 gx-2 align-items-end" id="filtersForm">
                <div class="col-12 col-md-2">
                    <label class="form-label mb-1">الفئة</label>
                    <select name="party_category" id="party_category" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        <option value="investors" @selected(($filters['party_category'] ?? '') === 'investors')>المستثمرون</option>
                        <option value="office"    @selected(($filters['party_category'] ?? '') === 'office')>المكتب</option>
                    </select>
                </div>

                <div class="col-12 col-md-2" id="investorWrap">
                    <label class="form-label mb-1">المستثمر</label>
                    <select name="investor_id" id="investor_id" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        @foreach($investors as $inv)
                            <option value="{{ $inv->id }}" @selected((string)($filters['investor_id'] ?? '') === (string)$inv->id)>{{ $inv->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <label class="form-label mb-1">الحالة</label>
                    <select name="status_id" id="status_id" class="form-select form-select-sm">
                        <option value="">الكل</option>

                        <optgroup label="حالات المستثمرين" data-cat="investors">
                            @foreach($statusesInvestors as $st)
                                @if(($st->transaction_type_id ?? null) != 3)
                                    <option value="{{ $st->id }}" data-cat="investors" data-type="{{ $st->transaction_type_id }}" @selected((string)($filters['status_id'] ?? '') === (string)$st->id)>{{ $st->name }}</option>
                                @endif
                            @endforeach
                        </optgroup>

                        <optgroup label="حالات المكتب" data-cat="office">
                            @foreach($statusesOffice as $st)
                                @if(($st->transaction_type_id ?? null) != 3)
                                    <option value="{{ $st->id }}" data-cat="office" data-type="{{ $st->transaction_type_id }}" @selected((string)($filters['status_id'] ?? '') === (string)$st->id)>{{ $st->name }}</option>
                                @endif
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">نوع الحساب</label>
                    <select name="account_type" id="account_type" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        <option value="bank" @selected(($filters['account_type'] ?? '') === 'bank')>حساب بنكي</option>
                        <option value="safe" @selected(($filters['account_type'] ?? '') === 'safe')>خزنة</option>
                    </select>
                </div>

                <div class="col-6 col-md-1 js-date">
                    <label class="form-label mb-1">من</label>
                    <input type="date" name="from" id="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-1 js-date">
                    <label class="form-label mb-1">إلى</label>
                    <input type="date" name="to" id="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
                </div>

                <div class="col-12 col-md-2 d-flex gap-2">
                    <a href="{{ route('ledger.index') }}" class="btn btn-outline-secondary btn-sm" id="btnClear">مسح</a>
                </div>
            </form>
        </div>
    </div>
</div>


@php
    // ====== مجاميع تفصيلية حسب الفئة (المستثمرون/المكتب) ======
    $coll = $entries instanceof \Illuminate\Pagination\LengthAwarePaginator ? $entries->getCollection() : collect($entries);

    $sumFn = function($cat = null, $dir = null, $acc = null) use ($coll) {
        $t = 0.0;
        foreach ($coll as $e) {
            $isOffice = (bool)($e->is_office ?? false);
            if ($cat === 'investors' && $isOffice) continue;
            if ($cat === 'office'    && !$isOffice) continue;
            if ($dir && ($e->direction ?? null) !== $dir) continue;

            if ($acc === 'bank' && empty($e->bankAccount)) continue;
            if ($acc === 'safe' && empty($e->safe)) continue;

            $t += (float)($e->amount ?? 0);
        }
        return $t;
    };

    // المستثمرون
    $invIn       = $totalsInvestors['in']       ?? $sumFn('investors','in',  null);
    $invOut      = $totalsInvestors['out']      ?? $sumFn('investors','out', null);
    $invBankIn   = $totalsInvestors['bank_in']  ?? $sumFn('investors','in',  'bank');
    $invBankOut  = $totalsInvestors['bank_out'] ?? $sumFn('investors','out', 'bank');
    $invSafeIn   = $totalsInvestors['safe_in']  ?? $sumFn('investors','in',  'safe');
    $invSafeOut  = $totalsInvestors['safe_out'] ?? $sumFn('investors','out', 'safe');
    $invNet      = $invIn - $invOut;

    // المكتب
    $offIn       = $totalsOffice['in']       ?? $sumFn('office','in',  null);
    $offOut      = $totalsOffice['out']      ?? $sumFn('office','out', null);
    $offBankIn   = $totalsOffice['bank_in']  ?? $sumFn('office','in',  'bank');
    $offBankOut  = $totalsOffice['bank_out'] ?? $sumFn('office','out', 'bank');
    $offSafeIn   = $totalsOffice['safe_in']  ?? $sumFn('office','in',  'safe');
    $offSafeOut  = $totalsOffice['safe_out'] ?? $sumFn('office','out', 'safe');
    $offNet      = $offIn - $offOut;

    $pct = function($part, $whole){ return $whole>0 ? round(($part/$whole)*100,1) : 0; };
    $invTotalFlow = max($invIn + $invOut, 0.00001);
    $offTotalFlow = max($offIn + $offOut, 0.00001);
    $invInPct   = $pct($invIn,  $invTotalFlow);
    $invOutPct  = $pct($invOut, $invTotalFlow);
    $offInPct   = $pct($offIn,  $offTotalFlow);
    $offOutPct  = $pct($offOut, $offTotalFlow);
    $invNetClass = $invNet >= 0 ? 'text-success' : 'text-danger';
    $offNetClass = $offNet >= 0 ? 'text-success' : 'text-danger';

    // ====== كروت إضافية: فرق البيع / فرق المكاتبة / ربح المكتب ======
    $containsAny = function($txt, $words){
        $txt = mb_strtolower($txt ?? '');
        foreach ($words as $w) {
            if ($w === null || $w === '') continue;
            if (mb_stripos($txt, mb_strtolower($w)) !== false) return true;
        }
        return false;
    };

    $saleKeywords      = ['بيع','مبيع','مبيعات','sale','sales'];
    $mukatabaKeywords  = ['مكاتبة','مُكاتبة','كتابة','mukataba','mukātaba'];
    $officeProfitInKW  = ['ربح','أرباح','عوائد','عمولة','عمولات','profit','revenue','return'];
    $officeProfitOutKW = ['مصاريف','مصروف','خصم','عمولة مدفوعة','خصومات','expenses','fee','commission'];

    $saleIn = $saleOut = $mktIn = $mktOut = 0.0;
    $offProfitIn = $offProfitOut = 0.0;

    foreach ($coll as $e) {
        $statusName = trim($e->status->name ?? '');
        $dir        = $e->direction ?? null;
        $amt        = (float)($e->amount ?? 0);

        if ($containsAny($statusName, $saleKeywords)) {
            if ($dir === 'in')  { $saleIn += $amt; }
            if ($dir === 'out') { $saleOut += $amt; }
        }
        if ($containsAny($statusName, $mukatabaKeywords)) {
            if ($dir === 'in')  { $mktIn += $amt; }
            if ($dir === 'out') { $mktOut += $amt; }
        }

        if ($e->is_office ?? false) {
            if ($containsAny($statusName, $officeProfitInKW)  && $dir === 'in')  $offProfitIn  += $amt;
            if ($containsAny($statusName, $officeProfitOutKW) && $dir === 'out') $offProfitOut += $amt;
        }
    }

    $saleNet = $saleIn - $saleOut;
    $mktNet  = $mktIn  - $mktOut;

    // إن لم يوجد تصنيف واضح لربح المكتب من الحالات، نستعمل صافي المكتب كبديل منطقي
    $officeProfit = ($offProfitIn + $offProfitOut) > 0 ? ($offProfitIn - $offProfitOut) : $offNet;

    $saleFlow = max($saleIn + $saleOut, 0.00001);
    $mktFlow  = max($mktIn  + $mktOut,  0.00001);
    $saleInPct  = $pct($saleIn,  $saleFlow);
    $saleOutPct = $pct($saleOut, $saleFlow);
    $mktInPct   = $pct($mktIn,   $mktFlow);
    $mktOutPct  = $pct($mktOut,  $mktFlow);

    $saleNetClass = $saleNet >= 0 ? 'text-success' : 'text-danger';
    $mktNetClass  = $mktNet  >= 0 ? 'text-success' : 'text-danger';
    $offProfClass = $officeProfit >= 0 ? 'text-success' : 'text-danger';
@endphp

{{-- كروت تفصيلية: المستثمرون والمكتب --}}
<div class="row g-3 mb-3" dir="rtl">
    {{-- المستثمرون --}}
    <div class="col-12 col-xl-6">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="kpi-icon"><i class="bi bi-person-badge fs-4 text-primary"></i></div>
                <div>
                    <div class="fw-bold">المستثمرون</div>
                    <div class="subnote">تفصيل الحركات ضمن نتائج البحث الحالية</div>
                </div>
            </div>

            <div class="row g-2">
                <div class="col-4">
                    <div class="border rounded p-2 h-100">
                        <div class="subnote">داخل</div>
                        <div class="kpi-value fw-bold text-success">{{ number_format($invIn,2) }}</div>
                        <div class="progress bar-8 mt-1"><div class="progress-bar" style="width: {{ $invInPct }}%"></div></div>
                        <div class="mini text-muted mt-1">{{ $invInPct }}% من تدفق المستثمر</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border rounded p-2 h-100">
                        <div class="subnote">خارج</div>
                        <div class="kpi-value fw-bold text-danger">{{ number_format($invOut,2) }}</div>
                        <div class="progress bar-8 mt-1"><div class="progress-bar bg-danger" style="width: {{ $invOutPct }}%"></div></div>
                        <div class="mini text-muted mt-1">{{ $invOutPct }}% من تدفق المستثمر</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border rounded p-2 h-100">
                        <div class="subnote">الصافي</div>
                        <div class="kpi-value fw-bold {{ $invNetClass }}">{{ number_format($invNet,2) }}</div>
                        <div class="mini text-muted mt-1">داخل − خارج</div>
                    </div>
                </div>
            </div>

            <div class="row g-2 mt-2">
                <div class="col-6">
                    <div class="border rounded p-2 h-100">
                        <div class="subnote"><i class="bi bi-bank"></i> بنك</div>
                        <div class="d-flex justify-content-between mini mt-1">
                            <span>داخل</span><strong class="text-success">{{ number_format($invBankIn,2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mini">
                            <span>خارج</span><strong class="text-danger">{{ number_format($invBankOut,2) }}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded p-2 h-100">
                        <div class="subnote"><i class="bi bi-safe2"></i> خزنة</div>
                        <div class="d-flex justify-content-between mini mt-1">
                            <span>داخل</span><strong class="text-success">{{ number_format($invSafeIn,2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mini">
                            <span>خارج</span><strong class="text-danger">{{ number_format($invSafeOut,2) }}</strong>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- المكتب --}}
    <div class="col-12 col-xl-6">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="kpi-icon"><i class="bi bi-building fs-4 text-primary"></i></div>
                <div>
                    <div class="fw-bold">المكتب</div>
                    <div class="subnote">تفصيل الحركات ضمن نتائج البحث الحالية</div>
                </div>
            </div>

            <div class="row g-2">
                <div class="col-4">
                    <div class="border rounded p-2 h-100">
                        <div class="subnote">داخل</div>
                        <div class="kpi-value fw-bold text-success">{{ number_format($offIn,2) }}</div>
                        <div class="progress bar-8 mt-1"><div class="progress-bar" style="width: {{ $offInPct }}%"></div></div>
                        <div class="mini text-muted mt-1">{{ $offInPct }}% من تدفق المكتب</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border rounded p-2 h-100">
                        <div class="subnote">خارج</div>
                        <div class="kpi-value fw-bold text-danger">{{ number_format($offOut,2) }}</div>
                        <div class="progress bar-8 mt-1"><div class="progress-bar bg-danger" style="width: {{ $offOutPct }}%"></div></div>
                        <div class="mini text-muted mt-1">{{ $offOutPct }}% من تدفق المكتب</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border rounded p-2 h-100">
                        <div class="subnote">الصافي</div>
                        <div class="kpi-value fw-bold {{ $offNetClass }}">{{ number_format($offNet,2) }}</div>
                        <div class="mini text-muted mt-1">داخل − خارج</div>
                    </div>
                </div>
            </div>

            <div class="row g-2 mt-2">
                <div class="col-6">
                    <div class="border rounded p-2 h-100">
                        <div class="subnote"><i class="bi bi-bank"></i> بنك</div>
                        <div class="d-flex justify-content-between mini mt-1">
                            <span>داخل</span><strong class="text-success">{{ number_format($offBankIn,2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mini">
                            <span>خارج</span><strong class="text-danger">{{ number_format($offBankOut,2) }}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded p-2 h-100">
                        <div class="subnote"><i class="bi bi-safe2"></i> خزنة</div>
                        <div class="d-flex justify-content-between mini mt-1">
                            <span>داخل</span><strong class="text-success">{{ number_format($offSafeIn,2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mini">
                            <span>خارج</span><strong class="text-danger">{{ number_format($offSafeOut,2) }}</strong>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ===== كروت إضافية: فرق البيع / فرق المكاتبة / ربح المكتب ===== --}}
<div class="row g-3 mb-3" dir="rtl">
    <div class="col-12 col-md-4">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="kpi-icon"><i class="bi bi-bag-check fs-4 text-success"></i></div>
                <div>
                    <div class="fw-bold">فرق البيع</div>
                    <div class="subnote">(داخل البيع − خارج البيع)</div>
                </div>
            </div>
            <div class="row g-2">
                <div class="col-4">
                    <div class="border rounded p-2 text-center">
                        <div class="mini text-muted">داخل</div>
                        <div class="fw-bold text-success">{{ number_format($saleIn,2) }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border rounded p-2 text-center">
                        <div class="mini text-muted">خارج</div>
                        <div class="fw-bold text-danger">{{ number_format($saleOut,2) }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border rounded p-2 text-center">
                        <div class="mini text-muted">الصافي</div>
                        <div class="fw-bold {{ $saleNetClass }}">{{ number_format($saleNet,2) }}</div>
                    </div>
                </div>
            </div>
            <div class="progress bar-8 mt-3">
                <div class="progress-bar" style="width: {{ $saleInPct }}%"></div>
            </div>
            <div class="mini text-muted mt-1">نسبة الداخل من إجمالي تدفق البيع: {{ $saleInPct }}%</div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="kpi-icon"><i class="bi bi-journal-text fs-4 text-primary"></i></div>
                <div>
                    <div class="fw-bold">فرق المكاتبة</div>
                    <div class="subnote">(داخل المكاتبة − خارج المكاتبة)</div>
                </div>
            </div>
            <div class="row g-2">
                <div class="col-4">
                    <div class="border rounded p-2 text-center">
                        <div class="mini text-muted">داخل</div>
                        <div class="fw-bold text-success">{{ number_format($mktIn,2) }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border rounded p-2 text-center">
                        <div class="mini text-muted">خارج</div>
                        <div class="fw-bold text-danger">{{ number_format($mktOut,2) }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border rounded p-2 text-center">
                        <div class="mini text-muted">الصافي</div>
                        <div class="fw-bold {{ $mktNetClass }}">{{ number_format($mktNet,2) }}</div>
                    </div>
                </div>
            </div>
            <div class="progress bar-8 mt-3">
                <div class="progress-bar" style="width: {{ $mktInPct }}%"></div>
            </div>
            <div class="mini text-muted mt-1">نسبة الداخل من إجمالي تدفق المكاتبة: {{ $mktInPct }}%</div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="kpi-icon"><i class="bi bi-briefcase fs-4 text-warning"></i></div>
                <div>
                    <div class="fw-bold">ربح المكتب</div>
                    <div class="subnote">
                        (عوائد/أرباح/عمولات المكتب − مصاريف/عمولات مدفوعة)
                        <span class="mini text-muted d-block">* إن لم تتوفر هذه التصنيفات، يُعرض صافي المكتب.</span>
                    </div>
                </div>
            </div>
            <div class="row g-2">
                <div class="col-4">
                    <div class="border rounded p-2 text-center">
                        <div class="mini text-muted">عوائد</div>
                        <div class="fw-bold text-success">{{ number_format($offProfitIn,2) }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border rounded p-2 text-center">
                        <div class="mini text-muted">مصاريف</div>
                        <div class="fw-bold text-danger">{{ number_format($offProfitOut,2) }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border rounded p-2 text-center">
                        <div class="mini text-muted">الربح</div>
                        <div class="fw-bold {{ $offProfClass }}">{{ number_format($officeProfit,2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- الجدول --}}
<div class="card shadow-sm">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover align-middle text-center mb-0">
            <thead class="table-light" style="position: sticky; top: 0; z-index: 2;">
                <tr>
                    <th style="width:120px">التاريخ</th>
                    <th>الجهة</th>
                    <th>الحالة</th>
                    <th>النوع</th>
                    <th>الاتجاه</th>
                    <th class="text-end">المبلغ</th>
                    <th>الحساب</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $e)
                    <tr>
                        <td>{{ $e->entry_date?->format('Y-m-d') }}</td>
                        <td>
                            @if($e->is_office)
                                <span class="badge bg-secondary">المكتب</span>
                            @else
                                {{ $e->investor->name ?? '-' }}
                            @endif
                        </td>
                        <td>
                            @php $statusText = $e->status->name ?? '-'; @endphp
                            @if(!empty($e->notes))
                                <span
                                    data-bs-toggle="tooltip"
                                    data-bs-container="body"
                                    data-bs-placement="top"
                                    title="{{ $e->notes }}">
                                    {{ $statusText }}
                                </span>
                            @else
                                {{ $statusText }}
                            @endif
                        </td>
                        <td>{{ $e->type->name ?? '-' }}</td>
                        <td>
                            @if($e->direction === 'in')
                                <span class="badge bg-success">داخل</span>
                            @else
                                <span class="badge bg-danger">خارج</span>
                            @endif
                        </td>
                        <td class="text-end fw-semibold">
                            @if($e->direction === 'out') - @endif
                            {{ number_format($e->amount, 2) }}
                        </td>
                        <td>
                            @if($e->bankAccount)
                                <i class="bi bi-bank"></i> {{ $e->bankAccount->name }}
                            @elseif($e->safe)
                                <i class="bi bi-safe2"></i> {{ $e->safe->name }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-5 text-muted">لا توجد قيود مطابقة للبحث.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($entries->hasPages())
        <div class="mt-3 p-3">
            {{ $entries->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
.table-responsive { max-height: 65vh; }

/* كروت */
:root{ --card-r:1rem; --soft:0 6px 18px rgba(0,0,0,.06); --soft2:0 10px 24px rgba(0,0,0,.08); }
.kpi-card{ border:1px solid #eef2f7; border-radius:var(--card-r); box-shadow:var(--soft); height:100%; transition:.2s; }
.kpi-card:hover{ box-shadow:var(--soft2); transform: translateY(-2px); }
.kpi-icon{ width:52px;height:52px;border-radius:.9rem;display:grid;place-items:center;background:#f4f6fb; }
.kpi-value{ font-size:1.6rem; line-height:1; }
.subnote{ font-size:.85rem; color:#6b7280; }
.bar-8{ height:8px; }
.mini{ font-size:.9rem; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form         = document.getElementById('filtersForm');
    const catSelect    = document.getElementById('party_category');
    const investorWrap = document.getElementById('investorWrap');
    const investorSel  = document.getElementById('investor_id');
    const statusSel    = document.getElementById('status_id');
    const accountType  = document.getElementById('account_type');
    const fromDate     = document.getElementById('from');
    const toDate       = document.getElementById('to');

    // Debounce
    let timer = null;
    function autosubmit() {
        clearTimeout(timer);
        timer = setTimeout(() => form.requestSubmit(), 300);
    }

    // إظهار/إخفاء المستثمر بدون تغيير الشبكة
    function syncInvestorVisibility() {
        const isInv = (catSelect.value === 'investors' || catSelect.value === '');
        investorWrap.classList.toggle('invisible', !isInv);
        investorSel.disabled = !isInv;
        if (!isInv) investorSel.value = '';
    }

    // فلترة الحالات حسب الفئة + إخفاء التحويل (type=3)
    const allStatusOptions = Array.from(statusSel.querySelectorAll('option[data-cat]'));
    function filterStatusesByCategory() {
        const cat = catSelect.value;
        let keepSelected = false;

        allStatusOptions.forEach(op => {
            const isTransfer = (op.dataset.type === '3');
            const show = (cat === '' ? true : (op.dataset.cat === cat));
            if (isTransfer || !show) {
                op.disabled = true;
                op.hidden   = true;
                if (op.selected) keepSelected = true;
            } else {
                op.disabled = false;
                op.hidden   = false;
            }
        });

        if (keepSelected) statusSel.value = '';
    }

    // تفعيل Tooltips
    if (window.bootstrap && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el, {container: 'body'});
        });
    }

    // ربط الأحداث مع Auto-submit
    [catSelect, investorSel, statusSel, accountType].forEach(el => {
        el && el.addEventListener('change', () => {
            if (el === catSelect) { syncInvestorVisibility(); filterStatusesByCategory(); }
            autosubmit();
        });
    });

    [fromDate, toDate].forEach(el => {
        el && el.addEventListener('change', autosubmit);
        el && el.addEventListener('keyup', (e)=> { if (e.key === 'Enter') autosubmit(); });
    });

    // زر مسح
    const btnClear = document.getElementById('btnClear');
    if (btnClear) {
        btnClear.addEventListener('click', function (e) {
            e.preventDefault();
            [catSelect, investorSel, statusSel, accountType, fromDate, toDate].forEach(el => { if (el) el.value = ''; });
            form.requestSubmit();
        });
    }

    // init
    syncInvestorVisibility();
    filterStatusesByCategory();
});
</script>
@endpush
