<?php

namespace App\Http\Controllers\Lookups;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lookups\InstallmentStatus;

class InstallmentStatusController extends Controller
{
    public function index()
    {
        $statuses = InstallmentStatus::orderBy('name')->get();

        return view('lookups.installment_statuses.index', compact('statuses'));
    }

    public function create()
    {
        return view('lookups.installment_statuses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:installment_statuses,name|max:255',
        ]);

        InstallmentStatus::create(['name' => $request->name]);

        return redirect()->route('installment_statuses.index')->with('success', 'تم إضافة حالة القسط بنجاح.');
    }

    public function edit(InstallmentStatus $installmentStatus)
    {
        return view('lookups.installment_statuses.edit', compact('installmentStatus'));
    }

    public function update(Request $request, InstallmentStatus $installmentStatus)
    {
        $request->validate([
            'name' => 'required|unique:installment_statuses,name,' . $installmentStatus->id . '|max:255',
        ]);

        $installmentStatus->update(['name' => $request->name]);

        return redirect()->route('installment_statuses.index')->with('success', 'تم تحديث حالة القسط بنجاح.');
    }

    public function destroy(InstallmentStatus $installmentStatus)
    {
        $installmentStatus->delete();

        return redirect()->route('installment_statuses.index')->with('success', 'تم حذف حالة القسط بنجاح.');
    }
}
