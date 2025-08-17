<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractInvestorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // لو عندك صلاحيات محددة استخدم Gate::allows(..) هنا، وإلا اسمح طالما المستخدم داخل
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'contract_id' => ['required','integer','exists:contracts,id'],

            'investors' => ['required','array','min:1'],
            'investors.*.id' => ['required','integer','distinct','exists:investors,id'],

            // المتصفح بيبعته صحيح، والسيرفر بيعيد حساب القيمة.. بس خلّيه مطلوب وصحيح
            'investors.*.share_percentage' => ['required','numeric','min:1','max:100'],

            // لو بتبعته مع الطلب خلّيه اختياري وصحيح
            'investors.*.share_value' => ['nullable','numeric','min:0'],
        ];
    }
}
