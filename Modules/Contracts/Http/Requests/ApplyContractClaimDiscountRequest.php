<?php

namespace Modules\Contracts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Contracts\Entities\ContractClaim;

class ApplyContractClaimDiscountRequest extends FormRequest
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
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'claim_payer_id' => ['nullable', 'exists:claim_payers,id'],
            'paid_at' => ['nullable', 'date'],
            'bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'safe_id' => ['nullable', 'integer', 'exists:safes,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'return_to_contract' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'discount_amount' => __('contracts::claims.discount_amount'),
            'claim_payer_id' => __('contracts::claims.claim_payer'),
            'paid_at' => __('contracts::claims.claim_payment_date'),
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

            $discountAmount = (float) $this->input('discount_amount', 0);
            $claimAmount = (float) $contractClaim->claim_amount;
            $alreadyPaid = (float) $contractClaim->paid_amount;

            if ($discountAmount > ($claimAmount + 0.00001)) {
                $validator->errors()->add(
                    'discount_amount',
                    __('contracts::claims.validation_discount_exceeds_claim', [
                        'claim' => number_format($claimAmount, 2),
                    ])
                );
            }

            $netAmount = max($claimAmount - $discountAmount, 0);

            if ($alreadyPaid - $netAmount > 0.00001) {
                $validator->errors()->add(
                    'discount_amount',
                    __('contracts::claims.validation_discount_below_paid', [
                        'paid' => number_format($alreadyPaid, 2),
                    ])
                );
            }

            $paymentAmount = max($netAmount - $alreadyPaid, 0);

            if ($paymentAmount > 0.009) {
                if (! $this->filled('claim_payer_id')) {
                    $validator->errors()->add(
                        'claim_payer_id',
                        __('contracts::claims.validation_discount_requires_payer')
                    );
                }

                if (! $this->filled('paid_at')) {
                    $validator->errors()->add(
                        'paid_at',
                        __('contracts::claims.validation_discount_requires_date')
                    );
                }
            }
        });
    }
}
