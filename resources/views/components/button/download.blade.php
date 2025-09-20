@props([
    'icon' => 'bi-download',
    'variant' => 'secondary',
    'type' => 'button',
])

<x-button.action :icon="$icon" :variant="$variant" :type="$type" :default="__('app.Download')" {{ $attributes }}>
    {{ $slot }}
</x-button.action>
