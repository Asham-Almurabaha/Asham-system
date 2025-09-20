@php
    $safe = $safe ?? null;
    $submitLabel = $submitLabel ?? __('Save');
    $cancelRoute = $cancelRoute ?? route('accounts.safes.index');
    $isUpdate    = $safe && ($safe->exists ?? true);
    $submitVariant = $isUpdate ? 'primary' : 'success';
    $submitIcon    = $isUpdate ? 'bi bi-save2' : 'bi bi-check2-circle';
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">@lang('accounts::accounts.safes.fields.name') <span class="text-danger">*</span></label>
        <input
            type="text"
            name="name"
            id="name"
            class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $safe->name ?? '') }}"
            maxlength="190"
            required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="location" class="form-label">@lang('accounts::accounts.safes.fields.location')</label>
        <input
            type="text"
            name="location"
            id="location"
            class="form-control @error('location') is-invalid @enderror"
            value="{{ old('location', $safe->location ?? '') }}"
            maxlength="190">
        @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="opening_balance" class="form-label">@lang('accounts::accounts.safes.fields.opening_balance')</label>
        <input
            type="number"
            name="opening_balance"
            id="opening_balance"
            class="form-control @error('opening_balance') is-invalid @enderror"
            value="{{ old('opening_balance', $safe->opening_balance ?? 0) }}"
            step="0.01"
            inputmode="decimal"
            dir="ltr">
        <div class="form-text">@lang('accounts::accounts.shared.opening_balance_hint')</div>
        @error('opening_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="currency_code" class="form-label">@lang('accounts::accounts.safes.fields.currency_code')</label>
        <input
            type="text"
            name="currency_code"
            id="currency_code"
            class="form-control @error('currency_code') is-invalid @enderror"
            value="{{ old('currency_code', $safe->currency_code ?? 'SAR') }}"
            maxlength="3"
            style="text-transform: uppercase;"
            dir="ltr"
            autocomplete="off">
        <div class="form-text">@lang('accounts::accounts.shared.currency_code_hint')</div>
        @error('currency_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="is_active" class="form-label">@lang('accounts::accounts.safes.fields.is_active')</label>
        <select
            name="is_active"
            id="is_active"
            class="form-select @error('is_active') is-invalid @enderror">
            @php $activeValue = old('is_active', isset($safe) ? ($safe->is_active ? '1' : '0') : '1'); @endphp
            <option value="1" @selected($activeValue === '1')>@lang('accounts::accounts.status.active')</option>
            <option value="0" @selected($activeValue === '0')>@lang('accounts::accounts.status.inactive')</option>
        </select>
        @error('is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">@lang('accounts::accounts.safes.fields.notes')</label>
        <textarea
            name="notes"
            id="notes"
            rows="3"
            class="form-control @error('notes') is-invalid @enderror"
            placeholder="@lang('accounts::accounts.shared.notes_hint')">{{ old('notes', $safe->notes ?? '') }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <x-button type="submit" variant="{{ $submitVariant }}" :outline="true">
        <i class="{{ $submitIcon }} me-1"></i> {{ $submitLabel }}
    </x-button>
    <x-button href="{{ $cancelRoute }}" variant="secondary" :outline="true">
        @lang('accounts::accounts.shared.cancel')
    </x-button>
</div>
