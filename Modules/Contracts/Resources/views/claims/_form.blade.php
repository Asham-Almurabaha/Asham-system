@php
    $editing = isset($claim);
@endphp

<div class="row g-3">
    <div class="col-12 col-md-4">
        <label class="form-label">{{ __('contracts::claims.contract') }}</label>
        <select name="contract_id" class="form-select" required>
            <option value="">{{ __('contracts::claims.choose_contract') }}</option>
            @foreach ($contracts as $contractOption)
                <option value="{{ $contractOption->id }}" @selected(old('contract_id', $claim->contract_id ?? '') == $contractOption->id)>
                    {{ $contractOption->contract_number ?? ('#' . $contractOption->id) }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label">{{ __('contracts::claims.filed_in_party') }}</label>
        <input type="text" name="filed_in_party" class="form-control" maxlength="255" required
               value="{{ old('filed_in_party', $claim->filed_in_party ?? '') }}">
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label">{{ __('contracts::claims.filed_against_party') }}</label>
        <input type="text" name="filed_against_party" class="form-control" maxlength="255" required
               value="{{ old('filed_against_party', $claim->filed_against_party ?? '') }}">
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label">{{ __('contracts::claims.claim_amount') }}</label>
        <input type="number" name="claim_amount" class="form-control" step="0.01" min="0" required
               value="{{ old('claim_amount', $claim->claim_amount ?? '') }}">
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label">{{ __('contracts::claims.claim_date') }}</label>
        <input type="date" name="claim_date" class="form-control" required
               value="{{ old('claim_date', optional($claim->claim_date ?? null)->format('Y-m-d')) }}">
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label">{{ __('contracts::claims.document_number') }}</label>
        <input type="text" name="document_number" class="form-control" maxlength="255" required
               value="{{ old('document_number', $claim->document_number ?? '') }}">
    </div>
</div>
