<?php

namespace Modules\Expenses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Accounts\Entities\BankAccount;
use Modules\Accounts\Entities\Safe;
use Modules\Expenses\Entities\Expense;
use Modules\Expenses\Entities\ExpensePayment;
use Modules\Expenses\Http\Requests\StoreExpensePaymentRequest;
use Modules\Ledger\Entities\LedgerEntry;
use Modules\Lookups\Entities\TransactionStatus;

class ExpensePaymentController extends Controller
{
    protected ?TransactionStatus $ledgerStatus = null;

    public function create(Expense $expense): View
    {
        $expense->load([
            'type',
            'payments' => fn ($query) => $query
                ->with(['bankAccount', 'safe'])
                ->latest('paid_at')
                ->latest('id'),
        ]);

        $banks = BankAccount::query()->orderBy('name')->get(['id', 'name']);
        $safes = Safe::query()->orderBy('name')->get(['id', 'name']);

        return view('expenses::payments.create', [
            'expense' => $expense,
            'banks' => $banks,
            'safes' => $safes,
        ]);
    }

    public function store(StoreExpensePaymentRequest $request, Expense $expense): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($expense, $data) {
            $expense->loadMissing(['type.recurrencePeriod']);
            $originalDueDate = $expense->due_date ? $expense->due_date->copy() : null;

            /** @var ExpensePayment $payment */
            $payment = $expense->payments()->create($data);

            $this->logLedgerEntry($payment);

            $this->advanceRecurringExpense($expense, $originalDueDate);
        });

        return redirect()
            ->route('expenses.expenses.index', $request->query())
            ->with('success', __('expenses::messages.payments.created'));
    }

    protected function logLedgerEntry(ExpensePayment $payment): void
    {
        if (! Schema::hasTable('ledger_entries') || ! Schema::hasTable('transaction_statuses')) {
            return;
        }

        $amount = round((float) $payment->amount, 2);
        if ($amount <= 0) {
            return;
        }

        $status = $this->resolveLedgerStatus();
        if (! $status) {
            return;
        }

        LedgerEntry::create([
            'entry_date' => optional($payment->paid_at)->toDateString() ?? now()->toDateString(),
            'investor_id' => null,
            'is_office' => true,
            'transaction_status_id' => $status->id,
            'transaction_type_id' => $status->transaction_type_id,
            'bank_account_id' => $payment->bank_account_id,
            'safe_id' => $payment->safe_id,
            'amount' => $amount,
            'direction' => 'out',
            'notes' => __('expenses::payments.ledger.notes', [
                'title' => $payment->expense->title,
                'id' => $payment->id,
            ]),
            'ref' => 'EXP-PAY-'.$payment->id,
        ]);
    }

    protected function resolveLedgerStatus(): ?TransactionStatus
    {
        if ($this->ledgerStatus instanceof TransactionStatus) {
            return $this->ledgerStatus;
        }

        $this->ledgerStatus = TransactionStatus::query()
            ->where('name', 'مصروفات')
            ->first();

        return $this->ledgerStatus;
    }

    protected function advanceRecurringExpense(Expense $expense, ?Carbon $originalDueDate = null): void
    {
        $type = $expense->type;

        if (! $type || ! $type->is_recurring) {
            $this->clearManualAmountOverrides($expense);

            return;
        }

        $totalPaid = (float) $expense->payments()->sum('amount');
        $amount = (float) $expense->amount;

        if ($totalPaid < $amount) {
            $this->clearManualAmountOverrides($expense);

            return;
        }

        $monthsToAdd = $this->resolveRecurrenceMonths($type->recurrencePeriod?->name);

        if (! $monthsToAdd) {
            $this->clearManualAmountOverrides($expense);

            return;
        }

        $currentDueDate = $originalDueDate ?? $expense->due_date;

        $nextDueDate = $currentDueDate instanceof Carbon
            ? $currentDueDate->copy()->addMonthsNoOverflow($monthsToAdd)
            : now()->addMonthsNoOverflow($monthsToAdd);

        $expense->forceFill([
            'due_date' => $nextDueDate,
            'manual_paid_amount' => 0,
            'manual_outstanding_amount' => $amount,
        ])->save();
    }

    protected function clearManualAmountOverrides(Expense $expense): void
    {
        if (is_null($expense->manual_paid_amount) && is_null($expense->manual_outstanding_amount)) {
            return;
        }

        $expense->forceFill([
            'manual_paid_amount' => null,
            'manual_outstanding_amount' => null,
        ])->save();
    }

    protected function resolveRecurrenceMonths(?string $periodName): ?int
    {
        if (! $periodName) {
            return null;
        }

        $normalized = Str::of($periodName)->trim()->lower()->value();

        return match ($normalized) {
            'شهري', 'شهرى', 'شهرية', 'شهر', 'monthly', 'month', '1 month' => 1,
            'نصف سنوي', 'نصف سنوى', 'نصف سنوية', 'semi annual', 'semi-annual', 'semiannual', 'every 6 months', 'six months', '6 months' => 6,
            'سنوي', 'سنوى', 'سنويا', 'سنوية', 'yearly', 'annual', 'annually', '12 months', '12 month' => 12,
            default => null,
        };
    }
}
