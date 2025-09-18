<?php

namespace Modules\Contracts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractClaim;
use Modules\Contracts\Http\Requests\StoreContractClaimRequest;
use Modules\Contracts\Http\Requests\UpdateContractClaimRequest;
use Modules\Lookups\Entities\ClaimStatus;
use Modules\Lookups\Entities\ContractStatus;
use Modules\Lookups\Entities\CustomerStatus;
use Modules\Lookups\Entities\GuarantorStatus;

class ContractClaimController extends Controller
{
    private ?int $customerClaimStatusId = null;
    private ?int $guarantorClaimStatusId = null;
    private ?int $contractClaimStatusId = null;
    private ?int $defaultClaimStatusId = null;

    public function index(Request $request)
    {
        $claimsQuery = ContractClaim::query()
            ->with([
                'contract' => function ($query) {
                    $query->select('id', 'contract_number', 'customer_id', 'guarantor_id');
                },
                'contract.customer:id,name',
                'contract.guarantor:id,name',
                'claimFirstParty:id,name',
                'claimStatus:id,name',
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

    public function store(StoreContractClaimRequest $request)
    {
        $claim = DB::transaction(function () use ($request) {
            $payload = $request->validated();
            $payload['claim_status_id'] = $this->defaultClaimStatusId();

            $claim = ContractClaim::create($payload);

            $this->updateRelatedStatuses($claim);

            return $claim;
        });

        if ($request->boolean('return_to_contract')) {
            return redirect()
                ->route('contracts.show', $claim->contract_id)
                ->with('success', __('contracts::claims.created'));
        }

        return redirect()
            ->route('contract-claims.index')
            ->with('success', __('contracts::claims.created'));
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

    private function partyRoleOptions(): array
    {
        return [
            ContractClaim::FILED_PARTY_CUSTOMER => __('contracts::claims.party_role_customer'),
            ContractClaim::FILED_PARTY_GUARANTOR => __('contracts::claims.party_role_guarantor'),
        ];
    }

    private function updateRelatedStatuses(ContractClaim $claim): void
    {
        $contract = $claim->contract()->with(['customer', 'guarantor'])->first();

        if (! $contract) {
            return;
        }

        $this->updateContractStatus($contract);

        if ($claim->filed_party_role === ContractClaim::FILED_PARTY_CUSTOMER) {
            $this->updateCustomerStatus($contract);
        } elseif ($claim->filed_party_role === ContractClaim::FILED_PARTY_GUARANTOR) {
            $this->updateGuarantorStatus($contract);
        }
    }

    private function updateContractStatus(Contract $contract): void
    {
        $statusId = $this->contractClaimStatusId();

        if (! $statusId || $contract->contract_status_id === $statusId) {
            return;
        }

        $contract->contract_status_id = $statusId;
        $contract->save();
    }

    private function updateCustomerStatus(Contract $contract): void
    {
        $customer = $contract->customer;
        $statusId = $this->customerClaimStatusId();

        if (! $customer || ! $statusId || $customer->customer_status_id === $statusId) {
            return;
        }

        $customer->customer_status_id = $statusId;
        $customer->save();
    }

    private function updateGuarantorStatus(Contract $contract): void
    {
        $guarantor = $contract->guarantor;
        $statusId = $this->guarantorClaimStatusId();

        if (! $guarantor || ! $statusId || $guarantor->guarantor_status_id === $statusId) {
            return;
        }

        $guarantor->guarantor_status_id = $statusId;
        $guarantor->save();
    }

    private function customerClaimStatusId(): ?int
    {
        if ($this->customerClaimStatusId === null) {
            $id = CustomerStatus::where('name', 'مرفوع فيه')->value('id');
            $this->customerClaimStatusId = $id ? (int) $id : 0;
        }

        return $this->customerClaimStatusId ?: null;
    }

    private function guarantorClaimStatusId(): ?int
    {
        if ($this->guarantorClaimStatusId === null) {
            $id = GuarantorStatus::where('name', 'مرفوع فيه')->value('id');
            $this->guarantorClaimStatusId = $id ? (int) $id : 0;
        }

        return $this->guarantorClaimStatusId ?: null;
    }

    private function contractClaimStatusId(): ?int
    {
        if ($this->contractClaimStatusId === null) {
            $id = ContractStatus::where('name', 'مطلوب')->value('id');
            $this->contractClaimStatusId = $id ? (int) $id : 0;
        }

        return $this->contractClaimStatusId ?: null;
    }

    private function defaultClaimStatusId(): ?int
    {
        if ($this->defaultClaimStatusId === null) {
            $id = ClaimStatus::where('name', 'قيد المراجعة')->value('id');
            $this->defaultClaimStatusId = $id ? (int) $id : 0;
        }

        return $this->defaultClaimStatusId ?: null;
    }
}
