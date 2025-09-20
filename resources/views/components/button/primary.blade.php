@props([
    'icon' => 'bi-check-circle',
    'variant' => 'primary',
    'type' => 'submit',
])

<x-button.action :icon="$icon" :variant="$variant" :type="$type" :default="__('Submit')" {{ $attributes }}>
    {{ $slot }}
</x-button.action>
