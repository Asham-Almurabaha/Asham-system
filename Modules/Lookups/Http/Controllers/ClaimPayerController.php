<?php

namespace Modules\Lookups\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Lookups\Entities\ClaimPayer;

class ClaimPayerController extends Controller
{
    public function index()
    {
        $claimPayers = ClaimPayer::orderBy('id')->get();

        return view('lookups::claim_payers.index', compact('claimPayers'));
    }

    public function create()
    {
        return view('lookups::claim_payers.create');
    }

    public function store(Request $request)
    {
        $name = $this->normalizeName($request->input('name'));
        $request->merge(['name' => $name]);

        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('claim_payers', 'name')],
        ], [], [
            'name' => __('lookups::messages.fields.name'),
        ]);

        ClaimPayer::create(['name' => $name]);

        return redirect()
            ->route('claim_payers.index')
            ->with('success', __('lookups::messages.claim_payers.created'));
    }

    public function edit(ClaimPayer $claimPayer)
    {
        if ($this->isProtected($claimPayer)) {
            return redirect()
                ->route('claim_payers.index')
                ->withErrors(['general' => __('lookups::messages.claim_payers.protected_edit')]);
        }

        return view('lookups::claim_payers.edit', compact('claimPayer'));
    }

    public function update(Request $request, ClaimPayer $claimPayer)
    {
        if ($this->isProtected($claimPayer)) {
            return redirect()
                ->route('claim_payers.index')
                ->withErrors(['general' => __('lookups::messages.claim_payers.protected_edit')]);
        }

        $name = $this->normalizeName($request->input('name'));
        $request->merge(['name' => $name]);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('claim_payers', 'name')->ignore($claimPayer->id),
            ],
        ], [], [
            'name' => __('lookups::messages.fields.name'),
        ]);

        $claimPayer->update(['name' => $name]);

        return redirect()
            ->route('claim_payers.index')
            ->with('success', __('lookups::messages.claim_payers.updated'));
    }

    public function destroy(ClaimPayer $claimPayer)
    {
        if ($this->isProtected($claimPayer)) {
            return redirect()
                ->route('claim_payers.index')
                ->withErrors(['general' => __('lookups::messages.claim_payers.protected_delete')]);
        }

        $claimPayer->delete();

        return redirect()
            ->route('claim_payers.index')
            ->with('success', __('lookups::messages.claim_payers.deleted'));
    }

    private function isProtected(ClaimPayer $claimPayer): bool
    {
        return (bool) $claimPayer->is_protected;
    }

    private function normalizeName(?string $name): string
    {
        $name = trim((string) $name);

        return preg_replace('/\s+/u', ' ', $name) ?: '';
    }
}
