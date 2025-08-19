@extends('layouts.master')

@section('title', 'قائمة العقود')

@section('content')

<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">قائمة العقود</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">العقود</li>
        </ol>
    </nav>
</div>

@php
    use Illuminate\Support\Facades\Schema;

    // أسماء الحالات (مرنة بالعربي/إنجليزي)
    $namesEnded   = ['منتهي','منتهى','سداد مبكر','سداد مُبكر','سداد مبكّر','Completed','Finished','Closed','Early Settlement','Settled'];
    $namesActive  = ['نشط','Active','Open','In Progress'];
    $namesPending = ['معلق','Pending','On Hold','Awaiting'];
    $namesNoInv   = ['بدون مستثمر','No Investor'];

    // إجماليات على مستوى النظام (غير متأثرة بالفلاتر)
    $contractsTotalAll          = $contractsTotalAll          ?? (\App\Models\Contract::query()->count());
    $contractsNoInvestorAll     = $contractsNoInvestorAll     ?? (\App\Models\Contract::query()->doesntHave('investors')->count());

    // كشف أعمدة الحالة المحتملة
    $statusIdCol = null;
    foreach (['status_id', 'contract_status_id', 'state_id'] as $col) {
        if (Schema::hasColumn('contracts', $col)) { $statusIdCol = $col; break; }
    }
    $statusTextCol = null;
    foreach (['status', 'state'] as $col) {
        if (Schema::hasColumn('contracts', $col)) { $statusTextCol = $col; break; }
    }

    // محاولة جلب IDs للحالات لو في جدول ContractStatus
    $endedIds = $pendingIds = $activeIds = [];
    if (class_exists(\App\Models\ContractStatus::class)) {
        $endedIds   = \App\Models\ContractStatus::whereIn('name', $namesEnded)->pluck('id')->all();
        $pendingIds = \App\Models\ContractStatus::whereIn('name', $namesPending)->pluck('id')->all();
        $activeIds  = \App\Models\ContractStatus::whereIn('name', $namesActive)->pluck('id')->all();
    }

    // حسابات مرنة للحالات
    $contractsEndedAll = $contractsEndedAll ?? (function() use ($statusIdCol,$statusTextCol,$namesEnded){
        $q = \App\Models\Contract::query();
        if ($statusIdCol) {
            // لو ما قدرنا نحدد IDs للحالات المنتهية، جرّب بدائل منطقية
            return !empty($GLOBALS['endedIds'] ?? []) ? $q->whereIn($statusIdCol, $GLOBALS['endedIds'])->count()
                 : (Schema::hasColumn('contracts','is_closed') ? $q->where('is_closed',1)->count()
                 : (Schema::hasColumn('contracts','closed_at')  ? $q->whereNotNull('closed_at')->count()
                 : 0));
        } elseif ($statusTextCol) {
            return $q->whereIn($statusTextCol, $namesEnded)->count();
        } elseif (Schema::hasColumn('contracts','is_closed')) {
            return $q->where('is_closed',1)->count();
        } elseif (Schema::hasColumn('contracts','closed_at')) {
            return $q->whereNotNull('closed_at')->count();
        }
        return 0;
    })();

    $contractsPendingAll = $contractsPendingAll ?? (function() use ($statusIdCol,$statusTextCol,$namesPending){
        $q = \App\Models\Contract::query();
        if ($statusIdCol && !empty($GLOBALS['pendingIds'] ?? [])) {
            return $q->whereIn($statusIdCol, $GLOBALS['pendingIds'])->count();
        } elseif ($statusTextCol) {
            return $q->whereIn($statusTextCol, $namesPending)->count();
        }
        return 0; // لو مافيش حقل حالة نصي/معرّف، نخليها 0
    })();

    $contractsActiveAll = $activeContractsTotalAll ?? (function() use ($statusIdCol,$statusTextCol,$namesEnded,$namesActive){
        $q = \App\Models\Contract::query();
        if ($statusIdCol) {
            if (!empty($GLOBALS['activeIds'] ?? [])) {
                return $q->whereIn($statusIdCol, $GLOBALS['activeIds'])->count();
            } elseif (!empty($GLOBALS['endedIds'] ?? [])) {
                return $q->whereNotIn($statusIdCol, $GLOBALS['endedIds'])->count();
            }
        } elseif ($statusTextCol) {
            // عندنا نص: استبعد المنتهية
            return $q->whereNotIn($statusTextCol, $namesEnded)->count();
        } elseif (Schema::hasColumn('contracts','is_closed')) {
            return $q->where('is_closed',0)->count();
        } elseif (Schema::hasColumn('contracts','closed_at')) {
            return $q->whereNull('closed_at')->count();
        }
        return $q->count(); // آخر حل
    })();

    $contractsNoInvestorAll = $contractsNoInvestorAll ?? (\App\Models\Contract::query()->doesntHave('investors')->count());

    // نسب
    $pct = fn($num) => ($contractsTotalAll>0) ? round(($num/$contractsTotalAll)*100,1) : 0;

    $activePct    = $pct($contractsActiveAll);
    $pendingPct   = $pct($contractsPendingAll);
    $noInvPct     = $pct($contractsNoInvestorAll);
    $endedPct     = $pct($contractsEndedAll);
@endphp

{{-- كروت الإحصائيات --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
    :root { --card-r: 1rem; --soft: 0 6px 18px rgba(0,0,0,.06); --soft2: 0 10px 24px rgba(0,0,0,.08); }
    .kpi-card{ border:1px solid #eef2f7; border-radius:var(--card-r); box-shadow:var(--soft); transition:.2s; height:100%;}
    .kpi-card:hover{ box-shadow:var(--soft2); transform: translateY(-2px); }
    .kpi-icon{ width:52px;height:52px;border-radius:.9rem;display:grid;place-items:center;background:#f4f6fb; }
    .kpi-value{ font-size:1.85rem; line-height:1; }
    .subnote{ font-size:.8rem; color:#6b7280; }
    .bar-8{ height:8px; }
</style>

<div class="row g-3 mb-3" dir="rtl">
    <div class="col-12 col-md-2">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-journal-text fs-4 text-primary"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">إجمالي العقود — كل النظام</div>
                    <div class="kpi-value fw-bold">{{ number_format($contractsTotalAll) }}</div>
                    <div class="subnote">غير متأثر بالفلاتر</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-2">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-check2-circle fs-4 text-success"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">عقود نشطة</div>
                    <div class="kpi-value fw-bold">{{ number_format($contractsActiveAll) }}</div>
                    <div class="subnote">النسبة: {{ number_format($activePct,1) }}%</div>
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
                    <div class="subnote">عقود معلّقة</div>
                    <div class="kpi-value fw-bold">{{ number_format($contractsPendingAll) }}</div>
                    <div class="subnote">النسبة: {{ number_format($pendingPct,1) }}%</div>
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
                    <div class="subnote">بدون مستثمر</div>
                    <div class="kpi-value fw-bold">{{ number_format($contractsNoInvestorAll) }}</div>
                    <div class="subnote">النسبة: {{ number_format($noInvPct,1) }}%</div>
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
                    <div class="subnote">عقود منتهية</div>
                    <div class="kpi-value fw-bold">{{ number_format($contractsEndedAll) }}</div>
                    <div class="subnote">النسبة: {{ number_format($endedPct,1) }}%</div>
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

{{-- شريط الأدوات --}}
<div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">
        <a href="{{ route('contracts.create') }}" class="btn btn-outline-success">
            + إضافة عقد جديد
        </a>

        <span class="ms-auto small text-muted">
            النتائج: <strong>{{ $contracts->total() }}</strong>
        </span>

        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#filterBar" aria-expanded="false">
            تصفية متقدمة
        </button>
    </div>

    <div class="collapse @if(request()->hasAny(['customer','type','status','from','to'])) show @endif border-top" id="filterBar">
        <div class="card-body">
            <form action="{{ route('contracts.index') }}" method="GET" class="row gy-2 gx-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label mb-1">العميل</label>
                    <input type="text" name="customer" value="{{ request('customer') }}" class="form-control form-control-sm" placeholder="اسم العميل">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">نوع البضاعة</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        @foreach($productTypes as $type)
                            <option value="{{ $type->id }}" @selected(request('type') == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">حالة العقد</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        @foreach($contractStatuses as $status)
                            <option value="{{ $status->id }}" @selected(request('status') == $status->id)>{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">من تاريخ</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">إلى تاريخ</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md-1 d-flex gap-2">
                    <button class="btn btn-primary btn-sm w-100">بحث</button>
                    <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary btn-sm w-100">مسح</a>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- الجدول --}}
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0">
                <thead class="table-light position-sticky top-0">
                    <tr>
                        <th style="width:60px">#</th>
                        <th>العميل</th>
                        <th>الكفيل</th>
                        <th>نوع البضاعة</th>
                        <th>الحالة</th>
                        <th>إجمالي العقد</th>
                        <th>ربح المستثمر</th>
                        <th style="min-width:160px;">المستثمرون</th>
                        <th>تاريخ البداية</th>
                        <th style="width:190px">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contracts as $contract)
                        @php
                            $statusName = $contract->contractStatus->name ?? '-';
                            $badge = match($statusName) {
                                'نشط' => 'secondary',
                                'معلق' => 'warning',
                                'بدون مستثمر' => 'danger',
                                default => 'success'
                            };
                            $count = $contract->investors->count();
                            $tip   = $contract->investors
                                    ->map(fn($i) => ($i->name ?? ('#'.$i->id)).' '.number_format($i->pivot->share_percentage,2).'%')
                                    ->join('، ');
                        @endphp
                        <tr>
                            <td class="text-muted">
                                {{ $loop->iteration + ($contracts->currentPage() - 1) * $contracts->perPage() }}
                            </td>
                            <td class="text-center">{{ $contract->customer->name ?? '-' }}</td>
                            <td class="text-center">{{ $contract->guarantor->name ?? '-' }}</td>
                            <td>{{ $contract->productType->name ?? '-' }}</td>
                            <td><span class="badge bg-{{ $badge }}">{{ $statusName }}</span></td>
                            <td>{{ number_format($contract->total_value, 0) }}</td>
                            <td>{{ number_format($contract->investor_profit, 0) }}</td>
                            <td class="text-center">
                                @if($count)
                                    <span class="badge bg-info text-dark" data-bs-toggle="tooltip" title="{{ $tip }}">
                                        {{ $count }} مستثمر
                                    </span>
                                @else
                                    <span class="badge bg-danger" title="0.00%">
                                        0 مستثمر
                                    </span>
                                @endif
                            </td>
                            <td>{{ optional($contract->start_date)->format('Y-m-d') }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('contracts.show', $contract) }}" class="btn btn-outline-secondary btn-sm">عرض</a>
                                {{-- 
                                <a href="{{ route('contracts.edit', $contract) }}" class="btn btn-outline-primary btn-sm">تعديل</a>
                                <form action="{{ route('contracts.destroy', $contract) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('هل أنت متأكد من حذف هذا العقد؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                </form> 
                                --}}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-5">
                                <div class="text-muted">
                                    لا توجد عقود مطابقة لبحثك.
                                    <a href="{{ route('contracts.index') }}" class="ms-1">عرض الكل</a>
                                </div>
                                <div class="mt-3">
                                    <a href="{{ route('contracts.create') }}" class="btn btn-sm btn-success">
                                        + إضافة أول عقد
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
