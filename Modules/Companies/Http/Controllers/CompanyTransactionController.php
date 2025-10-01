<?php

namespace Modules\Companies\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Modules\Accounts\Entities\BankAccount;
use Modules\Accounts\Entities\Safe;
use Modules\Companies\Entities\Company;
use Modules\Companies\Entities\CompanyTransaction;
use Modules\Companies\Entities\CompanyTransactionAllocation;
use Modules\Companies\Http\Requests\StoreCompanyTransactionRequest;
use Modules\Companies\Http\Requests\UpdateCompanyTransactionRequest;
use Modules\Ledger\Entities\LedgerEntry;
use Modules\Lookups\Entities\TransactionStatus;

class CompanyTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $categoryId = $this->getCompanyCategoryId();
        $statuses = $this->getCompanyStatuses($categoryId);
        $allowedStatusIds = $statuses->pluck('id')->all();

        $query = CompanyTransaction::query()
            ->with([
                'status',
                'bankAccount',
                'safe',
                'allocations.company',
            ])
            ->when(empty($allowedStatusIds), fn ($builder) => $builder->whereRaw('1=0'))
            ->when(!empty($allowedStatusIds), fn ($builder) => $builder->whereIn('status_id', $allowedStatusIds))
            ->latest('transaction_date');

        $statusId = (int) $request->input('status_id');
        if ($statusId > 0 && in_array($statusId, $allowedStatusIds, true)) {
            $query->where('status_id', $statusId);
        }

        $companyId = (int) $request->input('company_id');
        if ($companyId > 0) {
            $query->whereHas('allocations', fn ($builder) => $builder->where('company_id', $companyId));
        }

        $dateFrom = $request->input('date_from');
        if ($dateFrom) {
            $query->whereDate('transaction_date', '>=', $dateFrom);
        }

        $dateTo = $request->input('date_to');
        if ($dateTo) {
            $query->whereDate('transaction_date', '<=', $dateTo);
        }

        $transactions = $query->paginate(15)->withQueryString();

        $companies = Company::orderBy('name')->get();

        $pageCollection = $transactions->getCollection();
        $totals = [
            'amount' => round((float) $pageCollection->sum('total_amount'), 2),
            'disbursed' => round((float) $pageCollection->sum(fn ($tx) => $tx->disbursed_amount), 2),
            'repaid' => round((float) $pageCollection->sum(fn ($tx) => $tx->repaid_amount), 2),
            'outstanding' => round((float) $pageCollection->sum(fn ($tx) => $tx->outstanding_amount), 2),
        ];

        return view('companies::transactions.index', compact(
            'transactions',
            'statuses',
            'companies',
            'totals'
        ));
    }

    public function create(): View
    {
        $categoryId = $this->getCompanyCategoryId();
        $statuses = $this->getCompanyStatuses($categoryId);
        $companies = Company::orderBy('name')->get();
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('name')->get();
        $safes = Safe::where('is_active', true)->orderBy('name')->get();

        return view('companies::transactions.create', compact(
            'statuses',
            'companies',
            'bankAccounts',
            'safes'
        ));
    }

    public function store(StoreCompanyTransactionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $transaction = DB::transaction(function () use ($data) {
            $transaction = CompanyTransaction::create([
                'transaction_date' => $data['transaction_date'],
                'total_amount' => $data['total_amount'],
                'status_id' => $data['status_id'],
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'bank_amount' => $data['bank_amount'] ?? 0,
                'safe_id' => $data['safe_id'] ?? null,
                'safe_amount' => $data['safe_amount'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncAllocations($transaction, $data['allocations']);
            $this->syncLedgerEntries($transaction, $data);

            return $transaction;
        });

        return redirect()
            ->route('company-transactions.create')
            ->with('success', __('companies::messages.transactions.created'));
    }

    public function show(CompanyTransaction $companyTransaction): View
    {
        $companyTransaction->loadMissing(['status', 'bankAccount', 'safe', 'allocations.company']);

        $shareSummaries = $this->summarizeAllocations($companyTransaction->allocations, $companyTransaction);

        return view('companies::transactions.show', [
            'transaction' => $companyTransaction,
            'shareSummaries' => $shareSummaries,
        ]);
    }

    public function edit(CompanyTransaction $companyTransaction): View
    {
        $companyTransaction->loadMissing(['allocations.company']);

        $categoryId = $this->getCompanyCategoryId();
        $statuses = $this->getCompanyStatuses($categoryId);
        $companies = Company::orderBy('name')->get();
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('name')->get();
        $safes = Safe::where('is_active', true)->orderBy('name')->get();

        return view('companies::transactions.edit', [
            'transaction' => $companyTransaction,
            'statuses' => $statuses,
            'companies' => $companies,
            'bankAccounts' => $bankAccounts,
            'safes' => $safes,
        ]);
    }

    public function update(UpdateCompanyTransactionRequest $request, CompanyTransaction $companyTransaction): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($companyTransaction, $data) {
            $companyTransaction->update([
                'transaction_date' => $data['transaction_date'],
                'total_amount' => $data['total_amount'],
                'status_id' => $data['status_id'],
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'bank_amount' => $data['bank_amount'] ?? 0,
                'safe_id' => $data['safe_id'] ?? null,
                'safe_amount' => $data['safe_amount'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncAllocations($companyTransaction, $data['allocations']);
            $this->syncLedgerEntries($companyTransaction, $data);
        });

        return redirect()
            ->route('company-transactions.show', $companyTransaction)
            ->with('success', __('companies::messages.transactions.updated'));
    }

    public function destroy(CompanyTransaction $companyTransaction): RedirectResponse
    {
        DB::transaction(fn () => $companyTransaction->delete());

        return redirect()
            ->route('company-transactions.index')
            ->with('success', __('companies::messages.transactions.deleted'));
    }

    private function getCompanyCategoryId(): ?int
    {
        return DB::table('categories')
            ->whereIn('name', ['الشركات', 'شركات'])
            ->value('id');
    }

    private function getCompanyStatuses(?int $categoryId)
    {
        if (!$categoryId) {
            return collect();
        }

        return TransactionStatus::query()
            ->whereIn('id', function ($query) use ($categoryId) {
                $query->select('transaction_status_id')
                    ->from('category_transaction_status')
                    ->where('category_id', $categoryId);
            })
            ->orderBy('name')
            ->get();
    }

    private function syncAllocations(CompanyTransaction $transaction, array $allocations): void
    {
        $transaction->allocations()->delete();

        foreach ($allocations as $allocation) {
            $transaction->allocations()->create([
                'company_id' => (int) $allocation['company_id'],
                'share_amount' => $allocation['share_amount'],
                'share_percentage' => $allocation['share_percentage'] !== null && $allocation['share_percentage'] !== ''
                    ? $allocation['share_percentage']
                    : null,
                'notes' => $allocation['notes'] !== '' ? $allocation['notes'] : null,
            ]);
        }
    }

    private function summarizeAllocations(Collection $allocations, CompanyTransaction $transaction): Collection
    {
        return $allocations->mapWithKeys(function (CompanyTransactionAllocation $allocation) use ($transaction) {
            $ratio = $this->shareRatio($allocation, $transaction);

            $disbursed = round($transaction->disbursed_amount * $ratio, 2);
            $repaid = round($transaction->repaid_amount * $ratio, 2);
            $outstanding = max(round($disbursed - $repaid, 2), 0.0);

            return [$allocation->id => [
                'ratio' => $ratio,
                'disbursed' => $disbursed,
                'repaid' => $repaid,
                'outstanding' => $outstanding,
            ]];
        });
    }

    private function shareRatio(CompanyTransactionAllocation $allocation, CompanyTransaction $transaction): float
    {
        $shareAmount = (float) $allocation->share_amount;
        $totalAmount = (float) $transaction->total_amount;

        if ($totalAmount > 0) {
            return max(min($shareAmount / $totalAmount, 1.0), 0.0);
        }

        $sharePercentage = $allocation->share_percentage;
        if (!is_null($sharePercentage)) {
            return max(min(((float) $sharePercentage) / 100, 1.0), 0.0);
        }

        $totalShareAmount = (float) $transaction->allocations->sum('share_amount');
        if ($totalShareAmount > 0) {
            return max(min($shareAmount / $totalShareAmount, 1.0), 0.0);
        }

        return 0.0;
    }

    private function syncLedgerEntries(CompanyTransaction $transaction, array $data): void
    {
        if (!Schema::hasTable('ledger_entries')) {
            return;
        }

        $transaction->loadMissing('status.transactionType');

        $status = $transaction->status;
        if (!$status || !$status->transaction_type_id) {
            return;
        }

        $entryDate = $this->resolveEntryDate($transaction);
        $direction = $this->resolveLedgerDirection($status);

        $basePayload = [
            'entry_date' => $entryDate,
            'investor_id' => null,
            'is_office' => true,
            'transaction_status_id' => $status->id,
            'transaction_type_id' => $status->transaction_type_id,
            'company_transaction_id' => $transaction->id,
            'direction' => $direction,
        ];

        $this->upsertLedgerEntry($transaction, 'bank', $data['bank_amount'] ?? 0, $data['bank_account_id'] ?? null, $basePayload);
        $this->upsertLedgerEntry($transaction, 'safe', $data['safe_amount'] ?? 0, $data['safe_id'] ?? null, $basePayload);
    }

    private function resolveEntryDate(CompanyTransaction $transaction): string
    {
        $date = $transaction->transaction_date;

        if ($date instanceof Carbon) {
            return $date->toDateString();
        }

        if (is_string($date) && $date !== '') {
            return $date;
        }

        return now()->toDateString();
    }

    private function resolveLedgerDirection(TransactionStatus $status): string
    {
        $typeName = $status->transactionType?->name;

        if ($typeName === 'إيداع') {
            return 'in';
        }

        return 'out';
    }

    private function upsertLedgerEntry(CompanyTransaction $transaction, string $channel, $amount, $accountId, array $basePayload): void
    {
        $amount = round((float) $amount, 2);
        $accountId = $accountId ? (int) $accountId : null;
        $ref = sprintf('COMP-TX-%d-%s', $transaction->id, strtoupper($channel));

        if ($amount <= 0 || !$accountId) {
            LedgerEntry::query()
                ->where('company_transaction_id', $transaction->id)
                ->where('ref', $ref)
                ->delete();

            return;
        }

        $payload = array_merge($basePayload, [
            'amount' => $amount,
            'ref' => $ref,
            'bank_account_id' => $channel === 'bank' ? $accountId : null,
            'safe_id' => $channel === 'safe' ? $accountId : null,
            'notes' => $this->ledgerNote($transaction, $channel),
        ]);

        LedgerEntry::updateOrCreate(
            [
                'company_transaction_id' => $transaction->id,
                'ref' => $ref,
            ],
            $payload
        );
    }

    private function ledgerNote(CompanyTransaction $transaction, string $channel): string
    {
        return match ($channel) {
            'bank' => __('companies::messages.transactions.ledger.notes.bank', ['id' => $transaction->id]),
            'safe' => __('companies::messages.transactions.ledger.notes.safe', ['id' => $transaction->id]),
            default => __('companies::messages.transactions.ledger.notes.generic', ['id' => $transaction->id]),
        };
    }
}
