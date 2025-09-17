<?php

namespace Modules\Lookups\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Lookups\Entities\GuarantorStatus;

class GuarantorStatusController extends Controller
{
    public function index()
    {
        $statuses = GuarantorStatus::orderBy('id')->get();

        return view('lookups::guarantor_statuses.index', compact('statuses'));
    }

    public function create()
    {
        return view('lookups::guarantor_statuses.create');
    }

    public function store(Request $request)
    {
        $name = $this->normalizeName($request->input('name'));
        $request->merge(['name' => $name]);

        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('guarantor_statuses', 'name')],
        ], [], [
            'name' => __('lookups::messages.fields.name'),
        ]);

        GuarantorStatus::create(['name' => $name]);

        return redirect()
            ->route('guarantor_statuses.index')
            ->with('success', __('lookups::messages.guarantor_statuses.created'));
    }

    public function edit(GuarantorStatus $guarantor_status)
    {
        if ($this->isProtected($guarantor_status)) {
            return redirect()
                ->route('guarantor_statuses.index')
                ->withErrors(['general' => __('lookups::messages.guarantor_statuses.protected_edit')]);
        }

        return view('lookups::guarantor_statuses.edit', compact('guarantor_status'));
    }

    public function update(Request $request, GuarantorStatus $guarantor_status)
    {
        if ($this->isProtected($guarantor_status)) {
            return redirect()
                ->route('guarantor_statuses.index')
                ->withErrors(['general' => __('lookups::messages.guarantor_statuses.protected_edit')]);
        }

        $name = $this->normalizeName($request->input('name'));
        $request->merge(['name' => $name]);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('guarantor_statuses', 'name')->ignore($guarantor_status->id),
            ],
        ], [], [
            'name' => __('lookups::messages.fields.name'),
        ]);

        $guarantor_status->update(['name' => $name]);

        return redirect()
            ->route('guarantor_statuses.index')
            ->with('success', __('lookups::messages.guarantor_statuses.updated'));
    }

    public function destroy(GuarantorStatus $guarantor_status)
    {
        if ($this->isProtected($guarantor_status)) {
            return redirect()
                ->route('guarantor_statuses.index')
                ->withErrors(['general' => __('lookups::messages.guarantor_statuses.protected_delete')]);
        }

        $guarantor_status->delete();

        return redirect()
            ->route('guarantor_statuses.index')
            ->with('success', __('lookups::messages.guarantor_statuses.deleted'));
    }

    private function isProtected(GuarantorStatus $status): bool
    {
        return (bool) $status->is_protected;
    }

    private function normalizeName(?string $name): string
    {
        $name = trim((string) $name);

        return preg_replace('/\s+/u', ' ', $name) ?: '';
    }
}
