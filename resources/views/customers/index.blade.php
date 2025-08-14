@extends('layouts.master')

@section('title', 'قائمة العملاء')

@section('content')

<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">قائمة العملاء</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">العملاء</li>
        </ol>
    </nav>
</div>

{{-- شريط الأدوات --}}
<div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center p-20">
        <a href="{{ route('customers.create') }}" class="btn btn-success">
            + إضافة عميل جديد
        </a>

        <span class="ms-auto small text-muted">
            النتائج: <strong>{{ $customers->total() }}</strong>
        </span>

        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#filterBar" aria-expanded="false" aria-controls="filterBar">
            تصفية متقدمة
        </button>
    </div>

    <div class="collapse @if(request()->hasAny(['q','national_id','phone','email','nationality','title'])) show @endif border-top" id="filterBar">
        <div class="card-body">
            <form action="{{ route('customers.index') }}" method="GET" class="row gy-2 gx-2 align-items-end">

                <div class="col-12 col-md-3">
                    <label class="form-label mb-1">الاسم</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="اسم العميل">
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
                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm w-100">مسح</a>
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
                                <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary btn-sm">
                                    عرض
                                </a>
                                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-primary btn-sm">
                                    تعديل
                                </a>
                                <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('هل أنت متأكد من حذف هذا العميل؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                </form>
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
    // تفعيل Bootstrap Tooltip
    const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el, {container: 'body'}));
});
</script>
@endpush
