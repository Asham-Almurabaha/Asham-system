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
                Rule::unique('statuses', 'name')
                    ->ignore($status?->id)
                    ->where(fn ($query) => $query->where('domain', CompanyDisbursementStatus::DOMAIN)),
            ],
        ];
    }
}
