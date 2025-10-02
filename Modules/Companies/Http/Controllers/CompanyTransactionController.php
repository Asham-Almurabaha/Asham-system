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
        $data = $this->prepareTransactionsListing($request);

        return view('companies::transactions.index', array_merge($data, [
            'pageTitle' => __('companies::companies.Company Transactions'),
            'pageHeading' => __('companies::companies.Company Transactions'),
            'indexRoute' => route('company-transactions.index'),
            'createRoute' => route('company-transactions.create'),
            'createButtonLabel' => __('companies::companies.New Transaction'),
            'showStatusFilter' => true,
        ]));
    }

    public function expenses(): RedirectResponse
    {
        return redirect()->route('company-transactions.expenses.create');
    }

    public function expensePayments(): RedirectResponse
    {
        return redirect()->route('company-transactions.expenses.payments.create');
    }

    public function create(): View
    {
        return $this->renderTransactionForm();
    }

    public function createExpense(): View
    {
        return $this->renderTransactionForm([
            'status_name' => 'مصروفات شركات',
            'allow_status_selection' => false,
            'include_inactive_accounts' => true,
            'page_title' => __('companies::companies.New Company Expense'),
            'page_heading' => __('companies::companies.New Company Expense'),
            'breadcrumb_route' => route('company-transactions.expenses.create'),
            'breadcrumb_label' => __('companies::companies.Company Expenses Title'),
            'store_route' => 'company-transactions.expenses.store',
            'entry_hint' => __('companies::companies.CompanyExpenseEntryHint'),
            'category_label' => __('companies::companies.InvestorCategoryLabel'),
        ]);
    }

    public function createExpensePayment(): View
    {
        return $this->renderTransactionForm([
            'status_name' => 'سداد مصروفات شركات',
            'allow_status_selection' => false,
            'include_inactive_accounts' => true,
            'page_title' => __('companies::companies.New Company Expense Payment'),
            'page_heading' => __('companies::companies.New Company Expense Payment'),
            'breadcrumb_route' => route('company-transactions.expenses.payments.create'),
            'breadcrumb_label' => __('companies::companies.Company Expense Payments Title'),
            'store_route' => 'company-transactions.expenses.payments.store',
            'entry_hint' => __('companies::companies.CompanyExpenseEntryHint'),
            'category_label' => __('companies::companies.InvestorCategoryLabel'),
            'single_company_mode' => true,
        ]);
    }

    public function store(StoreCompanyTransactionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->createTransaction($data);

        return redirect()
            ->route('company-transactions.create')
            ->with('success', __('companies::messages.transactions.created'));
    }

    public function storeExpense(StoreCompanyTransactionRequest $request): RedirectResponse
    {
        $status = $this->findCompanyStatusByName('مصروفات شركات');

        $data = $request->validated();
        $data['status_id'] = $status?->id ?? $data['status_id'];

        $this->createTransaction($data);

        return redirect()
            ->route('company-transactions.expenses.create')
            ->with('success', __('companies::messages.transactions.created'));
    }

    public function storeExpensePayment(StoreCompanyTransactionRequest $request): RedirectResponse
    {
        $status = $this->findCompanyStatusByName('سداد مصروفات شركات');

        $data = $request->validated();
        $data['status_id'] = $status?->id ?? $data['status_id'];

        $this->createTransaction($data);

        return redirect()
            ->route('company-transactions.expenses.payments.create')
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

    private function prepareTransactionsListing(Request $request, array $options = []): array
    {
        $categoryId = $this->getCompanyCategoryId();
        $statuses = $this->getCompanyStatuses($categoryId);
        $allowedStatusIds = $statuses->pluck('id')->all();

        $fixedStatus = null;
        $fixedStatusId = null;

        if (isset($options['status_name'])) {
            $fixedStatus = $statuses->firstWhere('name', $options['status_name']);
            $fixedStatusId = $fixedStatus?->id;
            if (!$fixedStatusId) {
                $allowedStatusIds = [];
            }
        } elseif (isset($options['fixed_status_id'])) {
            $fixedStatusId = (int) $options['fixed_status_id'];
            $fixedStatus = $statuses->firstWhere('id', $fixedStatusId);
        }

        if ($fixedStatusId) {
            $allowedStatusIds = array_values(array_intersect($allowedStatusIds, [$fixedStatusId]));
        }

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

        if ($fixedStatusId) {
            $query->where('status_id', $fixedStatusId);
        } else {
            $statusId = (int) $request->input('status_id');
            if ($statusId > 0 && in_array($statusId, $allowedStatusIds, true)) {
                $query->where('status_id', $statusId);
            }
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

        return [
            'transactions' => $transactions,
            'statuses' => $statuses,
            'companies' => $companies,
            'totals' => $totals,
            'fixedStatus' => $fixedStatus,
        ];
    }

    private function renderTransactionForm(array $options = []): View
    {
        $categoryId = $this->getCompanyCategoryId();
        $statuses = $this->getCompanyStatuses($categoryId);

        $defaultStatus = null;
        if (isset($options['status_name'])) {
            $defaultStatus = $statuses->firstWhere('name', $options['status_name']);
        } elseif (isset($options['default_status_id'])) {
            $defaultStatus = $statuses->firstWhere('id', (int) $options['default_status_id']);
        }

        $includeInactiveAccounts = (bool) ($options['include_inactive_accounts'] ?? false);
        $allowStatusSelection = (bool) ($options['allow_status_selection'] ?? true);
        $singleCompanyMode = (bool) ($options['single_company_mode'] ?? false);

        if (!$allowStatusSelection && !$defaultStatus) {
            abort(404);
        }

        $companiesQuery = Company::query()->orderBy('name');

        if ($singleCompanyMode) {
            $companiesQuery->with([
                'allocations.transaction.status.transactionType',
                'allocations.transaction.bankAccount',
                'allocations.transaction.safe',
            ]);
        }

        $companies = $companiesQuery->get();

        $companySummaries = $singleCompanyMode
            ? $this->summariesForCompanyAllocations($companies)
            : collect();

        $bankAccountsQuery = BankAccount::query();
        $safesQuery = Safe::query();

        if (!$includeInactiveAccounts) {
            $bankAccountsQuery->where('is_active', true)->orderBy('name');
            $safesQuery->where('is_active', true)->orderBy('name');
        } else {
            $bankAccountsQuery->orderByDesc('is_active')->orderBy('name');
            $safesQuery->orderByDesc('is_active')->orderBy('name');
        }

        $bankAccounts = $bankAccountsQuery->get();
        $safes = $safesQuery->get();

        return view('companies::transactions.create', [
            'statuses' => $statuses,
            'companies' => $companies,
            'bankAccounts' => $bankAccounts,
            'safes' => $safes,
            'defaultStatusId' => $defaultStatus?->id,
            'allowStatusSelection' => $allowStatusSelection,
            'singleCompanyMode' => $singleCompanyMode,
            'companySummaries' => $companySummaries,
            'pageTitle' => $options['page_title'] ?? null,
            'pageHeading' => $options['page_heading'] ?? null,
            'includeInactiveAccounts' => $includeInactiveAccounts,
            'breadcrumbRoute' => $options['breadcrumb_route'] ?? route('company-transactions.index'),
            'breadcrumbLabel' => $options['breadcrumb_label'] ?? __('companies::companies.Company Transactions'),
            'storeRoute' => $options['store_route'] ?? 'company-transactions.store',
            'entryHint' => $options['entry_hint'] ?? null,
            'categoryLabel' => $options['category_label'] ?? null,
        ]);
    }

    private function summariesForCompanyAllocations(Collection $companies): Collection
    {
        return $companies->map(function (Company $company) {
            $statusSummaries = [];

            foreach ($company->allocations as $allocation) {
                $transaction = $allocation->transaction;

                if (!$transaction || !$transaction->status) {
                    continue;
                }

                $ratio = $this->shareRatio($allocation, $transaction);

                if ($ratio <= 0.0) {
                    continue;
                }

                $status = $transaction->status;
                $statusId = $status->id;

                if (!isset($statusSummaries[$statusId])) {
                    $statusSummaries[$statusId] = [
                        'status_id' => $statusId,
                        'status_name' => $status->name,
                        'transaction_count' => 0,
                        'total_amount' => 0.0,
                        'bank_amount' => 0.0,
                        'safe_amount' => 0.0,
                        'final_balance' => 0.0,
                        'bank_accounts' => [],
                        'safes' => [],
                    ];
                }

                $statusSummaries[$statusId]['transaction_count']++;

                $totalAmount = (float) $transaction->total_amount;
                $bankAmount = (float) $transaction->bank_amount;
                $safeAmount = (float) $transaction->safe_amount;
                $directionMultiplier = $status->transactionType?->name === 'إيداع' ? 1 : -1;

                $statusSummaries[$statusId]['total_amount'] += $totalAmount * $ratio;
                $statusSummaries[$statusId]['bank_amount'] += $bankAmount * $ratio;
                $statusSummaries[$statusId]['safe_amount'] += $safeAmount * $ratio;
                $statusSummaries[$statusId]['final_balance'] += $directionMultiplier * (($bankAmount + $safeAmount) * $ratio);

                $bankShare = $bankAmount * $ratio;
                if ($bankShare !== 0.0) {
                    $bankAccount = $transaction->bankAccount;
                    $bankKey = $transaction->bank_account_id ?? 'unassigned';
                    $bankName = $bankAccount?->name ?? __('companies::companies.Unassigned Bank Account');

                    if (!isset($statusSummaries[$statusId]['bank_accounts'][$bankKey])) {
                        $statusSummaries[$statusId]['bank_accounts'][$bankKey] = [
                            'name' => $bankName,
                            'amount' => 0.0,
                            'net' => 0.0,
                            'transaction_count' => 0,
                        ];
                    }

                    $statusSummaries[$statusId]['bank_accounts'][$bankKey]['amount'] += $bankShare;
                    $statusSummaries[$statusId]['bank_accounts'][$bankKey]['net'] += $directionMultiplier * $bankShare;
                    $statusSummaries[$statusId]['bank_accounts'][$bankKey]['transaction_count']++;
                }

                $safeShare = $safeAmount * $ratio;
                if ($safeShare !== 0.0) {
                    $safe = $transaction->safe;
                    $safeKey = $transaction->safe_id ?? 'unassigned';
                    $safeName = $safe?->name ?? __('companies::companies.Unassigned Safe');

                    if (!isset($statusSummaries[$statusId]['safes'][$safeKey])) {
                        $statusSummaries[$statusId]['safes'][$safeKey] = [
                            'name' => $safeName,
                            'amount' => 0.0,
                            'net' => 0.0,
                            'transaction_count' => 0,
                        ];
                    }

                    $statusSummaries[$statusId]['safes'][$safeKey]['amount'] += $safeShare;
                    $statusSummaries[$statusId]['safes'][$safeKey]['net'] += $directionMultiplier * $safeShare;
                    $statusSummaries[$statusId]['safes'][$safeKey]['transaction_count']++;
                }
            }

            $statusCollection = collect($statusSummaries)
                ->map(function (array $summary) {
                    $summary['total_amount'] = round($summary['total_amount'], 2);
                    $summary['bank_amount'] = round($summary['bank_amount'], 2);
                    $summary['safe_amount'] = round($summary['safe_amount'], 2);
                    $summary['final_balance'] = round($summary['final_balance'], 2);

                    $summary['bank_accounts'] = collect($summary['bank_accounts'])
                        ->map(function (array $account) {
                            return [
                                'name' => $account['name'],
                                'amount' => round($account['amount'], 2),
                                'net' => round($account['net'], 2),
                                'transaction_count' => (int) $account['transaction_count'],
                            ];
                        })
                        ->sortBy('name')
                        ->values()
                        ->all();

                    $summary['safes'] = collect($summary['safes'])
                        ->map(function (array $safe) {
                            return [
                                'name' => $safe['name'],
                                'amount' => round($safe['amount'], 2),
                                'net' => round($safe['net'], 2),
                                'transaction_count' => (int) $safe['transaction_count'],
                            ];
                        })
                        ->sortBy('name')
                        ->values()
                        ->all();

                    return $summary;
                })
                ->sortBy('status_name')
                ->values();

            return [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'is_active' => (bool) $company->is_active,
                'statuses' => $statusCollection->toArray(),
                'totals' => [
                    'transaction_count' => (int) $statusCollection->sum('transaction_count'),
                    'bank_amount' => round($statusCollection->sum('bank_amount'), 2),
                    'safe_amount' => round($statusCollection->sum('safe_amount'), 2),
                    'final_balance' => round($statusCollection->sum('final_balance'), 2),
                ],
            ];
        });
    }

    private function createTransaction(array $data): CompanyTransaction
    {
        return DB::transaction(function () use ($data) {
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
    }

    private function findCompanyStatusByName(string $name): ?TransactionStatus
    {
        $categoryId = $this->getCompanyCategoryId();

        if (!$categoryId) {
            return null;
        }

        return TransactionStatus::query()
            ->where('name', $name)
            ->whereIn('id', function ($query) use ($categoryId) {
                $query->select('transaction_status_id')
                    ->from('category_transaction_status')
                    ->where('category_id', $categoryId);
            })
            ->first();
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
