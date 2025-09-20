@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => null,
    'outline' => false,
    'block' => false,
    'tag' => null,
])

@php
    $variant = strtolower($variant);

    $outline = filter_var($outline, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $outline = $outline ?? false;

    $block = filter_var($block, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $block = $block ?? false;

    $variants = [
        'primary' => 'primary',
        'secondary' => 'secondary',
        'success' => 'success',
        'danger' => 'danger',
        'warning' => 'warning',
        'info' => 'info',
        'light' => 'light',
        'dark' => 'dark',
        'link' => 'link',
    ];

    $selectedVariant = $variants[$variant] ?? $variants['primary'];

    $sizeClasses = [
        'sm' => 'btn-sm',
        'lg' => 'btn-lg',
    ];

    $classes = ['btn', 'fw-semibold'];

    $isLinkStyle = $selectedVariant === 'link';

    if ($isLinkStyle) {
        $classes[] = 'btn-link';
        $classes[] = 'text-decoration-none';
    } else {
        $classes[] = 'd-inline-flex';
        $classes[] = 'align-items-center';
        $classes[] = 'justify-content-center';
        $classes[] = 'gap-2';
        $classes[] = $outline ? 'btn-outline-' . $selectedVariant : 'btn-' . $selectedVariant;
        $classes[] = 'shadow-sm';
    }

    if ($size && isset($sizeClasses[$size])) {
        $classes[] = $sizeClasses[$size];
    }

    if ($block) {
        $classes[] = 'w-100';
    }

    $classString = implode(' ', array_filter($classes));

    $tag = $tag ?? ($attributes->has('href') ? 'a' : 'button');
@endphp

@if ($tag === 'a')
    <a {{ $attributes->merge(['class' => $classString]) }}>
        {{ $slot }}
    </a>
@elseif ($tag === 'input')
    <input {{ $attributes->merge(['class' => $classString, 'type' => $type, 'value' => (string) $slot]) }}>
@else
    <button {{ $attributes->merge(['class' => $classString, 'type' => $type]) }}>
        {{ $slot }}
    </button>
@endif
