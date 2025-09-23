<?php

namespace Modules\Investors\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Investors\Entities\Investor;

class UpdateInvestorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $investor = $this->route('investor');
        $investorId = $investor instanceof Investor ? $investor->getKey() : $investor;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('investors', 'name')->ignore($investorId)],
            'national_id' => ['required', 'digits:10', 'regex:/^[12]\d{9}$/', Rule::unique('investors', 'national_id')->ignore($investorId)],
            'phone' => ['required', 'regex:/^(?:05\d{8}|\+?9665\d{8}|009665\d{8})$/', Rule::unique('investors', 'phone')->ignore($investorId)],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'nationality_id' => ['nullable', 'exists:nationalities,id'],
            'title_id' => ['nullable', 'exists:titles,id'],
            'id_card_image' => ['nullable', 'image', 'max:2048'],
            'contract_image' => ['nullable', 'image', 'max:2048'],
            'office_share_percentage' => ['required', 'numeric', 'between:0,100'],
            'investment_start_date' => ['nullable', 'date'],
        ];
    }
}
