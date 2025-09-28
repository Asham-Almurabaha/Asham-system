<?php

namespace Modules\Expenses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
            /** @var ExpensePayment $payment */
            $payment = $expense->payments()->create($data);

            $this->logLedgerEntry($payment);
        });

        return redirect()
            ->route('expenses.expenses.index')
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
}
