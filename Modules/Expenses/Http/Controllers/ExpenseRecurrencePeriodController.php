<?php

namespace Modules\Expenses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Lookups\Entities\ExpenseRecurrencePeriod;

class ExpenseRecurrencePeriodController extends Controller
{
    public function index(): View
    {
        $recurrencePeriods = ExpenseRecurrencePeriod::query()
            ->withCount('expenseTypes')
            ->orderBy('name')
            ->get();

        return view('expenses::recurrence_periods.index', compact('recurrencePeriods'));
    }

    public function create(): View
    {
        return view('expenses::recurrence_periods.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRecurrencePeriod($request);

        ExpenseRecurrencePeriod::create(array_merge($data, [
            'is_protected' => false,
        ]));

        return redirect()
            ->route('expenses.recurrence-periods.index')
            ->with('success', __('expenses::messages.recurrence_periods.created'));
    }

    public function edit(ExpenseRecurrencePeriod $recurrence_period): View
    {
        return view('expenses::recurrence_periods.edit', [
            'recurrencePeriod' => $recurrence_period,
        ]);
    }

    public function update(Request $request, ExpenseRecurrencePeriod $recurrence_period): RedirectResponse
    {
        if ($this->isProtected($recurrence_period)) {
            return redirect()
                ->route('expenses.recurrence-periods.index')
                ->withErrors(['general' => __('expenses::messages.recurrence_periods.protected_edit')]);
        }

        $data = $this->validateRecurrencePeriod($request, $recurrence_period->id);

        $recurrence_period->update($data);

        return redirect()
            ->route('expenses.recurrence-periods.index')
            ->with('success', __('expenses::messages.recurrence_periods.updated'));
    }

    public function destroy(ExpenseRecurrencePeriod $recurrence_period): RedirectResponse
    {
        if ($this->isProtected($recurrence_period)) {
            return redirect()
                ->route('expenses.recurrence-periods.index')
                ->withErrors(['general' => __('expenses::messages.recurrence_periods.protected_delete')]);
        }

        if ($recurrence_period->expenseTypes()->exists()) {
            return redirect()
                ->route('expenses.recurrence-periods.index')
                ->withErrors(['general' => __('expenses::messages.recurrence_periods.delete_blocked')]);
        }

        $recurrence_period->delete();

        return redirect()
            ->route('expenses.recurrence-periods.index')
            ->with('success', __('expenses::messages.recurrence_periods.deleted'));
    }

    private function validateRecurrencePeriod(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:190', Rule::unique('expense_recurrence_periods', 'code')->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:190', Rule::unique('expense_recurrence_periods', 'name')->ignore($ignoreId)],
            'description' => ['nullable', 'string'],
        ], [], [
            'code' => __('expenses::recurrence_periods.fields.code'),
            'name' => __('expenses::recurrence_periods.fields.name'),
            'description' => __('expenses::recurrence_periods.fields.description'),
        ]);

        return [
            'code' => $this->cleanCode($validated['code'] ?? ''),
            'name' => $this->cleanString($validated['name'] ?? ''),
            'description' => $this->cleanText($validated['description'] ?? null),
        ];
    }

    private function isProtected(ExpenseRecurrencePeriod $recurrencePeriod): bool
    {
        return (bool) $recurrencePeriod->is_protected;
    }

    private function cleanString(?string $value): string
    {
        $value = is_null($value) ? '' : trim($value);

        return $value;
    }

    private function cleanText(?string $value): ?string
    {
        $value = $this->cleanString($value);

        return $value === '' ? null : $value;
    }

    private function cleanCode(?string $value): string
    {
        $value = $this->cleanString($value);

        return mb_strtolower(str_replace(' ', '_', $value));
    }
}
