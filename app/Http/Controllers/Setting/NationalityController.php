<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Nationality;
use Illuminate\Http\Request;

class NationalityController extends Controller
{
    // عرض جميع الجنسيات
    public function index()
    {
        $nationalities = Nationality::orderBy('name')->get();
        return view('nationalities.index', compact('nationalities'));
    }

    // عرض نموذج إنشاء جنسية جديدة
    public function create()
    {
        return view('nationalities.create');
    }

    // تخزين جنسية جديدة
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:nationalities,name|max:255',
        ]);

        Nationality::create($request->only('name'));

        return redirect()->route('nationalities.index')->with('success', 'تمت إضافة الجنسية بنجاح');
    }

    // عرض نموذج تعديل جنسية
    public function edit($id)
    {
        $nationality = Nationality::findOrFail($id);
        return view('nationalities.edit', compact('nationality'));
    }

    // تحديث الجنسية
    public function update(Request $request, $id)
    {
        $nationality = Nationality::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:nationalities,name,' . $nationality->id,
        ]);

        $nationality->update($request->only('name'));

        return redirect()->route('nationalities.index')->with('success', 'تم تحديث الجنسية بنجاح');
    }

    // حذف الجنسية
    public function destroy($id)
    {
        $nationality = Nationality::findOrFail($id);
        $nationality->delete();

        return redirect()->route('nationalities.index')->with('success', 'تم حذف الجنسية بنجاح');
    }
}
