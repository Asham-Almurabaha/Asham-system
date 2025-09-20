@props(['type' => 'submit'])

<x-button :type="$type" variant="primary" {{ $attributes }}>
    {{ $slot }}
</x-button>
