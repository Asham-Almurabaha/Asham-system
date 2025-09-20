@props([
    'icon' => null,
    'variant' => 'primary',
    'type' => 'button',
    'default' => '',
    'outline' => false,
    'size' => null,
    'block' => false,
    'unstyled' => false,
])

@php
    $href = $attributes->get('href');
    $tag = $href ? 'a' : 'button';

    $classes = collect();

    if (! $unstyled) {
        $classes->push('btn');

        if ($variant === 'link') {
            $classes->push('btn-link');
        } else {
            $classes->push(($outline ? 'btn-outline-' : 'btn-') . $variant);
        }

        if ($size) {
            $classes->push('btn-' . $size);
        }
    }

    if ($block) {
        $classes->push('d-block', 'w-100');
    }

    $classAttribute = $classes->filter()->join(' ');

    $isDisabled = filter_var($attributes->get('disabled', false), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    if ($isDisabled === null) {
        $isDisabled = $attributes->has('disabled');
    }

    $mergedAttributes = $attributes->class($classAttribute);

    if ($tag === 'button') {
        $mergedAttributes = $mergedAttributes->merge(['type' => $type]);

        if ($isDisabled) {
            $mergedAttributes = $mergedAttributes->merge(['disabled' => 'disabled']);
        } else {
            $mergedAttributes = $mergedAttributes->except(['disabled']);
        }
    } elseif ($isDisabled) {
        $mergedAttributes = $mergedAttributes->except(['disabled']);

        $mergedAttributes = $mergedAttributes->merge([
            'class' => trim(($mergedAttributes->get('class') ?? '') . ' disabled'),
            'aria-disabled' => 'true',
            'tabindex' => '-1',
        ]);
    } else {
        $mergedAttributes = $mergedAttributes->except(['disabled']);
    }
@endphp

<{{ $tag }} {{ $mergedAttributes }}>
    @if ($icon)
        <i class="bi {{ $icon }} me-1" aria-hidden="true"></i>
    @endif

    @if ($slot->isEmpty())
        {{ $default }}
    @else
        {{ $slot }}
    @endif
</{{ $tag }}>
