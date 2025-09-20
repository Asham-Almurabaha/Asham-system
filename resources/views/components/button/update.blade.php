@props([
    'icon' => 'bi-arrow-repeat',
    'variant' => 'primary',
    'type' => 'submit',
])

<x-button.action :icon="$icon" :variant="$variant" :type="$type" :default="__('app.Update')" {{ $attributes }}>
    {{ $slot }}
</x-button.action>
