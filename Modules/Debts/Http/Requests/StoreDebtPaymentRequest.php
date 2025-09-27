<?php

namespace Modules\Debts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDebtPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $debt = $this->route('debt');

        $outstanding = $debt ? max($debt->outstanding_amount, 0.0) : null;

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
                $validator->errors()->add('bank_account_id', __('debts::messages.validation.account_required'));
            }

            if (!empty($bankId) && !empty($safeId)) {
                $validator->errors()->add('bank_account_id', __('debts::messages.validation.account_conflict'));
                $validator->errors()->add('safe_id', __('debts::messages.validation.account_conflict'));
            }
        });
    }
}
