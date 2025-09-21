<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Contracts\Entities\Contract;
use Modules\Customers\Entities\Customer;
use Modules\Guarantors\Entities\Guarantor;
use Modules\Investors\Entities\Investor;

class GlobalSearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        $minLength = 2;

        if (mb_strlen($term) < $minLength) {
            return response()->json([
                'query' => $term,
                'results' => [],
                'min_length' => $minLength,
            ]);
        }

        $perSource = (int) $request->query('per_source', 5);
        $perSource = max(1, min($perSource, 10));

        $results = array_merge(
            $this->searchInvestors($term, $perSource),
            $this->searchCustomers($term, $perSource),
            $this->searchContracts($term, $perSource),
            $this->searchGuarantors($term, $perSource),
        );

        return response()->json([
            'query' => $term,
            'results' => $results,
            'min_length' => $minLength,
        ]);
    }

    /**
     * Search investors.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function searchInvestors(string $term, int $limit): array
    {
        $like = "%{$term}%";

        return Investor::query()
            ->select(['id', 'name', 'national_id', 'phone'])
            ->where(function (Builder $query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('national_id', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function (Investor $investor) {
                $details = $this->compactDetails([
                    __('general.National ID') => $investor->national_id,
                    __('general.Phone') => $investor->phone,
                ]);

                return [
                    'id' => $investor->id,
                    'type' => 'investor',
                    'type_label' => __('general.Investors'),
                    'icon' => 'bi-graph-up-arrow',
                    'title' => $investor->name,
                    'subtitle' => $details,
                    'url' => route('investors.show', $investor),
                ];
            })
            ->all();
    }

    /**
     * Search customers.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function searchCustomers(string $term, int $limit): array
    {
        $like = "%{$term}%";

        return Customer::query()
            ->select(['id', 'name', 'national_id', 'phone'])
            ->where(function (Builder $query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('national_id', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function (Customer $customer) {
                $details = $this->compactDetails([
                    __('general.National ID') => $customer->national_id,
                    __('general.Phone') => $customer->phone,
                ]);

                return [
                    'id' => $customer->id,
                    'type' => 'customer',
                    'type_label' => __('general.Customers'),
                    'icon' => 'bi-person',
                    'title' => $customer->name,
                    'subtitle' => $details,
                    'url' => route('customers.show', $customer),
                ];
            })
            ->all();
    }

    /**
     * Search contracts.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function searchContracts(string $term, int $limit): array
    {
        $like = "%{$term}%";

        return Contract::query()
            ->select(['id', 'contract_number', 'customer_id', 'guarantor_id'])
            ->with([
                'customer:id,name',
                'guarantor:id,name',
            ])
            ->where(function (Builder $query) use ($like) {
                $query->where('contract_number', 'like', $like)
                    ->orWhereHas('customer', function (Builder $relation) use ($like) {
                        $relation->where('name', 'like', $like)
                            ->orWhere('national_id', 'like', $like)
                            ->orWhere('phone', 'like', $like);
                    })
                    ->orWhereHas('guarantor', function (Builder $relation) use ($like) {
                        $relation->where('name', 'like', $like)
                            ->orWhere('national_id', 'like', $like)
                            ->orWhere('phone', 'like', $like);
                    });
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (Contract $contract) {
                $customerName = optional($contract->customer)->name;
                $guarantorName = optional($contract->guarantor)->name;

                $details = $this->compactDetails([
                    __('Contract Number') => $contract->contract_number,
                    __('Customer') => $customerName,
                    __('Guarantor') => $guarantorName,
                ]);

                return [
                    'id' => $contract->id,
                    'type' => 'contract',
                    'type_label' => __('general.Contracts'),
                    'icon' => 'bi-file-earmark-text',
                    'title' => $contract->contract_number ?? __('general.Contracts'),
                    'subtitle' => $details,
                    'url' => route('contracts.show', $contract),
                ];
            })
            ->all();
    }

    /**
     * Search guarantors.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function searchGuarantors(string $term, int $limit): array
    {
        $like = "%{$term}%";

        return Guarantor::query()
            ->select(['id', 'name', 'national_id', 'phone'])
            ->where(function (Builder $query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('national_id', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function (Guarantor $guarantor) {
                $details = $this->compactDetails([
                    __('general.National ID') => $guarantor->national_id,
                    __('general.Phone') => $guarantor->phone,
                ]);

                return [
                    'id' => $guarantor->id,
                    'type' => 'guarantor',
                    'type_label' => __('general.Guarantors'),
                    'icon' => 'bi-shield-check',
                    'title' => $guarantor->name,
                    'subtitle' => $details,
                    'url' => route('guarantors.show', $guarantor),
                ];
            })
            ->all();
    }

    /**
     * Build a compact subtitle string from key/value pairs.
     */
    protected function compactDetails(array $items): string
    {
        $parts = [];

        foreach ($items as $label => $value) {
            if (is_scalar($value) && filled($value)) {
                $parts[] = sprintf('%s: %s', $label, $value);
            }
        }

        return implode(' • ', $parts);
    }
}
