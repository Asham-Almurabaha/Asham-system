<?php

namespace Modules\Lookups\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Lookups\Entities\Nationality;

class NationalityController extends Controller
{
    public function index()
    {
        $nationalities = Nationality::orderBy('name')->get();

        return view('lookups::nationalities.index', compact('nationalities'));
    }

    public function create()
    {
        return view('lookups::nationalities.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:nationalities,name|max:255',
        ]);

        Nationality::create($request->only('name'));

        return redirect()->route('nationalities.index')->with('success', 'تمت إضافة الجنسية بنجاح');
    }

    public function edit($id)
    {
        $nationality = Nationality::findOrFail($id);

        return view('lookups::nationalities.edit', compact('nationality'));
    }

    public function update(Request $request, $id)
    {
        $nationality = Nationality::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:nationalities,name,' . $nationality->id,
        ]);

        $nationality->update($request->only('name'));

        return redirect()->route('nationalities.index')->with('success', 'تم تحديث الجنسية بنجاح');
    }

    public function destroy($id)
    {
        $nationality = Nationality::findOrFail($id);
        $nationality->delete();

        return redirect()->route('nationalities.index')->with('success', 'تم حذف الجنسية بنجاح');
    }
}
