<?php

namespace Modules\Debts\Http\Requests;

use App\Support\AccountAvailability;
use Illuminate\Foundation\Http\FormRequest;

class StoreDebtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'party_type' => ['nullable', 'in:other'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'counterparty_name' => ['required', 'string', 'max:191'],
            'principal_amount' => ['required', 'numeric', 'min:0.01'],
            'issued_at' => ['required', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
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
            $amount = (float) $this->input('principal_amount', 0);
            $accountType = null;
            $accountId = null;

            if (empty($bankId) && empty($safeId)) {
                $validator->errors()->add('bank_account_id', __('debts::messages.validation.account_required'));
            }

            if (!empty($bankId) && !empty($safeId)) {
                $validator->errors()->add('bank_account_id', __('debts::messages.validation.account_conflict'));
                $validator->errors()->add('safe_id', __('debts::messages.validation.account_conflict'));
            } elseif (!empty($bankId)) {
                $accountType = 'bank';
                $accountId = $bankId;
            } elseif (!empty($safeId)) {
                $accountType = 'safe';
                $accountId = $safeId;
            }

            if ($accountType && $accountId && $amount > 0) {
                $available = AccountAvailability::availableBalance($accountType, (int) $accountId);

                if (! is_null($available) && $amount > $available) {
                    $validator->errors()->add(
                        'principal_amount',
                        __('debts::messages.validation.amount_exceeds_available', [
                            'available' => number_format($available, 2),
                        ])
                    );
                }
            }
        });
    }
}
