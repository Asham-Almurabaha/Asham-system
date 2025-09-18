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
            ->with(['contract:id,contract_number']);

        if ($request->filled('contract_number')) {
            $contractNumber = trim((string) $request->input('contract_number'));
            $claimsQuery->whereHas('contract', function ($query) use ($contractNumber) {
                $query->where('contract_number', 'like', "%{$contractNumber}%");
            });
        }

        if ($request->filled('filed_in_party')) {
            $filedInParty = trim((string) $request->input('filed_in_party'));
            $claimsQuery->where('filed_in_party', 'like', "%{$filedInParty}%");
        }

        if ($request->filled('filed_against_party')) {
            $filedAgainstParty = trim((string) $request->input('filed_against_party'));
            $claimsQuery->where('filed_against_party', 'like', "%{$filedAgainstParty}%");
        }

        $claims = $claimsQuery
            ->orderByDesc('claim_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('contracts::claims.index', [
            'claims' => $claims,
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
            ->orderBy('contract_number')
            ->get(['id', 'contract_number']);
    }
}
