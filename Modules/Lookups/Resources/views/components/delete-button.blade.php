@php
    $formClass = $formClass ?? 'd-inline';
    $label = $label ?? __('Delete');
    $confirm = $confirm ?? __('Are you sure you want to delete this item?');
    $buttonClass = $buttonClass ?? 'px-3 rounded-pill';
@endphp

<form action="{{ $action }}" method="POST" class="{{ $formClass }}" onsubmit='return confirm(@json($confirm));'>
    @csrf
    @method('DELETE')
    <x-button type="submit" variant="danger" :outline="true" size="sm" class="{{ $buttonClass }}">
        {{ $label }}
    </x-button>
</form>
