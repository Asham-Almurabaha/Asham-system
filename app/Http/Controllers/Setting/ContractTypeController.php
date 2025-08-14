<?php

namespace App\Http\Controllers\Setting;
use App\Http\Controllers\Controller;

use App\Models\ContractType;
use Illuminate\Http\Request;

class ContractTypeController extends Controller
{
    // عرض قائمة أنواع العقود
    public function index()
    {
        $contractTypes = ContractType::orderBy('name')->get();
        return view('contract_types.index', compact('contractTypes'));
    }

    // عرض نموذج إنشاء نوع عقد جديد
    public function create()
    {
        return view('contract_types.create');
    }

    // تخزين نوع عقد جديد
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:contract_types,name|max:255',
        ]);

        ContractType::create([
            'name' => $request->name,
        ]);

        return redirect()->route('contract_types.index')->with('success', 'تم إضافة نوع العقد بنجاح.');
    }

    // عرض نموذج تعديل نوع عقد موجود
    public function edit(ContractType $contractType)
    {
        return view('contract_types.edit', compact('contractType'));
    }

    // تحديث نوع العقد
    public function update(Request $request, ContractType $contractType)
    {
        $request->validate([
            'name' => 'required|unique:contract_types,name,' . $contractType->id . '|max:255',
        ]);

        $contractType->update([
            'name' => $request->name,
        ]);

        return redirect()->route('contract_types.index')->with('success', 'تم تحديث نوع العقد بنجاح.');
    }

    // حذف نوع العقد
    public function destroy(ContractType $contractType)
    {
        $contractType->delete();

        return redirect()->route('contract_types.index')->with('success', 'تم حذف نوع العقد بنجاح.');
    }
}