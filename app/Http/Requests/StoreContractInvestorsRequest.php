<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractInvestorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // أو منطق الصلاحيات عندك
    }

    public function rules(): array
    {
        return [
            'contract_id' => ['required','exists:contracts,id'],
            'investors'   => ['required','array','min:1'],

            'investors.*.id'               => ['required','distinct','exists:investors,id'],
            'investors.*.share_percentage' => ['required','numeric','min:0.01','max:100'],
            // هنحسب share_value في السيرفر، نخليها اختيارية لو جاية من الفرونت
            'investors.*.share_value'      => ['nullable','numeric','min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'investors.*.id.distinct' => 'لا يمكن اختيار نفس المستثمر أكثر من مرة.',
        ];
    }
}
