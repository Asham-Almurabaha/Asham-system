<?php

namespace Modules\Companies\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Accounts\Entities\BankAccount;
use Modules\Accounts\Entities\Safe;
use Modules\Companies\Entities\Company;
use Modules\Companies\Entities\CompanyDisbursementStatus;
use Modules\Companies\Entities\CompanyTransaction;
use Modules\Companies\Entities\CompanyTransactionAllocation;
use Modules\Companies\Http\Requests\StoreCompanyTransactionRequest;
use Modules\Companies\Http\Requests\UpdateCompanyTransactionRequest;

class CompanyTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $query = CompanyTransaction::query()->with([
            'status',
            'bankAccount',
            'safe',
            'allocations.company',
        ])->latest('transaction_date');

        $reference = trim((string) $request->input('reference', ''));
        if ($reference !== '') {
            $query->where('reference', 'like', "%{$reference}%");
        }

        $statusId = (int) $request->input('status_id');
        if ($statusId > 0) {
            $query->where('company_disbursement_status_id', $statusId);
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

        $statuses = CompanyDisbursementStatus::orderBy('id')->get();
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
        $statuses = CompanyDisbursementStatus::orderBy('id')->get();
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
                'company_disbursement_status_id' => $data['company_disbursement_status_id'],
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'bank_amount' => $data['bank_amount'] ?? 0,
                'safe_id' => $data['safe_id'] ?? null,
                'safe_amount' => $data['safe_amount'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncAllocations($transaction, $data['allocations']);

            return $transaction;
        });

        return redirect()
            ->route('company-transactions.show', $transaction)
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

        $statuses = CompanyDisbursementStatus::orderBy('id')->get();
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
                'company_disbursement_status_id' => $data['company_disbursement_status_id'],
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'bank_amount' => $data['bank_amount'] ?? 0,
                'safe_id' => $data['safe_id'] ?? null,
                'safe_amount' => $data['safe_amount'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncAllocations($companyTransaction, $data['allocations']);
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
}
