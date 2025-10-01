<?php

namespace Modules\Companies\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:190',
                Rule::unique('companies', 'name')->whereNull('deleted_at'),
            ],
            'notes' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
