@props([
    'floating' => false,
    'messages' => null,
    'dismissible' => true,
])

@php
    $isRtl = app()->getLocale() === 'ar';

    $flashKeys = [
        'success' => 'success',
        'status' => 'success',
        'info' => 'info',
        'warning' => 'warning',
        'error' => 'danger',
        'danger' => 'danger',
    ];

    $sessionMessages = collect();
    foreach ($flashKeys as $sessionKey => $type) {
        if (session()->has($sessionKey)) {
            $sessionMessages->push([
                'type' => $type,
                'content' => session($sessionKey),
            ]);
        }
    }

    if (session()->has('resent')) {
        $sessionMessages->push([
            'type' => 'success',
            'content' => __('A fresh verification link has been sent to your email address.'),
        ]);
    }

    if ($errors->any()) {
        $sessionMessages->push([
            'type' => 'danger',
            'content' => $errors->all(),
            'is_list' => true,
            'title' => __('Please review the highlighted errors below.'),
        ]);
    }

    $provided = collect($messages ?? []);

    $allMessages = $provided
        ->merge($sessionMessages)
        ->filter(function ($message) {
            $content = $message['content'] ?? null;

            if (is_array($content)) {
                return collect($content)->filter(fn ($line) => filled($line))->isNotEmpty();
            }

            return filled($content);
        });

    $alertMap = [
        'success' => 'success',
        'status' => 'success',
        'info' => 'info',
        'warning' => 'warning',
        'danger' => 'danger',
        'error' => 'danger',
    ];

    $icons = [
        'success' => 'bi-check-circle-fill',
        'info' => 'bi-info-circle-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        'danger' => 'bi-exclamation-octagon-fill',
    ];

    $containerClasses = 'flash-message-stack d-flex flex-column gap-2';
    $containerStyle = null;

    if ($floating) {
        $containerClasses .= ' position-fixed';
        $containerStyle = 'top: 70px; ' . ($isRtl ? 'left' : 'right') . ': 20px; z-index: 9999; min-width: 260px; max-width: 360px;';
    }

    $isDismissible = $dismissible;
@endphp

@if ($allMessages->isNotEmpty())
    <div {{ $attributes->class($containerClasses) }} @if ($containerStyle) style="{{ $containerStyle }}" @endif>
        @foreach ($allMessages as $message)
            @php
                $type = $message['type'] ?? 'info';
                $alertType = $alertMap[$type] ?? $type;
                $icon = $icons[$alertType] ?? null;
                $content = $message['content'] ?? '';
                $title = $message['title'] ?? null;
                $isList = $message['is_list'] ?? is_array($content);
                $dismissibleMessage = $message['dismissible'] ?? $isDismissible;
                $lines = $isList ? collect($content) : collect((array) $content);
            @endphp

            <div class="alert alert-{{ $alertType }} {{ $dismissibleMessage ? 'alert-dismissible fade show' : '' }} shadow mb-0" role="alert" aria-live="polite">
                <div class="d-flex align-items-start gap-2">
                    @if ($icon)
                        <span class="pt-1"><i class="bi {{ $icon }}"></i></span>
                    @endif
                    <div class="flex-grow-1">
                        @if ($title)
                            <div class="fw-semibold mb-1">{{ $title }}</div>
                        @endif

                        @if ($isList)
                            <ul class="mb-0 ps-3">
                                @foreach ($lines as $line)
                                    @if (filled($line))
                                        <li>{{ $line }}</li>
                                    @endif
                                @endforeach
                            </ul>
                        @else
                            @foreach ($lines as $line)
                                @if (filled($line))
                                    <div>{{ $line }}</div>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>

                @if ($dismissibleMessage)
                    <x-button type="button" :unstyled="true" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></x-button>
                @endif
            </div>
        @endforeach
    </div>
@endif
