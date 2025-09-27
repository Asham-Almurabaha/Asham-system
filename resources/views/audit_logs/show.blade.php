@extends('layouts.master')

@section('title', __('Audit Log Details'))

@section('content')
<div class="container py-3" dir="rtl">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="fw-bold mb-1">{{ __('Audit Log Details') }}</h4>
            <div class="text-muted small">{{ __('ID') }}: {{ $log->id }}</div>
        </div>
        <div class="d-flex gap-2">
            @php
                $revertConfirmations = [
                    'updated' => __('Audit log revert confirm updated'),
                    'created' => __('Audit log revert confirm created'),
                    'deleted' => __('Audit log revert confirm deleted'),
                ];
            @endphp

            @if(isset($revertConfirmations[$log->event]))
                <form method="POST" action="{{ route('audit.logs.revert', $log) }}" onsubmit='return confirm({{ json_encode($revertConfirmations[$log->event]) }});'>
                    @csrf
                    <x-button.action type="submit" variant="warning" size="sm">
                        {{ __('Revert Change') }}
                    </x-button.action>
                </form>
            @endif
            <x-button.secondary href="{{ route('audit.logs') }}" class="btn-sm">
                {{ __('Back') }}
            </x-button.secondary>
            <x-button.print variant="primary" size="sm" class="no-print" onclick="window.print()">
                {{ __('Print') }}
            </x-button.print>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body p-20">
            <dl class="row mb-0">
                <dt class="col-12 col-md-3">{{ __('Model') }}</dt>
                <dd class="col-12 col-md-9">
                    {{ class_basename($log->auditable_type) }}
                    <span class="text-muted">(#{{ $log->auditable_id }})</span>
                    <div class="small text-muted">{{ $log->auditable_type }}</div>
                </dd>

                <dt class="col-12 col-md-3">{{ __('Event') }}</dt>
                <dd class="col-12 col-md-9">
                    <span class="badge bg-{{ $eventColor }}">{{ $log->event }}</span>
                </dd>

                <dt class="col-12 col-md-3">{{ __('User') }}</dt>
                <dd class="col-12 col-md-9">{{ $log->user?->name ?? __('Undefined') }}</dd>

                <dt class="col-12 col-md-3">{{ __('IP Address') }}</dt>
                <dd class="col-12 col-md-9" dir="ltr">{{ $log->ip_address ?? '—' }}</dd>

                <dt class="col-12 col-md-3">URL</dt>
                <dd class="col-12 col-md-9" dir="ltr">
                    @if($log->url)
                        <a href="{{ $log->url }}" target="_blank" rel="noopener">{{ $log->url }}</a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </dd>

                <dt class="col-12 col-md-3">{{ __('User Agent') }}</dt>
                <dd class="col-12 col-md-9"><code class="small">{{ $log->user_agent ?? '—' }}</code></dd>

                <dt class="col-12 col-md-3">{{ __('Performed At') }}</dt>
                <dd class="col-12 col-md-9" dir="ltr">{{ optional($log->performed_at)->format('Y-m-d H:i:s') ?? '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light fw-semibold">{{ __('Old Values') }}</div>
                <div class="card-body">
                    <pre class="text-danger small mb-0 text-start" dir="ltr">{{ json_encode($log->old_values ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light fw-semibold">{{ __('New Values') }}</div>
                <div class="card-body">
                    <pre class="text-success small mb-0 text-start" dir="ltr">{{ json_encode($log->new_values ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
