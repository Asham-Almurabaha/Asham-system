@props([
    'href' => null,
    'type' => 'button',
])

@php
    $classes = 'btn btn-outline-secondary d-inline-flex align-items-center gap-2';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        <i class="bi bi-arrow-clockwise"></i>
        <span>{{ $slot->isEmpty() ? __('Refresh') : $slot }}</span>
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        <i class="bi bi-arrow-clockwise"></i>
        <span>{{ $slot->isEmpty() ? __('Refresh') : $slot }}</span>
    </button>
@endif
