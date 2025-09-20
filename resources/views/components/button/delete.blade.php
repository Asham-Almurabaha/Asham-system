@props([
    'icon' => 'bi-trash',
    'variant' => 'danger',
    'type' => 'button',
])

<x-button.action :icon="$icon" :variant="$variant" :type="$type" :default="__('Delete')" {{ $attributes }}>
    {{ $slot }}
</x-button.action>
