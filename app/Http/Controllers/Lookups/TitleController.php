<?php

namespace App\Http\Controllers\Lookups;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lookups\Title;

class TitleController extends Controller
{
    public function index()
    {
        $titles = Title::orderByDesc('id')->get();

        return view('lookups.titles.index', compact('titles'));
    }

    public function create()
    {
        return view('lookups.titles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:titles,name',
        ]);

        Title::create(['name' => $request->name]);

        return redirect()->route('titles.index')->with('success', 'تم إضافة العنوان بنجاح.');
    }

    public function edit(Title $title)
    {
        return view('lookups.titles.edit', compact('title'));
    }

    public function update(Request $request, Title $title)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:titles,name,' . $title->id,
        ]);

        $title->update(['name' => $request->name]);

        return redirect()->route('titles.index')->with('success', 'تم تحديث العنوان بنجاح.');
    }

    public function destroy(Title $title)
    {
        $title->delete();

        return redirect()->route('titles.index')->with('success', 'تم حذف العنوان بنجاح.');
    }
}
