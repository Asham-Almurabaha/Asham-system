<?php

namespace App\Http\Controllers\Setting;
use App\Http\Controllers\Controller;

use App\Models\TransactionStatus;
use App\Models\TransactionType;
use Illuminate\Http\Request;

class TransactionStatusController extends Controller
{
    public function index()
    {
        // جلب الحالات مع نوع العملية المرتبطة
        $statuses = TransactionStatus::with('transactionType')->get();
        return view('transaction_statuses.index', compact('statuses'));
    }

    public function create()
    {
        // جلب أنواع العمليات لاختيارها في الفورم
        $types = TransactionType::all();
        return view('transaction_statuses.create', compact('types'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'transaction_type_id' => 'required|exists:transaction_types,id',
        ]);

        TransactionStatus::create($request->all());

        return redirect()->route('transaction_statuses.index')->with('success', 'تم إضافة الحالة بنجاح');
    }

    public function edit(TransactionStatus $transactionStatus)
    {
        $types = TransactionType::all();
        return view('transaction_statuses.edit', compact('transactionStatus', 'types'));
    }

    public function update(Request $request, TransactionStatus $transactionStatus)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'transaction_type_id' => 'required|exists:transaction_types,id',
        ]);

        $transactionStatus->update($request->all());

        return redirect()->route('transaction_statuses.index')->with('success', 'تم تحديث الحالة بنجاح');
    }

    public function destroy(TransactionStatus $transactionStatus)
    {
        $transactionStatus->delete();

        return redirect()->route('transaction_statuses.index')->with('success', 'تم حذف الحالة بنجاح');
    }
}