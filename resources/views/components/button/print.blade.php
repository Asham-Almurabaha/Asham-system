@props([
    'icon' => 'bi-printer',
    'variant' => 'secondary',
    'type' => 'button',
])

<x-button.action :icon="$icon" :variant="$variant" :type="$type" :default="__('app.Print')" {{ $attributes }}>
    {{ $slot }}
</x-button.action>
