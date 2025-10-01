<?php

namespace Modules\Companies\Http\Requests;

use App\Support\AccountAvailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Companies\Entities\CompanyDisbursementStatus;

class StoreCompanyTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'entry_mode' => ['nullable', Rule::in(['single', 'split'])],
            'company_disbursement_status_id' => [
                'required',
                Rule::exists('statuses', 'id')
                    ->where(fn ($query) => $query->where('domain', CompanyDisbursementStatus::DOMAIN)),
            ],
            'bank_account_id' => ['nullable', Rule::exists('bank_accounts', 'id')],
            'safe_id' => ['nullable', Rule::exists('safes', 'id')],
            'bank_amount' => ['nullable', 'numeric', 'min:0'],
            'safe_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.company_id' => ['required', 'integer', Rule::exists('companies', 'id'), 'distinct'],
            'allocations.*.share_amount' => ['required', 'numeric', 'min:0.01'],
            'allocations.*.share_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'allocations.*.notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $mode = $this->input('entry_mode');
        if (!in_array($mode, ['single', 'split'], true)) {
            $mode = null;
        }

        $bankAmount = $this->filled('bank_amount') ? $this->input('bank_amount') : null;
        $safeAmount = $this->filled('safe_amount') ? $this->input('safe_amount') : null;
        $total = $this->input('total_amount');

        if ($mode !== 'split') {
            if ($this->filled('bank_account_id') && ($bankAmount === null || $bankAmount === '')) {
                $bankAmount = $total;
            }

            if ($this->filled('safe_id') && ($safeAmount === null || $safeAmount === '')) {
                $safeAmount = $total;
            }
        }

        $this->merge([
            'entry_mode' => $mode,
            'bank_account_id' => $this->filled('bank_account_id') ? $this->input('bank_account_id') : null,
            'safe_id' => $this->filled('safe_id') ? $this->input('safe_id') : null,
            'bank_amount' => $bankAmount,
            'safe_amount' => $safeAmount,
        ]);

        $allocations = $this->input('allocations');
        if (is_array($allocations)) {
            $this->merge([
                'allocations' => $this->normalizeAllocationAmounts($allocations),
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $bankAccount = $this->input('bank_account_id');
            $safe = $this->input('safe_id');
            $bankAmount = $this->toFloat($this->input('bank_amount'));
            $safeAmount = $this->toFloat($this->input('safe_amount'));
            $totalAmount = $this->toFloat($this->input('total_amount', 0));
            $mode = $this->input('entry_mode');
            $exceedsMessage = __('companies::messages.transactions.amount_exceeds_availability');

            if ($mode === 'split' || ($bankAmount > 0 && $safeAmount > 0)) {
                $mode = 'split';
            } else {
                $mode = 'single';
            }

            if ($this->round2($bankAmount) <= 0 && $this->round2($safeAmount) <= 0) {
                $validator->errors()->add('bank_amount', __('companies::messages.transactions.account_amount_required'));
                $validator->errors()->add('safe_amount', __('companies::messages.transactions.account_amount_required'));
            }

            if ($this->round2($bankAmount + $safeAmount) !== $this->round2($totalAmount)) {
                $validator->errors()->add('total_amount', __('companies::messages.transactions.account_total_mismatch'));
            }

            if ($bankAmount > 0 && empty($bankAccount)) {
                $validator->errors()->add('bank_account_id', __('companies::messages.transactions.bank_required_for_amount'));
            }

            if ($safeAmount > 0 && empty($safe)) {
                $validator->errors()->add('safe_id', __('companies::messages.transactions.safe_required_for_amount'));
            }

            if ($mode === 'single') {
                if ($bankAmount > 0 && $safeAmount > 0) {
                    $validator->errors()->add('bank_amount', __('companies::messages.transactions.single_mode_conflict'));
                    $validator->errors()->add('safe_amount', __('companies::messages.transactions.single_mode_conflict'));
                }
            }

            if ($bankAmount > 0 && $bankAccount) {
                $available = AccountAvailability::availableBalance('bank', (int) $bankAccount);

                if (!is_null($available) && $this->round2($bankAmount) > $this->round2($available)) {
                    $validator->errors()->add('bank_amount', $exceedsMessage);
                }
            }

            if ($safeAmount > 0 && $safe) {
                $available = AccountAvailability::availableBalance('safe', (int) $safe);

                if (!is_null($available) && $this->round2($safeAmount) > $this->round2($available)) {
                    $validator->errors()->add('safe_amount', $exceedsMessage);
                }
            }

            $allocations = $this->input('allocations', []);
            if (!is_array($allocations) || empty($allocations)) {
                $validator->errors()->add('allocations', __('companies::messages.transactions.allocations_required'));
                return;
            }

            $sum = 0.0;
            foreach ($allocations as $allocation) {
                $sum += (float) ($allocation['share_amount'] ?? 0);
            }

            if (abs($sum - $totalAmount) > 0.01) {
                $validator->errors()->add('allocations', __('companies::messages.transactions.allocation_total_mismatch'));
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

    private function round2($value): float
    {
        return round((float) $value, 2);
    }

    private function normalizeAllocationAmounts(array $allocations): array
    {
        $totalAmount = $this->toFloat($this->input('total_amount', 0));

        if ($totalAmount <= 0) {
            return $allocations;
        }

        $currentSum = 0.0;
        $hasAmount = false;

        foreach ($allocations as $allocation) {
            if (isset($allocation['share_amount']) && $allocation['share_amount'] !== '') {
                $currentSum += $this->toFloat($allocation['share_amount']);
                $hasAmount = true;
            }
        }

        if ($hasAmount && abs($currentSum - $totalAmount) <= 0.01) {
            return $allocations;
        }

        $percentages = [];
        $totalPercentage = 0.0;

        foreach ($allocations as $index => $allocation) {
            if (!isset($allocation['share_percentage']) || $allocation['share_percentage'] === '') {
                continue;
            }

            $percentage = max(0.0, $this->toFloat($allocation['share_percentage']));
            $percentages[$index] = $percentage;
            $totalPercentage += $percentage;
        }

        if ($totalPercentage <= 0) {
            return $allocations;
        }

        $remaining = $totalAmount;
        $lastIndex = array_key_last($percentages);

        foreach ($percentages as $index => $percentage) {
            if ($totalPercentage <= 0) {
                break;
            }

            if ($index === $lastIndex) {
                $amount = $this->round2($remaining);
            } else {
                $ratio = $percentage / $totalPercentage;
                $amount = $this->round2($totalAmount * $ratio);
                $remaining -= $amount;
            }

            $allocations[$index]['share_amount'] = number_format($amount, 2, '.', '');

            if ($totalAmount > 0) {
                $normalizedPercentage = $this->round2(($amount / $totalAmount) * 100);
                $allocations[$index]['share_percentage'] = number_format($normalizedPercentage, 2, '.', '');
            }
        }

        return $allocations;
    }
}
