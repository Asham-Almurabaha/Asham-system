<?php

namespace Modules\Companies\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyDisbursementStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_default' => $this->boolean('is_default'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('company_disbursement_statuses', 'name'),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
