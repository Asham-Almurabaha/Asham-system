<?php

namespace Modules\Lookups\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Lookups\Entities\ClaimStatus;

class ClaimStatusController extends Controller
{
    public function index()
    {
        $statuses = ClaimStatus::orderBy('id')->get();

        return view('lookups::claim_statuses.index', compact('statuses'));
    }

    public function create()
    {
        return view('lookups::claim_statuses.create');
    }

    public function store(Request $request)
    {
        $name = $this->normalizeName($request->input('name'));
        $request->merge(['name' => $name]);

        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('claim_statuses', 'name')],
        ], [], [
            'name' => __('lookups::messages.fields.name'),
        ]);

        ClaimStatus::create(['name' => $name]);

        return redirect()
            ->route('claim_statuses.index')
            ->with('success', __('lookups::messages.claim_statuses.created'));
    }

    public function edit(ClaimStatus $claim_status)
    {
        if ($this->isProtected($claim_status)) {
            return redirect()
                ->route('claim_statuses.index')
                ->withErrors(['general' => __('lookups::messages.claim_statuses.protected_edit')]);
        }

        return view('lookups::claim_statuses.edit', compact('claim_status'));
    }

    public function update(Request $request, ClaimStatus $claim_status)
    {
        if ($this->isProtected($claim_status)) {
            return redirect()
                ->route('claim_statuses.index')
                ->withErrors(['general' => __('lookups::messages.claim_statuses.protected_edit')]);
        }

        $name = $this->normalizeName($request->input('name'));
        $request->merge(['name' => $name]);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('claim_statuses', 'name')->ignore($claim_status->id),
            ],
        ], [], [
            'name' => __('lookups::messages.fields.name'),
        ]);

        $claim_status->update(['name' => $name]);

        return redirect()
            ->route('claim_statuses.index')
            ->with('success', __('lookups::messages.claim_statuses.updated'));
    }

    public function destroy(ClaimStatus $claim_status)
    {
        if ($this->isProtected($claim_status)) {
            return redirect()
                ->route('claim_statuses.index')
                ->withErrors(['general' => __('lookups::messages.claim_statuses.protected_delete')]);
        }

        $claim_status->delete();

        return redirect()
            ->route('claim_statuses.index')
            ->with('success', __('lookups::messages.claim_statuses.deleted'));
    }

    private function isProtected(ClaimStatus $status): bool
    {
        return (bool) $status->is_protected;
    }

    private function normalizeName(?string $name): string
    {
        $name = trim((string) $name);

        return preg_replace('/\s+/u', ' ', $name) ?: '';
    }
}
