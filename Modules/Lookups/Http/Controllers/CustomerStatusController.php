<?php

namespace Modules\Lookups\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Lookups\Entities\CustomerStatus;

class CustomerStatusController extends Controller
{
    public function index()
    {
        $statuses = CustomerStatus::orderBy('id')->get();

        return view('lookups::customer_statuses.index', compact('statuses'));
    }

    public function create()
    {
        return view('lookups::customer_statuses.create');
    }

    public function store(Request $request)
    {
        $name = $this->normalizeName($request->input('name'));
        $request->merge(['name' => $name]);

        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('customer_statuses', 'name')],
        ], [], [
            'name' => __('lookups::messages.fields.name'),
        ]);

        CustomerStatus::create(['name' => $name]);

        return redirect()
            ->route('customer_statuses.index')
            ->with('success', __('lookups::messages.customer_statuses.created'));
    }

    public function edit(CustomerStatus $customer_status)
    {
        if ($this->isProtected($customer_status)) {
            return redirect()
                ->route('customer_statuses.index')
                ->withErrors(['general' => __('lookups::messages.customer_statuses.protected_edit')]);
        }

        return view('lookups::customer_statuses.edit', compact('customer_status'));
    }

    public function update(Request $request, CustomerStatus $customer_status)
    {
        if ($this->isProtected($customer_status)) {
            return redirect()
                ->route('customer_statuses.index')
                ->withErrors(['general' => __('lookups::messages.customer_statuses.protected_edit')]);
        }

        $name = $this->normalizeName($request->input('name'));
        $request->merge(['name' => $name]);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('customer_statuses', 'name')->ignore($customer_status->id),
            ],
        ], [], [
            'name' => __('lookups::messages.fields.name'),
        ]);

        $customer_status->update(['name' => $name]);

        return redirect()
            ->route('customer_statuses.index')
            ->with('success', __('lookups::messages.customer_statuses.updated'));
    }

    public function destroy(CustomerStatus $customer_status)
    {
        if ($this->isProtected($customer_status)) {
            return redirect()
                ->route('customer_statuses.index')
                ->withErrors(['general' => __('lookups::messages.customer_statuses.protected_delete')]);
        }

        $customer_status->delete();

        return redirect()
            ->route('customer_statuses.index')
            ->with('success', __('lookups::messages.customer_statuses.deleted'));
    }

    private function isProtected(CustomerStatus $status): bool
    {
        return (bool) $status->is_protected;
    }

    private function normalizeName(?string $name): string
    {
        $name = trim((string) $name);

        return preg_replace('/\s+/u', ' ', $name) ?: '';
    }
}
