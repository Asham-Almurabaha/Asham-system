@extends('layouts.master')

@section('title', __('Contracts List'))

@section('content')

<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ __('Contracts List') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">{{ __('Contracts') }}</li>
        </ol>
    </nav>
</div>

@php
    use Illuminate\Support\Facades\Schema;

    // عملة افتراضية للعرض فقط
    $currencySymbol = $currencySymbol ?? 'ر.س';

    // ✅ التقسيم الصارم للحالات
    $namesEnded   = ['منتهي','سداد مبكر']; // المنتهي = منتهي أو سداد مبكر
    $namesPending = ['معلق'];               // المعلّق = معلق فقط

    $endedNamesTx   = implode('، ', $namesEnded);
    $pendingNamesTx = implode('، ', $namesPending);

    // إجماليات على مستوى النظام (غير متأثرة بالفلاتر)
    $contractsTotalAll = $contractsTotalAll ?? (\App\Models\Contract::query()->count());

    // كشف أعمدة الحالة المحتملة
    $statusIdCol = null;
    foreach (['status_id', 'contract_status_id', 'state_id'] as $col) {
        if (Schema::hasColumn('contracts', $col)) { $statusIdCol = $col; break; }
    }
    $statusTextCol = null;
    foreach (['status', 'state'] as $col) {
        if (Schema::hasColumn('contracts', $col)) { $statusTextCol = $col; break; }
    }

    // محاولة جلب IDs للحالات من جدول ContractStatus (إن وجد)
    $endedIds = $pendingIds = [];
    if (class_exists(\App\Models\ContractStatus::class)) {
        $endedIds   = \App\Models\ContractStatus::whereIn('name', $namesEnded)->pluck('id')->all();
        $pendingIds = \App\Models\ContractStatus::whereIn('name', $namesPending)->pluck('id')->all();
    }

    // ===== منتهي =====
    $contractsEndedAll = $contractsEndedAll ?? (function() use ($statusIdCol,$statusTextCol,$namesEnded,$endedIds){
        $q = \App\Models\Contract::query();
        if ($statusIdCol) {
            return !empty($endedIds) ? $q->whereIn($statusIdCol, $endedIds)->count() : 0;
        } elseif ($statusTextCol) {
            return $q->whereIn($statusTextCol, $namesEnded)->count();
        }
        return 0;
    })();

    // ===== معلّق =====
    $contractsPendingAll = $contractsPendingAll ?? (function() use ($statusIdCol,$statusTextCol,$namesPending,$pendingIds){
        $q = \App\Models\Contract::query();
        if ($statusIdCol && !empty($pendingIds)) {
            return $q->whereIn($statusIdCol, $pendingIds)->count();
        } elseif ($statusTextCol) {
            return $q->whereIn($statusTextCol, $namesPending)->count();
        }
        return 0;
    })();

    // ===== بدون مستثمر =====
    $contractsNoInvestorAll = $contractsNoInvestorAll ?? (\App\Models\Contract::query()->doesntHave('investors')->count());

    // ===== نشط = كل ما عدا (منتهي + معلّق) =====
    $contractsActiveAll = $activeContractsTotalAll ?? (function() use ($statusIdCol,$statusTextCol,$namesEnded,$namesPending,$endedIds,$pendingIds){
        $q = \App\Models\Contract::query();
        if ($statusIdCol) {
            $excludeIds = array_merge($endedIds, $pendingIds);
            if (!empty($excludeIds)) {
                return $q->whereNotIn($statusIdCol, $excludeIds)->count();
            }
        } elseif ($statusTextCol) {
            $excludeNames = array_merge($namesEnded, $namesPending);
            return $q->whereNotIn($statusTextCol, $excludeNames)->count();
        }
        // fallback لو لم نجد عمود حالة مناسب
        return $q->count();
    })();

    // نسب
    $pct = fn($num) => ($contractsTotalAll>0) ? round(($num/$contractsTotalAll)*100,1) : 0;

    $activePct  = $pct($contractsActiveAll);
    $pendingPct = $pct($contractsPendingAll);
    $noInvPct   = $pct($contractsNoInvestorAll);
    $endedPct   = $pct($contractsEndedAll);

    // ===== دالة أيقونات الحالات (Dropdown + جدول) =====
    $normalize = fn($arr) => array_map(fn($s) => mb_strtolower(trim((string)$s), 'UTF-8'), $arr);
    $statusIcon = function ($name) use ($normalize) {
        $n = mb_strtolower(trim((string)$name), 'UTF-8');

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
                switch ($key) {
                    case 'active':           return ['bi-check2-circle',        'text-success'];
                    case 'pending':          return ['bi-hourglass-split',      'text-warning'];
                    case 'ended':            return ['bi-flag-fill',            'text-secondary'];
                    case 'canceled':         return ['bi-slash-circle',         'text-danger'];
                    case 'late':             return ['bi-exclamation-triangle', 'text-danger'];
                    case 'review':           return ['bi-eye',                  'text-info'];
                    case 'draft':            return ['bi-file-earmark',         'text-muted'];
                    case 'early_settlement': return ['bi-cash-coin',            'text-success'];
                    case 'rescheduled':      return ['bi-arrow-repeat',         'text-primary'];
                    case 'suspended':        return ['bi-pause-circle',         'text-warning'];
                    case 'renewed':          return ['bi-arrow-clockwise',      'text-primary'];
                    case 'in_progress':      return ['bi-gear-wide-connected',  'text-primary'];
                    case 'archived':         return ['bi-archive',              'text-muted'];
                    case 'deferred':         return ['bi-calendar-minus',       'text-warning'];
                    case 'apologized':       return ['bi-emoji-neutral',        'text-muted'];
                    case 'collection':       return ['bi-piggy-bank',           'text-info'];
                    case 'dispute':          return ['bi-exclamation-octagon',  'text-danger'];
                    case 'partial':          return ['bi-pie-chart',            'text-info'];
                }
            }
        }
        return ['bi-circle', 'text-primary']; // افتراضي
    };
@endphp

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">


{{-- ====== ملخص أقساط هذا الشهر (ستايل مماثل لصفحة المستثمر) ====== --}}
@php
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
@endphp

<div class="card shadow-sm mb-4" dir="rtl">
    <div class="card-header bg-white d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <h6 class="mb-0">{{ __('Monthly Installments Summary') }} <span class="text-muted">({{ $monthLabel }})</span></h6>
            <span class="subnote"><i class="bi bi-filter"></i> {{ __('Excludes statuses:') }} {{ $excludedStatusesTx }}</span>
        </div>
        {{-- اختيار سريع للشهر/السنة مع الحفاظ على فلاتر العقود الحالية --}}
        <form action="{{ route('contracts.index') }}" method="GET" class="d-flex align-items-center gap-2">
            @foreach(request()->except(['m','y','page']) as $k => $v)
                @if(is_array($v))
                    @foreach($v as $vv)
                        <input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endif
            @endforeach

            <input type="number" name="m" min="1" max="12" class="form-control form-control-sm" style="width:86px" value="{{ request('m', $mVal) }}" placeholder="{{ __('Month') }}">
            <input type="number" name="y" min="2000" max="2100" class="form-control form-control-sm" style="width:92px" value="{{ request('y', $yVal) }}" placeholder="{{ __('Year') }}">
            <button class="btn btn-outline-primary btn-sm">{{ __('Update') }}</button>
        </form>
    </div>
    <div class="card-body p-20">
        <div class="row g-3">
            {{-- عدد الأقساط --}}
            <div class="col-12 col-md-3">
                <div class="kpi-card p-3">
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
            {{-- إجمالي المستحق + المدفوع + نسبة --}}
            <div class="col-12 col-md-5">
                <div class="kpi-card p-3">
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
                            <div class="fw-bold">{{ number_format($paidSum,2) }}</div>
                        </div>
                    </div>
                    <div class="progress bar-8" title="{{ __('Paid Percentage') }}">
                        <div class="progress-bar" style="width: {{ $paidPct2 }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between subnote mt-1">
                        <span>{{ __('Percentage:') }} {{ number_format($paidPct2,1) }}%</span>
                        <span>{{ __('Remaining:') }} {{ number_format($remainSum,2) }}</span>
                    </div>
                </div>
            </div>
            {{-- المتبقي --}}
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
            {{-- (اختياري) إضافة جدول مختصر للأقساط لاحقاً --}}
        </div>
    </div>
</div>

{{-- ====== KPI Cards ====== --}}
<div class="row g-3 mb-3" dir="rtl">
    <div class="col-12 col-md-2">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-journal-text fs-4 text-primary"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">{{ __('Total Contracts — Entire System') }}</div>
                    <div class="kpi-value fw-bold">{{ number_format($contractsTotalAll) }}</div>
                    <div class="subnote">{{ __('Not affected by filters') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-2">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-check2-circle fs-4 text-success"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">
                        {{ __('Active Contracts') }}
                        <span class="hint" data-bs-toggle="tooltip" title="{{ __('Active = All statuses except :ended and :pending', ['ended' => $endedNamesTx, 'pending' => $pendingNamesTx]) }}">
                            <i class="bi bi-info-circle"></i>
                        </span>
                    </div>
                    <div class="kpi-value fw-bold">{{ number_format($contractsActiveAll) }}</div>
                    <div class="subnote">{{ __('Percentage:') }} {{ number_format($activePct,1) }}%</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="progress bar-8">
                    <div class="progress-bar" style="width: {{ $activePct }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-2">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-hourglass-split fs-4 text-warning"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">
                        {{ __('Pending Contracts') }}
                        <span class="hint" data-bs-toggle="tooltip" title="{{ __('Includes only:') }} {{ $pendingNamesTx }}">
                            <i class="bi bi-info-circle"></i>
                        </span>
                    </div>
                    <div class="kpi-value fw-bold">{{ number_format($contractsPendingAll) }}</div>
                    <div class="subnote">{{ __('Percentage:') }} {{ number_format($pendingPct,1) }}%</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="progress bar-8">
                    <div class="progress-bar bg-warning" style="width: {{ $pendingPct }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-2">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-people fs-4 text-danger"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">
                        {{ __('Without Investor') }}
                        <span class="hint" data-bs-toggle="tooltip" title="{{ __('Contracts that are not linked to any investor (from the relationship)') }}">
                            <i class="bi bi-info-circle"></i>
                        </span>
                    </div>
                    <div class="kpi-value fw-bold">{{ number_format($contractsNoInvestorAll) }}</div>
                    <div class="subnote">{{ __('Percentage:') }} {{ number_format($noInvPct,1) }}%</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="progress bar-8">
                    <div class="progress-bar bg-danger" style="width: {{ $noInvPct }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-2">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-flag-fill fs-4 text-secondary"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">
                        {{ __('Ended Contracts') }}
                        <span class="hint" data-bs-toggle="tooltip" title="{{ __('Includes:') }} {{ $endedNamesTx }}">
                            <i class="bi bi-info-circle"></i>
                        </span>
                    </div>
                    <div class="kpi-value fw-bold">{{ number_format($contractsEndedAll) }}</div>
                    <div class="subnote">{{ __('Percentage:') }} {{ number_format($endedPct,1) }}%</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="progress bar-8">
                    <div class="progress-bar bg-secondary" style="width: {{ $endedPct }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ====== شريط الأدوات ====== --}}
<div class="card shadow-sm mb-3">
  <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">

    {{-- أزرار الإجراءات --}}
    <div class="btn-group" role="group" aria-label="{{ __('Contract Actions') }}">
      <a href="{{ route('contracts.create') }}" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> {{ __('Add New Contract') }}
      </a>
      @role('admin')
        <a href="{{ route('contracts.import.form') }}" class="btn btn-outline-primary">
            <i class="bi bi-upload"></i> {{ __('Import Excel') }}
        </a>
      @endrole
    </div>

    {{-- زر التقارير: يفتح لتحت + أيقونات حسب الحالة --}}
    <div class="dropdown">
      <button type="button"
              class="btn btn-outline-dark dropdown-toggle"
              data-bs-toggle="dropdown"
              data-bs-display="static"   {{-- يثبت الاتجاه: لتحت دائماً --}}
              aria-expanded="false">
        📊 {{ __('Reports') }}
      </button>

      <ul class="dropdown-menu dropdown-menu-end text-end shadow mt-2">
        @foreach($contractStatuses as $status)
          @php
            $name = (string)($status->name ?? '-');
            [$ic, $cls] = $statusIcon($name);
          @endphp
          <li>
            <a class="dropdown-item d-flex align-items-center gap-2"
               href="{{ route('reports.contracts.status', $status->id) }}">
              <i class="bi {{ $ic }} {{ $cls }}"></i>
              <span>{{ $name }}</span>
            </a>
          </li>
        @endforeach

        <li><hr class="dropdown-divider"></li>

        <li>
          <a class="dropdown-item d-flex align-items-center gap-2"
             href="{{ route('reports.contracts.without_investor') }}">
            <i class="bi bi-person-slash text-muted"></i>
            <span>{{ __('Without Investor') }}</span>
          </a>
        </li>
      </ul>
    </div>

    <span class="ms-auto small text-muted">
      {{ __('Results') }}: <strong>{{ $contracts->total() }}</strong>
    </span>

    <button class="btn btn-outline-secondary btn-sm" type="button"
            data-bs-toggle="collapse" data-bs-target="#filterBar" aria-expanded="false" aria-controls="filterBar">
      {{ __('Advanced Filter') }}
    </button>
  </div>

  {{-- ✅ أزلنا type وأضفنا investor_id --}}
  <div class="collapse @if(request()->hasAny(['customer','investor_id','status','from','to'])) show @endif border-top" id="filterBar">
    <div class="card-body">
      <form action="{{ route('contracts.index') }}" method="GET" class="row gy-2 gx-2 align-items-end">
        <div class="col-12 col-md-3">
          <label class="form-label mb-1">{{ __('Customer') }}</label>
          <input type="text" name="customer" value="{{ request('customer') }}" class="form-control form-control-sm" placeholder="{{ __('Customer Name') }}">
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label mb-1">{{ __('Investor') }}</label>
          <select name="investor_id" class="form-select form-select-sm">
            <option value="">{{ __('All') }}</option>
            <option value="_none" @selected(request('investor_id') === '_none')>{{ __('Without Investor') }}</option>
            @foreach($investors as $inv)
              <option value="{{ $inv->id }}" @selected((string)request('investor_id') === (string)$inv->id)>
                {{ $inv->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-6 col-md-1">
          <label class="form-label mb-1">{{ __('Contract Status') }}</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">{{ __('All') }}</option>
            @foreach($contractStatuses as $status)
              <option value="{{ $status->id }}" @selected(request('status') == $status->id)>{{ $status->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label mb-1">{{ __('From Date') }}</label>
          <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm js-date" placeholder="{{ __('YYYY-MM-DD') }}">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">{{ __('To Date') }}</label>
          <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm js-date" placeholder="{{ __('YYYY-MM-DD') }}">
        </div>

        <div class="col-12 col-md-1 d-flex gap-2">
          <button class="btn btn-primary btn-sm w-100">{{ __('Search') }}</button>
          <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary btn-sm w-100">{{ __('Clear') }}</a>
        </div>
      </form>
    </div>
  </div>
</div>


{{-- ====== الجدول ====== --}}
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0">
                <thead class="table-light position-sticky top-0">
                    <tr>
                        <th style="width:60px">#</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Guarantor') }}</th>
                        <th>{{ __('Product Type') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Total Contract') }}</th>
                        <th>{{ __('Investor Profit') }}</th>
                        <th style="min-width:160px;">{{ __('Investors') }}</th>
                        <th>{{ __('Start Date') }}</th>
                        <th style="width:190px">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contracts as $contract)
                        @php
                            $statusName = $contract->contractStatus->name ?? '-';
                            // لون الشارة كما كان
                            $badge = match($statusName) {
                                'نشط' => 'secondary',
                                'معلق' => 'warning',
                                'بدون مستثمر' => 'danger',
                                default => 'success'
                            };
                            // أيقونة الحالة + لونها
                            [$ic, $icCls] = $statusIcon($statusName);

                            $count = $contract->investors->count();
                            $sep   = app()->getLocale() === 'ar' ? '، ' : ', ';
                            $tip   = $contract->investors
                                    ->map(fn($i) => ($i->name ?? ('#'.$i->id)).' '.number_format($i->pivot->share_percentage,2).'%')
                                    ->join($sep);
                        @endphp
                        <tr>
                            <td class="text-muted">
                                {{ $loop->iteration + ($contracts->currentPage() - 1) * $contracts->perPage() }}
                            </td>
                            <td class="text-center">{{ $contract->customer->name ?? '-' }}</td>
                            <td class="text-center">{{ $contract->guarantor->name ?? '-' }}</td>
                            <td>{{ $contract->productType->name ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $badge }} d-inline-flex align-items-center gap-1">
                                    {{ $statusName }}
                                </span>
                            </td>
                            <td>{{ number_format($contract->total_value, 0) }}</td>
                            <td>{{ number_format($contract->investor_profit, 0) }}</td>
                            <td class="text-center">
                                @if($count)
                                    <span class="badge bg-info text-dark" data-bs-toggle="tooltip" title="{{ $tip }}">
                                        {{ $count }} {{ __('Investor') }}
                                    </span>
                                @else
                                    <span class="badge bg-danger" title="0.00%">
                                        0 {{ __('Investor') }}
                                    </span>
                                @endif
                            </td>
                            <td>{{ optional($contract->start_date)->format('Y-m-d') }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('contracts.show', $contract) }}" class="btn btn-outline-secondary btn-sm">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-5">
                                <div class="text-muted">
                                    {{ __('No matching contracts for your search.') }}
                                    <a href="{{ route('contracts.index') }}" class="ms-1">{{ __('View All') }}</a>
                                </div>
                                <div class="mt-3">
                                    <a href="{{ route('contracts.create') }}" class="btn btn-sm btn-success">
                                        + {{ __('Add First Contract') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($contracts->hasPages())
    <div class="card-footer bg-white">
        {{ $contracts->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
    @endif
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
