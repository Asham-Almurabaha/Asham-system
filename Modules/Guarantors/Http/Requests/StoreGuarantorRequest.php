<?php

namespace Modules\Guarantors\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGuarantorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('guarantors', 'name')],
            'national_id' => ['required', 'digits:10', 'regex:/^[12]\d{9}$/', Rule::unique('guarantors', 'national_id')],
            'phone' => ['required', 'regex:/^(?:05\d{8}|\+?9665\d{8}|009665\d{8})$/', Rule::unique('guarantors', 'phone')],
            'email' => ['nullable', 'email', 'max:255'],
            'title_id' => ['nullable', 'exists:titles,id'],
            'address' => ['nullable', 'string'],
            'nationality_id' => ['nullable', 'exists:nationalities,id'],
            'guarantor_status_id' => ['nullable', 'exists:guarantor_statuses,id'],
            'id_card_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
