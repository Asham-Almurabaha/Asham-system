<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Title;
use Illuminate\Http\Request;

class TitleController extends Controller
{
    // عرض كل العناوين
    public function index()
    {
        $titles = Title::orderBy('id', 'desc')->get();
        return view('titles.index', compact('titles'));
    }

    // عرض صفحة إنشاء عنوان جديد
    public function create()
    {
        return view('titles.create');
    }

    // حفظ عنوان جديد
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:titles,name',
        ]);

        Title::create([
            'name' => $request->name,
        ]);

        return redirect()->route('titles.index')->with('success', 'تم إضافة العنوان بنجاح.');
    }

    // عرض صفحة تعديل عنوان
    public function edit(Title $title)
    {
        return view('titles.edit', compact('title'));
    }

    // تحديث عنوان
    public function update(Request $request, Title $title)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:titles,name,' . $title->id,
        ]);

        $title->update([
            'name' => $request->name,
        ]);

        return redirect()->route('titles.index')->with('success', 'تم تحديث العنوان بنجاح.');
    }

    // حذف عنوان
    public function destroy(Title $title)
    {
        $title->delete();

        return redirect()->route('titles.index')->with('success', 'تم حذف العنوان بنجاح.');
    }
}
