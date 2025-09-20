@props(['type' => 'submit'])

<x-button :type="$type" variant="danger" {{ $attributes }}>
    {{ $slot }}
</x-button>
