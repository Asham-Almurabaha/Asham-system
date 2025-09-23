<?php

namespace Modules\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('customers', 'name')],
            'national_id' => ['required', 'digits:10', 'regex:/^[12]\d{9}$/', Rule::unique('customers', 'national_id')],
            'phone' => ['required', 'regex:/^(?:\+?9665\d{8}|05\d{8}|9665\d{8})$/', Rule::unique('customers', 'phone')],
            'email' => ['nullable', 'email', 'max:255'],
            'title_id' => ['nullable', 'exists:titles,id'],
            'address' => ['nullable', 'string'],
            'nationality_id' => ['nullable', 'exists:nationalities,id'],
            'customer_status_id' => ['nullable', 'exists:customer_statuses,id'],
            'id_card_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
