{{-- resources/views/customers/index.blade.php --}}
@extends('layouts.master')

@section('title', 'قائمة العملاء')

@section('content')

<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">قائمة العملاء</h1>
    <nav><ol class="breadcrumb"><li class="breadcrumb-item active">العملاء</li></ol></nav>
</div>

@php
    $allTotal    = (int)($customersTotalAll ?? 0);
    $allActive   = (int)($activeCustomersTotalAll ?? 0);
    $allInactive = max($allTotal - $allActive, 0);

    $activePct   = $allTotal > 0 ? round(($allActive   / $allTotal) * 100, 1) : 0;
    $inactivePct = $allTotal > 0 ? round(($allInactive / $allTotal) * 100, 1) : 0;

    $newThisMonthAll = (int)($newCustomersThisMonthAll ?? 0);
    $newThisWeekAll  = (int)($newCustomersThisWeekAll  ?? 0);
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
                    <div class="subnote">إجمالي العملاء — كل النظام</div>
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
                    <div class="subnote">العملاء النشطون</div>
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
                    <div class="subnote">عملاء جدد هذا الشهر</div>
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

    <div class="btn-group" role="group" aria-label="Actions">
      <a href="{{ route('customers.create') }}" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> إضافة عميل
      </a>
      @role('admin')
        <a href="{{ route('customers.import.form') }}" class="btn btn-outline-primary">
            <i class="bi bi-upload"></i> استيراد Excel
        </a>
      @endrole

      {{-- 🔥 تم حذف زر "تمبليت" كما طلبت --}}
    </div>

    <span class="ms-auto small text-muted">
      النتائج: <strong>{{ $customers->total() }}</strong>
    </span>

    <button class="btn btn-outline-secondary btn-sm" type="button"
            data-bs-toggle="collapse" data-bs-target="#filterBar"
            aria-expanded="false" aria-controls="filterBar">
      تصفية
    </button>
  </div>

  <div class="collapse @if(request()->hasAny(['customer_q','national_id','phone'])) show @endif border-top" id="filterBar">
    <div class="card-body">
      <form id="filterForm" action="{{ route('customers.index') }}" method="GET" class="row gy-2 gx-2 align-items-end">
        {{-- ✅ بحث باسم العميل فقط --}}
        <div class="col-12 col-md-4">
          <label class="form-label mb-1">العميل (بالاسم)</label>
          <input type="text"
                 name="customer_q"
                 value="{{ request('customer_q') }}"
                 class="form-control form-control-sm auto-submit-input"
                 placeholder="اكتب اسم العميل...">
        </div>

        {{-- فلاتر إضافية (اختياري) --}}
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">رقم الهوية</label>
          <input type="text" name="national_id" value="{{ request('national_id') }}"
                 class="form-control form-control-sm auto-submit-input" placeholder="1234567890">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">الهاتف</label>
          <input type="text" name="phone" value="{{ request('phone') }}"
                 class="form-control form-control-sm auto-submit-input" placeholder="+9665XXXXXXXX">
        </div>

        <div class="col-12 col-md-2">
          <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm w-100">مسح</a>
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
                        <th style="width:150px">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td class="text-muted">
                                {{ $loop->iteration + ($customers->currentPage() - 1) * $customers->perPage() }}
                            </td>
                            <td class="text-start">{{ $customer->name }}</td>
                            <td dir="ltr">{{ $customer->national_id ?? '—' }}</td>
                            <td dir="ltr">{{ $customer->phone ?? '—' }}</td>
                            <td class="text-start">{{ $customer->email ?? '—' }}</td>
                            <td>{{ optional($customer->nationality)->name ?? '—' }}</td>
                            <td class="text-start">{{ $customer->address ?? '—' }}</td>
                            <td>{{ optional($customer->title)->name ?? '—' }}</td>
                            <td>
                                @if($customer->id_card_image)
                                    <a href="{{ asset('storage/' . $customer->id_card_image) }}" target="_blank" data-bs-toggle="tooltip" title="عرض الصورة بالحجم الكامل">
                                        <img src="{{ asset('storage/' . $customer->id_card_image) }}"
                                             alt="صورة الهوية"
                                             width="70" height="48"
                                             style="object-fit: cover; border-radius: .25rem;">
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary btn-sm">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-5">
                                <div class="text-muted">
                                    لا توجد نتائج مطابقة لبحثك.
                                    <a href="{{ route('customers.index') }}" class="ms-1">عرض الكل</a>
                                </div>
                                <div class="mt-3">
                                    <a href="{{ route('customers.create') }}" class="btn btn-sm btn-success">
                                        + إضافة أول عميل
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($customers->hasPages())
    <div class="card-footer bg-white">
        {{ $customers->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Tooltip للصور
    const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el, {container: 'body'}));

    // Auto-submit للمدخلات النصية مع تأخير بسيط
    let typingTimer;
    document.querySelectorAll('.auto-submit-input').forEach(el => {
        el.addEventListener('input', function() {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 600);
        });
    });
});
</script>
@endpush
