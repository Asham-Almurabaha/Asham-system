<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;

use App\Models\BankCashAccount;
use Illuminate\Http\Request;

class BankCashAccountController extends Controller
{
    public function index()
    {
        $accounts = BankCashAccount::all();
        return view('bank_cash_accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('bank_cash_accounts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'type'           => 'required|in:bank,cash',
            'account_number' => 'nullable|string|max:100',
            'branch'         => 'nullable|string|max:255',
            'balance'        => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string',
            'active'         => 'boolean',
        ]);

        BankCashAccount::create($request->all());

        return redirect()->route('bank_cash_accounts.index')->with('success', 'تم إضافة الحساب بنجاح');
    }

    public function edit(BankCashAccount $bankCashAccount)
    {
        return view('bank_cash_accounts.edit', compact('bankCashAccount'));
    }

    public function update(Request $request, BankCashAccount $bankCashAccount)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'type'           => 'required|in:bank,cash',
            'account_number' => 'nullable|string|max:100',
            'branch'         => 'nullable|string|max:255',
            'balance'        => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string',
            'active'         => 'boolean',
        ]);

        $bankCashAccount->update($request->all());

        return redirect()->route('bank_cash_accounts.index')->with('success', 'تم تحديث الحساب بنجاح');
    }

    public function destroy(BankCashAccount $bankCashAccount)
    {
        $bankCashAccount->delete();

        return redirect()->route('bank_cash_accounts.index')->with('success', 'تم حذف الحساب بنجاح');
    }
}
