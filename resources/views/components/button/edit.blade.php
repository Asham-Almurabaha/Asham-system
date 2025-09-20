@props([
    'icon' => 'bi-pencil-square',
    'variant' => 'info',
    'type' => 'button',
])

<x-button.action :icon="$icon" :variant="$variant" :type="$type" :default="__('Edit')" {{ $attributes }}>
    {{ $slot }}
</x-button.action>
