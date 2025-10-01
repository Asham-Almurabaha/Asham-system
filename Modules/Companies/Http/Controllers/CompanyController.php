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

        $redirectRoute = $request->user()?->can('companies.show')
            ? ['companies.show', $company]
            : ['companies.index'];

        return redirect()
            ->route(...$redirectRoute)
            ->with('success', __('companies::messages.companies.created'));
    }

    public function show(Company $company, Request $request): View
    {
        $company->loadMissing([
            'allocations.transaction.status',
            'allocations.transaction.bankAccount',
            'allocations.transaction.safe',
            'allocations.transaction.allocations',
        ]);

        $transactions = $this->paginatedTransactions($company);
        $transactionSummaries = $this->summariesForTransactions($transactions->getCollection(), $company);

        $summary = $this->summariesFor(collect([$company]))->get($company->id, [
            'allocations_total' => 0.0,
            'disbursed_share' => 0.0,
            'repaid_share' => 0.0,
            'outstanding_share' => 0.0,
            'active_transactions' => 0,
        ]);

        return view('companies::companies.show', compact('company', 'transactions', 'summary', 'transactionSummaries'));
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

        $redirectRoute = $request->user()?->can('companies.show')
            ? ['companies.show', $company]
            : ['companies.index'];

        return redirect()
            ->route(...$redirectRoute)
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
