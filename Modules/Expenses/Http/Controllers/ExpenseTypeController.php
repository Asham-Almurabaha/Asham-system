<?php

namespace Modules\Expenses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Expenses\Entities\ExpenseType;

class ExpenseTypeController extends Controller
{
    public function index(): View
    {
        $types = ExpenseType::query()->orderBy('name')->get();

        return view('expenses::expense_types.index', compact('types'));
    }

    public function create(): View
    {
        return view('expenses::expense_types.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateType($request);

        ExpenseType::create($data);

        return redirect()
            ->route('expenses.expense-types.index')
            ->with('success', __('expenses::messages.types.created'));
    }

    public function edit(ExpenseType $expense_type): View
    {
        return view('expenses::expense_types.edit', [
            'expenseType' => $expense_type,
        ]);
    }

    public function update(Request $request, ExpenseType $expense_type): RedirectResponse
    {
        $data = $this->validateType($request, $expense_type->id);

        $expense_type->update($data);

        return redirect()
            ->route('expenses.expense-types.index')
            ->with('success', __('expenses::messages.types.updated'));
    }

    public function destroy(ExpenseType $expense_type): RedirectResponse
    {
        if ($expense_type->expenses()->exists()) {
            return redirect()
                ->route('expenses.expense-types.index')
                ->withErrors(['general' => __('expenses::messages.types.delete_blocked')]);
        }

        $expense_type->delete();

        return redirect()
            ->route('expenses.expense-types.index')
            ->with('success', __('expenses::messages.types.deleted'));
    }

    private function validateType(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190', Rule::unique('expense_types', 'name')->ignore($ignoreId)],
            'description' => ['nullable', 'string'],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence_interval' => ['nullable', 'string', 'max:50'],
        ], [], [
            'name' => __('expenses::types.fields.name'),
            'description' => __('expenses::types.fields.description'),
            'default_amount' => __('expenses::types.fields.default_amount'),
            'currency_code' => __('expenses::types.fields.currency_code'),
            'is_recurring' => __('expenses::types.fields.is_recurring'),
            'recurrence_interval' => __('expenses::types.fields.recurrence_interval'),
        ]);

        return [
            'name' => $this->cleanString($validated['name'] ?? ''),
            'description' => $this->cleanText($validated['description'] ?? null),
            'default_amount' => $this->normalizeAmount($validated['default_amount'] ?? 0),
            'currency_code' => $this->normalizeCurrency($validated['currency_code'] ?? null),
            'is_recurring' => $request->boolean('is_recurring'),
            'recurrence_interval' => $this->cleanString($validated['recurrence_interval'] ?? null),
        ];
    }

    private function cleanString(?string $value): ?string
    {
        $value = is_null($value) ? null : trim($value);

        return $value === '' ? null : $value;
    }

    private function cleanText(?string $value): ?string
    {
        return $this->cleanString($value);
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
