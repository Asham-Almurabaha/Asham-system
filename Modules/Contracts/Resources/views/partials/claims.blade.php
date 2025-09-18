@php
    use Modules\Contracts\Entities\ContractClaim;

    $claimsCollection = $contract->claims ?? collect();
    $claimsCollection = $claimsCollection->values();

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

    $availableFirstParties = collect($claimFirstParties ?? [])->values();
    $selectedFirstPartyId = old('claim_first_party_id');

    $defaultClaimDate = old('claim_date', now()->toDateString());
    $shouldReopenModal = $errors->has('contract_id')
        || $errors->has('filed_party_role')
        || $errors->has('claim_amount')
        || $errors->has('claim_date')
        || $errors->has('document_number')
        || $errors->has('claim_first_party_id');
@endphp

<div class="card shadow-sm mb-4">
    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
        <strong>{{ __('contracts::claims.claims') }}</strong>
        <a  class="btn btn-dark btn-sm text-light" data-bs-toggle="modal" data-bs-target="#addClaimModal">
            <i class="bi bi-plus-lg"></i> {{ __('contracts::claims.add_claim') }}
        </a>
    </div>
    <div class="card-body p-0">
        @if ($claimsCollection->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;" class="text-center">#</th>
                            <th>{{ __('contracts::claims.claim_date') }}</th>
                            <th>{{ __('contracts::claims.document_number') }}</th>
                            <th>{{ __('contracts::claims.claim_first_party') }}</th>
                            <th>{{ __('contracts::claims.filed_party_role') }}</th>
                            <th class="text-end">{{ __('contracts::claims.claim_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($claimsCollection as $index => $claim)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ optional($claim->claim_date)->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ $claim->document_number }}</td>
                                <td>{{ optional($claim->claimFirstParty)->name ?? '—' }}</td>
                                <td>
                                    <div>{{ $claim->filed_party_name ?? '—' }}</div>
                                    <div class="text-muted small">
                                        {{ __('contracts::claims.party_role_' . $claim->filed_party_role) }}
                                    </div>
                                </td>
                                <td class="text-end">{{ number_format((float) $claim->claim_amount, 2) }}</td>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('contracts::claims.contract') }}</label>
                        <div class="form-control bg-light">{{ $contract->contract_number ?? ('#' . $contract->id) }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('contracts::claims.claim_first_party') }}</label>
                        <select name="claim_first_party_id" class="form-select" @if ($availableFirstParties->isEmpty()) disabled @endif>
                            <option value="">{{ __('contracts::claims.choose_claim_first_party') }}</option>
                            @foreach ($availableFirstParties as $firstParty)
                                <option value="{{ $firstParty->id }}" @selected((string) $selectedFirstPartyId === (string) $firstParty->id)>
                                    {{ $firstParty->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($availableFirstParties->isEmpty())
                            <div class="text-danger small mt-1">
                                {{ __('contracts::claims.no_claim_first_parties') }}
                            </div>
                        @endif
                        @error('claim_first_party_id')
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
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('contracts::claims.back') }}</button>
                    <button type="submit" class="btn btn-outline-success">{{ __('contracts::claims.save') }}</button>
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
@endpush
