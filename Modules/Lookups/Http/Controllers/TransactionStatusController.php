<?php

namespace Modules\Lookups\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Lookups\Entities\TransactionStatus;
use Modules\Lookups\Entities\TransactionType;

class TransactionStatusController extends Controller
{
    public function index()
    {
        $statuses = TransactionStatus::with('transactionType')->get();

        return view('lookups::transaction_statuses.index', compact('statuses'));
    }

    public function create()
    {
        $types = TransactionType::all();

        return view('lookups::transaction_statuses.create', compact('types'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'transaction_type_id' => 'required|exists:transaction_types,id',
        ]);

        TransactionStatus::create($request->only('name', 'transaction_type_id'));

        return redirect()
            ->route('transaction_statuses.index')
            ->with('success', __('lookups::messages.transaction_statuses.created'));
    }

    public function edit(TransactionStatus $transactionStatus)
    {
        $types = TransactionType::all();

        return view('lookups::transaction_statuses.edit', compact('transactionStatus', 'types'));
    }

    public function update(Request $request, TransactionStatus $transactionStatus)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'transaction_type_id' => 'required|exists:transaction_types,id',
        ]);

        $transactionStatus->update($request->only('name', 'transaction_type_id'));

        return redirect()
            ->route('transaction_statuses.index')
            ->with('success', __('lookups::messages.transaction_statuses.updated'));
    }

    public function destroy(TransactionStatus $transactionStatus)
    {
        $transactionStatus->delete();

        return redirect()
            ->route('transaction_statuses.index')
            ->with('success', __('lookups::messages.transaction_statuses.deleted'));
    }
}
