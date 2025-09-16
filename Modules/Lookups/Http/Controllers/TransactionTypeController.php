<?php

namespace Modules\Lookups\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Lookups\Entities\TransactionType;

class TransactionTypeController extends Controller
{
    public function index()
    {
        $types = TransactionType::all();

        return view('lookups::transaction_types.index', compact('types'));
    }

    public function create()
    {
        return view('lookups::transaction_types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:transaction_types,name',
            'description' => 'nullable|string',
        ]);

        TransactionType::create($request->only('name', 'description'));

        return redirect()
            ->route('transaction_types.index')
            ->with('success', __('lookups::messages.transaction_types.created'));
    }

    public function edit(TransactionType $transactionType)
    {
        return view('lookups::transaction_types.edit', compact('transactionType'));
    }

    public function update(Request $request, TransactionType $transactionType)
    {
        $request->validate([
            'name' => 'required|string|unique:transaction_types,name,' . $transactionType->id,
            'description' => 'nullable|string',
        ]);

        $transactionType->update($request->only('name', 'description'));

        return redirect()
            ->route('transaction_types.index')
            ->with('success', __('lookups::messages.transaction_types.updated'));
    }

    public function destroy(TransactionType $transactionType)
    {
        $transactionType->delete();

        return redirect()
            ->route('transaction_types.index')
            ->with('success', __('lookups::messages.transaction_types.deleted'));
    }
}
