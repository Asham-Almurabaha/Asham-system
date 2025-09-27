<?php

namespace Modules\Contracts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\BankAccount;
use Modules\Accounts\Entities\Safe;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractClaim;
use Modules\Contracts\Services\ClaimPaymentDistributionService;
use Modules\Contracts\Services\ContractStatusRefresher;
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
    private ?int $partialPaidClaimStatusId = null;
    private ?int $paidInFullClaimStatusId = null;
    private ?int $raisedContractStatusId = null;
    private ?int $finishedWithClaimContractStatusId = null;
    private ?int $rejectedClaimStatusId = null;

    public function __construct(
        private ClaimPaymentDistributionService $claimPaymentDistribution,
        private ContractStatusRefresher $contractStatusRefresher
    )
    {
    }

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
                'payments' => fn ($query) => $query->orderByDesc('paid_at')->orderByDesc('id'),
                'payments.claimPayer:id,name',
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

        if ($request->filled('claim_status_id')) {
            $statusId = (int) $request->input('claim_status_id');

            if ($statusId > 0) {
                $claimsQuery->where('claim_status_id', $statusId);
            }
        }

        $claims = $claimsQuery
            ->orderByDesc('claim_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $claimStatuses = ClaimStatus::orderBy('name')->get(['id', 'name']);
        $claimPayers = ClaimPayer::orderBy('name')->get(['id', 'name']);
        $banks = BankAccount::orderBy('name')->get(['id', 'name']);
        $safes = Safe::orderBy('name')->get(['id', 'name']);

        return view('contracts::claims.index', [
            'claims' => $claims,
            'partyRoles' => $this->partyRoleOptions(),
            'claimStatuses' => $claimStatuses,
            'claimPayers' => $claimPayers,
            'banks' => $banks,
            'safes' => $safes,
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

            $contract = $claim->contract()
                ->with([
                    'installments:id,contract_id,payment_amount',
                    'claims:id,contract_id,discount_amount',
                    'claims.payments:id,contract_claim_id,amount',
                ])
                ->first();

            if ($contract) {
                $claimAmount = round((float) $claim->claim_amount, 2);
                $contractOutstanding = round($this->calculateContractOutstanding($contract), 2);
                $legalFeeValue = round($claimAmount - $contractOutstanding, 2);

                $formattedClaimAmount = number_format($claimAmount, 2, '.', '');
                $formattedOutstanding = number_format($contractOutstanding, 2, '.', '');
                $formattedLegalFee = number_format($legalFeeValue, 2, '.', '');

                $contract->notes()->create([
                    'note_date' => $claim->claim_date
                        ? $claim->claim_date->toDateString()
                        : now()->toDateString(),
                    'note' => sprintf(
                        'قيمة المحاماة = مبلغ المطالبة (%s) - المتبقي في العقد (%s) = %s',
                        $formattedClaimAmount,
                        $formattedOutstanding,
                        $formattedLegalFee
                    ),
                ]);
            }

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

    public function reopen(Request $request, ContractClaim $contractClaim)
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

        if ($request->boolean('return_to_contract')) {
            return redirect()
                ->route('contracts.show', $claim->contract_id)
                ->with('success', __('contracts::claims.reopened'));
        }

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

        $claim = DB::transaction(function () use ($contractClaim, $payload, $statusId) {
            $contractClaim->refresh();

            $discountAmount = round((float) $payload['discount_amount'], 2);
            $claimAmount = (float) $contractClaim->claim_amount;
            $alreadyPaid = (float) $contractClaim->paid_amount;

            $netAmount = max($claimAmount - $discountAmount, 0);
            $paymentAmount = round(max($netAmount - $alreadyPaid, 0), 2);

            $contractClaim->update([
                'discount_amount' => $discountAmount,
                'claim_status_id' => $statusId,
            ]);

            $payment = null;
            $contract = $contractClaim->contract()->first();

            if ($paymentAmount > 0) {
                $payment = $contractClaim->payments()->create([
                    'claim_payer_id' => $payload['claim_payer_id'],
                    'amount' => $paymentAmount,
                    'paid_at' => $payload['paid_at'],
                ]);

                if ($contract) {
                    $this->claimPaymentDistribution->logClaimPayment(
                        $contract,
                        $contractClaim,
                        $payment,
                        $paymentAmount,
                        $payload['bank_account_id'] ?? null,
                        $payload['safe_id'] ?? null,
                        $payload['notes'] ?? null
                    );
                }
            }

            $contractClaim->refresh();

            $this->syncClaimSettlementStatus($contractClaim);

            return $contractClaim->fresh();
        });

        $this->updateRelatedStatuses($claim);

        if ($request->boolean('return_to_contract')) {
            return redirect()
                ->route('contracts.show', $claim->contract_id)
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
            $payment = $contractClaim->payments()->create([
                'claim_payer_id' => $payload['claim_payer_id'],
                'amount' => $payload['amount'],
                'paid_at' => $payload['paid_at'],
            ]);

            $contract = $contractClaim->contract()->first();

            if ($contract) {
                $this->claimPaymentDistribution->logClaimPayment(
                    $contract,
                    $contractClaim,
                    $payment,
                    $payload['amount'],
                    $payload['bank_account_id'] ?? null,
                    $payload['safe_id'] ?? null,
                    $payload['notes'] ?? null
                );
            }

            $contractClaim->refresh();

            $this->syncClaimSettlementStatus($contractClaim);

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
        $contract = $claim->contract()->with([
            'customer',
            'guarantor',
            'installments',
            'claims' => fn ($query) => $query->with('payments'),
        ])->first();

        if (! $contract) {
            return;
        }

        $contractOutstanding = $this->calculateContractOutstanding($contract);
        $outstandingCleared = $contractOutstanding <= 0.009;

        if ($this->isClaimSettled($claim)) {
            if ($outstandingCleared) {
                $this->updateContractStatusToFinishedWithClaim($contract);
            } else {
                $this->updateContractStatusToRaised($contract);
            }
        } elseif ($this->isClaimPartiallyPaid($claim)) {
            $claimCoverage = round((float) $claim->paid_amount + (float) $claim->discount_amount, 2);

            if ($outstandingCleared || $claimCoverage >= $contractOutstanding) {
                $this->updateContractStatusToFinishedWithClaim($contract);
            } else {
                $this->updateContractStatusToRaised($contract);
            }
        } elseif ($this->isClaimAccepted($claim)) {
            $this->updateContractStatusToRaised($contract);
        } elseif ($this->shouldRestoreContractStatus($contract, $claim)) {
            $this->restoreContractStatus($contract);
        } else {
            $this->updateContractStatus($contract);
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
        $raisedStatusId = $this->raisedContractStatusId();

        $currentStatusId = (int) ($contract->contract_status_id ?? 0);

        if (! $statusId || $currentStatusId === (int) $statusId) {
            return;
        }

        if ($raisedStatusId && $currentStatusId === (int) $raisedStatusId) {
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

    private function updateContractStatusToFinishedWithClaim(Contract $contract): void
    {
        $statusId = $this->finishedWithClaimContractStatusId();

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

    private function isClaimRejected(ContractClaim $claim): bool
    {
        $statusId = $this->rejectedClaimStatusId();

        return $statusId !== null && (int) $claim->claim_status_id === $statusId;
    }

    private function shouldRestoreContractStatus(Contract $contract, ContractClaim $claim): bool
    {
        if (! $this->isClaimRejected($claim)) {
            return false;
        }

        $eligibleStatuses = array_filter([
            $this->contractClaimStatusId(),
            $this->raisedContractStatusId(),
            $this->finishedWithClaimContractStatusId(),
        ]);

        if (empty($eligibleStatuses)) {
            return false;
        }

        if (! in_array((int) ($contract->contract_status_id ?? 0), $eligibleStatuses, true)) {
            return false;
        }

        return ! $this->contractHasNonRejectedClaims($contract, $claim);
    }

    private function restoreContractStatus(Contract $contract): void
    {
        $this->contractStatusRefresher->refreshContract($contract, true);
    }

    private function contractHasNonRejectedClaims(Contract $contract, ContractClaim $excludedClaim): bool
    {
        $contract->loadMissing('claims');

        foreach ($contract->claims as $contractClaim) {
            if ((int) $contractClaim->id === (int) $excludedClaim->id) {
                continue;
            }

            if (! $this->isClaimRejected($contractClaim)) {
                return true;
            }
        }

        return false;
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

    private function finishedWithClaimContractStatusId(): ?int
    {
        if ($this->finishedWithClaimContractStatusId === null) {
            $id = ContractStatus::where('name', 'منتهي بمطالبة')->value('id');
            $this->finishedWithClaimContractStatusId = $id ? (int) $id : 0;
        }

        return $this->finishedWithClaimContractStatusId ?: null;
    }

    private function acceptedClaimStatusId(): ?int
    {
        if ($this->acceptedClaimStatusId === null) {
            $id = ClaimStatus::where('name', 'مقبول')->value('id');
            $this->acceptedClaimStatusId = $id ? (int) $id : 0;
        }

        return $this->acceptedClaimStatusId ?: null;
    }

    private function rejectedClaimStatusId(): ?int
    {
        if ($this->rejectedClaimStatusId === null) {
            $id = ClaimStatus::where('name', 'مرفوض')->value('id');
            $this->rejectedClaimStatusId = $id ? (int) $id : 0;
        }

        return $this->rejectedClaimStatusId ?: null;
    }

    private function paidWithDiscountClaimStatusId(): ?int
    {
        if ($this->paidWithDiscountClaimStatusId === null) {
            $this->paidWithDiscountClaimStatusId = $this->resolveClaimStatusId([
                'مدفوع بخصم',
                'مسدد بخصم',
            ]);
        }

        return $this->paidWithDiscountClaimStatusId ?: null;
    }

    private function partialPaidClaimStatusId(): ?int
    {
        if ($this->partialPaidClaimStatusId === null) {
            $this->partialPaidClaimStatusId = $this->resolveClaimStatusId([
                'مدفوع جزئي',
                'مدفوع جزئياً',
                'مدفوع جزئيا',
            ]);
        }

        return $this->partialPaidClaimStatusId ?: null;
    }

    private function paidInFullClaimStatusId(): ?int
    {
        if ($this->paidInFullClaimStatusId === null) {
            $this->paidInFullClaimStatusId = $this->resolveClaimStatusId([
                'مدفوع كامل',
                'مسدد كامل',
                'مدفوع بالكامل',
            ]);
        }

        return $this->paidInFullClaimStatusId ?: null;
    }

    private function defaultClaimStatusId(): ?int
    {
        if ($this->defaultClaimStatusId === null) {
            $id = ClaimStatus::where('name', 'قيد المراجعة')->value('id');
            $this->defaultClaimStatusId = $id ? (int) $id : 0;
        }

        return $this->defaultClaimStatusId ?: null;
    }

    private function isClaimSettled(ContractClaim $claim): bool
    {
        $settledStatusIds = array_filter([
            $this->paidWithDiscountClaimStatusId(),
            $this->paidInFullClaimStatusId(),
        ]);

        if (empty($settledStatusIds) || ! in_array((int) $claim->claim_status_id, $settledStatusIds, true)) {
            return false;
        }

        $remainingAmount = round((float) $claim->remaining_amount, 2);

        if ($remainingAmount > 0.009) {
            return false;
        }

        $paidAmount = round((float) $claim->paid_amount, 2);
        $discountAmount = round((float) $claim->discount_amount, 2);

        return $paidAmount > 0 || $discountAmount > 0;
    }

    private function isClaimPartiallyPaid(ContractClaim $claim): bool
    {
        $statusId = $this->partialPaidClaimStatusId();

        return $statusId !== null && (int) $claim->claim_status_id === $statusId;
    }

    private function calculateContractOutstanding(Contract $contract): float
    {
        return $contract->outstandingAmount();
    }

    private function resolveClaimStatusId(array $names): int
    {
        $statuses = ClaimStatus::query()
            ->where(function ($query) use ($names) {
                foreach ($names as $name) {
                    $query->orWhere('name', $name);
                }
            })
            ->get(['id', 'name']);

        foreach ($names as $name) {
            $match = $statuses->firstWhere('name', $name);
            if ($match) {
                return (int) $match->id;
            }
        }

        return 0;
    }

    private function syncClaimSettlementStatus(ContractClaim $claim): void
    {
        $discountAmount = round((float) $claim->discount_amount, 2);
        $paidAmount = round((float) $claim->paid_amount, 2);
        $remainingAmount = round((float) $claim->remaining_amount, 2);

        $targetStatusId = null;

        if ($discountAmount > 0) {
            $statusId = $this->paidWithDiscountClaimStatusId();
            if ($statusId) {
                $targetStatusId = $statusId;
            }
        } elseif ($paidAmount > 0 && $remainingAmount <= 0.009) {
            $statusId = $this->paidInFullClaimStatusId();
            if ($statusId) {
                $targetStatusId = $statusId;
            }
        } elseif ($paidAmount > 0 && $remainingAmount > 0.009) {
            $statusId = $this->partialPaidClaimStatusId();
            if ($statusId) {
                $targetStatusId = $statusId;
            }
        }

        if (! $targetStatusId || (int) $claim->claim_status_id === $targetStatusId) {
            return;
        }

        $claim->claim_status_id = $targetStatusId;
        $claim->save();
    }
}
