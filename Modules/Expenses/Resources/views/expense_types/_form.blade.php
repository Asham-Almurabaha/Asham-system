@php
    $expenseType = $expenseType ?? null;
    $submitLabel = $submitLabel ?? __('expenses::types.actions.save');
    $cancelRoute = $cancelRoute ?? route('expenses.expense-types.index');
    $isUpdate = $expenseType && ($expenseType->exists ?? false);
    $submitVariant = $isUpdate ? 'primary' : 'success';
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">@lang('expenses::types.fields.name') <span class="text-danger">*</span></label>
        <input type="text"
               name="name"
               id="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $expenseType->name ?? '') }}"
               maxlength="190"
               required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="default_amount" class="form-label">@lang('expenses::types.fields.default_amount')</label>
        <input type="number"
               name="default_amount"
               id="default_amount"
               class="form-control @error('default_amount') is-invalid @enderror"
               value="{{ old('default_amount', $expenseType->default_amount ?? 0) }}"
               step="0.01"
               min="0"
               inputmode="decimal"
               dir="ltr">
        @error('default_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="currency_code" class="form-label">@lang('expenses::types.fields.currency_code')</label>
        <input type="text"
               name="currency_code"
               id="currency_code"
               class="form-control @error('currency_code') is-invalid @enderror"
               value="{{ old('currency_code', $expenseType->currency_code ?? 'SAR') }}"
               maxlength="3"
               style="text-transform: uppercase"
               dir="ltr">
        @error('currency_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="recurrence_interval" class="form-label">@lang('expenses::types.fields.recurrence_interval')</label>
        <input type="text"
               name="recurrence_interval"
               id="recurrence_interval"
               class="form-control @error('recurrence_interval') is-invalid @enderror"
               value="{{ old('recurrence_interval', $expenseType->recurrence_interval ?? '') }}"
               maxlength="50">
        <div class="form-text">@lang('expenses::types.status.recurring')</div>
        @error('recurrence_interval') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <div class="form-check mt-4">
            @php
                $isRecurring = old('is_recurring', $expenseType?->is_recurring ? '1' : '0');
                $isRecurring = in_array($isRecurring, ['1', 1, true, 'on'], true) ? '1' : '0';
            @endphp
            <input type="checkbox"
                   name="is_recurring"
                   id="is_recurring"
                   value="1"
                   class="form-check-input @error('is_recurring') is-invalid @enderror"
                   @checked($isRecurring === '1')>
            <label class="form-check-label" for="is_recurring">@lang('expenses::types.fields.is_recurring')</label>
            @error('is_recurring') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="col-12">
        <label for="description" class="form-label">@lang('expenses::types.fields.description')</label>
        <textarea name="description"
                  id="description"
                  class="form-control @error('description') is-invalid @enderror"
                  rows="3"
                  placeholder="@lang('expenses::types.fields.description')">{{ old('description', $expenseType->description ?? '') }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <x-button.action type="submit" variant="{{ $submitVariant }}" :outline="true">
        <i class="bi bi-check2-circle me-1"></i> {{ $submitLabel }}
    </x-button.action>
    <x-button.action href="{{ $cancelRoute }}" variant="secondary" :outline="true">@lang('expenses::types.actions.cancel')</x-button.action>
</div>
