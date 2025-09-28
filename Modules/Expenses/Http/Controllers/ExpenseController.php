<?php

namespace Modules\Expenses\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Expenses\Entities\Expense;
use Modules\Lookups\Entities\ExpenseType;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $today = Carbon::today();

        $expenses = Expense::query()
            ->with('type')
            ->withCount('payments')
            ->withSum('payments as payments_total', 'amount')
            ->orderBy('due_date')
            ->orderBy('id')
            ->paginate(20);

        $stats = [
            'total' => Expense::count(),
            'upcoming' => Expense::query()->whereDate('due_date', '>=', $today)->count(),
            'overdue' => Expense::query()->whereDate('due_date', '<', $today)->count(),
        ];

        return view('expenses::expenses.index', [
            'expenses' => $expenses,
            'stats' => $stats,
        ]);
    }

    public function create(): View
    {
        return view('expenses::expenses.create', [
            'types' => $this->typeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateExpense($request);

        Expense::create($data);

        return redirect()
            ->route('expenses.expenses.index')
            ->with('success', __('expenses::messages.expenses.created'));
    }

    public function edit(Expense $expense): View
    {
        return view('expenses::expenses.edit', [
            'expense' => $expense,
            'types' => $this->typeOptions(),
        ]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $data = $this->validateExpense($request);

        $expense->update($data);

        return redirect()
            ->route('expenses.expenses.index')
            ->with('success', __('expenses::messages.expenses.updated'));
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()
            ->route('expenses.expenses.index')
            ->with('success', __('expenses::messages.expenses.deleted'));
    }

    protected function validateExpense(Request $request): array
    {
        $validated = $request->validate([
            'expense_type_id' => ['required', Rule::exists('expense_types', 'id')],
            'title' => ['required', 'string', 'max:190'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ], [], [
            'expense_type_id' => __('expenses::expenses.fields.expense_type_id'),
            'title' => __('expenses::expenses.fields.title'),
            'amount' => __('expenses::expenses.fields.amount'),
            'due_date' => __('expenses::expenses.fields.due_date'),
            'notes' => __('expenses::expenses.fields.notes'),
        ]);

        return [
            'expense_type_id' => (int) $validated['expense_type_id'],
            'title' => $this->cleanString($validated['title'] ?? ''),
            'amount' => $this->normalizeAmount($validated['amount'] ?? 0),
            'due_date' => $validated['due_date'],
            'notes' => $this->cleanText($validated['notes'] ?? null),
        ];
    }

    private function typeOptions()
    {
        return ExpenseType::query()->orderBy('name')->pluck('name', 'id');
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

}
