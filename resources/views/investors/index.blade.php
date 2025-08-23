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
    $allTotal    = (int)($investorsTotalAll ?? 0);
    $allActive   = (int)($activeInvestorsTotalAll ?? 0);
    $allInactive = max($allTotal - $allActive, 0);

    $activePct   = $allTotal > 0 ? round(($allActive / $allTotal) * 100, 1) : 0;
    $inactivePct = $allTotal > 0 ? round(($allInactive / $allTotal) * 100, 1) : 0;

    $newThisMonthAll = (int)($newInvestorsThisMonthAll ?? 0);
    $newThisWeekAll  = (int)($newInvestorsThisWeekAll  ?? 0);
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

{{-- ====== شريط الأدوات + فلاتر ====== --}}
<div class="card shadow-sm mb-3">
  <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">

    <div class="btn-group" role="group" aria-label="Investor Actions">
      <a href="{{ route('investors.create') }}" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> إضافة مستثمر
      </a>

      <a href="{{ route('investors.import.form') }}" class="btn btn-outline-primary">
        <i class="bi bi-upload"></i> استيراد Excel
      </a>

      {{-- تم حذف زر التمبليت زي العملاء --}}
      {{-- @if (Route::has('investors.import.template'))
        <a href="{{ route('investors.import.template') }}" class="btn btn-outline-secondary">
          <i class="bi bi-file-earmark-spreadsheet"></i> تمبليت
        </a>
      @endif --}}

      @if (session('failures') && count(session('failures')))
        <a href="{{ route('investors.import.export_failures') }}" class="btn btn-warning">
          <i class="bi bi-exclamation-triangle"></i> تصدير الأخطاء
        </a>
      @endif
    </div>

    <div class="btn-group">
      <button type="button" class="btn btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        📊 التقارير
      </button>
      <ul class="dropdown-menu dropdown-menu-end text-end">
        <li>
          <a class="dropdown-item" href="{{ route('reports.investors.Allliquidity') }}">
            📄 تقرير سيولات المستثمرين
          </a>
        </li>
      </ul>
    </div>

    <span class="ms-auto small text-muted">
      النتائج: <strong>{{ $investors->total() }}</strong>
    </span>

    <button class="btn btn-outline-secondary btn-sm" type="button"
            data-bs-toggle="collapse" data-bs-target="#filterBar"
            aria-expanded="false" aria-controls="filterBar">
      تصفية
    </button>
  </div>

  <div class="collapse @if(request()->has('investor_q')) show @endif border-top" id="filterBar">
    <div class="card-body">
      <form id="filterForm" action="{{ route('investors.index') }}" method="GET" class="row gy-2 gx-2 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label mb-1">المستثمر (بالاسم)</label>
          <input type="text" name="investor_q" value="{{ request('investor_q') }}"
                 class="form-control form-control-sm auto-submit-input" placeholder="اكتب اسم المستثمر">
        </div>

        <div class="col-12 col-md-1">
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
                            <td class="text-muted">{{ $loop->iteration + ($investors->currentPage() - 1) * $investors->perPage() }}</td>
                            <td class="text-start">{{ $investor->name }}</td>
                            <td dir="ltr">{{ $investor->national_id ?? '—' }}</td>
                            <td dir="ltr">{{ $investor->phone ?? '—' }}</td>
                            <td class="text-start">{{ $investor->email ?? '—' }}</td>
                            <td>{{ optional($investor->nationality)->name ?? '—' }}</td>
                            <td class="text-start">{{ $investor->address ?? '—' }}</td>
                            <td>{{ optional($investor->title)->name ?? '—' }}</td>
                            <td>
                                @if($investor->id_card_image)
                                    <a href="{{ asset('storage/' . $investor->id_card_image) }}" target="_blank" data-bs-toggle="tooltip" title="عرض صورة الهوية">
                                        <img src="{{ asset('storage/' . $investor->id_card_image) }}" width="70" height="48" style="object-fit:cover; border-radius:.25rem;">
                                    </a>
                                @else <span class="text-muted">—</span> @endif
                            </td>
                            <td>
                                @if($investor->contract_image)
                                    <a href="{{ asset('storage/' . $investor->contract_image) }}" target="_blank" data-bs-toggle="tooltip" title="عرض صورة العقد">
                                        <img src="{{ asset('storage/' . $investor->contract_image) }}" width="70" height="48" style="object-fit:cover; border-radius:.25rem;">
                                    </a>
                                @else <span class="text-muted">—</span> @endif
                            </td>
                            <td>{{ is_numeric($investor->office_share_percentage) ? number_format($investor->office_share_percentage, 2) : '—' }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('investors.show', $investor) }}" class="btn btn-outline-secondary btn-sm">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="py-5">
                                <div class="text-muted">لا توجد نتائج مطابقة. <a href="{{ route('investors.index') }}" class="ms-1">عرض الكل</a></div>
                                <div class="mt-3"><a href="{{ route('investors.create') }}" class="btn btn-sm btn-success">+ إضافة أول مستثمر</a></div>
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
    // tooltips
    const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el, {container: 'body'}));

    // auto-submit inputs with debounce (اسم فقط)
    let typingTimer;
    document.querySelectorAll('.auto-submit-input').forEach(el => {
        el.addEventListener('input', function () {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 700);
        });
    });
});
</script>
@endpush
