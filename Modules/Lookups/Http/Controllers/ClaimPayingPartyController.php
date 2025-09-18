<?php

namespace Modules\Lookups\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Lookups\Entities\ClaimPayingParty;

class ClaimPayingPartyController extends Controller
{
    public function index()
    {
        $parties = ClaimPayingParty::orderBy('id')->get();

        return view('lookups::claim_paying_parties.index', compact('parties'));
    }

    public function create()
    {
        return view('lookups::claim_paying_parties.create');
    }

    public function store(Request $request)
    {
        $name = $this->normalizeName($request->input('name'));
        $request->merge(['name' => $name]);

        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('claim_paying_parties', 'name')],
        ], [], [
            'name' => __('lookups::messages.fields.name'),
        ]);

        ClaimPayingParty::create(['name' => $name]);

        return redirect()
            ->route('claim_paying_parties.index')
            ->with('success', __('lookups::messages.claim_paying_parties.created'));
    }

    public function edit(ClaimPayingParty $claim_paying_party)
    {
        if ($this->isProtected($claim_paying_party)) {
            return redirect()
                ->route('claim_paying_parties.index')
                ->withErrors(['general' => __('lookups::messages.claim_paying_parties.protected_edit')]);
        }

        return view('lookups::claim_paying_parties.edit', compact('claim_paying_party'));
    }

    public function update(Request $request, ClaimPayingParty $claim_paying_party)
    {
        if ($this->isProtected($claim_paying_party)) {
            return redirect()
                ->route('claim_paying_parties.index')
                ->withErrors(['general' => __('lookups::messages.claim_paying_parties.protected_edit')]);
        }

        $name = $this->normalizeName($request->input('name'));
        $request->merge(['name' => $name]);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('claim_paying_parties', 'name')->ignore($claim_paying_party->id),
            ],
        ], [], [
            'name' => __('lookups::messages.fields.name'),
        ]);

        $claim_paying_party->update(['name' => $name]);

        return redirect()
            ->route('claim_paying_parties.index')
            ->with('success', __('lookups::messages.claim_paying_parties.updated'));
    }

    public function destroy(ClaimPayingParty $claim_paying_party)
    {
        if ($this->isProtected($claim_paying_party)) {
            return redirect()
                ->route('claim_paying_parties.index')
                ->withErrors(['general' => __('lookups::messages.claim_paying_parties.protected_delete')]);
        }

        $claim_paying_party->delete();

        return redirect()
            ->route('claim_paying_parties.index')
            ->with('success', __('lookups::messages.claim_paying_parties.deleted'));
    }

    private function isProtected(ClaimPayingParty $party): bool
    {
        return (bool) $party->is_protected;
    }

    private function normalizeName(?string $name): string
    {
        $name = trim((string) $name);

        return preg_replace('/\s+/u', ' ', $name) ?: '';
    }
}
