@php
    use Carbon\Carbon;

    // تهيئة آمنة للمتغيرات
    $expense      = isset($expense) ? $expense : null;
    $types        = isset($types) ? $types : collect();
    $submitLabel  = isset($submitLabel) ? $submitLabel : __('expenses::expenses.actions.save');
    $cancelRoute  = isset($cancelRoute) ? $cancelRoute : route('expenses.expenses.index');

    // تحديد هل تحديث أم إنشاء
    $isUpdate = false;
    if ($expense) {
        // exists خاصية في Eloquent Model؛ نتأكد من وجودها وبوليانها
        $isUpdate = property_exists($expense, 'exists') ? (bool) $expense->exists : false;
    }
    $submitVariant = $isUpdate ? 'primary' : 'success';

    // دوال صغيرة لقراءة التاريخ بأمان
    $toDateStringSafe = function ($value) {
        if ($value instanceof Carbon) {
            return $value->toDateString();
        }
        if (is_string($value)) {
            // نفترض أنه yyyy-mm-dd جاهز
            return $value;
        }
        return '';
    };

    // إعداد قيم الحقول مع old()
    $oldExpenseType   = old('expense_type_id', $expense ? ($expense->expense_type_id ?? '') : '');
    $oldTitle         = old('title', $expense ? ($expense->title ?? '') : '');
    $oldAmount        = old('amount', $expense ? ($expense->amount ?? '') : '');
    $oldCurrency      = old('currency_code', $expense ? ($expense->currency_code ?? 'SAR') : 'SAR');
    $oldReference     = old('reference', $expense ? ($expense->reference ?? '') : '');
    $oldNotes         = old('notes', $expense ? ($expense->notes ?? '') : '');

    $oldDueDate = old(
        'due_date',
        $expense ? $toDateStringSafe($expense->due_date ?? null) : ''
    );

    $oldPaidAt = old(
        'paid_at',
        $expense ? $toDateStringSafe($expense->paid_at ?? null) : ''
    );
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="expense_type_id" class="form-label">
            @lang('expenses::expenses.fields.expense_type_id') <span class="text-danger">*</span>
        </label>
        <select
            name="expense_type_id"
            id="expense_type_id"
            class="form-select @error('expense_type_id') is-invalid @enderror"
            required
        >
            <option value="">@lang('expenses::expenses.fields.expense_type_id')</option>
            @foreach($types as $id => $name)
                <option value="{{ $id }}" @selected($oldExpenseType == $id)>{{ $name }}</option>
            @endforeach
        </select>
        @error('expense_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="title" class="form-label">
            @lang('expenses::expenses.fields.title') <span class="text-danger">*</span>
        </label>
        <input
            type="text"
            name="title"
            id="title"
            class="form-control @error('title') is-invalid @enderror"
            value="{{ $oldTitle }}"
            maxlength="190"
            required
        >
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="amount" class="form-label">
            @lang('expenses::expenses.fields.amount') <span class="text-danger">*</span>
        </label>
        <input
            type="number"
            name="amount"
            id="amount"
            class="form-control @error('amount') is-invalid @enderror"
            value="{{ $oldAmount }}"
            step="0.01"
            min="0"
            inputmode="decimal"
            dir="ltr"
            required
        >
        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="currency_code" class="form-label">
            @lang('expenses::expenses.fields.currency_code')
        </label>
        <input
            type="text"
            name="currency_code"
            id="currency_code"
            class="form-control @error('currency_code') is-invalid @enderror"
            value="{{ $oldCurrency }}"
            maxlength="3"
            style="text-transform: uppercase"
            dir="ltr"
        >
        @error('currency_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="due_date" class="form-label">
            @lang('expenses::expenses.fields.due_date') <span class="text-danger">*</span>
        </label>
        <input
            type="date"
            name="due_date"
            id="due_date"
            class="form-control @error('due_date') is-invalid @enderror"
            value="{{ $oldDueDate }}"
            required
        >
        @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="paid_at" class="form-label">
            @lang('expenses::expenses.fields.paid_at')
        </label>
        <input
            type="date"
            name="paid_at"
            id="paid_at"
            class="form-control @error('paid_at') is-invalid @enderror"
            value="{{ $oldPaidAt }}"
        >
        @error('paid_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="reference" class="form-label">
            @lang('expenses::expenses.fields.reference')
        </label>
        <input
            type="text"
            name="reference"
            id="reference"
            class="form-control @error('reference') is-invalid @enderror"
            value="{{ $oldReference }}"
            maxlength="190"
        >
        @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">
            @lang('expenses::expenses.fields.notes')
        </label>
        <textarea
            name="notes"
            id="notes"
            class="form-control @error('notes') is-invalid @enderror"
            rows="3"
            placeholder="@lang('expenses::expenses.fields.notes')"
        >{{ $oldNotes }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <x-button.action type="submit" variant="{{ $submitVariant }}" :outline="true">
        <i class="bi bi-check2-circle me-1"></i> {{ $submitLabel }}
    </x-button.action>
    <x-button.action href="{{ $cancelRoute }}" variant="secondary" :outline="true">
        @lang('expenses::expenses.actions.cancel')
    </x-button.action>
</div>
