<?php

namespace Modules\Companies\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Companies\Entities\CompanyDisbursementStatus;

class UpdateCompanyDisbursementStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var CompanyDisbursementStatus $status */
        $status = $this->route('company_disbursement_status');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('company_disbursement_statuses', 'name')->ignore($status?->id),
            ],
        ];
    }
}
