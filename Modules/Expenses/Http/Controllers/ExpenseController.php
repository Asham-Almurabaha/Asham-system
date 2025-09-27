<?php

namespace Modules\Expenses\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Expenses\Entities\Expense;
use Modules\Expenses\Entities\ExpenseType;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'upcoming');

        $query = Expense::query()->with('type');

        $today = Carbon::today();

        if ($status === 'overdue') {
            $query->whereNull('paid_at')->whereDate('due_date', '<', $today);
        } elseif ($status === 'paid') {
            $query->whereNotNull('paid_at');
        } else {
            $query->whereNull('paid_at')->whereDate('due_date', '>=', $today);
        }

        $expenses = $query->orderBy('due_date')->orderBy('id')->paginate(20)->withQueryString();

        $stats = [
            'total' => Expense::count(),
            'upcoming' => Expense::query()->whereNull('paid_at')->whereDate('due_date', '>=', $today)->count(),
            'overdue' => Expense::query()->whereNull('paid_at')->whereDate('due_date', '<', $today)->count(),
            'paid' => Expense::query()->whereNotNull('paid_at')->count(),
        ];

        return view('expenses::expenses.index', [
            'expenses' => $expenses,
            'status' => $status,
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
            'currency_code' => ['nullable', 'string', 'size:3'],
            'due_date' => ['required', 'date'],
            'paid_at' => ['nullable', 'date', 'after_or_equal:due_date'],
            'notes' => ['nullable', 'string'],
            'reference' => ['nullable', 'string', 'max:190'],
        ], [], [
            'expense_type_id' => __('expenses::expenses.fields.expense_type_id'),
            'title' => __('expenses::expenses.fields.title'),
            'amount' => __('expenses::expenses.fields.amount'),
            'currency_code' => __('expenses::expenses.fields.currency_code'),
            'due_date' => __('expenses::expenses.fields.due_date'),
            'paid_at' => __('expenses::expenses.fields.paid_at'),
            'notes' => __('expenses::expenses.fields.notes'),
            'reference' => __('expenses::expenses.fields.reference'),
        ]);

        return [
            'expense_type_id' => (int) $validated['expense_type_id'],
            'title' => $this->cleanString($validated['title'] ?? ''),
            'amount' => $this->normalizeAmount($validated['amount'] ?? 0),
            'currency_code' => $this->normalizeCurrency($validated['currency_code'] ?? null),
            'due_date' => $validated['due_date'],
            'paid_at' => $validated['paid_at'] ?? null,
            'notes' => $this->cleanText($validated['notes'] ?? null),
            'reference' => $this->cleanString($validated['reference'] ?? null),
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

    private function normalizeCurrency(?string $value): string
    {
        $value = $this->cleanString($value);

        return $value ? mb_strtoupper($value) : 'SAR';
    }
}
