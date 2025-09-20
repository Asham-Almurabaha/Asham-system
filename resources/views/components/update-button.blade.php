@props([
    'type' => 'submit',
    'variant' => 'info',
    'outline' => false,
])

<x-button :type="$type" :variant="$variant" :outline="$outline" {{ $attributes->class(['px-4', 'rounded-pill']) }}>
    <i class="bi bi-arrow-repeat"></i>
    <span>{{ $slot->isEmpty() ? __('Update') : $slot }}</span>
</x-button>
