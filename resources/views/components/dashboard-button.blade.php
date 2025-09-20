@props([
    'type' => 'button',
    'variant' => 'primary',
    'outline' => false,
])

<x-button :type="$type" :variant="$variant" :outline="$outline" {{ $attributes->class(['px-4', 'rounded-pill']) }}>
    <i class="bi bi-speedometer2"></i>
    <span>{{ $slot->isEmpty() ? __('Dashboard') : $slot }}</span>
</x-button>
