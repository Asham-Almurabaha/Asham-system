<?php

namespace Modules\Lookups\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Lookups\Entities\ClaimFirstParty;

class ClaimFirstPartyController extends Controller
{
    public function index()
    {
        $claimFirstParties = ClaimFirstParty::orderByDesc('id')->get();

        return view('lookups::claim_first_parties.index', compact('claimFirstParties'));
    }

    public function create()
    {
        return view('lookups::claim_first_parties.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:claim_first_parties,name',
        ]);

        ClaimFirstParty::create($validated);

        return redirect()
            ->route('claim_first_parties.index')
            ->with('success', __('lookups::messages.claim_first_parties.created'));
    }

    public function edit(ClaimFirstParty $claimFirstParty)
    {
        return view('lookups::claim_first_parties.edit', compact('claimFirstParty'));
    }

    public function update(Request $request, ClaimFirstParty $claimFirstParty)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:claim_first_parties,name,' . $claimFirstParty->id,
        ]);

        $claimFirstParty->update($validated);

        return redirect()
            ->route('claim_first_parties.index')
            ->with('success', __('lookups::messages.claim_first_parties.updated'));
    }

    public function destroy(ClaimFirstParty $claimFirstParty)
    {
        $claimFirstParty->delete();

        return redirect()
            ->route('claim_first_parties.index')
            ->with('success', __('lookups::messages.claim_first_parties.deleted'));
    }
}
