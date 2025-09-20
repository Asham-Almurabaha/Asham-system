@php
    $bankAccount = $bankAccount ?? null;
    $submitLabel = $submitLabel ?? __('Save');
    $cancelRoute = $cancelRoute ?? route('accounts.bank-accounts.index');
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">@lang('accounts::accounts.bank_accounts.fields.name') <span class="text-danger">*</span></label>
        <input
            type="text"
            name="name"
            id="name"
            class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $bankAccount->name ?? '') }}"
            maxlength="190"
            required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="bank_name" class="form-label">@lang('accounts::accounts.bank_accounts.fields.bank_name')</label>
        <input
            type="text"
            name="bank_name"
            id="bank_name"
            class="form-control @error('bank_name') is-invalid @enderror"
            value="{{ old('bank_name', $bankAccount->bank_name ?? '') }}"
            maxlength="190">
        @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="account_number" class="form-label">@lang('accounts::accounts.bank_accounts.fields.account_number')</label>
        <input
            type="text"
            name="account_number"
            id="account_number"
            class="form-control @error('account_number') is-invalid @enderror"
            value="{{ old('account_number', $bankAccount->account_number ?? '') }}"
            maxlength="190"
            dir="ltr"
            inputmode="numeric">
        @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="iban" class="form-label">@lang('accounts::accounts.bank_accounts.fields.iban')</label>
        <input
            type="text"
            name="iban"
            id="iban"
            class="form-control @error('iban') is-invalid @enderror"
            value="{{ old('iban', $bankAccount->iban ?? '') }}"
            maxlength="34"
            dir="ltr"
            style="text-transform: uppercase;">
        @error('iban') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="opening_balance" class="form-label">@lang('accounts::accounts.bank_accounts.fields.opening_balance')</label>
        <input
            type="number"
            name="opening_balance"
            id="opening_balance"
            class="form-control @error('opening_balance') is-invalid @enderror"
            value="{{ old('opening_balance', $bankAccount->opening_balance ?? 0) }}"
            step="0.01"
            inputmode="decimal"
            dir="ltr">
        <div class="form-text">@lang('accounts::accounts.shared.opening_balance_hint')</div>
        @error('opening_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="currency_code" class="form-label">@lang('accounts::accounts.bank_accounts.fields.currency_code')</label>
        <input
            type="text"
            name="currency_code"
            id="currency_code"
            class="form-control @error('currency_code') is-invalid @enderror"
            value="{{ old('currency_code', $bankAccount->currency_code ?? 'SAR') }}"
            maxlength="3"
            style="text-transform: uppercase;"
            dir="ltr"
            autocomplete="off">
        <div class="form-text">@lang('accounts::accounts.shared.currency_code_hint')</div>
        @error('currency_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="is_active" class="form-label">@lang('accounts::accounts.bank_accounts.fields.is_active')</label>
        <select
            name="is_active"
            id="is_active"
            class="form-select @error('is_active') is-invalid @enderror">
            @php $activeValue = old('is_active', isset($bankAccount) ? ($bankAccount->is_active ? '1' : '0') : '1'); @endphp
            <option value="1" @selected($activeValue === '1')>@lang('accounts::accounts.status.active')</option>
            <option value="0" @selected($activeValue === '0')>@lang('accounts::accounts.status.inactive')</option>
        </select>
        @error('is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">@lang('accounts::accounts.bank_accounts.fields.notes')</label>
        <textarea
            name="notes"
            id="notes"
            rows="3"
            class="form-control @error('notes') is-invalid @enderror"
            placeholder="@lang('accounts::accounts.shared.notes_hint')">{{ old('notes', $bankAccount->notes ?? '') }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <x-button type="submit" variant="success" :outline="true">{{ $submitLabel }}</x-button>
    <x-button href="{{ $cancelRoute }}" variant="secondary" :outline="true">@lang('accounts::accounts.shared.cancel')</x-button>
</div>
