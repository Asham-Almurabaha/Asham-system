@props([
    'icon' => 'bi-file-earmark-bar-graph',
    'variant' => 'info',
    'type' => 'button',
])

<x-button.action :icon="$icon" :variant="$variant" :type="$type" :default="__('app.Report')" {{ $attributes }}>
    {{ $slot }}
</x-button.action>
