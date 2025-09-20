@props([
    'type' => 'submit',
    'outline' => false,
])

<x-button :type="$type" variant="success" :outline="$outline" {{ $attributes->class(['px-4', 'rounded-pill']) }}>
    <i class="bi bi-check-circle-fill"></i>
    <span>{{ $slot->isEmpty() ? __('Save') : $slot }}</span>
</x-button>
