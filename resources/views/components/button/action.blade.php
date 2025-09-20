@props([
    'icon' => null,
    'variant' => 'primary',
    'type' => 'button',
    'default' => '',
])

<x-button :variant="$variant" :type="$type" {{ $attributes }}>
    @if ($icon)
        <i class="bi {{ $icon }} me-1" aria-hidden="true"></i>
    @endif

    @if ($slot->isEmpty())
        {{ $default }}
    @else
        {{ $slot }}
    @endif
</x-button>
