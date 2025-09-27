@extends('layouts.master')

@section('title', __('Audit Logs'))

@section('content')
<div class="container py-3" dir="rtl">

    <div class="page-heading rounded-4 border-0 shadow-sm mb-4 p-4 bg-gradient">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="page-heading__icon d-inline-flex align-items-center justify-content-center rounded-circle">
                        <i class="bi bi-clipboard-data"></i>
                    </span>
                    <h4 class="fw-bold mb-0">{{ __('Audit Logs') }}</h4>
                </div>
                <p class="text-muted mb-0 small">{{ __('Review every action recorded by the system with rich filters and detailed insights.') }}</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <x-button.refresh :href="route('audit.logs')" class="btn-sm" />
                <x-button.print variant="primary" size="sm" class="no-print" onclick="window.print()">
                    {{ __('Print') }}
                </x-button.print>
            </div>
        </div>
    </div>

    <div class="card audit-card shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pb-0">
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis px-3 py-2">
                    <i class="bi bi-funnel"></i>
                    <span class="ms-1">{{ __('Advanced Filters') }}</span>
                </span>
                <span class="text-muted small">{{ __('Fine tune the log list by combining multiple filters together.') }}</span>
            </div>
        </div>
        <div class="card-body pt-3">
            <form method="GET" action="{{ route('audit.logs') }}" id="filters" class="row g-3 align-items-end">
                <div class="col-12 col-md-2">
                    <label class="form-label mb-1">{{ __('Event') }}</label>
                    <select name="event" class="form-select form-select-sm auto-submit">
                        <option value="">{{ __('-- Choose --') }}</option>
                        @foreach(($events ?? []) as $ev)
                            <option value="{{ $ev }}" @selected(request('event') === $ev)>{{ $ev }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label mb-1">{{ __('User') }}</label>
                    <select name="user_id" class="form-select form-select-sm auto-submit">
                        <option value="">{{ __('-- Choose --') }}</option>
                        @foreach(($users ) as $u)
                            <option value="{{ $u->id }}" @selected((string)request('user_id')===(string)$u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label mb-1">{{ __('Model') }}</label>
                    <select name="model" class="form-select form-select-sm auto-submit">
                        <option value="">{{ __('-- Choose --') }}</option>
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
                    <label class="form-label mb-1">{{ __('From Date') }}</label>
                    <input type="date" name="from" class="form-control form-control-sm auto-submit" value="{{ request('from') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">{{ __('To Date') }}</label>
                    <input type="date" name="to" class="form-control form-control-sm auto-submit" value="{{ request('to') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">IP</label>
                    <input type="text" name="ip" class="form-control form-control-sm" value="{{ request('ip') }}" placeholder="{{ __('Example: 192.168...') }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label mb-1">{{ __('Search') }}</label>
                    <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="{{ __('Search') }}">
                </div>
                <div class="col-12 col-md-4 d-flex gap-2">
                    <x-button.action type="submit" variant="primary" size="sm" :block="true" class="d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-search"></i>
                        <span>{{ __('Search') }}</span>
                    </x-button.action>
                    <x-button.secondary href="{{ route('audit.logs') }}" size="sm" :block="true" class="d-flex align-items-center justify-content-center gap-2">
                        <span>{{ __('Clear') }}</span>
                    </x-button.secondary>
                </div>
            </form>
        </div>
    </div>

    @can('audit.logs.purge')
        <div class="card audit-card shadow-sm mb-4 border-start border-4 border-danger-subtle">
            <div class="card-header bg-transparent border-0 pb-0">
                <div class="d-flex align-items-center gap-2 text-danger">
                    <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                    <h6 class="mb-0 fw-bold">{{ __('Manage Log Retention') }}</h6>
                </div>
                <p class="small text-muted mb-0 mt-2">{{ __('Remove a range of audit entries carefully. Deleted records cannot be restored.') }}</p>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('audit.logs.purge') }}" class="row g-3 align-items-end" onsubmit="return confirm(@js(__('Audit log range delete confirm')));">
                    @csrf
                    @method('DELETE')
                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1">{{ __('From Date') }}</label>
                        <input type="date" name="from" class="form-control form-control-sm js-date" value="{{ old('from') }}" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1">{{ __('To Date') }}</label>
                        <input type="date" name="to" class="form-control form-control-sm js-date" value="{{ old('to') }}" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="d-flex align-items-center gap-2">
                            <x-button.delete id="audit-log-delete-range" type="submit" size="sm" class="d-inline-flex align-items-center gap-2">
                                <span>{{ __('Delete Range') }}</span>
                            </x-button.delete>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    <div class="card audit-card shadow-sm">
        <div class="card-header bg-transparent border-0 pb-0 d-flex justify-content-between flex-wrap gap-2">
            <div>
                <h6 class="fw-semibold mb-1">{{ __('Latest Activity') }}</h6>
                <p class="text-muted small mb-0">{{ __('Every log entry is displayed with the associated model, user, IP address and timestamps.') }}</p>
            </div>
            <span class="badge rounded-pill bg-info-subtle text-info-emphasis align-self-start px-3 py-2">{{ __('Total') }}: {{ $logs->total() }}</span>
        </div>
        <div class="card-body mt-1">
            <div class="table-responsive">
                <x-table head-class="table-light" striped bordered class="text-center align-middle audit-log-table" :hover="false">
                    <x-slot name="head">
                        <tr>
                            <th style="width:70px">#</th>
                            <th>{{ __('Model') }}</th>
                        <th>{{ __('ID') }}</th>
                        <th>{{ __('Event') }}</th>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Old Values') }}</th>
                        <th>{{ __('New Values') }}</th>
                        <th style="width:110px">{{ __('Actions') }}</th>
                        <th>{{ __('IP Address') }}</th>
                        <th>{{ __('Performed At') }}</th>
                    </tr>
                </x-slot>
                    @forelse($logs as $i => $log)
                        <tr>
                            <td>{{ $logs->firstItem() + $i }}</td>
                            <td class="fw-semibold">{{ class_basename($log->auditable_type) }}</td>
                            <td><span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $log->auditable_id }}</span></td>
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
                                $badgeClass = "bg-$color-subtle text-$color-emphasis";
                            @endphp
                            <span class="badge {{ $badgeClass }} text-uppercase">{{ $log->event }}</span>
                        </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-semibold">{{ $log->user?->name ?? __('Undefined') }}</span>
                                    @if($log->user)
                                        <span class="small text-muted">{{ $log->user->email }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <pre class="log-json log-json--old">{{ json_encode($log->old_values, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                            </td>
                            <td>
                                <pre class="log-json log-json--new">{{ json_encode($log->new_values, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                            </td>
                            <td>
                                <x-button.secondary href="{{ route('audit.logs.show', $log) }}" size="sm" class="d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-eye"></i>
                                    <span>{{ __('Show') }}</span>
                                </x-button.secondary>
                            </td>
                            <td dir="ltr"><span class="badge bg-light text-dark fw-normal px-3 py-2">{{ $log->ip_address }}</span></td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-semibold">{{ optional($log->performed_at)->format('Y-m-d') }}</span>
                                    <span class="small text-muted">{{ optional($log->performed_at)->format('H:i') }}</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-4 text-muted">{{ __('No entries found.') }}</td>
                        </tr>
                    @endforelse
                </x-table>
            </div>
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
document.querySelectorAll('.auto-submit').forEach(el => {
    el.addEventListener('change', () => {
        document.getElementById('filters').requestSubmit();
    });
});
</script>
@endpush

@push('styles')
<style>
    .page-heading {
        background: linear-gradient(135deg, rgba(45, 96, 253, 0.1), rgba(13, 148, 136, 0.1));
    }

    .page-heading__icon {
        width: 48px;
        height: 48px;
        background: rgba(45, 96, 253, 0.1);
        color: var(--bs-primary);
        font-size: 1.5rem;
    }

    .audit-card {
        border-radius: 1rem;
        border: 1px solid var(--bs-border-color-translucent);
    }

    .audit-log-table th {
        white-space: nowrap;
    }

    .log-json {
        text-align: left;
        direction: ltr;
        max-height: 220px;
        overflow: auto;
        border-radius: 0.75rem;
        border: 1px solid var(--bs-border-color);
        background-color: var(--bs-body-bg);
        padding: 0.75rem;
        font-size: 0.75rem;
        line-height: 1.4;
    }

    .log-json--old {
        border-color: rgba(220, 53, 69, 0.3);
        background-color: rgba(220, 53, 69, 0.05);
    }

    .log-json--new {
        border-color: rgba(25, 135, 84, 0.3);
        background-color: rgba(25, 135, 84, 0.05);
    }

    @media (max-width: 992px) {
        .audit-log-table {
            font-size: 0.9rem;
        }

        .log-json {
            max-height: 160px;
        }
    }
</style>
@endpush
@endsection

