<?php

namespace App\Http\Controllers;

use App\Models\InvestorTransaction;
use App\Models\Investor;
use App\Models\TransactionStatus;
use Illuminate\Http\Request;

class InvestorTransactionController extends Controller
{
    public function index()
    {
        $transactions = InvestorTransaction::with(['investor', 'status'])
            ->latest()
            ->paginate(15);

        return view('investor_transactions.index', compact('transactions'));
    }

    public function create()
    {
        $investors = Investor::all();
        $statuses  = TransactionStatus::all();

        return view('investor_transactions.create', compact('investors', 'statuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'investor_id'      => ['required', 'exists:investors,id'],
            'status_id'        => ['required', 'exists:transaction_statuses,id'],
            'amount'           => ['required', 'numeric'],
            'transaction_date' => ['required', 'date'],
            'notes'            => ['nullable', 'string'],
        ]);

        InvestorTransaction::create($validated);

        return redirect()->route('investor-transactions.index')
            ->with('success', 'تمت إضافة العملية بنجاح');
    }
}