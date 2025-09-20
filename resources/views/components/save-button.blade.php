@props(['type' => 'submit'])

<button {{ $attributes->merge(['type' => $type, 'class' => 'btn btn-outline-success d-inline-flex align-items-center gap-2 px-4']) }}>
    <i class="bi bi-check-circle"></i>
    <span>{{ $slot->isEmpty() ? __('Save') : $slot }}</span>
</button>
