@props([
    'isProtected' => false,
])

@if($isProtected)
    <span class="badge bg-secondary-subtle text-muted">@lang('lookups::messages.status.protected')</span>
@else
    <span class="badge bg-success-subtle text-success">@lang('lookups::messages.status.editable')</span>
@endif
