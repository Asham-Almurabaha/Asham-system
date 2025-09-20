@extends('layouts.master')

@section('title', __('contracts::claims.claims_list'))

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ __('contracts::claims.claims_list') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('contracts.index') }}">{{ __('contracts::contracts.Contracts') }}</a></li>
            <li class="breadcrumb-item active">{{ __('contracts::claims.claims') }}</li>
        </ol>
    </nav>
</div>


<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px">#</th>
                        <th>{{ __('contracts::claims.contract_number') }}</th>
                        <th>{{ __('contracts::claims.claimant') }}</th>
                        <th>{{ __('contracts::claims.filed_party_role') }}</th>
                        <th>{{ __('contracts::claims.claim_amount') }}</th>
                        <th>{{ __('contracts::claims.claim_paid_total') }}</th>
                        <th>{{ __('contracts::claims.claim_remaining_amount') }}</th>
                        <th>{{ __('contracts::claims.claim_date') }}</th>
                        <th>{{ __('contracts::claims.document_number') }}</th>
                        <th>{{ __('contracts::claims.claim_status') }}</th>
                        <th>{{ __('contracts::claims.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @php($oldPaymentClaimId = (string) old('payment_claim_id'))
                @php($banksCollection = collect($banks ?? [])->values())
                @php($safesCollection = collect($safes ?? [])->values())
                @forelse ($claims as $claim)
                    @php($payments = collect($claim->payments ?? [])->values())
                    @php($totalPaid = (float) ($claim->paid_amount ?? $payments->sum('amount')))
                    @php($remainingAmount = (float) ($claim->remaining_amount ?? 0))
                    @php($currentClaimStatus = (string) optional($claim->claimStatus)->name)
                    @php($isPaidStatus = str_contains($currentClaimStatus, 'مدفوع'))
                    @php($isUnderReviewStatus = $currentClaimStatus === 'قيد المراجعة')
                    @php($isRejectedStatus = $currentClaimStatus === 'مرفوض')
                    @php($modalId = 'changeClaimStatusModal-' . $claim->id)
                    @php($discountModalId = 'applyClaimDiscountModal-' . $claim->id)
                    @php($paymentModalId = 'recordClaimPaymentModal-' . $claim->id)
                    @php($paymentsRowId = 'claim-payments-' . $claim->id)
                    @php($isCurrentPaymentClaim = $oldPaymentClaimId === (string) $claim->id)
                    @php($oldPaymentPayer = $isCurrentPaymentClaim ? old('claim_payer_id') : null)
                    @php($oldPaymentAmount = $isCurrentPaymentClaim ? old('amount') : null)
                    @php($oldPaymentDate = $isCurrentPaymentClaim ? old('paid_at') : null)
                    @php($oldPaymentBank = $isCurrentPaymentClaim ? old('bank_account_id') : null)
                    @php($oldPaymentSafe = $isCurrentPaymentClaim ? old('safe_id') : null)
                    @php($oldPaymentNotes = $isCurrentPaymentClaim ? old('notes') : null)
                    <tr>
                        <td class="text-muted">{{ $loop->iteration + ($claims->currentPage() - 1) * $claims->perPage() }}</td>
                        <td class="text-start">
                            @if ($claim->contract)
                                <a href="{{ route('contracts.show', $claim->contract) }}" class="fw-bold text-dark text-decoration-none">
                                    {{ $claim->contract->contract_number }}
                                </a>
                            @else
                                {{ '#' . $claim->contract_id }}
                            @endif
                        </td>
                        <td class="text-start">{{ optional($claim->claimant)->name ?? '—' }}</td>
                        <td class="text-start">
                            <div>{{ $claim->filed_party_name ?? '—' }}</div>
                            @if ($claim->filed_party_role)
                                <div class="text-muted small">{{ __('contracts::claims.party_role_' . $claim->filed_party_role) }}</div>
                            @endif
                        </td>
                        <td>{{ number_format((float) $claim->claim_amount, 2) }}</td>
                        <td>{{ number_format($totalPaid, 2) }}</td>
                        <td>{{ number_format($remainingAmount, 2) }}</td>
                        <td>{{ optional($claim->claim_date)->format('Y-m-d') }}</td>
                        <td>{{ $claim->document_number }}</td>
                        <td class="text-start">{{ optional($claim->claimStatus)->name ?? '—' }}</td>
                        <td class="text-nowrap">
                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                <button type="button"
                                        class="btn btn-outline-secondary btn-sm collapsed"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#{{ $paymentsRowId }}"
                                        aria-expanded="false"
                                        aria-controls="{{ $paymentsRowId }}">
                                    {{ __('contracts::claims.view_payments') }}
                                </button>

                                @unless ($isPaidStatus)
                                    @if ($isUnderReviewStatus)
                                        <button type="button"
                                                class="btn btn-outline-primary btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#{{ $modalId }}"
                                                @if ($changeStatusOptions->isEmpty()) disabled @endif>
                                            {{ __('contracts::claims.change_status') }}
                                        </button>
                                    @endif

                                    @if ($isRejectedStatus)
                                        <form action="{{ route('contract-claims.reopen', $claim) }}" method="post">
                                            @csrf
                                            @method('patch')
                                            <button type="submit" class="btn btn-outline-warning btn-sm">
                                                {{ __('contracts::claims.reopen_claim') }}
                                            </button>
                                        </form>
                                    @endif

                                    @if (! $isUnderReviewStatus && ! $isRejectedStatus)
                                        <button type="button"
                                                class="btn btn-outline-dark btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#{{ $paymentModalId }}"
                                                @if ($claimPayers->isEmpty() || $remainingAmount <= 0) disabled @endif>
                                            {{ __('contracts::claims.record_payment') }}
                                        </button>

                                        <button type="button"
                                                class="btn btn-outline-success btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#{{ $discountModalId }}"
                                                @if (empty($paidWithDiscountClaimStatusId)) disabled @endif>
                                            {{ __('contracts::claims.pay_with_discount') }}
                                        </button>
                                    @endif
                                @endunless
                            </div>
                        </td>
                    </tr>
                    <tr class="table-light">
                        <td colspan="11" class="text-start">
                            <div class="collapse" id="{{ $paymentsRowId }}">
                                <div class="px-3 py-2">
                                    <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                        <div class="fw-semibold text-muted">{{ __('contracts::claims.payments') }}</div>
                                        <div class="d-flex flex-wrap gap-2 small">
                                            <span class="badge bg-light text-dark border">{{ __('contracts::claims.claim_amount') }}: {{ number_format((float) $claim->claim_amount, 2) }}</span>
                                            <span class="badge bg-light text-dark border">{{ __('contracts::claims.claim_paid_total') }}: {{ number_format($totalPaid, 2) }}</span>
                                            <span class="badge {{ $remainingAmount > 0 ? 'bg-warning text-dark' : 'bg-success' }}">{{ __('contracts::claims.claim_remaining_amount') }}: {{ number_format($remainingAmount, 2) }}</span>
                                        </div>
                                    </div>
                                    @if ($payments->isNotEmpty())
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered align-middle mb-0">
                                                <thead class="table-secondary">
                                                    <tr>
                                                        <th style="width: 60px;" class="text-center">#</th>
                                                        <th>{{ __('contracts::claims.claim_payer') }}</th>
                                                        <th class="text-end">{{ __('contracts::claims.claim_payment_amount') }}</th>
                                                        <th>{{ __('contracts::claims.claim_payment_date') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($payments as $index => $payment)
                                                        <tr>
                                                            <td class="text-center">{{ $index + 1 }}</td>
                                                            <td>{{ optional($payment->claimPayer)->name ?? '—' }}</td>
                                                            <td class="text-end">{{ number_format((float) $payment->amount, 2) }}</td>
                                                            <td>{{ optional($payment->paid_at)->format('Y-m-d') ?? '—' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-muted small">{{ __('contracts::claims.no_payments') }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="9" class="py-4">
                            <div class="text-muted">{{ __('contracts::claims.no_results') }}</div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($claims->hasPages())
        <div class="card-footer bg-white">
            {{ $claims->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

@if ($claimStatuses->isEmpty())
    <div class="alert alert-warning mt-3" role="alert">
        {{ __('contracts::claims.no_claim_statuses') }}
    </div>
@endif

@if ($claimStatuses->isEmpty())
    <div class="alert alert-warning mt-3" role="alert">
        {{ __('contracts::claims.no_claim_statuses') }}
    </div>
@endif

@if ($claimPayers->isEmpty())
    <div class="alert alert-warning mt-3" role="alert">
        {{ __('contracts::claims.no_claim_payers') }}
    </div>
@endif

@foreach ($claims as $claim)
    @php($modalId = 'changeClaimStatusModal-' . $claim->id)
    @php($labelId = $modalId . 'Label')
    @php($discountModalId = 'applyClaimDiscountModal-' . $claim->id)
    @php($discountLabelId = $discountModalId . 'Label')
    @php($paymentModalId = 'recordClaimPaymentModal-' . $claim->id)
    @php($paymentLabelId = $paymentModalId . 'Label')
    @php($currentClaimStatus = (string) optional($claim->claimStatus)->name)
    @php($isPaidStatus = str_contains($currentClaimStatus, 'مدفوع'))
    @php($isUnderReviewStatus = $currentClaimStatus === 'قيد المراجعة')
    @php($remainingAmount = (float) ($claim->remaining_amount ?? 0))
    @php($maxPaymentAmount = number_format($remainingAmount, 2, '.', ''))
    @if ($isPaidStatus)
        @continue
    @endif
    @if ($isUnderReviewStatus && $claimStatuses->isNotEmpty())
        <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $labelId }}" aria-hidden="true">
            <div class="modal-dialog">
                <form action="{{ route('contract-claims.update-status', $claim) }}" method="post" class="modal-content">
                    @csrf
                    @method('patch')
                    <div class="modal-header">
                        <h5 class="modal-title" id="{{ $labelId }}">{{ __('contracts::claims.change_status') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3 text-start">
                            <label for="claim-status-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.claim_status') }}</label>
                            <select name="claim_status_id" id="claim-status-{{ $claim->id }}" class="form-select" required>
                                <option value="">{{ __('contracts::claims.choose_claim_status') }}</option>
                                @foreach ($changeStatusOptions as $status)
                                    <option value="{{ $status->id }}" @selected((string) old('claim_status_id', $claim->claim_status_id) === (string) $status->id)>{{ $status->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('contracts::claims.back') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('contracts::claims.update_status') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="modal fade" id="{{ $paymentModalId }}" tabindex="-1" aria-labelledby="{{ $paymentLabelId }}" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('contract-claims.payments.store', $claim) }}" method="post" class="modal-content">
                @csrf
                <input type="hidden" name="payment_claim_id" value="{{ $claim->id }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $paymentLabelId }}">{{ __('contracts::claims.record_payment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                        <div class="mb-3 text-start">
                            <label for="claim-payment-payer-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.claim_payer') }}</label>
                            <select name="claim_payer_id" id="claim-payment-payer-{{ $claim->id }}" class="form-select" required @if ($claimPayers->isEmpty()) disabled @endif>
                                <option value="">{{ __('contracts::claims.choose_claim_payer') }}</option>
                                @foreach ($claimPayers as $payer)
                                    <option value="{{ $payer->id }}" @selected((string) $oldPaymentPayer === (string) $payer->id)>{{ $payer->name }}</option>
                                @endforeach
                            </select>
                            @if ($claimPayers->isEmpty())
                                <div class="text-danger small">{{ __('contracts::claims.no_claim_payers') }}</div>
                            @endif
                            @error('claim_payer_id')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 text-start">
                            <label for="claim-payment-amount-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.claim_payment_amount') }}</label>
                            <input type="number"
                                   name="amount"
                                   id="claim-payment-amount-{{ $claim->id }}"
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
                            <label for="claim-payment-date-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.claim_payment_date') }}</label>
                            <input type="text"
                                   name="paid_at"
                                   id="claim-payment-date-{{ $claim->id }}"
                                   class="form-control js-date"
                                   required
                                   value="{{ $oldPaymentDate ?? now()->toDateString() }}">
                            @error('paid_at')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 text-start">
                            <label for="claim-payment-account-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.payment_account') }}</label>
                            @php($selectedAccount = $oldPaymentBank ? 'bank:' . $oldPaymentBank : ($oldPaymentSafe ? 'safe:' . $oldPaymentSafe : ''))
                            <select id="claim-payment-account-{{ $claim->id }}"
                                    class="form-select"
                                    data-claim-account-picker="1"
                                    data-bank-input="claim-payment-bank-{{ $claim->id }}"
                                    data-safe-input="claim-payment-safe-{{ $claim->id }}"
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
                            <input type="hidden" name="bank_account_id" id="claim-payment-bank-{{ $claim->id }}" value="{{ $oldPaymentBank }}">
                            <input type="hidden" name="safe_id" id="claim-payment-safe-{{ $claim->id }}" value="{{ $oldPaymentSafe }}">
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
                            <label for="claim-payment-notes-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.payment_notes') }}</label>
                            <textarea name="notes" id="claim-payment-notes-{{ $claim->id }}" class="form-control" rows="2">{{ $oldPaymentNotes }}</textarea>
                            <div class="form-text">{{ __('contracts::claims.payment_notes_hint') }}</div>
                            @error('notes')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('contracts::claims.back') }}</button>
                        <button type="submit" class="btn btn-dark" @if ($claimPayers->isEmpty() || $remainingAmount <= 0) disabled @endif>{{ __('contracts::claims.record_payment') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="{{ $discountModalId }}" tabindex="-1" aria-labelledby="{{ $discountLabelId }}" aria-hidden="true">
            <div class="modal-dialog">
                <form action="{{ route('contract-claims.apply-discount', $claim) }}" method="post" class="modal-content">
                    @csrf
                    @method('patch')
                    <div class="modal-header">
                        <h5 class="modal-title" id="{{ $discountLabelId }}">{{ __('contracts::claims.apply_discount') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3 text-start">
                            <label for="claim-discount-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.discount_amount') }}</label>
                            <input type="number"
                                   name="discount_amount"
                                   id="claim-discount-{{ $claim->id }}"
                                   class="form-control"
                                   step="0.01"
                                   min="0"
                                   required
                                   value="{{ old('discount_amount', $claim->discount_amount) }}">
                            @error('discount_amount')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('contracts::claims.back') }}</button>
                        <button type="submit" class="btn btn-success">{{ __('contracts::claims.apply_discount') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

@push('scripts')
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

            var claimId = "{{ old('payment_claim_id') }}";
            if (!claimId) {
                return;
            }

            var modalElement = document.getElementById('recordClaimPaymentModal-' + claimId);
            if (modalElement) {
                var modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
                modalInstance.show();
            }
        });
    </script>
@endpush
@endsection
