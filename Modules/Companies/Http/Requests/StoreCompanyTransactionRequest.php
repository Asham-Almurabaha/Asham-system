<?php

namespace Modules\Companies\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => ['nullable', 'string', 'max:190'],
            'transaction_date' => ['required', 'date'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'company_disbursement_status_id' => [
                'required',
                Rule::exists('company_disbursement_statuses', 'id'),
            ],
            'bank_account_id' => ['nullable', Rule::exists('bank_accounts', 'id')],
            'safe_id' => ['nullable', Rule::exists('safes', 'id')],
            'notes' => ['nullable', 'string'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.company_id' => ['required', 'integer', Rule::exists('companies', 'id'), 'distinct'],
            'allocations.*.share_amount' => ['required', 'numeric', 'min:0.01'],
            'allocations.*.share_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'allocations.*.notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $bankAccount = $this->input('bank_account_id');
            $safe = $this->input('safe_id');

            if (empty($bankAccount) && empty($safe)) {
                $validator->errors()->add('bank_account_id', __('companies::messages.transactions.account_required'));
            }

            if (!empty($bankAccount) && !empty($safe)) {
                $validator->errors()->add('bank_account_id', __('companies::messages.transactions.account_conflict'));
            }

            $allocations = $this->input('allocations', []);
            if (!is_array($allocations) || empty($allocations)) {
                $validator->errors()->add('allocations', __('companies::messages.transactions.allocations_required'));
                return;
            }

            $totalAmount = (float) $this->input('total_amount', 0);
            $sum = 0.0;
            foreach ($allocations as $allocation) {
                $sum += (float) ($allocation['share_amount'] ?? 0);
            }

            if (abs($sum - $totalAmount) > 0.01) {
                $validator->errors()->add('allocations', __('companies::messages.transactions.allocation_total_mismatch'));
            }
        });
    }
}
