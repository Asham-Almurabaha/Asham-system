@php
    $expense = $expense ?? null;
    $types = $types ?? collect();
    $submitLabel = $submitLabel ?? __('expenses::expenses.actions.save');
    $cancelRoute = $cancelRoute ?? route('expenses.expenses.index');
    $isUpdate = $expense && ($expense->exists ?? false);
    $submitVariant = $isUpdate ? 'primary' : 'success';
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="expense_type_id" class="form-label">@lang('expenses::expenses.fields.expense_type_id') <span class="text-danger">*</span></label>
        <select name="expense_type_id" id="expense_type_id" class="form-select @error('expense_type_id') is-invalid @enderror" required>
            <option value="">@lang('expenses::expenses.fields.expense_type_id')</option>
            @foreach($types as $id => $name)
                <option value="{{ $id }}" @selected(old('expense_type_id', $expense->expense_type_id ?? '') == $id)>{{ $name }}</option>
            @endforeach
        </select>
        @error('expense_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="title" class="form-label">@lang('expenses::expenses.fields.title') <span class="text-danger">*</span></label>
        <input type="text"
               name="title"
               id="title"
               class="form-control @error('title') is-invalid @enderror"
               value="{{ old('title', $expense->title ?? '') }}"
               maxlength="190"
               required>
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="amount" class="form-label">@lang('expenses::expenses.fields.amount') <span class="text-danger">*</span></label>
        <input type="number"
               name="amount"
               id="amount"
               class="form-control @error('amount') is-invalid @enderror"
               value="{{ old('amount', $expense->amount ?? '') }}"
               step="0.01"
               min="0"
               inputmode="decimal"
               dir="ltr"
               required>
        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="currency_code" class="form-label">@lang('expenses::expenses.fields.currency_code')</label>
        <input type="text"
               name="currency_code"
               id="currency_code"
               class="form-control @error('currency_code') is-invalid @enderror"
               value="{{ old('currency_code', $expense->currency_code ?? 'SAR') }}"
               maxlength="3"
               style="text-transform: uppercase"
               dir="ltr">
        @error('currency_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="due_date" class="form-label">@lang('expenses::expenses.fields.due_date') <span class="text-danger">*</span></label>
        <input type="date"
               name="due_date"
               id="due_date"
               class="form-control @error('due_date') is-invalid @enderror"
               value="{{ old('due_date', optional($expense->due_date)->toDateString()) }}"
               required>
        @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="paid_at" class="form-label">@lang('expenses::expenses.fields.paid_at')</label>
        <input type="date"
               name="paid_at"
               id="paid_at"
               class="form-control @error('paid_at') is-invalid @enderror"
               value="{{ old('paid_at', optional($expense->paid_at)->toDateString()) }}">
        @error('paid_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="reference" class="form-label">@lang('expenses::expenses.fields.reference')</label>
        <input type="text"
               name="reference"
               id="reference"
               class="form-control @error('reference') is-invalid @enderror"
               value="{{ old('reference', $expense->reference ?? '') }}"
               maxlength="190">
        @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">@lang('expenses::expenses.fields.notes')</label>
        <textarea name="notes"
                  id="notes"
                  class="form-control @error('notes') is-invalid @enderror"
                  rows="3"
                  placeholder="@lang('expenses::expenses.fields.notes')">{{ old('notes', $expense->notes ?? '') }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <x-button.action type="submit" variant="{{ $submitVariant }}" :outline="true">
        <i class="bi bi-check2-circle me-1"></i> {{ $submitLabel }}
    </x-button.action>
    <x-button.action href="{{ $cancelRoute }}" variant="secondary" :outline="true">@lang('expenses::expenses.actions.cancel')</x-button.action>
</div>
