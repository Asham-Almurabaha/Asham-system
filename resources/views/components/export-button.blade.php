@props([
    'type' => 'button',
    'variant' => 'success',
    'outline' => true,
])

<x-button :type="$type" :variant="$variant" :outline="$outline" {{ $attributes->class(['px-4', 'rounded-pill']) }}>
    <i class="bi bi-cloud-download"></i>
    <span>{{ $slot->isEmpty() ? __('Export') : $slot }}</span>
</x-button>
