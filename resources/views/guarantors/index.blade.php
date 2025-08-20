@extends('layouts.master')

@section('title', 'قائمة الكفلاء')

@section('content')

<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">قائمة الكفلاء</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">الكفلاء</li>
        </ol>
    </nav>
</div>

@php
    $allTotal    = (int)($guarantorsTotalAll ?? 0);
    $allActive   = (int)($activeGuarantorsTotalAll ?? 0);
    $allInactive = max($allTotal - $allActive, 0);

    $activePct   = $allTotal > 0 ? round(($allActive   / $allTotal) * 100, 1) : 0;
    $inactivePct = $allTotal > 0 ? round(($allInactive / $allTotal) * 100, 1) : 0;

    $newThisMonthAll = (int)($newGuarantorsThisMonthAll ?? 0);
    $newThisWeekAll  = (int)($newGuarantorsThisWeekAll  ?? 0);
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
                    <div class="subnote">إجمالي الكفلاء — كل النظام</div>
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
                    <div class="subnote">الكفلاء النشطون</div>
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
                    <div class="subnote">كفلاء جدد هذا الشهر</div>
                    <div class="kpi-value fw-bold">{{ number_format($newThisMonthAll) }}</div>
                    <div class="subnote">هذا الأسبوع: {{ number_format($newThisWeekAll) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ====== شريط الأدوات ====== --}}
<div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">
        <a href="{{ route('guarantors.create') }}" class="btn btn-outline-success">+ إضافة كفيل جديد</a>
        <span class="ms-auto small text-muted">النتائج: <strong>{{ $guarantors->total() }}</strong></span>
        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#filterBar" aria-expanded="false" aria-controls="filterBar">
            تصفية متقدمة
        </button>
    </div>

    <div class="collapse @if(request()->hasAny(['q','national_id','phone','email','nationality','title'])) show @endif border-top" id="filterBar">
        <div class="card-body">
            <form action="{{ route('guarantors.index') }}" method="GET" class="row gy-2 gx-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label mb-1">الاسم</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="اسم الكفيل">
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
                    <a href="{{ route('guarantors.index') }}" class="btn btn-outline-secondary btn-sm w-100">مسح</a>
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
                        <th style="width:190px">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($guarantors as $guarantor)
                        <tr>
                            <td class="text-muted">
                                {{ $loop->iteration + ($guarantors->currentPage() - 1) * $guarantors->perPage() }}
                            </td>
                            <td class="text-start">{{ $guarantor->name }}</td>
                            <td dir="ltr">{{ $guarantor->national_id ?? '—' }}</td>
                            <td dir="ltr">{{ $guarantor->phone ?? '—' }}</td>
                            <td class="text-start">{{ $guarantor->email ?? '—' }}</td>
                            <td>{{ optional($guarantor->nationality)->name ?? '—' }}</td>
                            <td class="text-start">{{ $guarantor->address ?? '—' }}</td>
                            <td>{{ optional($guarantor->title)->name ?? '—' }}</td>
                            <td>
                                @if($guarantor->id_card_image)
                                    <a href="{{ asset('storage/' . $guarantor->id_card_image) }}" target="_blank" data-bs-toggle="tooltip" title="عرض الصورة بالحجم الكامل">
                                        <img src="{{ asset('storage/' . $guarantor->id_card_image) }}"
                                             alt="صورة الهوية" width="70" height="48"
                                             style="object-fit: cover; border-radius: .25rem;">
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('guarantors.show', $guarantor) }}" class="btn btn-outline-secondary btn-sm">عرض</a>
                                {{-- <a href="{{ route('guarantors.edit', $guarantor) }}" class="btn btn-outline-primary btn-sm">تعديل</a>
                                <form action="{{ route('guarantors.destroy', $guarantor) }}" method="POST" class="d-inline" onsubmit="return confirm('حذف هذا الكفيل؟');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                </form> --}}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-5">
                                <div class="text-muted">
                                    لا توجد نتائج مطابقة.
                                    <a href="{{ route('guarantors.index') }}" class="ms-1">عرض الكل</a>
                                </div>
                                <div class="mt-3">
                                    <a href="{{ route('guarantors.create') }}" class="btn btn-sm btn-success">
                                        + إضافة أول كفيل
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($guarantors->hasPages())
    <div class="card-footer bg-white">
        {{ $guarantors->withQueryString()->links('pagination::bootstrap-5') }}
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
