@php
    $formClass = $formClass ?? 'd-inline';
    $buttonClass = $buttonClass ?? 'btn btn-danger btn-sm';
    $label = $label ?? __('Delete');
    $confirm = $confirm ?? __('Are you sure you want to delete this item?');
@endphp

<form action="{{ $action }}" method="POST" class="{{ $formClass }}" onsubmit='return confirm(@json($confirm));'>
    @csrf
    @method('DELETE')
    <button type="submit" class="{{ $buttonClass }}">
        {{ $label }}
    </button>
</form>
