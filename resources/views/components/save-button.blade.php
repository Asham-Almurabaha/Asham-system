@props([
    'type' => 'submit',
    'outline' => true,
])

<x-button :type="$type" variant="success" :outline="$outline" {{ $attributes->class(['px-4']) }}>
    <i class="bi bi-check-circle"></i>
    <span>{{ $slot->isEmpty() ? __('Save') : $slot }}</span>
</x-button>
