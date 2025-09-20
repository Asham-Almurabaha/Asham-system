@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'info',
    'outline' => true,
])

<x-button :type="$type" :variant="$variant" :outline="$outline" @if($href) href="{{ $href }}" @endif {{ $attributes->class(['px-4', 'rounded-pill']) }}>
    <i class="bi bi-arrow-clockwise"></i>
    <span>{{ $slot->isEmpty() ? __('Refresh') : $slot }}</span>
</x-button>
