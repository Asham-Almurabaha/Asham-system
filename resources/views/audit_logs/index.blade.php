@extends('layouts.master')

@section('title', 'سجل التغييرات')

@section('content')
<div class="container py-3" dir="rtl">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">📑 سجل التغييرات (Audit Log)</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('audit.logs') }}" class="btn btn-outline-secondary btn-sm">تحديث</a>
            <button class="btn btn-primary btn-sm no-print" onclick="window.print()">🖨 طباعة</button>
        </div>
    </div>

    {{-- فلاتر --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('audit.logs') }}" id="filters" class="row g-2 align-items-end">
                <div class="col-12 col-md-2">
                    <label class="form-label mb-1">نوع العملية</label>
                    <select name="event" class="form-select form-select-sm auto-submit">
                        <option value="">الكل</option>
                        @foreach(($events ?? []) as $ev)
                            <option value="{{ $ev }}" @selected(request('event') === $ev)>{{ $ev }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label mb-1">المستخدم</label>
                    <select name="user_id" class="form-select form-select-sm auto-submit">
                        <option value="">الكل</option>
                        @foreach(($users ) as $u)
                            <option value="{{ $u->id }}" @selected((string)request('user_id')===(string)$u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label mb-1">الموديل</label>
                    <select name="model" class="form-select form-select-sm auto-submit">
                        <option value="">الكل</option>
                        @foreach(($models ?? []) as $m)
                            @php
                                $optVal = $m['fqn'];
                                $optTxt = $m['base'].' — '.$m['fqn'];
                            @endphp
                            <option value="{{ $optVal }}" @selected(request('model')===$optVal || request('model')===$m['base'])>
                                {{ $optTxt }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">من تاريخ</label>
                    <input type="date" name="from" class="form-control form-control-sm auto-submit" value="{{ request('from') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">إلى تاريخ</label>
                    <input type="date" name="to" class="form-control form-control-sm auto-submit" value="{{ request('to') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">IP</label>
                    <input type="text" name="ip" class="form-control form-control-sm" value="{{ request('ip') }}" placeholder="مثال: 192.168...">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label mb-1">بحث حر</label>
                    <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="كلمة في النوع/الحالة/IP/الملاحظات...">
                </div>
                <div class="col-12 col-md-4 d-flex gap-2">
                    <button class="btn btn-primary btn-sm w-100">بحث</button>
                    <a href="{{ route('audit.logs') }}" class="btn btn-outline-secondary btn-sm w-100">مسح</a>
                </div>
            </form>
        </div>
    </div>

    {{-- جدول السجل --}}
    <div class="card shadow-sm">
        <div class="card-body p-0 table-responsive">
            <table class="table table-bordered table-striped text-center align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:70px">#</th>
                        <th>الموديل</th>
                        <th>المعرف</th>
                        <th>العملية</th>
                        <th>المستخدم</th>
                        <th>قبل التغيير</th>
                        <th>بعد التغيير</th>
                        <th>العنوان IP</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $i => $log)
                        <tr>
                            <td>{{ $logs->firstItem() + $i }}</td>
                            <td>{{ class_basename($log->auditable_type) }}</td>
                            <td>{{ $log->auditable_id }}</td>
                            <td>
                                @php
                                    $color = match($log->event) {
                                        'created' => 'success',
                                        'updated' => 'warning',
                                        'deleted' => 'danger',
                                        'restored' => 'info',
                                        default => 'secondary'
                                    };
                                @endphp
                                <span class="badge bg-{{ $color }}">{{ $log->event }}</span>
                            </td>
                            <td>{{ $log->user?->name ?? '—' }}</td>
                            <td>
                                <pre class="text-danger small text-start mb-0">{{ json_encode($log->old_values, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                            </td>
                            <td>
                                <pre class="text-success small text-start mb-0">{{ json_encode($log->new_values, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                            </td>
                            <td dir="ltr">{{ $log->ip_address }}</td>
                            <td>{{ optional($log->performed_at)->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-4 text-muted">لا توجد سجلات تغييرات ضمن الفلترة الحالية.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// أوتوسبميت للفلاتر السريعة
document.querySelectorAll('.auto-submit').forEach(el => {
    el.addEventListener('change', () => {
        document.getElementById('filters').requestSubmit();
    });
});
</script>
@endpush
@endsection
