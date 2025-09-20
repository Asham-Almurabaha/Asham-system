@props(['type' => 'button'])

<x-button :type="$type" variant="secondary" :outline="true" {{ $attributes }}>
    {{ $slot }}
</x-button>
