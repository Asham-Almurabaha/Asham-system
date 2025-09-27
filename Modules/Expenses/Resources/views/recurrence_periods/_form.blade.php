@php
    $recurrencePeriod = $recurrencePeriod ?? null;
    $submitLabel = $submitLabel ?? __('expenses::recurrence_periods.actions.save');
    $cancelRoute = $cancelRoute ?? route('expenses.recurrence-periods.index');
    $isUpdate = $recurrencePeriod && ($recurrencePeriod->exists ?? false);
    $submitVariant = $isUpdate ? 'primary' : 'success';
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="code" class="form-label">@lang('expenses::recurrence_periods.fields.code') <span class="text-danger">*</span></label>
        <input type="text"
               name="code"
               id="code"
               class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $recurrencePeriod->code ?? '') }}"
               maxlength="190"
               required>
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="name" class="form-label">@lang('expenses::recurrence_periods.fields.name') <span class="text-danger">*</span></label>
        <input type="text"
               name="name"
               id="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $recurrencePeriod->name ?? '') }}"
               maxlength="190"
               required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="description" class="form-label">@lang('expenses::recurrence_periods.fields.description')</label>
        <textarea name="description"
                  id="description"
                  class="form-control @error('description') is-invalid @enderror"
                  rows="3"
                  placeholder="@lang('expenses::recurrence_periods.fields.description')">{{ old('description', $recurrencePeriod->description ?? '') }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <x-button.action type="submit" variant="{{ $submitVariant }}" :outline="true">
        <i class="bi bi-check2-circle me-1"></i> {{ $submitLabel }}
    </x-button.action>
    <x-button.action href="{{ $cancelRoute }}" variant="secondary" :outline="true">
        @lang('expenses::recurrence_periods.actions.cancel')
    </x-button.action>
</div>
