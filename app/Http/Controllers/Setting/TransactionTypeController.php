<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\TransactionType;
use Illuminate\Http\Request;

class TransactionTypeController extends Controller
{
    public function index()
    {
        $types = TransactionType::all();
        return view('transaction_types.index', compact('types'));
    }

    public function create()
    {
        return view('transaction_types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:transaction_types,name',
            'description' => 'nullable|string',
        ]);

        TransactionType::create($request->all());

        return redirect()->route('transaction_types.index')->with('success', 'تم إضافة نوع العملية بنجاح');
    }

    public function edit(TransactionType $transactionType)
    {
        return view('transaction_types.edit', compact('transactionType'));
    }

    public function update(Request $request, TransactionType $transactionType)
    {
        $request->validate([
            'name' => 'required|string|unique:transaction_types,name,' . $transactionType->id,
            'description' => 'nullable|string',
        ]);

        $transactionType->update($request->all());

        return redirect()->route('transaction_types.index')->with('success', 'تم تحديث نوع العملية بنجاح');
    }

    public function destroy(TransactionType $transactionType)
    {
        $transactionType->delete();

        return redirect()->route('transaction_types.index')->with('success', 'تم حذف نوع العملية بنجاح');
    }
}
