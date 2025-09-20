@props([
    'type' => 'button',
    'variant' => 'secondary',
    'outline' => false,
])

<x-button :type="$type" :variant="$variant" :outline="$outline" {{ $attributes->class(['px-4', 'rounded-pill']) }}>
    <i class="bi bi-file-earmark-arrow-down"></i>
    <span>{{ $slot->isEmpty() ? __('Download Template') : $slot }}</span>
</x-button>
