@props([
    'icon' => 'bi-x-circle',
    'variant' => 'secondary',
    'type' => 'button',
])

<x-button.action :icon="$icon" :variant="$variant" :type="$type" :default="__('app.Cancel')" {{ $attributes }}>
    {{ $slot }}
</x-button.action>
