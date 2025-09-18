<?php

namespace Modules\Contracts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractClaim;

class StoreContractClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contract_id' => ['required', 'integer', Rule::exists('contracts', 'id')],
            'filed_party_role' => ['required', 'string', Rule::in(ContractClaim::FILED_PARTY_ROLES)],
            'claim_amount' => ['required', 'numeric', 'min:0'],
            'claim_date' => ['required', 'date'],
            'document_number' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'contract_id' => __('contracts::claims.contract'),
            'filed_party_role' => __('contracts::claims.filed_party_role'),
            'claim_amount' => __('contracts::claims.claim_amount'),
            'claim_date' => __('contracts::claims.claim_date'),
            'document_number' => __('contracts::claims.document_number'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $contractId = $this->input('contract_id');
            $filedPartyRole = $this->input('filed_party_role');

            if (! $contractId || ! $filedPartyRole) {
                return;
            }

            $contract = Contract::query()
                ->select('id', 'customer_id', 'guarantor_id')
                ->find($contractId);

            if (! $contract) {
                return;
            }

            if ($filedPartyRole === ContractClaim::FILED_PARTY_CUSTOMER && ! $contract->customer_id) {
                $validator->errors()->add('filed_party_role', __('contracts::claims.validation_missing_customer'));
            }

            if ($filedPartyRole === ContractClaim::FILED_PARTY_GUARANTOR && ! $contract->guarantor_id) {
                $validator->errors()->add('filed_party_role', __('contracts::claims.validation_missing_guarantor'));
            }
        });
    }
}
