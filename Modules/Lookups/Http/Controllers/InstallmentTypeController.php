<?php

namespace Modules\Lookups\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Lookups\Entities\InstallmentType;

class InstallmentTypeController extends Controller
{
    public function index()
    {
        $types = InstallmentType::orderBy('name')->get();

        return view('lookups::installment_types.index', compact('types'));
    }

    public function create()
    {
        return view('lookups::installment_types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:installment_types,name|max:255',
        ]);

        InstallmentType::create(['name' => $request->name]);

        return redirect()
            ->route('installment_types.index')
            ->with('success', __('lookups::messages.installment_types.created'));
    }

    public function edit(InstallmentType $installmentType)
    {
        return view('lookups::installment_types.edit', compact('installmentType'));
    }

    public function update(Request $request, InstallmentType $installmentType)
    {
        $request->validate([
            'name' => 'required|unique:installment_types,name,' . $installmentType->id . '|max:255',
        ]);

        $installmentType->update(['name' => $request->name]);

        return redirect()
            ->route('installment_types.index')
            ->with('success', __('lookups::messages.installment_types.updated'));
    }

    public function destroy(InstallmentType $installmentType)
    {
        $installmentType->delete();

        return redirect()
            ->route('installment_types.index')
            ->with('success', __('lookups::messages.installment_types.deleted'));
    }
}
