<?php

namespace App\Http\Controllers\Setting;
use App\Http\Controllers\Controller;
use App\Models\InstallmentType;
use Illuminate\Http\Request;

class InstallmentTypeController extends Controller
{
    // عرض كل أنواع الأقساط
    public function index()
    {
        $types = InstallmentType::orderBy('name')->get();
        return view('installment_types.index', compact('types'));
    }

    // نموذج إنشاء نوع قسط جديد
    public function create()
    {
        return view('installment_types.create');
    }

    // تخزين نوع قسط جديد
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:installment_types,name|max:255',
        ]);

        InstallmentType::create(['name' => $request->name]);

        return redirect()->route('installment_types.index')->with('success', 'تم إضافة نوع القسط بنجاح.');
    }

    // نموذج تعديل نوع قسط موجود
    public function edit(InstallmentType $installmentType)
    {
        return view('installment_types.edit', compact('installmentType'));
    }

    // تحديث نوع القسط
    public function update(Request $request, InstallmentType $installmentType)
    {
        $request->validate([
            'name' => 'required|unique:installment_types,name,' . $installmentType->id . '|max:255',
        ]);

        $installmentType->update(['name' => $request->name]);

        return redirect()->route('installment_types.index')->with('success', 'تم تحديث نوع القسط بنجاح.');
    }

    // حذف نوع القسط
    public function destroy(InstallmentType $installmentType)
    {
        $installmentType->delete();

        return redirect()->route('installment_types.index')->with('success', 'تم حذف نوع القسط بنجاح.');
    }
}