<?php

namespace Modules\Debts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Support\Facades\Schema;
use Modules\Accounts\Entities\BankAccount;
use Modules\Accounts\Entities\Safe;
use Modules\Customers\Entities\Customer;
use Modules\Debts\Entities\Debt;
use Modules\Debts\Http\Requests\StoreDebtPaymentRequest;
use Modules\Debts\Http\Requests\StoreDebtRequest;
use Modules\Debts\Http\Requests\UpdateDebtRequest;
use Modules\Investors\Entities\Investor;
use Modules\Ledger\Entities\LedgerEntry;
use Modules\Lookups\Entities\TransactionStatus;

class DebtController extends Controller
{
    protected array $ledgerStatusCache = [];

    public function index(Request $request): View
    {
        $filters = [
            'party_type' => $request->string('party_type')->toString(),
            'status' => $request->string('status')->toString(),
            'search' => $request->string('search')->toString(),
        ];

        $query = Debt::query()
            ->with([
                'customer:id,name',
                'investor:id,name',
                'bankAccount:id,name',
                'safe:id,name',
                'payments' => fn ($relation) => $relation
                    ->with(['bankAccount:id,name', 'safe:id,name'])
                    ->orderByDesc('paid_at')
                    ->orderByDesc('id'),
            ])
            ->orderByDesc('issued_at')
            ->orderByDesc('id');

        if ($filters['party_type']) {
            $query->where('party_type', $filters['party_type']);
        }

        if ($filters['status'] === 'open') {
            $query->whereColumn('principal_amount', '>', 'paid_amount');
        } elseif ($filters['status'] === 'settled') {
            $query->whereColumn('principal_amount', '<=', 'paid_amount');
        }

        if ($filters['search']) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search) {
                $q->where('counterparty_name', 'like', $search)
                    ->orWhere('notes', 'like', $search)
                    ->orWhereHas('customer', fn ($sub) => $sub->where('name', 'like', $search))
                    ->orWhereHas('investor', fn ($sub) => $sub->where('name', 'like', $search));
            });
        }

        $debts = $query->paginate(20)->withQueryString();

        $totalsQuery = clone $query;
        $totals = [
            'principal' => (float) $totalsQuery->sum('principal_amount'),
            'paid' => (float) (clone $totalsQuery)->sum('paid_amount'),
        ];
        $totals['outstanding'] = round($totals['principal'] - $totals['paid'], 2);

        return view('debts::index', [
            'debts' => $debts,
            'totals' => $totals,
            'filters' => $filters,
        ] + $this->formLookups());
    }

    public function create(): View
    {
        return view('debts::create', $this->formLookups() + [
            'debt' => new Debt([
                'issued_at' => now(),
            ]),
        ]);
    }

    public function store(StoreDebtRequest $request): RedirectResponse
    {
        $data = $this->prepareData($request->validated());
        $data['paid_amount'] = 0;

        DB::transaction(function () use ($data) {
            $debt = Debt::create($data);

            $this->logLedgerEntry($debt, [
                'status' => 'مديونية',
                'direction' => 'out',
                'amount' => $debt->principal_amount,
                'date' => optional($debt->issued_at)->toDateString() ?? now()->toDateString(),
                'bank_account_id' => $debt->bank_account_id,
                'safe_id' => $debt->safe_id,
                'notes' => __('debts::messages.ledger.notes.created', ['name' => $this->resolveCounterpartyName($debt)]),
                'ref' => 'DEBT-'.$debt->id,
            ]);
        });

        return redirect()
            ->route('debts.index')
            ->with('success', __('debts::messages.flash.created'));
    }

    public function edit(Debt $debt): View
    {
        return view('debts::edit', $this->formLookups() + compact('debt'));
    }

    public function update(UpdateDebtRequest $request, Debt $debt): RedirectResponse
    {
        $data = $this->prepareData($request->validated());

        $debt->update($data);

        return redirect()
            ->route('debts.index')
            ->with('success', __('debts::messages.flash.updated'));
    }

    public function storePayment(StoreDebtPaymentRequest $request, Debt $debt): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($debt, $data) {
            $payment = $debt->payments()->create($data);
            $debt->refreshPaidAmount();

            $this->logLedgerEntry($debt, [
                'status' => 'سداد مديونية',
                'direction' => 'in',
                'amount' => $payment->amount,
                'date' => optional($payment->paid_at)->toDateString() ?? now()->toDateString(),
                'bank_account_id' => $payment->bank_account_id,
                'safe_id' => $payment->safe_id,
                'notes' => __('debts::messages.ledger.notes.payment', ['id' => $payment->id]),
                'ref' => 'DEBT-PAY-'.$payment->id,
            ]);
        });

        return redirect()
            ->route('debts.index', request()->query())
            ->with('success', __('debts::messages.flash.payment_recorded'));
    }

    public function destroy(Debt $debt): RedirectResponse
    {
        $debt->delete();

        return redirect()
            ->route('debts.index')
            ->with('success', __('debts::messages.flash.deleted'));
    }

    protected function formLookups(): array
    {
        $customers = Customer::query()->orderBy('name')->get(['id', 'name']);
        $investors = Investor::query()->orderBy('name')->get(['id', 'name']);
        $banks = BankAccount::query()->orderBy('name')->get(['id', 'name']);
        $safes = Safe::query()->orderBy('name')->get(['id', 'name']);

        return compact('customers', 'investors', 'banks', 'safes');
    }

    protected function prepareData(array $data): array
    {
        if (isset($data['principal_amount'])) {
            $data['principal_amount'] = (float) $data['principal_amount'];
        }

        if (isset($data['paid_amount'])) {
            $data['paid_amount'] = (float) $data['paid_amount'];
        }

        foreach (['customer_id', 'investor_id', 'bank_account_id', 'safe_id'] as $key) {
            if (empty($data[$key])) {
                $data[$key] = null;
            }
        }

        if (empty($data['due_at'])) {
            $data['due_at'] = null;
        }

        if (empty($data['counterparty_name'])) {
            if (($data['party_type'] ?? null) === 'customer' && !empty($data['customer_id'])) {
                $data['counterparty_name'] = Customer::query()
                    ->whereKey($data['customer_id'])
                    ->value('name');
            } elseif (($data['party_type'] ?? null) === 'investor' && !empty($data['investor_id'])) {
                $data['counterparty_name'] = Investor::query()
                    ->whereKey($data['investor_id'])
                    ->value('name');
            }
        }

        return $data;
    }

    protected function logLedgerEntry(Debt $debt, array $context): void
    {
        if (! Schema::hasTable('ledger_entries') || ! Schema::hasTable('transaction_statuses')) {
            return;
        }

        $amount = round((float) ($context['amount'] ?? 0), 2);
        if ($amount <= 0) {
            return;
        }

        $statusId = $this->resolveLedgerStatusId($context['status'] ?? '');
        if (! $statusId) {
            return;
        }

        $status = TransactionStatus::query()->find($statusId);
        if (! $status) {
            return;
        }

        $date = $context['date'] ?? now()->toDateString();
        if ($date instanceof Carbon) {
            $date = $date->toDateString();
        }

        $investorId = ($debt->party_type === 'investor') ? $debt->investor_id : null;

        LedgerEntry::create([
            'entry_date' => $date,
            'investor_id' => $investorId,
            'is_office' => $investorId ? false : true,
            'transaction_status_id' => $status->id,
            'transaction_type_id' => $status->transaction_type_id,
            'bank_account_id' => $context['bank_account_id'] ?? null,
            'safe_id' => $context['safe_id'] ?? null,
            'amount' => $amount,
            'direction' => $context['direction'] ?? 'out',
            'notes' => $context['notes'] ?? null,
            'ref' => $context['ref'] ?? null,
        ]);
    }

    protected function resolveLedgerStatusId(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        if (! array_key_exists($name, $this->ledgerStatusCache)) {
            $this->ledgerStatusCache[$name] = TransactionStatus::query()
                ->where('name', $name)
                ->value('id');
        }

        return $this->ledgerStatusCache[$name];
    }

    protected function resolveCounterpartyName(Debt $debt): string
    {
        return $debt->counterparty_name
            ?? optional($debt->customer)->name
            ?? optional($debt->investor)->name
            ?? ('#'.$debt->id);
    }
}
