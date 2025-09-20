@props([
    'icon' => 'bi-save',
    'variant' => 'primary',
    'type' => 'submit',
])

<x-button.action :icon="$icon" :variant="$variant" :type="$type" :default="__('app.Save')" {{ $attributes }}>
    {{ $slot }}
</x-button.action>
