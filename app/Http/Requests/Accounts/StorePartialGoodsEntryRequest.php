<?php

namespace App\Http\Requests\Accounts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartialGoodsEntryRequest extends FormRequest
{
    protected $errorBag = 'goodsPartial';

    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        if (method_exists($user, 'can')) {
            return $user->can('accounts.entries.create');
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'active_tab'        => ['nullable', Rule::in(['partial'])],
            'amount'            => ['required', 'numeric', 'min:0.01'],
            'bank_share'        => ['nullable', 'numeric', 'min:0'],
            'safe_share'        => ['nullable', 'numeric', 'min:0'],
            'bank_account_id'   => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'safe_id'           => ['nullable', 'integer', 'exists:safes,id'],
            'transaction_date'  => ['required', 'date_format:Y-m-d'],
            'notes'             => ['nullable', 'string', 'max:1000'],
            'products'          => ['required', 'array', 'min:1'],
            'products.*.product_type_id' => ['required', 'integer', 'exists:product_types,id'],
            'products.*.quantity'        => ['required', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'amount'                   => 'إجمالي المبلغ',
            'bank_share'               => 'مبلغ البنك',
            'safe_share'               => 'مبلغ الخزنة',
            'bank_account_id'          => 'الحساب البنكي',
            'safe_id'                  => 'الخزنة',
            'transaction_date'         => 'تاريخ العملية',
            'notes'                    => 'ملاحظات',
            'products'                 => 'المنتجات',
            'products.*.product_type_id' => 'نوع البضاعة',
            'products.*.quantity'        => 'الكمية',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'bank_share'      => $this->input('bank_share', 0),
            'safe_share'      => $this->input('safe_share', 0),
            'bank_account_id' => $this->filled('bank_account_id') ? $this->input('bank_account_id') : null,
            'safe_id'         => $this->filled('safe_id') ? $this->input('safe_id') : null,
        ]);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $bank = $this->toFloat($this->input('bank_share'));
            $safe = $this->toFloat($this->input('safe_share'));
            $total = $this->toFloat($this->input('amount'));

            $bank = $bank < 0 ? 0 : $bank;
            $safe = $safe < 0 ? 0 : $safe;

            if ($bank <= 0 && $safe <= 0) {
                $validator->errors()->add('bank_share', 'أدخل قيمة في البنك أو الخزنة على الأقل.');
                $validator->errors()->add('safe_share', 'أدخل قيمة في البنك أو الخزنة على الأقل.');
            }

            if ($this->round2($bank + $safe) !== $this->round2($total)) {
                $validator->errors()->add('amount', 'يجب أن يساوي مجموع البنك + الخزنة إجمالي المبلغ.');
            }

            if ($bank > 0 && !$this->filled('bank_account_id')) {
                $validator->errors()->add('bank_account_id', 'اختر الحساب البنكي لهذا الجزء.');
            }

            if ($safe > 0 && !$this->filled('safe_id')) {
                $validator->errors()->add('safe_id', 'اختر الخزنة لهذا الجزء.');
            }
        });
    }

    private function toFloat($value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }

    private function round2(float $value): float
    {
        return round($value, 2);
    }
}
