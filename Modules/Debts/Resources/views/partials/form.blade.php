@php
    $partyType = old('party_type', $debt->party_type ?? 'customer');
    $initialBank = old('bank_account_id', $debt->bank_account_id ?? null);
    $initialSafe = old('safe_id', $debt->safe_id ?? null);
    $accountPickerValue = $initialBank ? ('bank:'.$initialBank) : ($initialSafe ? ('safe:'.$initialSafe) : '');
    $hasAccounts = $banks->isNotEmpty() || $safes->isNotEmpty();
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">{{ __('debts::messages.fields.party_type') }}</label>
        <select name="party_type" id="party_type" class="form-select" required>
            @foreach(__('debts::messages.types') as $key => $label)
                <option value="{{ $key }}" @selected($partyType === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4 js-party-field" data-party="customer">
        <label class="form-label">{{ __('debts::messages.fields.customer') }}</label>
        <select name="customer_id" id="customer_id" class="form-select">
            <option value="">{{ __('debts::messages.filters.all') }}</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" @selected(old('customer_id', $debt->customer_id ?? '') == $customer->id)>{{ $customer->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4 js-party-field" data-party="investor">
        <label class="form-label">{{ __('debts::messages.fields.investor') }}</label>
        <select name="investor_id" id="investor_id" class="form-select">
            <option value="">{{ __('debts::messages.filters.all') }}</option>
            @foreach($investors as $investor)
                <option value="{{ $investor->id }}" @selected(old('investor_id', $debt->investor_id ?? '') == $investor->id)>{{ $investor->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4 js-counterparty-name">
        <label class="form-label">{{ __('debts::messages.fields.counterparty_name') }}</label>
        <input type="text" name="counterparty_name" id="counterparty_name" class="form-control" value="{{ old('counterparty_name', $debt->counterparty_name ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label" for="principal_amount">{{ __('debts::messages.fields.principal_amount') }}</label>
        <input type="number" name="principal_amount" id="principal_amount" min="0" step="0.01" class="form-control" value="{{ old('principal_amount', $debt->principal_amount ?? '') }}" required>
        @error('principal_amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="account_picker">{{ __('debts::messages.fields.account') }}</label>
        <select id="account_picker" name="account_picker" class="form-select" required {{ $hasAccounts ? '' : 'disabled' }}>
            <option value="" disabled {{ $accountPickerValue ? '' : 'selected' }}>{{ __('debts::messages.placeholders.select_account') }}</option>
            <optgroup label="{{ __('debts::messages.groups.banks') }}">
                @foreach($banks as $bank)
                    <option value="bank:{{ $bank->id }}" @selected($accountPickerValue === 'bank:'.$bank->id)>{{ $bank->name }}</option>
                @endforeach
            </optgroup>
            <optgroup label="{{ __('debts::messages.groups.safes') }}">
                @foreach($safes as $safe)
                    <option value="safe:{{ $safe->id }}" @selected($accountPickerValue === 'safe:'.$safe->id)>{{ $safe->name }}</option>
                @endforeach
            </optgroup>
        </select>

        <input type="hidden" name="bank_account_id" id="bank_account_id" value="{{ $initialBank ?? '' }}">
        <input type="hidden" name="safe_id" id="safe_id" value="{{ $initialSafe ?? '' }}">

        <div id="account_availability" class="form-text mt-1">
            <span class="text-muted">{{ __('debts::messages.hints.account_available') }}</span>
            <strong id="account_availability_value">—</strong>
            <span id="account_availability_spinner" class="spinner-border spinner-border-sm align-middle d-none" role="status" aria-hidden="true"></span>
        </div>

        @error('bank_account_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        @error('safe_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        <div class="form-text">{{ __('debts::messages.hints.account_choice') }}</div>
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('debts::messages.fields.issued_at') }}</label>
        <input type="date" name="issued_at" class="form-control" value="{{ old('issued_at', optional($debt->issued_at)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('debts::messages.fields.due_at') }}</label>
        <input type="date" name="due_at" class="form-control" value="{{ old('due_at', optional($debt->due_at)->format('Y-m-d')) }}">
    </div>

    <div class="col-12">
        <label class="form-label">{{ __('debts::messages.fields.notes') }}</label>
        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $debt->notes ?? '') }}</textarea>
    </div>
</div>
