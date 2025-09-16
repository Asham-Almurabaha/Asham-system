<?php

namespace Modules\Accounts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Accounts\Entities\BankAccount;

class BankAccountController extends Controller
{
    public function index()
    {
        $bankAccounts = BankAccount::orderBy('name')->get();

        return view('accounts::bank_accounts.index', compact('bankAccounts'));
    }

    public function create()
    {
        return view('accounts::bank_accounts.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateBankAccount($request);

        BankAccount::create($data);

        return redirect()
            ->route('accounts.bank-accounts.index')
            ->with('success', __('accounts::messages.bank_accounts.created'));
    }

    public function edit(BankAccount $bank_account)
    {
        return view('accounts::bank_accounts.edit', [
            'bankAccount' => $bank_account,
        ]);
    }

    public function update(Request $request, BankAccount $bank_account)
    {
        $data = $this->validateBankAccount($request, $bank_account->id);

        $bank_account->update($data);

        return redirect()
            ->route('accounts.bank-accounts.index')
            ->with('success', __('accounts::messages.bank_accounts.updated'));
    }

    public function destroy(BankAccount $bank_account)
    {
        if ($bank_account->ledgerEntries()->exists()) {
            return redirect()
                ->route('accounts.bank-accounts.index')
                ->withErrors(['general' => __('accounts::messages.bank_accounts.delete_failed_in_use')]);
        }

        $bank_account->delete();

        return redirect()
            ->route('accounts.bank-accounts.index')
            ->with('success', __('accounts::messages.bank_accounts.deleted'));
    }

    private function validateBankAccount(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:190', Rule::unique('bank_accounts', 'name')->ignore($ignoreId)],
            'bank_name'       => ['nullable', 'string', 'max:190'],
            'account_number'  => ['nullable', 'string', 'max:190', Rule::unique('bank_accounts', 'account_number')->ignore($ignoreId)],
            'iban'            => ['nullable', 'string', 'max:34', Rule::unique('bank_accounts', 'iban')->ignore($ignoreId)],
            'opening_balance' => ['nullable', 'numeric'],
            'currency_code'   => ['nullable', 'string', 'size:3'],
            'is_active'       => ['nullable', 'boolean'],
            'notes'           => ['nullable', 'string'],
        ], [], [
            'name'            => __('accounts::accounts.bank_accounts.fields.name'),
            'bank_name'       => __('accounts::accounts.bank_accounts.fields.bank_name'),
            'account_number'  => __('accounts::accounts.bank_accounts.fields.account_number'),
            'iban'            => __('accounts::accounts.bank_accounts.fields.iban'),
            'opening_balance' => __('accounts::accounts.bank_accounts.fields.opening_balance'),
            'currency_code'   => __('accounts::accounts.bank_accounts.fields.currency_code'),
            'is_active'       => __('accounts::accounts.bank_accounts.fields.is_active'),
            'notes'           => __('accounts::accounts.bank_accounts.fields.notes'),
        ]);

        return [
            'name'            => $this->cleanString($validated['name'] ?? ''),
            'bank_name'       => $this->cleanString($validated['bank_name'] ?? null),
            'account_number'  => $this->cleanString($validated['account_number'] ?? null),
            'iban'            => $this->cleanString($validated['iban'] ?? null),
            'opening_balance' => $this->normalizeAmount($validated['opening_balance'] ?? null),
            'currency_code'   => $this->normalizeCurrency($validated['currency_code'] ?? null),
            'is_active'       => $request->boolean('is_active', true),
            'notes'           => $this->cleanString($validated['notes'] ?? null),
        ];
    }

    private function cleanString(?string $value): ?string
    {
        $value = is_null($value) ? null : trim($value);

        return $value === '' ? null : $value;
    }

    private function normalizeAmount($value): float
    {
        $numeric = is_null($value) || $value === '' ? 0 : (float) $value;

        return round($numeric, 2);
    }

    private function normalizeCurrency(?string $value): string
    {
        $value = $this->cleanString($value);

        return $value ? mb_strtoupper($value) : 'SAR';
    }
}
