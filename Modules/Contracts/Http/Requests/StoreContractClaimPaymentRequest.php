<?php

namespace Modules\Contracts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Contracts\Entities\ContractClaim;

class StoreContractClaimPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'bank_account_id' => $this->filled('bank_account_id') ? $this->input('bank_account_id') : null,
            'safe_id' => $this->filled('safe_id') ? $this->input('safe_id') : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'payment_claim_id' => ['nullable', 'integer'],
            'claim_payer_id' => ['required', 'exists:claim_payers,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            'bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'safe_id' => ['nullable', 'integer', 'exists:safes,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'return_to_contract' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'amount' => __('contracts::claims.claim_payment_amount'),
            'bank_account_id' => __('contracts::claims.payment_account'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $contractClaim = $this->route('contractClaim');

            if (! $contractClaim instanceof ContractClaim) {
                return;
            }

            $bankId = $this->input('bank_account_id');
            $safeId = $this->input('safe_id');

            if ($bankId && $safeId) {
                $validator->errors()->add(
                    'bank_account_id',
                    __('contracts::claims.validation_account_conflict')
                );
            }

            $amount = (float) $this->input('amount', 0);
            $remaining = (float) $contractClaim->remaining_amount;

            if ($amount < 0.01) {
                return;
            }

            if (($amount - $remaining) > 0.00001) {
                $validator->errors()->add(
                    'amount',
                    __('contracts::claims.validation_payment_exceeds_remaining', [
                        'remaining' => number_format($remaining, 2),
                    ])
                );
            }
        });
    }
}
