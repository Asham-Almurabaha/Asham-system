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


<div class="card shadow-sm mb-3">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="fw-semibold">{{ __('contracts::claims.filters') }}</div>
        <div class="small text-muted">{{ __('contracts::claims.results_count', ['count' => $claims->total()]) }}</div>
    </div>
    <div class="card-body">
        <form action="{{ route('contract-claims.index') }}" method="GET" class="row gy-2 gx-2 align-items-end">
            <div class="col-12 col-md-4 col-lg-3">
                <label for="claim-filter-contract-number" class="form-label mb-1">{{ __('contracts::claims.contract_number') }}</label>
                <input id="claim-filter-contract-number"
                       type="text"
                       name="contract_number"
                       value="{{ request('contract_number') }}"
                       class="form-control form-control-sm"
                       placeholder="{{ __('contracts::claims.contract_number_placeholder') }}">
            </div>

            <div class="col-12 col-md-4 col-lg-3">
                <label for="claim-filter-filed-party" class="form-label mb-1">{{ __('contracts::claims.filed_party_role') }}</label>
                <select id="claim-filter-filed-party" name="filed_party_role" class="form-select form-select-sm">
                    <option value="">{{ __('contracts::claims.choose_filed_party') }}</option>
                    @foreach ($partyRoles as $role => $label)
                        <option value="{{ $role }}" @selected(request('filed_party_role') === $role)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-4 col-lg-3">
                <label for="claim-filter-status" class="form-label mb-1">{{ __('contracts::claims.claim_status') }}</label>
                <select id="claim-filter-status" name="claim_status_id" class="form-select form-select-sm">
                    <option value="">{{ __('contracts::claims.choose_claim_status') }}</option>
                    @foreach ($claimStatuses as $status)
                        <option value="{{ $status->id }}" @selected((string) request('claim_status_id') === (string) $status->id)>{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-lg-3 d-flex flex-column flex-sm-row gap-2">
                <x-button type="submit" variant="primary" size="sm" class="w-100">{{ __('contracts::claims.search') }}</x-button>
                <x-button href="{{ route('contract-claims.index') }}" variant="secondary" :outline="true" size="sm" class="w-100">{{ __('contracts::claims.clear') }}</x-button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <x-table head-class="table-light" class="text-center">
            <x-slot name="head">
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
            </x-slot>
            @php($oldPaymentClaimId = (string) old('payment_claim_id'))
            @php($oldDiscountClaimId = (string) old('discount_claim_id'))
            @php($banksCollection = collect($banks ?? [])->values())
            @php($safesCollection = collect($safes ?? [])->values())
            @php($partialPaidStatusNames = ['مدفوع جزئي', 'مدفوع جزئياً', 'مدفوع جزئيا'])
            @forelse ($claims as $claim)
                @php($payments = collect($claim->payments ?? [])->values())
                @php($totalPaid = (float) ($claim->paid_amount ?? $payments->sum('amount')))
                @php($remainingAmount = (float) ($claim->remaining_amount ?? 0))
                @php($discountAmountValue = (float) ($claim->discount_amount ?? 0))
                @php($currentClaimStatus = (string) optional($claim->claimStatus)->name)
                @php($isPartialPaidStatus = in_array($currentClaimStatus, $partialPaidStatusNames, true))
                @php($isPaidStatus = ! $isPartialPaidStatus && (str_contains($currentClaimStatus, 'مدفوع') || str_contains($currentClaimStatus, 'مسدد')))
                @php($isUnderReviewStatus = $currentClaimStatus === 'قيد المراجعة')
                @php($isRejectedStatus = $currentClaimStatus === 'مرفوض')
                @php($modalId = 'changeClaimStatusModal-' . $claim->id)
                @php($discountModalId = 'applyClaimDiscountModal-' . $claim->id)
                @php($paymentModalId = 'recordClaimPaymentModal-' . $claim->id)
                @php($paymentsRowId = 'claim-payments-' . $claim->id)
                @php($isCurrentPaymentClaim = $oldPaymentClaimId === (string) $claim->id)
                @php($isCurrentDiscountClaim = $oldDiscountClaimId === (string) $claim->id)
                @php($oldPaymentPayer = $isCurrentPaymentClaim ? old('claim_payer_id') : null)
                @php($oldPaymentAmount = $isCurrentPaymentClaim ? old('amount') : null)
                @php($oldPaymentDate = $isCurrentPaymentClaim ? old('paid_at') : null)
                @php($oldPaymentBank = $isCurrentPaymentClaim ? old('bank_account_id') : null)
                @php($oldPaymentSafe = $isCurrentPaymentClaim ? old('safe_id') : null)
                @php($oldPaymentNotes = $isCurrentPaymentClaim ? old('notes') : null)
                @php($oldDiscountAmountInput = $isCurrentDiscountClaim ? old('discount_amount') : null)
                @php($oldDiscountPayer = $isCurrentDiscountClaim ? old('claim_payer_id') : null)
                @php($oldDiscountDate = $isCurrentDiscountClaim ? old('paid_at') : null)
                @php($oldDiscountBank = $isCurrentDiscountClaim ? old('bank_account_id') : null)
                @php($oldDiscountSafe = $isCurrentDiscountClaim ? old('safe_id') : null)
                @php($oldDiscountNotes = $isCurrentDiscountClaim ? old('notes') : null)
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
                            <x-button type="button" variant="secondary" :outline="true" size="sm" class="collapsed" data-bs-toggle="collapse" data-bs-target="#{{ $paymentsRowId }}" aria-expanded="false" aria-controls="{{ $paymentsRowId }}">
                                {{ __('contracts::claims.view_payments') }}
                            </x-button>
            
                            @unless ($isPaidStatus)
                                @if ($isUnderReviewStatus)
                                    <x-button type="button" variant="primary" :outline="true" size="sm" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}" @if ($changeStatusOptions->isEmpty()) disabled @endif>
                                        {{ __('contracts::claims.change_status') }}
                                    </x-button>
                                @endif
            
                                @if ($isRejectedStatus)
                                    <form action="{{ route('contract-claims.reopen', $claim) }}" method="post">
                                        @csrf
                                        @method('patch')
                                        <x-button type="submit" variant="warning" :outline="true" size="sm">
                                            {{ __('contracts::claims.reopen_claim') }}
                                        </x-button>
                                    </form>
                                @endif
            
                                @if (! $isUnderReviewStatus && ! $isRejectedStatus)
                                    <x-button type="button" variant="dark" :outline="true" size="sm" data-bs-toggle="modal" data-bs-target="#{{ $paymentModalId }}" @if ($claimPayers->isEmpty() || $remainingAmount <= 0) disabled @endif>
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
                    <td colspan="11" class="text-start">
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

                                        @foreach ($payments as $index => $payment)
                                            <tr>
                                                <td class="text-center">{{ $index + 1 }}</td>
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
    @php($isPartialPaidStatus = in_array($currentClaimStatus, $partialPaidStatusNames, true))
    @php($isPaidStatus = ! $isPartialPaidStatus && (str_contains($currentClaimStatus, 'مدفوع') || str_contains($currentClaimStatus, 'مسدد')))
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
                        <x-button type="button" :unstyled="true" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></x-button>
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
                        <x-button type="button" variant="light" data-bs-dismiss="modal">{{ __('contracts::claims.back') }}</x-button>
                        <x-button type="submit" variant="primary">{{ __('contracts::claims.update_status') }}</x-button>
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
                    <x-button type="button" :unstyled="true" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></x-button>
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
                        <x-button type="button" variant="light" data-bs-dismiss="modal">{{ __('contracts::claims.back') }}</x-button>
                        <x-button type="submit" variant="dark" @if ($claimPayers->isEmpty() || $remainingAmount <= 0) disabled @endif>{{ __('contracts::claims.record_payment') }}</x-button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="{{ $discountModalId }}" tabindex="-1" aria-labelledby="{{ $discountLabelId }}" aria-hidden="true">
            <div class="modal-dialog">
                <form action="{{ route('contract-claims.apply-discount', $claim) }}" method="post" class="modal-content">
                    @csrf
                    @method('patch')
                    <input type="hidden" name="discount_claim_id" value="{{ $claim->id }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="{{ $discountLabelId }}">{{ __('contracts::claims.apply_discount') }}</h5>
                        <x-button type="button" :unstyled="true" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></x-button>
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
                                   value="{{ $oldDiscountAmountInput ?? $claim->discount_amount }}">
                            @error('discount_amount')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                            <div class="form-text">{{ __('contracts::claims.discount_payment_hint') }}</div>
                        </div>

                        <div class="mb-3 text-start">
                            <label for="claim-discount-payer-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.claim_payer') }}</label>
                            <select name="claim_payer_id" id="claim-discount-payer-{{ $claim->id }}" class="form-select" @if ($claimPayers->isEmpty()) disabled @endif>
                                <option value="">{{ __('contracts::claims.choose_claim_payer') }}</option>
                                @foreach ($claimPayers as $payer)
                                    <option value="{{ $payer->id }}" @selected((string) $oldDiscountPayer === (string) $payer->id)>{{ $payer->name }}</option>
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
                            <label for="claim-discount-date-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.claim_payment_date') }}</label>
                            <input type="text"
                                   name="paid_at"
                                   id="claim-discount-date-{{ $claim->id }}"
                                   class="form-control js-date"
                                   value="{{ $oldDiscountDate ?? now()->toDateString() }}">
                            @error('paid_at')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 text-start">
                            <label for="claim-discount-account-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.payment_account') }}</label>
                            @php($selectedDiscountAccount = $oldDiscountBank ? 'bank:' . $oldDiscountBank : ($oldDiscountSafe ? 'safe:' . $oldDiscountSafe : ''))
                            <select id="claim-discount-account-{{ $claim->id }}"
                                    class="form-select"
                                    data-claim-account-picker="1"
                                    data-bank-input="claim-discount-bank-{{ $claim->id }}"
                                    data-safe-input="claim-discount-safe-{{ $claim->id }}"
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
                            <input type="hidden" name="bank_account_id" id="claim-discount-bank-{{ $claim->id }}" value="{{ $oldDiscountBank }}">
                            <input type="hidden" name="safe_id" id="claim-discount-safe-{{ $claim->id }}" value="{{ $oldDiscountSafe }}">
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
                            <label for="claim-discount-notes-{{ $claim->id }}" class="form-label">{{ __('contracts::claims.payment_notes') }}</label>
                            <textarea name="notes" id="claim-discount-notes-{{ $claim->id }}" class="form-control" rows="2">{{ $oldDiscountNotes }}</textarea>
                            <div class="form-text">{{ __('contracts::claims.payment_notes_hint') }}</div>
                            @error('notes')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <x-button type="button" variant="light" data-bs-dismiss="modal">{{ __('contracts::claims.back') }}</x-button>
                        <x-button type="submit" variant="success">{{ __('contracts::claims.apply_discount') }}</x-button>
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

            var paymentClaimId = "{{ old('payment_claim_id') }}";
            if (paymentClaimId) {
                var paymentModal = document.getElementById('recordClaimPaymentModal-' + paymentClaimId);
                if (paymentModal) {
                    bootstrap.Modal.getOrCreateInstance(paymentModal).show();
                }
            }

            var discountClaimId = "{{ old('discount_claim_id') }}";
            if (discountClaimId) {
                var discountModal = document.getElementById('applyClaimDiscountModal-' + discountClaimId);
                if (discountModal) {
                    bootstrap.Modal.getOrCreateInstance(discountModal).show();
                }
            }
        });
    </script>
@endpush
@endsection
