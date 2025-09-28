<?php

namespace Modules\Expenses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpensePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $expense = $this->route('expense');

        $outstanding = null;
        if ($expense) {
            $paid = (float) $expense->payments()->sum('amount');
            $outstanding = max((float) $expense->amount - $paid, 0.0);
        }

        $amountRule = ['required', 'numeric', 'min:0.01'];
        if (! is_null($outstanding)) {
            $amountRule[] = 'lte:'.number_format($outstanding, 2, '.', '');
        }

        return [
            'amount' => $amountRule,
            'paid_at' => ['required', 'date'],
            'bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'safe_id' => ['nullable', 'integer', 'exists:safes,id'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $bankId = $this->integer('bank_account_id');
            $safeId = $this->integer('safe_id');

            if (empty($bankId) && empty($safeId)) {
                $validator->errors()->add('bank_account_id', __('expenses::messages.validation.account_required'));
            }

            if (! empty($bankId) && ! empty($safeId)) {
                $validator->errors()->add('bank_account_id', __('expenses::messages.validation.account_conflict'));
                $validator->errors()->add('safe_id', __('expenses::messages.validation.account_conflict'));
            }
        });
    }
}
