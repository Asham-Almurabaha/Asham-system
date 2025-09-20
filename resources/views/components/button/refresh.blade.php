@props([
    'icon' => 'bi-arrow-clockwise',
    'variant' => 'secondary',
    'type' => 'button',
])

<x-button.action :icon="$icon" :variant="$variant" :type="$type" :default="__('app.Refresh')" {{ $attributes }}>
    {{ $slot }}
</x-button.action>
