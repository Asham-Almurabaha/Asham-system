@props([
    'type' => 'button',
    'variant' => 'dark',
    'outline' => false,
])

<x-button :type="$type" :variant="$variant" :outline="$outline" {{ $attributes->class(['px-4', 'rounded-pill', 'text-white']) }}>
    <i class="bi bi-graph-up-arrow"></i>
    <span>{{ $slot->isEmpty() ? __('Reports') : $slot }}</span>
</x-button>
