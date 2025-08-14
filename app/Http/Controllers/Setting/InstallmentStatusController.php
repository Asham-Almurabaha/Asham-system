<?php

namespace App\Http\Controllers\Setting;
use App\Http\Controllers\Controller;
use App\Models\InstallmentStatus;
use Illuminate\Http\Request;

class InstallmentStatusController extends Controller
{
    // عرض كل حالات الأقساط
    public function index()
    {
        $statuses = InstallmentStatus::orderBy('name')->get();
        return view('installment_statuses.index', compact('statuses'));
    }

    // نموذج إنشاء حالة جديدة
    public function create()
    {
        return view('installment_statuses.create');
    }

    // تخزين حالة جديدة
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:installment_statuses,name|max:255',
        ]);

        InstallmentStatus::create(['name' => $request->name]);

        return redirect()->route('installment_statuses.index')->with('success', 'تم إضافة حالة القسط بنجاح.');
    }

    // نموذج تعديل حالة موجودة
    public function edit(InstallmentStatus $installmentStatus)
    {
        return view('installment_statuses.edit', compact('installmentStatus'));
    }

    // تحديث الحالة
    public function update(Request $request, InstallmentStatus $installmentStatus)
    {
        $request->validate([
            'name' => 'required|unique:installment_statuses,name,' . $installmentStatus->id . '|max:255',
        ]);

        $installmentStatus->update(['name' => $request->name]);

        return redirect()->route('installment_statuses.index')->with('success', 'تم تحديث حالة القسط بنجاح.');
    }

    // حذف الحالة
    public function destroy(InstallmentStatus $installmentStatus)
    {
        $installmentStatus->delete();

        return redirect()->route('installment_statuses.index')->with('success', 'تم حذف حالة القسط بنجاح.');
    }

    
}