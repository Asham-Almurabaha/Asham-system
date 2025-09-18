<?php

namespace Modules\Contracts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractClaim;
use Modules\Contracts\Http\Requests\ApplyContractClaimDiscountRequest;
use Modules\Contracts\Http\Requests\StoreContractClaimPaymentRequest;
use Modules\Contracts\Http\Requests\StoreContractClaimRequest;
use Modules\Contracts\Http\Requests\UpdateContractClaimRequest;
use Modules\Contracts\Http\Requests\UpdateContractClaimStatusRequest;
use Modules\Lookups\Entities\ClaimPayer;
use Modules\Lookups\Entities\ClaimStatus;
use Modules\Lookups\Entities\ContractStatus;
use Modules\Lookups\Entities\CustomerStatus;
use Modules\Lookups\Entities\GuarantorStatus;

class ContractClaimController extends Controller
{
    private const CHANGE_STATUS_NAMES = ['مقبول', 'مرفوض'];

    private ?int $customerClaimStatusId = null;
    private ?int $guarantorClaimStatusId = null;
    private ?int $contractClaimStatusId = null;
    private ?int $defaultClaimStatusId = null;
    private ?int $acceptedClaimStatusId = null;
    private ?int $paidWithDiscountClaimStatusId = null;
    private ?int $raisedContractStatusId = null;

    public function index(Request $request)
    {
        $claimsQuery = ContractClaim::query()
            ->with([
                'contract' => function ($query) {
                    $query->select('id', 'contract_number', 'customer_id', 'guarantor_id');
                },
                'contract.customer:id,name',
                'contract.guarantor:id,name',
                'claimant:id,name',
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

        $claimStatuses = ClaimStatus::orderBy('name')->get(['id', 'name']);
        $claimPayers = ClaimPayer::orderBy('name')->get(['id', 'name']);

        return view('contracts::claims.index', [
            'claims' => $claims,
            'partyRoles' => $this->partyRoleOptions(),
            'claimStatuses' => $claimStatuses,
            'claimPayers' => $claimPayers,
            'changeStatusOptions' => $claimStatuses
                ->filter(fn ($status) => in_array($status->name, self::CHANGE_STATUS_NAMES, true))
                ->values(),
            'paidWithDiscountClaimStatusId' => $this->paidWithDiscountClaimStatusId(),
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

    public function updateStatus(UpdateContractClaimStatusRequest $request, ContractClaim $contractClaim)
    {
        $contractClaim->update($request->validated());

        $this->updateRelatedStatuses($contractClaim);

        if ($request->boolean('return_to_contract')) {
            return redirect()
                ->route('contracts.show', $contractClaim->contract_id)
                ->with('success', __('contracts::claims.status_updated'));
        }

        return redirect()
            ->route('contract-claims.index')
            ->with('success', __('contracts::claims.status_updated'));
    }

    public function reopen(ContractClaim $contractClaim)
    {
        $statusId = $this->defaultClaimStatusId();

        if (! $statusId) {
            return redirect()
                ->back()
                ->with('error', __('contracts::claims.under_review_status_missing'));
        }

        $claim = DB::transaction(function () use ($contractClaim, $statusId) {
            $contractClaim->update([
                'claim_status_id' => $statusId,
            ]);

            $contract = $contractClaim->contract()->first();

            if ($contract) {
                $this->updateContractStatus($contract);
            }

            return $contractClaim->fresh();
        });

        $this->updateRelatedStatuses($claim);

        return redirect()
            ->route('contract-claims.index')
            ->with('success', __('contracts::claims.reopened'));
    }

    public function applyDiscount(ApplyContractClaimDiscountRequest $request, ContractClaim $contractClaim)
    {
        $statusId = $this->paidWithDiscountClaimStatusId();

        if (! $statusId) {
            return redirect()
                ->back()
                ->with('error', __('contracts::claims.paid_with_discount_status_missing'));
        }

        $payload = $request->validated();
        $payload['claim_status_id'] = $statusId;

        $contractClaim->update($payload);

        $this->updateRelatedStatuses($contractClaim);

        if ($request->boolean('return_to_contract')) {
            return redirect()
                ->route('contracts.show', $contractClaim->contract_id)
                ->with('success', __('contracts::claims.discount_applied'));
        }

        return redirect()
            ->route('contract-claims.index')
            ->with('success', __('contracts::claims.discount_applied'));
    }

    public function storePayment(StoreContractClaimPaymentRequest $request, ContractClaim $contractClaim)
    {
        $payload = $request->validated();

        $claim = DB::transaction(function () use ($contractClaim, $payload) {
            $contractClaim->payments()->create([
                'claim_payer_id' => $payload['claim_payer_id'],
                'amount' => $payload['amount'],
                'paid_at' => $payload['paid_at'],
            ]);

            return $contractClaim->fresh();
        });

        $this->updateRelatedStatuses($claim);

        if ($request->boolean('return_to_contract')) {
            return redirect()
                ->route('contracts.show', $claim->contract_id)
                ->with('success', __('contracts::claims.payment_recorded'));
        }

        return redirect()
            ->route('contract-claims.index')
            ->with('success', __('contracts::claims.payment_recorded'));
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

        if ($this->isClaimAccepted($claim)) {
            $this->updateContractStatusToRaised($contract);
        } else {
            $hasPreviousClaims = ContractClaim::query()
                ->where('contract_id', $contract->id)
                ->where('id', '!=', $claim->id)
                ->exists();

            if (! $hasPreviousClaims) {
                $this->updateContractStatus($contract);
            }
        }

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

    private function updateContractStatusToRaised(Contract $contract): void
    {
        $statusId = $this->raisedContractStatusId();

        if (! $statusId || $contract->contract_status_id === $statusId) {
            return;
        }

        $contract->contract_status_id = $statusId;
        $contract->save();
    }

    private function isClaimAccepted(ContractClaim $claim): bool
    {
        $statusId = $this->acceptedClaimStatusId();

        return $statusId !== null && (int) $claim->claim_status_id === $statusId;
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

    private function raisedContractStatusId(): ?int
    {
        if ($this->raisedContractStatusId === null) {
            $id = ContractStatus::where('name', 'مرفوع فيه')->value('id');
            $this->raisedContractStatusId = $id ? (int) $id : 0;
        }

        return $this->raisedContractStatusId ?: null;
    }

    private function acceptedClaimStatusId(): ?int
    {
        if ($this->acceptedClaimStatusId === null) {
            $id = ClaimStatus::where('name', 'مقبول')->value('id');
            $this->acceptedClaimStatusId = $id ? (int) $id : 0;
        }

        return $this->acceptedClaimStatusId ?: null;
    }

    private function paidWithDiscountClaimStatusId(): ?int
    {
        if ($this->paidWithDiscountClaimStatusId === null) {
            $id = ClaimStatus::where('name', 'مدفوع بخصم')->value('id');
            $this->paidWithDiscountClaimStatusId = $id ? (int) $id : 0;
        }

        return $this->paidWithDiscountClaimStatusId ?: null;
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
