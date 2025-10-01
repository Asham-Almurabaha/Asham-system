<?php

namespace Modules\Companies\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Companies\Entities\Company;
use Modules\Companies\Entities\CompanyTransaction;
use Modules\Companies\Entities\CompanyTransactionAllocation;
use Modules\Companies\Http\Requests\StoreCompanyRequest;
use Modules\Companies\Http\Requests\UpdateCompanyRequest;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $query = Company::query();

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%");
            });
        }

        $status = $request->input('status');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $companies = $query
            ->with([
                'allocations.transaction.status',
                'allocations.transaction.allocations',
            ])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $summaries = $this->summariesFor($companies->getCollection());
        $pageOutstanding = $summaries->sum('outstanding_share');
        $pageDisbursed = $summaries->sum('disbursed_share');
        $pageRepaid = $summaries->sum('repaid_share');

        $totalCompanies = Company::count();
        $activeCompanies = Company::where('is_active', true)->count();
        $inactiveCompanies = Company::where('is_active', false)->count();

        return view('companies::companies.index', compact(
            'companies',
            'summaries',
            'pageOutstanding',
            'pageDisbursed',
            'pageRepaid',
            'totalCompanies',
            'activeCompanies',
            'inactiveCompanies'
        ));
    }

    public function dashboard(): View
    {
        $companies = Company::query()
            ->with([
                'allocations.transaction.status.transactionType',
                'allocations.transaction.bankAccount',
                'allocations.transaction.safe',
            ])
            ->orderBy('name')
            ->get();

        $companySummaries = $companies->map(function (Company $company) {
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
                        'direction' => $status->transactionType?->name === 'إيداع' ? 'in' : 'out',
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

                $statusSummaries[$statusId]['total_amount'] += $totalAmount * $ratio;
                $statusSummaries[$statusId]['bank_amount'] += $bankAmount * $ratio;
                $statusSummaries[$statusId]['safe_amount'] += $safeAmount * $ratio;

                $directionMultiplier = $statusSummaries[$statusId]['direction'] === 'in' ? 1 : -1;
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
                'company' => $company,
                'statuses' => $statusCollection,
                'totals' => [
                    'transactions' => (int) $statusCollection->sum('transaction_count'),
                    'bank_amount' => round($statusCollection->sum('bank_amount'), 2),
                    'safe_amount' => round($statusCollection->sum('safe_amount'), 2),
                    'final_balance' => round($statusCollection->sum('final_balance'), 2),
                ],
            ];
        });

        $overallTotals = [
            'companies' => $companies->count(),
            'active' => $companies->where('is_active', true)->count(),
            'inactive' => $companies->where('is_active', false)->count(),
            'bank_amount' => round($companySummaries->sum(fn ($row) => $row['totals']['bank_amount']), 2),
            'safe_amount' => round($companySummaries->sum(fn ($row) => $row['totals']['safe_amount']), 2),
            'final_balance' => round($companySummaries->sum(fn ($row) => $row['totals']['final_balance']), 2),
        ];

        return view('companies::dashboard', [
            'companySummaries' => $companySummaries,
            'overallTotals' => $overallTotals,
        ]);
    }

    public function create(): View
    {
        return view('companies::companies.create');
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $company = Company::create([
            'name' => $data['name'],
            'notes' => $data['notes'] ?? null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()
            ->route('companies.index')
            ->with('success', __('companies::messages.companies.created'));
    }

    public function show(Company $company): RedirectResponse
    {
        return redirect()->route('companies.index');
    }

    public function edit(Company $company): View
    {
        return view('companies::companies.edit', compact('company'));
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $data = $request->validated();

        $company->fill([
            'name' => $data['name'],
            'notes' => $data['notes'] ?? null,
            'is_active' => (bool) $data['is_active'],
        ])->save();

        return redirect()
            ->route('companies.index')
            ->with('success', __('companies::messages.companies.updated'));
    }

    public function destroy(Company $company): RedirectResponse
    {
        DB::transaction(fn () => $company->delete());

        return redirect()
            ->route('companies.index')
            ->with('success', __('companies::messages.companies.deleted'));
    }

    private function paginatedTransactions(Company $company): LengthAwarePaginator
    {
        $perPage = 10;

        return $company->transactions()
            ->with(['status', 'bankAccount', 'safe', 'allocations', 'allocations.company'])
            ->latest('transaction_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function summariesFor(Collection $companies): Collection
    {
        return $companies->mapWithKeys(function (Company $company) {
            $summary = [
                'allocations_total' => 0.0,
                'disbursed_share' => 0.0,
                'repaid_share' => 0.0,
                'outstanding_share' => 0.0,
                'active_transactions' => 0,
            ];

            foreach ($company->allocations as $allocation) {
                $transaction = $allocation->transaction;
                if (!$transaction instanceof CompanyTransaction) {
                    continue;
                }

                $summary['allocations_total'] += (float) $allocation->share_amount;

                $ratio = $this->shareRatio($allocation, $transaction);
                if ($ratio <= 0.0) {
                    continue;
                }

                $disbursedShare = round($transaction->disbursed_amount * $ratio, 2);
                $repaidShare = round($transaction->repaid_amount * $ratio, 2);
                $outstandingShare = max(round($disbursedShare - $repaidShare, 2), 0.0);

                $summary['disbursed_share'] += $disbursedShare;
                $summary['repaid_share'] += $repaidShare;
                $summary['outstanding_share'] += $outstandingShare;

                if ($outstandingShare > 0.01) {
                    $summary['active_transactions']++;
                }
            }

            foreach ($summary as $key => $value) {
                if (is_float($value)) {
                    $summary[$key] = round($value, 2);
                }
            }

            return [$company->id => $summary];
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

    private function summariesForTransactions(Collection $transactions, Company $company): Collection
    {
        return $transactions->mapWithKeys(function (CompanyTransaction $transaction) use ($company) {
            $allocation = $transaction->allocations
                ->firstWhere('company_id', $company->id);

            if (!$allocation instanceof CompanyTransactionAllocation) {
                return [$transaction->id => [
                    'share_amount' => 0.0,
                    'share_percentage' => null,
                    'disbursed' => 0.0,
                    'repaid' => 0.0,
                    'outstanding' => 0.0,
                    'ratio' => 0.0,
                    'notes' => null,
                ]];
            }

            $ratio = $this->shareRatio($allocation, $transaction);
            $disbursedShare = round($transaction->disbursed_amount * $ratio, 2);
            $repaidShare = round($transaction->repaid_amount * $ratio, 2);
            $outstandingShare = max(round($disbursedShare - $repaidShare, 2), 0.0);

            return [$transaction->id => [
                'share_amount' => round((float) $allocation->share_amount, 2),
                'share_percentage' => $allocation->share_percentage,
                'disbursed' => $disbursedShare,
                'repaid' => $repaidShare,
                'outstanding' => $outstandingShare,
                'ratio' => $ratio,
                'notes' => $allocation->notes,
            ]];
        });
    }
}
