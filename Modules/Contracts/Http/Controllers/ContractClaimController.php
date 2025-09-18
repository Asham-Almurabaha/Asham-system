<?php

namespace Modules\Contracts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractClaim;
use Modules\Contracts\Http\Requests\StoreContractClaimRequest;
use Modules\Contracts\Http\Requests\UpdateContractClaimRequest;

class ContractClaimController extends Controller
{
    public function index(Request $request)
    {
        $claimsQuery = ContractClaim::query()
            ->with([
                'contract' => function ($query) {
                    $query->select('id', 'contract_number', 'customer_id', 'guarantor_id');
                },
                'contract.customer:id,name',
                'contract.guarantor:id,name',
            ]);

        if ($request->filled('contract_number')) {
            $contractNumber = trim((string) $request->input('contract_number'));
            $claimsQuery->whereHas('contract', function ($query) use ($contractNumber) {
                $query->where('contract_number', 'like', "%{$contractNumber}%");
            });
        }

        if ($request->filled('filed_party_role')) {
            $filedPartyRole = (string) $request->input('filed_party_role');

            if (in_array($filedPartyRole, ContractClaim::FILED_PARTY_ROLES, true)) {
                $claimsQuery->where('filed_party_role', $filedPartyRole);
            }
        }

        $claims = $claimsQuery
            ->orderByDesc('claim_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('contracts::claims.index', [
            'claims' => $claims,
            'partyRoles' => $this->partyRoleOptions(),
        ]);
    }

    public function create()
    {
        return view('contracts::claims.create', [
            'contracts' => $this->contractsForSelect(),
        ]);
    }

    public function store(StoreContractClaimRequest $request)
    {
        ContractClaim::create($request->validated());

        return redirect()
            ->route('contract-claims.index')
            ->with('success', __('contracts::claims.created'));
    }

    public function edit(ContractClaim $contractClaim)
    {
        $contractClaim->loadMissing('contract.customer:id,name', 'contract.guarantor:id,name');

        return view('contracts::claims.edit', [
            'claim' => $contractClaim,
            'contracts' => $this->contractsForSelect(),
        ]);
    }

    public function update(UpdateContractClaimRequest $request, ContractClaim $contractClaim)
    {
        $contractClaim->update($request->validated());

        return redirect()
            ->route('contract-claims.index')
            ->with('success', __('contracts::claims.updated'));
    }

    public function destroy(ContractClaim $contractClaim)
    {
        $contractClaim->delete();

        return redirect()
            ->route('contract-claims.index')
            ->with('success', __('contracts::claims.deleted'));
    }

    private function contractsForSelect()
    {
        return Contract::query()
            ->with(['customer:id,name', 'guarantor:id,name'])
            ->orderBy('contract_number')
            ->get(['id', 'contract_number', 'customer_id', 'guarantor_id']);
    }

    private function partyRoleOptions(): array
    {
        return [
            ContractClaim::FILED_PARTY_CUSTOMER => __('contracts::claims.party_role_customer'),
            ContractClaim::FILED_PARTY_GUARANTOR => __('contracts::claims.party_role_guarantor'),
        ];
    }
}
