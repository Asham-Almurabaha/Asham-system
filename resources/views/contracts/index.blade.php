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

{{-- شريط الأدوات --}}
<div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">
        <a href="{{ route('contracts.create') }}" class="btn btn-success">
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
                    <label class="form-label mb-1">نوع العقد</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        @foreach($contractTypes as $type)
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
                        <th>نوع العقد</th>
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
                            <td>{{ $contract->contractType->name ?? '-' }}</td>
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
                            <td>{{ $contract->start_date?->format('Y-m-d') }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('contracts.show', $contract) }}" class="btn btn-outline-secondary btn-sm">عرض</a>
                                {{-- <a href="{{ route('contracts.edit', $contract) }}" class="btn btn-outline-primary btn-sm">تعديل</a>
                                <form action="{{ route('contracts.destroy', $contract) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('هل أنت متأكد من حذف هذا العقد؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                </form> --}}
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
