<?php

namespace Modules\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Customers\Entities\Customer;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customer = $this->route('customer');
        $customerId = $customer instanceof Customer ? $customer->getKey() : $customer;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('customers', 'name')->ignore($customerId)],
            'national_id' => ['required', 'digits:10', 'regex:/^[12]\d{9}$/', Rule::unique('customers', 'national_id')->ignore($customerId)],
            'phone' => ['required', 'regex:/^(?:\+?9665\d{8}|05\d{8}|9665\d{8})$/', Rule::unique('customers', 'phone')->ignore($customerId)],
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
