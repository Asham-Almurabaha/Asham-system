<?php

namespace Modules\Investors\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvestorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('investors', 'name')],
            'national_id' => ['required', 'digits:10', 'regex:/^[12]\d{9}$/', Rule::unique('investors', 'national_id')],
            'phone' => ['required', 'regex:/^(?:05\d{8}|\+?9665\d{8}|009665\d{8})$/', Rule::unique('investors', 'phone')],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'nationality_id' => ['nullable', 'exists:nationalities,id'],
            'title_id' => ['nullable', 'exists:titles,id'],
            'id_card_image' => ['nullable', 'image', 'max:2048'],
            'contract_image' => ['nullable', 'image', 'max:2048'],
            'office_share_percentage' => ['required', 'numeric', 'between:0,100'],
            'investment_start_date' => ['required', 'date'],
        ];
    }
}
