@props([
    'icon' => 'bi-eye',
    'variant' => 'secondary',
    'type' => 'button',
])

<x-button.action :icon="$icon" :variant="$variant" :type="$type" :default="__('app.Show')" {{ $attributes }}>
    {{ $slot }}
</x-button.action>
