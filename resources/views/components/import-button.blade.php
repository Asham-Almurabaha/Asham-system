@props([
    'type' => 'button',
    'variant' => 'primary',
    'outline' => true,
])

<x-button :type="$type" :variant="$variant" :outline="$outline" {{ $attributes->class(['px-4', 'rounded-pill']) }}>
    <i class="bi bi-cloud-upload"></i>
    <span>{{ $slot->isEmpty() ? __('Import') : $slot }}</span>
</x-button>
