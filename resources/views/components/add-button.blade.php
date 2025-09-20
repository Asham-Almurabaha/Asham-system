@props([
    'type' => 'button',
    'variant' => 'primary',
    'outline' => false,
])

<x-button :type="$type" :variant="$variant" :outline="$outline" {{ $attributes->class(['px-4', 'rounded-pill']) }}>
    <i class="bi bi-plus-circle-fill"></i>
    <span>{{ $slot->isEmpty() ? __('Add') : $slot }}</span>
</x-button>
