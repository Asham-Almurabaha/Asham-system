<?php

namespace Modules\Contracts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractClaimStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'claim_status_id' => ['required', 'integer', Rule::exists('claim_statuses', 'id')],
        ];
    }

    public function attributes(): array
    {
        return [
            'claim_status_id' => __('contracts::claims.claim_status'),
        ];
    }
}
