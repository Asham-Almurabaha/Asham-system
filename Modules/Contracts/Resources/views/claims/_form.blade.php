@php
    $editing = isset($claim);
    $selectedContractId = old('contract_id', $claim->contract_id ?? null);
    $selectedRole = old('filed_party_role', $claim->filed_party_role ?? '');
    $selectedContract = $selectedContractId ? $contracts->firstWhere('id', (int) $selectedContractId) : null;
    $customerRole = \Modules\Contracts\Entities\ContractClaim::FILED_PARTY_CUSTOMER;
    $guarantorRole = \Modules\Contracts\Entities\ContractClaim::FILED_PARTY_GUARANTOR;
    $partyRoleLabels = [
        $customerRole => __('contracts::claims.party_role_customer'),
        $guarantorRole => __('contracts::claims.party_role_guarantor'),
    ];
    $contractsLookup = $contracts->map(function ($contract) {
        return [
            'id' => $contract->id,
            'customer' => $contract->customer?->name,
            'guarantor' => $contract->guarantor?->name,
        ];
    })->values()->all();
@endphp

<div class="row g-3">
    <div class="col-12 col-md-4">
        <label class="form-label">{{ __('contracts::claims.contract') }}</label>
        <select name="contract_id" id="contract_id" class="form-select" required data-contract-select>
            <option value="">{{ __('contracts::claims.choose_contract') }}</option>
            @foreach ($contracts as $contractOption)
                <option value="{{ $contractOption->id }}"
                        data-customer-name="{{ $contractOption->customer->name ?? '' }}"
                        data-guarantor-name="{{ $contractOption->guarantor->name ?? '' }}"
                        @selected(old('contract_id', $claim->contract_id ?? '') == $contractOption->id)>
                    {{ $contractOption->contract_number ?? ('#' . $contractOption->id) }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label">{{ __('contracts::claims.filed_party_role') }}</label>
        <select name="filed_party_role" id="filed_party_role" class="form-select" required data-party-select data-selected="{{ $selectedRole }}" @if (! $selectedContract) disabled @endif>
            <option value="">{{ __('contracts::claims.choose_filed_party') }}</option>
            @if ($selectedContract)
                @if ($selectedContract->customer)
                    <option value="{{ $customerRole }}" @selected($selectedRole === $customerRole)>
                        {{ $selectedContract->customer->name }} ({{ $partyRoleLabels[$customerRole] }})
                    </option>
                @endif
                @if ($selectedContract->guarantor)
                    <option value="{{ $guarantorRole }}" @selected($selectedRole === $guarantorRole)>
                        {{ $selectedContract->guarantor->name }} ({{ $partyRoleLabels[$guarantorRole] }})
                    </option>
                @endif
            @endif
        </select>
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label">{{ __('contracts::claims.claim_amount') }}</label>
        <input type="number" name="claim_amount" class="form-control" step="0.01" min="0" required
               value="{{ old('claim_amount', $claim->claim_amount ?? '') }}">
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label">{{ __('contracts::claims.claim_date') }}</label>
        <input type="date" name="claim_date" class="form-control" required
               value="{{ old('claim_date', optional($claim->claim_date ?? null)->format('Y-m-d')) }}">
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label">{{ __('contracts::claims.document_number') }}</label>
        <input type="text" name="document_number" class="form-control" maxlength="255" required
               value="{{ old('document_number', $claim->document_number ?? '') }}">
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const contractSelect = document.querySelector('[data-contract-select]');
            const partySelect = document.querySelector('[data-party-select]');

            if (!contractSelect || !partySelect) {
                return;
            }

            const contractsLookup = @json($contractsLookup);

            const placeholderText = @json(__('contracts::claims.choose_filed_party'));
            const roleLabels = @json($partyRoleLabels);

            function populatePartyOptions(contractId, selectedRole = null) {
                const selectedContract = contractsLookup.find(contract => String(contract.id) === String(contractId));

                partySelect.innerHTML = '';

                const placeholderOption = new Option(placeholderText, '');
                partySelect.appendChild(placeholderOption);

                if (!selectedContract) {
                    partySelect.value = '';
                    partySelect.setAttribute('disabled', 'disabled');
                    return;
                }

                partySelect.removeAttribute('disabled');

                const availableRoles = [];

                if (selectedContract.customer) {
                    const roleKey = '{{$customerRole}}';
                    const option = new Option(`${selectedContract.customer} (${roleLabels[roleKey] ?? ''})`, roleKey);
                    partySelect.appendChild(option);
                    availableRoles.push(roleKey);
                }

                if (selectedContract.guarantor) {
                    const roleKey = '{{$guarantorRole}}';
                    const option = new Option(`${selectedContract.guarantor} (${roleLabels[roleKey] ?? ''})`, roleKey);
                    partySelect.appendChild(option);
                    availableRoles.push(roleKey);
                }

                if (selectedRole && availableRoles.includes(selectedRole)) {
                    partySelect.value = selectedRole;
                } else if (availableRoles.length === 1) {
                    partySelect.value = availableRoles[0];
                } else {
                    partySelect.value = '';
                }
            }

            const initialSelectedRole = partySelect.dataset.selected || null;
            populatePartyOptions(contractSelect.value, initialSelectedRole);
            partySelect.dataset.selected = '';

            contractSelect.addEventListener('change', function (event) {
                populatePartyOptions(event.target.value);
            });
        });
    </script>
@endpush
