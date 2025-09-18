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
                        <th>{{ __('contracts::claims.claim_first_party') }}</th>
                        <th>{{ __('contracts::claims.filed_party_role') }}</th>
                        <th>{{ __('contracts::claims.claim_amount') }}</th>
                        <th>{{ __('contracts::claims.claim_date') }}</th>
                        <th>{{ __('contracts::claims.document_number') }}</th>
                        <th>{{ __('contracts::claims.claim_status') }}</th>
                        <th>{{ __('contracts::claims.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($claims as $claim)
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
                        <td class="text-start">{{ optional($claim->claimFirstParty)->name ?? '—' }}</td>
                        <td class="text-start">
                            <div>{{ $claim->filed_party_name ?? '—' }}</div>
                            @if ($claim->filed_party_role)
                                <div class="text-muted small">{{ __('contracts::claims.party_role_' . $claim->filed_party_role) }}</div>
                            @endif
                        </td>
                        <td>{{ number_format((float) $claim->claim_amount, 2) }}</td>
                        <td>{{ optional($claim->claim_date)->format('Y-m-d') }}</td>
                        <td>{{ $claim->document_number }}</td>
                        <td class="text-start">{{ optional($claim->claimStatus)->name ?? '—' }}</td>
                        @php($modalId = 'changeClaimStatusModal-' . $claim->id)
                        @php($discountModalId = 'applyClaimDiscountModal-' . $claim->id)
                        <td class="text-nowrap">
                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                <button type="button"
                                        class="btn btn-outline-primary btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#{{ $modalId }}"
                                        @if ($claimStatuses->isEmpty()) disabled @endif>
                                    {{ __('contracts::claims.change_status') }}
                                </button>

                                <button type="button"
                                        class="btn btn-outline-success btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#{{ $discountModalId }}"
                                        @if (empty($paidWithDiscountClaimStatusId)) disabled @endif>
                                    {{ __('contracts::claims.apply_discount') }}
                                </button>
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
@else
    @foreach ($claims as $claim)
        @php($modalId = 'changeClaimStatusModal-' . $claim->id)
        @php($labelId = $modalId . 'Label')
        @php($discountModalId = 'applyClaimDiscountModal-' . $claim->id)
        @php($discountLabelId = $discountModalId . 'Label')
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
                                @foreach ($claimStatuses as $status)
                                    <option value="{{ $status->id }}" @selected($status->id === $claim->claim_status_id)>{{ $status->name }}</option>
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
@endif
@endsection
