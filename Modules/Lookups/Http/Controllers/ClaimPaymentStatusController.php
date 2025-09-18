<?php

namespace Modules\Lookups\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Lookups\Entities\ClaimPaymentStatus;

class ClaimPaymentStatusController extends Controller
{
    public function index()
    {
        $statuses = ClaimPaymentStatus::orderBy('id')->get();

        return view('lookups::claim_payment_statuses.index', compact('statuses'));
    }

    public function create()
    {
        return view('lookups::claim_payment_statuses.create');
    }

    public function store(Request $request)
    {
        $name = $this->normalizeName($request->input('name'));
        $request->merge(['name' => $name]);

        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('claim_payment_statuses', 'name')],
        ], [], [
            'name' => __('lookups::messages.fields.name'),
        ]);

        ClaimPaymentStatus::create(['name' => $name]);

        return redirect()
            ->route('claim_payment_statuses.index')
            ->with('success', __('lookups::messages.claim_payment_statuses.created'));
    }

    public function edit(ClaimPaymentStatus $claim_payment_status)
    {
        if ($this->isProtected($claim_payment_status)) {
            return redirect()
                ->route('claim_payment_statuses.index')
                ->withErrors(['general' => __('lookups::messages.claim_payment_statuses.protected_edit')]);
        }

        return view('lookups::claim_payment_statuses.edit', compact('claim_payment_status'));
    }

    public function update(Request $request, ClaimPaymentStatus $claim_payment_status)
    {
        if ($this->isProtected($claim_payment_status)) {
            return redirect()
                ->route('claim_payment_statuses.index')
                ->withErrors(['general' => __('lookups::messages.claim_payment_statuses.protected_edit')]);
        }

        $name = $this->normalizeName($request->input('name'));
        $request->merge(['name' => $name]);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('claim_payment_statuses', 'name')->ignore($claim_payment_status->id),
            ],
        ], [], [
            'name' => __('lookups::messages.fields.name'),
        ]);

        $claim_payment_status->update(['name' => $name]);

        return redirect()
            ->route('claim_payment_statuses.index')
            ->with('success', __('lookups::messages.claim_payment_statuses.updated'));
    }

    public function destroy(ClaimPaymentStatus $claim_payment_status)
    {
        if ($this->isProtected($claim_payment_status)) {
            return redirect()
                ->route('claim_payment_statuses.index')
                ->withErrors(['general' => __('lookups::messages.claim_payment_statuses.protected_delete')]);
        }

        $claim_payment_status->delete();

        return redirect()
            ->route('claim_payment_statuses.index')
            ->with('success', __('lookups::messages.claim_payment_statuses.deleted'));
    }

    private function isProtected(ClaimPaymentStatus $status): bool
    {
        return (bool) $status->is_protected;
    }

    private function normalizeName(?string $name): string
    {
        $name = trim((string) $name);

        return preg_replace('/\s+/u', ' ', $name) ?: '';
    }
}
