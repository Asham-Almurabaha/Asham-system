@props([
    'icon' => 'bi-plus-lg',
    'variant' => 'success',
    'type' => 'button',
])

<x-button.action :icon="$icon" :variant="$variant" :type="$type" :default="__('Add')" {{ $attributes }}>
    {{ $slot }}
</x-button.action>
