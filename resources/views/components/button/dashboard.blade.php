@props([
    'icon' => 'bi-speedometer2',
    'variant' => 'primary',
    'type' => 'button',
])

<x-button.action :icon="$icon" :variant="$variant" :type="$type" :default="__('app.Dashboard')" {{ $attributes }}>
    {{ $slot }}
</x-button.action>
