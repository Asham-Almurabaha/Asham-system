<?php

namespace Modules\Companies\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Companies\Entities\CompanyDisbursementStatus;

class StoreCompanyDisbursementStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('statuses', 'name')
                    ->where(fn ($query) => $query->where('domain', CompanyDisbursementStatus::DOMAIN)),
            ],
        ];
    }
}
