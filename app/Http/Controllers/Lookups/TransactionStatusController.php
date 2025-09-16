<?php

namespace App\Http\Controllers\Lookups;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lookups\TransactionStatus;
use App\Models\Lookups\TransactionType;

class TransactionStatusController extends Controller
{
    public function index()
    {
        $statuses = TransactionStatus::with('transactionType')->get();

        return view('lookups.transaction_statuses.index', compact('statuses'));
    }

    public function create()
    {
        $types = TransactionType::all();

        return view('lookups.transaction_statuses.create', compact('types'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'transaction_type_id' => 'required|exists:transaction_types,id',
        ]);

        TransactionStatus::create($request->only('name', 'transaction_type_id'));

        return redirect()->route('transaction_statuses.index')->with('success', 'تم إضافة الحالة بنجاح');
    }

    public function edit(TransactionStatus $transactionStatus)
    {
        $types = TransactionType::all();

        return view('lookups.transaction_statuses.edit', compact('transactionStatus', 'types'));
    }

    public function update(Request $request, TransactionStatus $transactionStatus)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'transaction_type_id' => 'required|exists:transaction_types,id',
        ]);

        $transactionStatus->update($request->only('name', 'transaction_type_id'));

        return redirect()->route('transaction_statuses.index')->with('success', 'تم تحديث الحالة بنجاح');
    }

    public function destroy(TransactionStatus $transactionStatus)
    {
        $transactionStatus->delete();

        return redirect()->route('transaction_statuses.index')->with('success', 'تم حذف الحالة بنجاح');
    }
}
