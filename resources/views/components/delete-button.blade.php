@props([
    'type' => 'button',
    'variant' => 'danger',
    'outline' => false,
])

<x-button :type="$type" :variant="$variant" :outline="$outline" {{ $attributes->class(['px-4', 'rounded-pill']) }}>
    <i class="bi bi-trash-fill"></i>
    <span>{{ $slot->isEmpty() ? __('Delete') : $slot }}</span>
</x-button>
