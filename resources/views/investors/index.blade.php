@extends('layouts.master')

@section('title', 'قائمة المستثمرين')

@section('content')

<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">قائمة المستثمرين</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">المستثمرون</li>
        </ol>
    </nav>
</div>

@php
    // ===== أرقام عامة =====
    $allTotal    = (int)($investorsTotalAll ?? 0);
    $allActive   = (int)($activeInvestorsTotalAll ?? 0);
    $allInactive = max($allTotal - $allActive, 0);

    $activePct   = $allTotal > 0 ? round(($allActive   / $allTotal) * 100, 1) : 0;
    $inactivePct = $allTotal > 0 ? round(($allInactive / $allTotal) * 100, 1) : 0;

    $newThisMonthAll = (int)($newInvestorsThisMonthAll ?? 0);
    $newThisWeekAll  = (int)($newInvestorsThisWeekAll  ?? 0);

    // ===== ملخص أقساط الشهر (من InstallmentsMonthlyService) =====
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

{{-- ====== كروت عامة ====== --}}
<div class="row g-4 mb-3" dir="rtl">
    <div class="col-12 col-md-3">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-people fs-4 text-primary"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">إجمالي المستثمرين — كل النظام</div>
                    <div class="kpi-value fw-bold">{{ number_format($allTotal) }}</div>
                    <div class="subnote">غير متأثر بالفلاتر</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-3">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-person-check fs-4 text-success"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">المستثمرون النشطون</div>
                    <div class="kpi-value fw-bold">{{ number_format($allActive) }}</div>
                    <div class="subnote">نسبة النشطين: {{ number_format($activePct,1) }}%</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="progress bar-8">
                    <div class="progress-bar" style="width: {{ $activePct }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-3">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-person-x fs-4 text-danger"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">غير نشطين</div>
                    <div class="kpi-value fw-bold">{{ number_format($allInactive) }}</div>
                    <div class="subnote">النسبة: {{ number_format($inactivePct,1) }}%</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="progress bar-8">
                    <div class="progress-bar bg-danger" style="width: {{ $inactivePct }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-3">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-calendar2-plus fs-4 text-primary"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">مستثمرون جدد هذا الشهر</div>
                    <div class="kpi-value fw-bold">{{ number_format($newThisMonthAll) }}</div>
                    <div class="subnote">هذا الأسبوع: {{ number_format($newThisWeekAll) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ====== ملخص أقساط هذا الشهر (كل المستثمرين) ====== --}}
<div class="row g-4 mb-4" dir="rtl">
    <div class="col-12 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <h6 class="mb-0">ملخص أقساط هذا الشهر <span class="text-muted">({{ $monthLabel }})</span></h6>
            <span class="subnote"><i class="bi bi-filter"></i> يستثني الحالات: {{ $excludedStatusesTx }}</span>
        </div>

        {{-- اختيار سريع للشهر/السنة --}}
        <form action="{{ route('investors.index') }}" method="GET" class="d-flex align-items-center gap-2">
            {{-- الحفاظ على باراميترات البحث الحالية --}}
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
            <button class="btn btn-outline-primary btn-sm">تحديث</button>
        </form>
    </div>

    {{-- بطاقات الأقساط العامة --}}
    <div class="col-12 col-md-3">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-journal-check fs-4 text-primary"></i></div>
                <div>
                    <div class="subnote">عدد الأقساط المستحقة</div>
                    <div class="kpi-value fw-bold">{{ number_format($dueCount) }}</div>
                    <div class="subnote">هذا الشهر</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-3">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-cash-coin fs-4 text-success"></i></div>
                <div>
                    <div class="subnote">إجمالي المستحق</div>
                    <div class="kpi-value fw-bold">{{ number_format($dueSum, 2) }}</div>
                    <div class="subnote">ريـال</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="progress bar-8">
                    <div class="progress-bar" style="width: {{ $paidPct2 }}%" title="نسبة المدفوع"></div>
                </div>
                <div class="d-flex justify-content-between subnote mt-1">
                    <span>مدفوع: {{ number_format($paidSum,2) }}</span>
                    <span>({{ number_format($paidPct2,1) }}%)</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-3">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-wallet2 fs-4 text-warning"></i></div>
                <div>
                    <div class="subnote">المتبقي للدفع</div>
                    <div class="kpi-value fw-bold">{{ number_format($remainSum, 2) }}</div>
                    <div class="subnote">ريـال</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ====== شريط الأدوات ====== --}}
<div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">
        <a href="{{ route('investors.create') }}" class="btn btn-outline-success">+ إضافة مستثمر جديد</a>
        <span class="ms-auto small text-muted">النتائج: <strong>{{ $investors->total() }}</strong></span>
        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#filterBar" aria-expanded="false" aria-controls="filterBar">
            تصفية متقدمة
        </button>
    </div>

    <div class="collapse @if(request()->hasAny(['q','national_id','phone','email','nationality','title'])) show @endif border-top" id="filterBar">
        <div class="card-body">
            <form action="{{ route('investors.index') }}" method="GET" class="row gy-2 gx-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label mb-1">الاسم</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="اسم المستثمر">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">رقم الهوية</label>
                    <input type="text" name="national_id" value="{{ request('national_id') }}" class="form-control form-control-sm" placeholder="مثال: 1234567890">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">الهاتف</label>
                    <input type="text" name="phone" value="{{ request('phone') }}" class="form-control form-control-sm" placeholder="+9665XXXXXXXX">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">البريد الإلكتروني</label>
                    <input type="email" name="email" value="{{ request('email') }}" class="form-control form-control-sm" placeholder="name@email.com">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">الجنسية</label>
                    <select name="nationality" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        @isset($nationalities)
                            @foreach($nationalities as $nat)
                                <option value="{{ $nat->id }}" @selected(request('nationality') == $nat->id)>{{ $nat->name }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">الوظيفة</label>
                    <select name="title" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        @isset($titles)
                            @foreach($titles as $t)
                                <option value="{{ $t->id }}" @selected(request('title') == $t->id)>{{ $t->name }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button class="btn btn-primary btn-sm w-100">بحث</button>
                    <a href="{{ route('investors.index') }}" class="btn btn-outline-secondary btn-sm w-100">مسح</a>
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
                <thead class="table-light position-sticky top-0" style="z-index: 1;">
                    <tr>
                        <th style="width:60px">#</th>
                        <th>الاسم</th>
                        <th>رقم الهوية</th>
                        <th>الهاتف</th>
                        <th>البريد الإلكتروني</th>
                        <th>الجنسية</th>
                        <th>العنوان</th>
                        <th>الوظيفة</th>
                        <th style="min-width:110px;">صورة الهوية</th>
                        <th style="min-width:110px;">صورة العقد</th>
                        <th style="width:140px">حصة المكتب %</th>
                        <th style="width:190px">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($investors as $investor)
                        <tr>
                            <td class="text-muted">
                                {{ $loop->iteration + ($investors->currentPage() - 1) * $investors->perPage() }}
                            </td>
                            <td class="text-start">{{ $investor->name }}</td>
                            <td dir="ltr">{{ $investor->national_id ?? '—' }}</td>
                            <td dir="ltr">{{ $investor->phone ?? '—' }}</td>
                            <td class="text-start">{{ $investor->email ?? '—' }}</td>
                            <td>{{ optional($investor->nationality)->name ?? '—' }}</td>
                            <td class="text-start">{{ $investor->address ?? '—' }}</td>
                            <td>{{ optional($investor->title)->name ?? '—' }}</td>
                            <td>
                                @if($investor->id_card_image)
                                    <a href="{{ asset('storage/' . $investor->id_card_image) }}" target="_blank" data-bs-toggle="tooltip" title="عرض صورة الهوية بالحجم الكامل">
                                        <img src="{{ asset('storage/' . $investor->id_card_image) }}"
                                             alt="صورة الهوية"
                                             width="70" height="48"
                                             style="object-fit: cover; border-radius: .25rem;">
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($investor->contract_image)
                                    <a href="{{ asset('storage/' . $investor->contract_image) }}" target="_blank" data-bs-toggle="tooltip" title="عرض صورة العقد بالحجم الكامل">
                                        <img src="{{ asset('storage/' . $investor->contract_image) }}"
                                             alt="صورة العقد"
                                             width="70" height="48"
                                             style="object-fit: cover; border-radius: .25rem;">
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ is_numeric($investor->office_share_percentage) ? number_format($investor->office_share_percentage, 2) : '—' }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('investors.show', $investor) }}" class="btn btn-outline-secondary btn-sm">عرض</a>
                                {{-- <a href="{{ route('investors.edit', $investor) }}" class="btn btn-outline-primary btn-sm">تعديل</a>
                                <form action="{{ route('investors.destroy', $investor) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('هل أنت متأكد من حذف هذا المستثمر؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                </form> --}}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="py-5">
                                <div class="text-muted">
                                    لا توجد نتائج مطابقة لبحثك.
                                    <a href="{{ route('investors.index') }}" class="ms-1">عرض الكل</a>
                                </div>
                                <div class="mt-3">
                                    <a href="{{ route('investors.create') }}" class="btn btn-sm btn-success">
                                        + إضافة أول مستثمر
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($investors->hasPages())
    <div class="card-footer bg-white">
        {{ $investors->withQueryString()->links('pagination::bootstrap-5') }}
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
