<?php

namespace Modules\Contracts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'filed_in_party' => ['required', 'string', 'max:255'],
            'filed_against_party' => ['required', 'string', 'max:255'],
            'claim_amount' => ['required', 'numeric', 'min:0'],
            'claim_date' => ['required', 'date'],
            'document_number' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'contract_id' => __('contracts::claims.contract'),
            'filed_in_party' => __('contracts::claims.filed_in_party'),
            'filed_against_party' => __('contracts::claims.filed_against_party'),
            'claim_amount' => __('contracts::claims.claim_amount'),
            'claim_date' => __('contracts::claims.claim_date'),
            'document_number' => __('contracts::claims.document_number'),
        ];
    }
}
