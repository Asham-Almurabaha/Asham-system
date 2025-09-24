<?php

namespace App\Http\Requests\Accounts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGoodsEntryRequest extends FormRequest
{
    protected $errorBag = 'goodsPurchase';

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
            'active_tab'        => ['nullable', Rule::in(['purchase'])],
            'status_id'         => ['nullable', 'integer', 'exists:transaction_statuses,id'],
            'bank_account_id'   => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'safe_id'           => ['nullable', 'integer', 'exists:safes,id'],
            'amount'            => ['required', 'numeric', 'min:0.01'],
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
            'bank_account_id' => 'الحساب البنكي',
            'safe_id'         => 'الخزنة',
            'status_id'       => 'حالة العملية',
            'amount'          => 'المبلغ',
            'transaction_date'=> 'تاريخ العملية',
            'notes'           => 'ملاحظات',
            'products'        => 'المنتجات',
            'products.*.product_type_id' => 'نوع البضاعة',
            'products.*.quantity'        => 'الكمية',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'bank_account_id' => $this->filled('bank_account_id') ? $this->input('bank_account_id') : null,
            'safe_id'         => $this->filled('safe_id') ? $this->input('safe_id') : null,
            'status_id'       => $this->filled('status_id') ? (int) $this->input('status_id') : null,
        ]);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $hasBank = $this->filled('bank_account_id');
            $hasSafe = $this->filled('safe_id');

            if (!$hasBank && !$hasSafe) {
                $validator->errors()->add('bank_account_id', 'اختر حسابًا بنكيًا أو خزنة.');
            }

            if ($hasBank && $hasSafe) {
                $validator->errors()->add('bank_account_id', 'لا يمكن اختيار بنك وخزنة في نفس القيد.');
                $validator->errors()->add('safe_id', 'لا يمكن اختيار بنك وخزنة في نفس القيد.');
            }
        });
    }
}
