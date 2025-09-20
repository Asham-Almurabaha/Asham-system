@props([
    'type' => 'button',
    'variant' => 'secondary',
    'outline' => true,
])

<x-button :type="$type" :variant="$variant" :outline="$outline" {{ $attributes->class(['px-4', 'rounded-pill']) }}>
    <i class="bi bi-printer"></i>
    <span>{{ $slot->isEmpty() ? __('Print') : $slot }}</span>
</x-button>
