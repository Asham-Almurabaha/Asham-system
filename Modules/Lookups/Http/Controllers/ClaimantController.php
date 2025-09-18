<?php

namespace Modules\Lookups\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Lookups\Entities\Claimant;

class ClaimantController extends Controller
{
    public function index()
    {
        $claimants = Claimant::orderByDesc('id')->get();

        return view('lookups::claimants.index', compact('claimants'));
    }

    public function create()
    {
        return view('lookups::claimants.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:claimants,name',
        ]);

        Claimant::create($validated);

        return redirect()
            ->route('claimants.index')
            ->with('success', __('lookups::messages.claimants.created'));
    }

    public function edit(Claimant $claimant)
    {
        return view('lookups::claimants.edit', compact('claimant'));
    }

    public function update(Request $request, Claimant $claimant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:claimants,name,' . $claimant->id,
        ]);

        $claimant->update($validated);

        return redirect()
            ->route('claimants.index')
            ->with('success', __('lookups::messages.claimants.updated'));
    }

    public function destroy(Claimant $claimant)
    {
        $claimant->delete();

        return redirect()
            ->route('claimants.index')
            ->with('success', __('lookups::messages.claimants.deleted'));
    }
}
