@php
    use Modules\Contracts\Entities\ContractClaim;

    $claimsCollection = $contract->claims ?? collect();
    $claimsCollection = $claimsCollection->values();
    $claimStatusesCollection = collect($claimStatuses ?? [])->values();
    $claimPayersCollection = collect($claimPayers ?? [])->values();
    $banksCollection = collect($banks ?? [])->values();
    $safesCollection = collect($safes ?? [])->values();
    $changeStatusOptionsCollection = collect($changeStatusOptions ?? [])->values();
    $paidWithDiscountClaimStatusId = $paidWithDiscountClaimStatusId ? (int) $paidWithDiscountClaimStatusId : null;
    $partialPaidStatusNames = ['مدفوع جزئي', 'مدفوع جزئياً', 'مدفوع جزئيا'];

    $availablePartyOptions = [];

    if ($contract->customer) {
        $availablePartyOptions[ContractClaim::FILED_PARTY_CUSTOMER] = [
            'name' => $contract->customer->name,
            'label' => __('contracts::claims.party_role_customer'),
        ];
    }

    if ($contract->guarantor) {
        $availablePartyOptions[ContractClaim::FILED_PARTY_GUARANTOR] = [
            'name' => $contract->guarantor->name,
            'label' => __('contracts::claims.party_role_guarantor'),
        ];
    }

    $selectedRole = old('filed_party_role');
    if (! $selectedRole && count($availablePartyOptions) === 1) {
        $selectedRole = array_key_first($availablePartyOptions);
    }

    $availableClaimants = collect($claimants ?? [])->values();
    $selectedClaimantId = old('claimant_id');

    $todayDate = now()->toDateString();
    $defaultClaimDate = old('claim_date', $todayDate);
    $oldPaymentClaimId = (string) old('payment_claim_id');
    $oldDiscountClaimId = (string) old('discount_claim_id');
    $shouldReopenModal = $errors->has('contract_id')
        || $errors->has('filed_party_role')
        || $errors->has('claim_amount')
        || $errors->has('claim_date')
        || $errors->has('document_number')
        || $errors->has('claimant_id');
@endphp

<div class="card shadow-sm mb-4">
    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
        <strong>{{ __('contracts::claims.claims') }}</strong>
        <x-button variant="dark" size="sm" class="text-light" data-bs-toggle="modal" data-bs-target="#addClaimModal">
            <i class="bi bi-plus-lg"></i> {{ __('contracts::claims.add_claim') }}
        </x-button>
    </div>
    <div class="card-body p-0">
        @if ($claimsCollection->isNotEmpty())
            <x-table head-class="table-light" striped>
                <x-slot name="head">
                    <tr>
                        <th style="width: 60px;" class="text-center">#</th>
                        <th>{{ __('contracts::claims.claim_date') }}</th>
                        <th>{{ __('contracts::claims.document_number') }}</th>
                        <th>{{ __('contracts::claims.claim_status') }}</th>
                        <th>{{ __('contracts::claims.claimant') }}</th>
                        <th>{{ __('contracts::claims.filed_party_role') }}</th>
                        <th class="text-end">{{ __('contracts::claims.claim_amount') }}</th>
                        <th class="text-end">{{ __('contracts::claims.claim_paid_total') }}</th>
                        <th class="text-end">{{ __('contracts::claims.claim_remaining_amount') }}</th>
                        <th class="text-center">{{ __('contracts::claims.actions') }}</th>
                    </tr>
                </x-slot>
                @foreach ($claimsCollection as $index => $claim)
                    @php($payments = collect($claim->payments ?? [])->values())
                    @php($totalPaid = (float) ($claim->paid_amount ?? $payments->sum('amount')))
                    @php($remainingAmount = (float) ($claim->remaining_amount ?? 0))
                    @php($discountAmountValue = (float) ($claim->discount_amount ?? 0))
                    @php($modalId = 'changeClaimStatusModal-contract-' . $contract->id . '-' . $claim->id)
                    @php($paymentModalId = 'recordClaimPaymentModal-contract-' . $contract->id . '-' . $claim->id)
                    @php($discountModalId = 'applyClaimDiscountModal-contract-' . $contract->id . '-' . $claim->id)
                    @php($paymentsRowId = 'claim-payments-contract-' . $contract->id . '-' . $claim->id)
                    @php($currentClaimStatus = (string) optional($claim->claimStatus)->name)
                    @php($isPartialPaidStatus = in_array($currentClaimStatus, $partialPaidStatusNames, true))
                    @php($isPaidStatus = ! $isPartialPaidStatus && (str_contains($currentClaimStatus, 'مدفوع') || str_contains($currentClaimStatus, 'مسدد')))
                    @php($isUnderReviewStatus = $currentClaimStatus === 'قيد المراجعة')
                    @php($isRejectedStatus = $currentClaimStatus === 'مرفوض')
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ optional($claim->claim_date)->format('Y-m-d') ?? '—' }}</td>
                        <td>{{ $claim->document_number }}</td>
                        <td>{{ optional($claim->claimStatus)->name ?? '—' }}</td>
                        <td>{{ optional($claim->claimant)->name ?? '—' }}</td>
                        <td>
                            <div>{{ $claim->filed_party_name ?? '—' }}</div>
                            @if ($claim->filed_party_role)
                                <div class="text-muted small">
                                    {{ __('contracts::claims.party_role_' . $claim->filed_party_role) }}
                                </div>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format((float) $claim->claim_amount, 2) }}</td>
                        <td class="text-end">{{ number_format($totalPaid, 2) }}</td>
                        <td class="text-end">{{ number_format($remainingAmount, 2) }}</td>
                        <td class="text-center">
                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                <x-button type="button" variant="secondary" :outline="true" size="sm" class="collapsed" data-bs-toggle="collapse" data-bs-target="#{{ $paymentsRowId }}" aria-expanded="false" aria-controls="{{ $paymentsRowId }}">
                                    {{ __('contracts::claims.view_payments') }}
                                </x-button>
                
                                @unless ($isPaidStatus)
                                    @if ($isUnderReviewStatus)
                                        <x-button type="button" variant="primary" :outline="true" size="sm" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}" @if ($changeStatusOptionsCollection->isEmpty()) disabled @endif>
                                            {{ __('contracts::claims.change_status') }}
                                        </x-button>
                                    @endif
                
                                    @if ($isRejectedStatus)
                                        <form action="{{ route('contract-claims.reopen', $claim) }}" method="post">
                                            @csrf
                                            @method('patch')
                                            <input type="hidden" name="return_to_contract" value="1">
                                            <x-button type="submit" variant="warning" :outline="true" size="sm">
                                                {{ __('contracts::claims.reopen_claim') }}
                                            </x-button>
                                        </form>
                                    @endif
                
                                    @if (! $isUnderReviewStatus && ! $isRejectedStatus)
                                        <x-button type="button" variant="dark" :outline="true" size="sm" data-bs-toggle="modal" data-bs-target="#{{ $paymentModalId }}" @if ($claimPayersCollection->isEmpty() || $remainingAmount <= 0) disabled @endif>
                                            {{ __('contracts::claims.record_payment') }}
                                        </x-button>
                
                                        <x-button type="button" variant="success" :outline="true" size="sm" data-bs-toggle="modal" data-bs-target="#{{ $discountModalId }}" @if (empty($paidWithDiscountClaimStatusId)) disabled @endif>
                                            {{ __('contracts::claims.pay_with_discount') }}
                                        </x-button>
                                    @endif
                                @endunless
                            </div>
                        </td>
                    </tr>
                    <tr class="table-light">
                        <td colspan="10" class="text-start">
                            <div class="collapse" id="{{ $paymentsRowId }}">
                                <div class="px-3 py-2">
                                    <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                        <div class="fw-semibold text-muted">{{ __('contracts::claims.payments') }}</div>
                                        <div class="d-flex flex-wrap gap-2 small">
                                            <span class="badge bg-light text-dark border">{{ __('contracts::claims.claim_amount') }}: {{ number_format((float) $claim->claim_amount, 2) }}</span>
                                            @if ($discountAmountValue > 0)
                                                <span class="badge bg-light text-dark border">{{ __('contracts::claims.claim_discount_badge') }}: {{ number_format($discountAmountValue, 2) }}</span>
                                            @endif
                                            <span class="badge bg-light text-dark border">{{ __('contracts::claims.claim_paid_total') }}: {{ number_format($totalPaid, 2) }}</span>
                                            <span class="badge {{ $remainingAmount > 0 ? 'bg-warning text-dark' : 'bg-success' }}">{{ __('contracts::claims.claim_remaining_amount') }}: {{ number_format($remainingAmount, 2) }}</span>
                                        </div>
                                    </div>
                                    @if ($payments->isNotEmpty())
                                        <x-table small bordered head-class="table-secondary">
                                            <x-slot name="head">
                                                <tr>
                                                    <th style="width: 60px;" class="text-center">#</th>
                                                    <th>{{ __('contracts::claims.claim_payer') }}</th>
                                                    <th class="text-end">{{ __('contracts::claims.claim_payment_amount') }}</th>
                                                    <th>{{ __('contracts::claims.claim_payment_date') }}</th>
                                                </tr>
                                            </x-slot>

                                            @foreach ($payments as $paymentIndex => $payment)
                                                <tr>
                                                    <td class="text-center">{{ $paymentIndex + 1 }}</td>
                                                    <td>{{ optional($payment->claimPayer)->name ?? '—' }}</td>
                                                    <td class="text-end">{{ number_format((float) $payment->amount, 2) }}</td>
                                                    <td>{{ optional($payment->paid_at)->format('Y-m-d') ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </x-table>
                                    @else
                                                <div class="text-muted small">{{ __('contracts::claims.no_payments') }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-3 text-muted">{{ __('contracts::claims.no_results') }}</div>
        @endif
    </div>
</div>

<div class="modal fade" id="addClaimModal" tabindex="-1" aria-labelledby="addClaimModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('contract-claims.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <input type="hidden" name="contract_id" value="{{ $contract->id }}">
                <input type="hidden" name="return_to_contract" value="1">

                <div class="modal-header">
                    <h5 class="modal-title" id="addClaimModalLabel">{{ __('contracts::claims.add_claim') }}</h5>
                    <x-button type="button" :unstyled="true" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></x-button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('contracts::claims.contract') }}</label>
                        <div class="form-control bg-light">{{ $contract->contract_number ?? ('#' . $contract->id) }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('contracts::claims.claimant') }}</label>
                        <select name="claimant_id" class="form-select" @if ($availableClaimants->isEmpty()) disabled @endif>
                            <option value="">{{ __('contracts::claims.choose_claimant') }}</option>
                            @foreach ($availableClaimants as $claimant)
                                <option value="{{ $claimant->id }}" @selected((string) $selectedClaimantId === (string) $claimant->id)>
                                    {{ $claimant->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($availableClaimants->isEmpty())
                            <div class="text-danger small mt-1">
                                {{ __('contracts::claims.no_claimants') }}
                            </div>
                        @endif
                        @error('claimant_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('contracts::claims.filed_party_role') }}</label>
                        <select name="filed_party_role" class="form-select" @if (empty($availablePartyOptions)) disabled @endif required>
                            <option value="">{{ __('contracts::claims.choose_filed_party') }}</option>
                            @foreach ($availablePartyOptions as $role => $info)
                                <option value="{{ $role }}" @selected($selectedRole === $role)>
                                    {{ $info['name'] }} ({{ $info['label'] }})
                                </option>
                            @endforeach
                        </select>
                        @if (empty($availablePartyOptions))
                            <div class="text-danger small mt-1">
                                {{ __('contracts::claims.validation_missing_customer') }}
                            </div>
                        @endif
                        @error('filed_party_role')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('contracts::claims.claim_amount') }}</label>
                        <input type="number" name="claim_amount" class="form-control" step="0.01" min="0" required value="{{ old('claim_amount') }}">
                        @error('claim_amount')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('contracts::claims.claim_date') }}</label>
                        <input type="text" name="claim_date" class="form-control js-date" required value="{{ $defaultClaimDate }}">
                        @error('claim_date')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('contracts::claims.document_number') }}</label>
                        <input type="text" name="document_number" class="form-control" maxlength="255" required value="{{ old('document_number') }}">
                        @error('document_number')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <x-button type="button" variant="secondary" :outline="true" data-bs-dismiss="modal">{{ __('contracts::claims.back') }}</x-button>
                    <x-button type="submit" variant="success" :outline="true">
                        <i class="bi bi-check2-circle me-1"></i> {{ __('contracts::claims.save') }}
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    @if ($shouldReopenModal)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalElement = document.getElementById('addClaimModal');
                if (modalElement) {
                    var modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
                    modalInstance.show();
                }
            });
        </script>
    @endif
    @if ($changeStatusOptionsCollection->isNotEmpty())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var claimStatusModals = document.querySelectorAll('[id^="changeClaimStatusModal-contract-"]');
                claimStatusModals.forEach(function (modalEl) {
                    modalEl.addEventListener('shown.bs.modal', function () {
                        var select = modalEl.querySelector('select[name="claim_status_id"]');
                        if (select) {
                            select.focus();
                        }
                    });
                });
            });
        </script>
    @endif
    @if ($oldPaymentClaimId)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var claimId = "{{ $oldPaymentClaimId }}";
                if (!claimId) {
                    return;
                }

                var modalElement = document.getElementById('recordClaimPaymentModal-contract-{{ $contract->id }}-' + claimId);
                if (modalElement) {
                    var modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
                    modalInstance.show();
                }
            });
        </script>
    @endif
    @if ($oldDiscountClaimId)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var claimId = "{{ $oldDiscountClaimId }}";
                if (!claimId) {
                    return;
                }

                var modalElement = document.getElementById('applyClaimDiscountModal-contract-{{ $contract->id }}-' + claimId);
                if (modalElement) {
                    var modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
                    modalInstance.show();
                }
            });
        </script>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var pickers = document.querySelectorAll('[data-claim-account-picker]');

            pickers.forEach(function (picker) {
                var bankInput = picker.dataset.bankInput ? document.getElementById(picker.dataset.bankInput) : null;
                var safeInput = picker.dataset.safeInput ? document.getElementById(picker.dataset.safeInput) : null;

                var sync = function () {
                    if (bankInput) bankInput.value = '';
                    if (safeInput) safeInput.value = '';

                    var value = picker.value || '';
                    if (!value) {
                        return;
                    }

                    var parts = value.split(':');
                    if (parts.length !== 2) {
                        return;
                    }

                    if (parts[0] === 'bank' && bankInput) {
                        bankInput.value = parts[1];
                    }

                    if (parts[0] === 'safe' && safeInput) {
                        safeInput.value = parts[1];
                    }
                };

                picker.addEventListener('change', sync);
                sync();
            });
        });
    </script>
@endpush

@if ($changeStatusOptionsCollection->isNotEmpty())
    @foreach ($claimsCollection as $claim)
        @php($currentClaimStatus = (string) optional($claim->claimStatus)->name)
        @php($isPartialPaidStatus = in_array($currentClaimStatus, $partialPaidStatusNames, true))
        @php($isPaidStatus = ! $isPartialPaidStatus && (str_contains($currentClaimStatus, 'مدفوع') || str_contains($currentClaimStatus, 'مسدد')))
        @php($isUnderReviewStatus = $currentClaimStatus === 'قيد المراجعة')
        @if ($isPaidStatus || ! $isUnderReviewStatus)
            @continue
        @endif
        @php($modalId = 'changeClaimStatusModal-contract-' . $contract->id . '-' . $claim->id)
        @php($labelId = $modalId . 'Label')
        <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $labelId }}" aria-hidden="true">
            <div class="modal-dialog">
                <form action="{{ route('contract-claims.update-status', $claim) }}" method="post" class="modal-content">
                    @csrf
                    @method('patch')
                    <input type="hidden" name="return_to_contract" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title" id="{{ $labelId }}">{{ __('contracts::claims.change_status') }}</h5>
                        <x-button type="button" :unstyled="true" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></x-button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3 text-start">
                            <label for="claim-status-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.claim_status') }}</label>
                            <select name="claim_status_id" id="claim-status-{{ $claim->id }}" class="form-select" required>
                                <option value="">{{ __('contracts::claims.choose_claim_status') }}</option>
                                @foreach ($changeStatusOptionsCollection as $status)
                                    <option value="{{ $status->id }}" @selected((string) old('claim_status_id', $claim->claim_status_id) === (string) $status->id)>{{ $status->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <x-button type="button" variant="secondary" :outline="true" data-bs-dismiss="modal">{{ __('contracts::claims.back') }}</x-button>
                        <x-button type="submit" variant="primary" :outline="true">
                            <i class="bi bi-save2 me-1"></i> {{ __('contracts::claims.update_status') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endif

@foreach ($claimsCollection as $claim)
    @php($paymentModalId = 'recordClaimPaymentModal-contract-' . $contract->id . '-' . $claim->id)
    @php($paymentLabelId = $paymentModalId . 'Label')
    @php($isCurrentPaymentClaim = $oldPaymentClaimId === (string) $claim->id)
    @php($oldPaymentPayer = $isCurrentPaymentClaim ? old('claim_payer_id') : null)
    @php($oldPaymentAmount = $isCurrentPaymentClaim ? old('amount') : null)
    @php($oldPaymentDate = $isCurrentPaymentClaim ? old('paid_at') : null)
    @php($oldPaymentBank = $isCurrentPaymentClaim ? old('bank_account_id') : null)
    @php($oldPaymentSafe = $isCurrentPaymentClaim ? old('safe_id') : null)
    @php($oldPaymentNotes = $isCurrentPaymentClaim ? old('notes') : null)
    @php($remainingAmount = (float) ($claim->remaining_amount ?? 0))
    @php($maxPaymentAmount = number_format($remainingAmount, 2, '.', ''))
    <div class="modal fade" id="{{ $paymentModalId }}" tabindex="-1" aria-labelledby="{{ $paymentLabelId }}" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('contract-claims.payments.store', $claim) }}" method="post" class="modal-content">
                @csrf
                <input type="hidden" name="return_to_contract" value="1">
                <input type="hidden" name="payment_claim_id" value="{{ $claim->id }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $paymentLabelId }}">{{ __('contracts::claims.record_payment') }}</h5>
                    <x-button type="button" :unstyled="true" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></x-button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 text-start">
                        <label for="claim-payment-payer-contract-{{ $contract->id }}-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.claim_payer') }}</label>
                        <select name="claim_payer_id"
                                id="claim-payment-payer-contract-{{ $contract->id }}-{{ $claim->id }}"
                                class="form-select"
                                required
                                @if ($claimPayersCollection->isEmpty()) disabled @endif>
                            <option value="">{{ __('contracts::claims.choose_claim_payer') }}</option>
                            @foreach ($claimPayersCollection as $payer)
                                <option value="{{ $payer->id }}" @selected((string) $oldPaymentPayer === (string) $payer->id)>{{ $payer->name }}</option>
                            @endforeach
                        </select>
                        @if ($claimPayersCollection->isEmpty())
                            <div class="text-danger small">{{ __('contracts::claims.no_claim_payers') }}</div>
                        @endif
                        @error('claim_payer_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 text-start">
                        <label for="claim-payment-amount-contract-{{ $contract->id }}-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.claim_payment_amount') }}</label>
                        <input type="number"
                               name="amount"
                               id="claim-payment-amount-contract-{{ $contract->id }}-{{ $claim->id }}"
                               class="form-control"
                               step="0.01"
                               min="0.01"
                               max="{{ $maxPaymentAmount }}"
                               required
                               value="{{ $oldPaymentAmount }}"
                               @if ($remainingAmount <= 0) disabled @endif>
                        @error('amount')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            {{ __('contracts::claims.claim_remaining_amount') }}: {{ number_format($remainingAmount, 2) }}
                        </div>
                    </div>

                    <div class="mb-3 text-start">
                        <label for="claim-payment-date-contract-{{ $contract->id }}-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.claim_payment_date') }}</label>
                        <input type="text"
                               name="paid_at"
                               id="claim-payment-date-contract-{{ $contract->id }}-{{ $claim->id }}"
                               class="form-control js-date"
                               required
                               value="{{ $oldPaymentDate ?? $todayDate }}">
                        @error('paid_at')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 text-start">
                        <label for="claim-payment-account-contract-{{ $contract->id }}-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.payment_account') }}</label>
                        @php($selectedAccount = $oldPaymentBank ? 'bank:' . $oldPaymentBank : ($oldPaymentSafe ? 'safe:' . $oldPaymentSafe : ''))
                        <select id="claim-payment-account-contract-{{ $contract->id }}-{{ $claim->id }}"
                                class="form-select"
                                data-claim-account-picker="1"
                                data-bank-input="claim-payment-bank-contract-{{ $contract->id }}-{{ $claim->id }}"
                                data-safe-input="claim-payment-safe-contract-{{ $contract->id }}-{{ $claim->id }}"
                                @if ($banksCollection->isEmpty() && $safesCollection->isEmpty()) disabled @endif>
                            <option value="" @selected($selectedAccount === '')>{{ __('contracts::claims.choose_payment_account') }}</option>
                            @if ($banksCollection->isNotEmpty())
                                <optgroup label="{{ __('contracts::claims.bank_accounts_label') }}">
                                    @foreach ($banksCollection as $bank)
                                        <option value="bank:{{ $bank->id }}" @selected($selectedAccount === 'bank:' . $bank->id)>{{ $bank->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if ($safesCollection->isNotEmpty())
                                <optgroup label="{{ __('contracts::claims.safes_label') }}">
                                    @foreach ($safesCollection as $safe)
                                        <option value="safe:{{ $safe->id }}" @selected($selectedAccount === 'safe:' . $safe->id)>{{ $safe->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        <input type="hidden" name="bank_account_id" id="claim-payment-bank-contract-{{ $contract->id }}-{{ $claim->id }}" value="{{ $oldPaymentBank }}">
                        <input type="hidden" name="safe_id" id="claim-payment-safe-contract-{{ $contract->id }}-{{ $claim->id }}" value="{{ $oldPaymentSafe }}">
                        <div class="form-text">{{ __('contracts::claims.payment_account_hint') }}</div>
                        @if ($banksCollection->isEmpty() && $safesCollection->isEmpty())
                            <div class="text-danger small">{{ __('contracts::claims.no_accounts_available') }}</div>
                        @endif
                        @error('bank_account_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                        @error('safe_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0 text-start">
                        <label for="claim-payment-notes-contract-{{ $contract->id }}-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.payment_notes') }}</label>
                        <textarea name="notes" id="claim-payment-notes-contract-{{ $contract->id }}-{{ $claim->id }}" class="form-control" rows="2">{{ $oldPaymentNotes }}</textarea>
                        <div class="form-text">{{ __('contracts::claims.payment_notes_hint') }}</div>
                        @error('notes')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <x-button type="button" variant="secondary" :outline="true" data-bs-dismiss="modal">{{ __('contracts::claims.back') }}</x-button>
                    <x-button type="submit" variant="dark" :outline="true" @if ($claimPayersCollection->isEmpty() || $remainingAmount <= 0) disabled @endif>{{ __('contracts::claims.record_payment') }}</x-button>
                </div>
            </form>
        </div>
    </div>
@endforeach

@foreach ($claimsCollection as $claim)
    @php($discountModalId = 'applyClaimDiscountModal-contract-' . $contract->id . '-' . $claim->id)
    @php($discountLabelId = $discountModalId . 'Label')
    @php($isCurrentDiscountClaim = $oldDiscountClaimId === (string) $claim->id)
    @php($oldDiscountAmountInput = $isCurrentDiscountClaim ? old('discount_amount') : null)
    @php($oldDiscountPayer = $isCurrentDiscountClaim ? old('claim_payer_id') : null)
    @php($oldDiscountDate = $isCurrentDiscountClaim ? old('paid_at') : null)
    @php($oldDiscountBank = $isCurrentDiscountClaim ? old('bank_account_id') : null)
    @php($oldDiscountSafe = $isCurrentDiscountClaim ? old('safe_id') : null)
    @php($oldDiscountNotes = $isCurrentDiscountClaim ? old('notes') : null)
    <div class="modal fade" id="{{ $discountModalId }}" tabindex="-1" aria-labelledby="{{ $discountLabelId }}" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('contract-claims.apply-discount', $claim) }}" method="post" class="modal-content">
                @csrf
                @method('patch')
                <input type="hidden" name="return_to_contract" value="1">
                <input type="hidden" name="discount_claim_id" value="{{ $claim->id }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $discountLabelId }}">{{ __('contracts::claims.apply_discount') }}</h5>
                    <x-button type="button" :unstyled="true" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></x-button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 text-start">
                        <label for="claim-discount-{{ $contract->id }}-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.discount_amount') }}</label>
                        <input type="number"
                               name="discount_amount"
                               id="claim-discount-{{ $contract->id }}-{{ $claim->id }}"
                               class="form-control"
                               step="0.01"
                               min="0"
                               required
                               value="{{ $oldDiscountAmountInput ?? $claim->discount_amount }}">
                        @error('discount_amount')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                        <div class="form-text">{{ __('contracts::claims.discount_payment_hint') }}</div>
                    </div>

                    <div class="mb-3 text-start">
                        <label for="claim-discount-payer-{{ $contract->id }}-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.claim_payer') }}</label>
                        <select name="claim_payer_id"
                                id="claim-discount-payer-{{ $contract->id }}-{{ $claim->id }}"
                                class="form-select"
                                @if ($claimPayersCollection->isEmpty()) disabled @endif>
                            <option value="">{{ __('contracts::claims.choose_claim_payer') }}</option>
                            @foreach ($claimPayersCollection as $payer)
                                <option value="{{ $payer->id }}" @selected((string) $oldDiscountPayer === (string) $payer->id)>{{ $payer->name }}</option>
                            @endforeach
                        </select>
                        @if ($claimPayersCollection->isEmpty())
                            <div class="text-danger small">{{ __('contracts::claims.no_claim_payers') }}</div>
                        @endif
                        @error('claim_payer_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 text-start">
                        <label for="claim-discount-date-{{ $contract->id }}-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.claim_payment_date') }}</label>
                        <input type="text"
                               name="paid_at"
                               id="claim-discount-date-{{ $contract->id }}-{{ $claim->id }}"
                               class="form-control js-date"
                               value="{{ $oldDiscountDate ?? $todayDate }}">
                        @error('paid_at')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 text-start">
                        <label for="claim-discount-account-{{ $contract->id }}-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.payment_account') }}</label>
                        @php($selectedDiscountAccount = $oldDiscountBank ? 'bank:' . $oldDiscountBank : ($oldDiscountSafe ? 'safe:' . $oldDiscountSafe : ''))
                        <select id="claim-discount-account-{{ $contract->id }}-{{ $claim->id }}"
                                class="form-select"
                                data-claim-account-picker="1"
                                data-bank-input="claim-discount-bank-contract-{{ $contract->id }}-{{ $claim->id }}"
                                data-safe-input="claim-discount-safe-contract-{{ $contract->id }}-{{ $claim->id }}"
                                @if ($banksCollection->isEmpty() && $safesCollection->isEmpty()) disabled @endif>
                            <option value="" @selected($selectedDiscountAccount === '')>{{ __('contracts::claims.choose_payment_account') }}</option>
                            @if ($banksCollection->isNotEmpty())
                                <optgroup label="{{ __('contracts::claims.bank_accounts_label') }}">
                                    @foreach ($banksCollection as $bank)
                                        <option value="bank:{{ $bank->id }}" @selected($selectedDiscountAccount === 'bank:' . $bank->id)>{{ $bank->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if ($safesCollection->isNotEmpty())
                                <optgroup label="{{ __('contracts::claims.safes_label') }}">
                                    @foreach ($safesCollection as $safe)
                                        <option value="safe:{{ $safe->id }}" @selected($selectedDiscountAccount === 'safe:' . $safe->id)>{{ $safe->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        <input type="hidden" name="bank_account_id" id="claim-discount-bank-contract-{{ $contract->id }}-{{ $claim->id }}" value="{{ $oldDiscountBank }}">
                        <input type="hidden" name="safe_id" id="claim-discount-safe-contract-{{ $contract->id }}-{{ $claim->id }}" value="{{ $oldDiscountSafe }}">
                        <div class="form-text">{{ __('contracts::claims.payment_account_hint') }}</div>
                        @if ($banksCollection->isEmpty() && $safesCollection->isEmpty())
                            <div class="text-danger small">{{ __('contracts::claims.no_accounts_available') }}</div>
                        @endif
                        @error('bank_account_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                        @error('safe_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0 text-start">
                        <label for="claim-discount-notes-contract-{{ $contract->id }}-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.payment_notes') }}</label>
                        <textarea name="notes" id="claim-discount-notes-contract-{{ $contract->id }}-{{ $claim->id }}" class="form-control" rows="2">{{ $oldDiscountNotes }}</textarea>
                        <div class="form-text">{{ __('contracts::claims.payment_notes_hint') }}</div>
                        @error('notes')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <x-button type="button" variant="secondary" :outline="true" data-bs-dismiss="modal">{{ __('contracts::claims.back') }}</x-button>
                    <x-button type="submit" variant="success">{{ __('contracts::claims.apply_discount') }}</x-button>
                </div>
            </form>
        </div>
    </div>
@endforeach
