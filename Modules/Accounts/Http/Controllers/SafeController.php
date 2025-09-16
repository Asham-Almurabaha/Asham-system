<?php

namespace Modules\Accounts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Accounts\Entities\Safe;

class SafeController extends Controller
{
    public function index()
    {
        $safes = Safe::orderBy('name')->get();

        return view('accounts::safes.index', compact('safes'));
    }

    public function create()
    {
        return view('accounts::safes.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateSafe($request);

        Safe::create($data);

        return redirect()
            ->route('accounts.safes.index')
            ->with('success', __('accounts::messages.safes.created'));
    }

    public function edit(Safe $safe)
    {
        return view('accounts::safes.edit', compact('safe'));
    }

    public function update(Request $request, Safe $safe)
    {
        $data = $this->validateSafe($request, $safe->id);

        $safe->update($data);

        return redirect()
            ->route('accounts.safes.index')
            ->with('success', __('accounts::messages.safes.updated'));
    }

    public function destroy(Safe $safe)
    {
        if ($safe->ledgerEntries()->exists()) {
            return redirect()
                ->route('accounts.safes.index')
                ->withErrors(['general' => __('accounts::messages.safes.delete_failed_in_use')]);
        }

        $safe->delete();

        return redirect()
            ->route('accounts.safes.index')
            ->with('success', __('accounts::messages.safes.deleted'));
    }

    private function validateSafe(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:190', Rule::unique('safes', 'name')->ignore($ignoreId)],
            'location'        => ['nullable', 'string', 'max:190'],
            'opening_balance' => ['nullable', 'numeric'],
            'currency_code'   => ['nullable', 'string', 'size:3'],
            'is_active'       => ['nullable', 'boolean'],
            'notes'           => ['nullable', 'string'],
        ], [], [
            'name'            => __('accounts::accounts.safes.fields.name'),
            'location'        => __('accounts::accounts.safes.fields.location'),
            'opening_balance' => __('accounts::accounts.safes.fields.opening_balance'),
            'currency_code'   => __('accounts::accounts.safes.fields.currency_code'),
            'is_active'       => __('accounts::accounts.safes.fields.is_active'),
            'notes'           => __('accounts::accounts.safes.fields.notes'),
        ]);

        return [
            'name'            => $this->cleanString($validated['name'] ?? ''),
            'location'        => $this->cleanString($validated['location'] ?? null),
            'opening_balance' => $this->normalizeAmount($validated['opening_balance'] ?? null),
            'currency_code'   => $this->normalizeCurrency($validated['currency_code'] ?? null),
            'is_active'       => $request->boolean('is_active', true),
            'notes'           => $this->cleanString($validated['notes'] ?? null),
        ];
    }

    private function cleanString(?string $value): ?string
    {
        $value = is_null($value) ? null : trim($value);

        return $value === '' ? null : $value;
    }

    private function normalizeAmount($value): float
    {
        $numeric = is_null($value) || $value === '' ? 0 : (float) $value;

        return round($numeric, 2);
    }

    private function normalizeCurrency(?string $value): string
    {
        $value = $this->cleanString($value);

        return $value ? mb_strtoupper($value) : 'SAR';
    }
}
