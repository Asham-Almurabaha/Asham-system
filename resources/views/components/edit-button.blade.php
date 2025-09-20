@props([
    'type' => 'button',
    'variant' => 'warning',
    'outline' => false,
])

<x-button :type="$type" :variant="$variant" :outline="$outline" {{ $attributes->class(['px-4', 'rounded-pill', 'text-dark']) }}>
    <i class="bi bi-pencil-square"></i>
    <span>{{ $slot->isEmpty() ? __('Edit') : $slot }}</span>
</x-button>
