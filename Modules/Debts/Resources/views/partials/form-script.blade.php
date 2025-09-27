@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect = document.getElementById('party_type');
        const partyFields = document.querySelectorAll('.js-party-field');
        const counterpartyGroup = document.querySelector('.js-counterparty-name');
        const counterpartyInput = document.getElementById('counterparty_name');

        const accountPicker = document.getElementById('account_picker');
        const bankField = document.getElementById('bank_account_id');
        const safeField = document.getElementById('safe_id');
        const availabilityValue = document.getElementById('account_availability_value');
        const availabilitySpinner = document.getElementById('account_availability_spinner');
        const availabilityUrl = @json(route('ajax.accounts.availability'));
        const amountField = document.getElementById('principal_amount');
        const amountLimitMessageTemplate = @json(__('debts::messages.validation.amount_exceeds_available', ['available' => ':available']));
        let currentAvailability = null;
        let currentAvailabilityFormatted = '—';

        function syncPartyFields() {
            const type = typeSelect ? typeSelect.value : '';
            partyFields.forEach(function (field) {
                const shouldShow = field.dataset.party === type;
                field.classList.toggle('d-none', !shouldShow);
                const select = field.querySelector('select');
                if (select) {
                    select.disabled = !shouldShow;
                    if (!shouldShow) {
                        select.value = '';
                    }
                }
            });

            if (counterpartyGroup && counterpartyInput) {
                const showCounterparty = type === 'other';
                counterpartyGroup.classList.toggle('d-none', !showCounterparty);
                counterpartyInput.disabled = !showCounterparty;

                if (!showCounterparty) {
                    counterpartyInput.value = '';
                }
            }
        }

        function syncAccountHiddenFields() {
            if (!accountPicker || !bankField || !safeField) {
                return;
            }

            const value = accountPicker.value || '';
            bankField.value = '';
            safeField.value = '';

            if (!value) {
                return;
            }

            const [type, id] = value.split(':');
            if (type === 'bank') {
                bankField.value = id;
            } else if (type === 'safe') {
                safeField.value = id;
            }
        }

        function setCurrentAvailability(value, formatted) {
            if (!amountField) {
                currentAvailability = null;
                currentAvailabilityFormatted = '—';
                return;
            }

            const numericValue = Number(value);
            if (Number.isFinite(numericValue) && numericValue >= 0) {
                currentAvailability = numericValue;
                currentAvailabilityFormatted = formatted || numericValue.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
                amountField.max = numericValue.toFixed(2);
                amountField.dataset.availableFormatted = currentAvailabilityFormatted;
            } else {
                currentAvailability = null;
                currentAvailabilityFormatted = '—';
                amountField.removeAttribute('max');
                delete amountField.dataset.availableFormatted;
            }

            enforceAmountLimit();
        }

        function enforceAmountLimit() {
            if (!amountField) {
                return;
            }

            const value = Number(amountField.value);
            if (currentAvailability !== null && Number.isFinite(value) && value > currentAvailability + 0.00001) {
                const formatted = amountField.dataset.availableFormatted || currentAvailabilityFormatted;
                amountField.setCustomValidity(amountLimitMessageTemplate.replace(':available', formatted));
            } else {
                amountField.setCustomValidity('');
            }
        }

        async function refreshAccountAvailability() {
            if (!accountPicker || !availabilityValue || !availabilitySpinner) {
                return;
            }

            const value = accountPicker.value || '';
            availabilityValue.textContent = '—';
            setCurrentAvailability(null);

            if (!value) {
                return;
            }

            const [type, id] = value.split(':');
            if (!type || !id) {
                return;
            }

            availabilitySpinner.classList.remove('d-none');

            try {
                const params = new URLSearchParams({
                    account_type: type,
                    account_id: id,
                });
                const response = await fetch(`${availabilityUrl}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                const data = await response.json();
                if (data && data.success) {
                    const formatted = data.available_formatted ?? Number(data.available ?? 0).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                    availabilityValue.textContent = formatted;
                    setCurrentAvailability(data.available, formatted);
                } else {
                    availabilityValue.textContent = '—';
                    setCurrentAvailability(null);
                }
            } catch (error) {
                availabilityValue.textContent = '—';
                setCurrentAvailability(null);
            } finally {
                availabilitySpinner.classList.add('d-none');
            }
        }

        if (typeSelect) {
            typeSelect.addEventListener('change', syncPartyFields);
            syncPartyFields();
        }

        if (accountPicker) {
            accountPicker.addEventListener('change', function () {
                syncAccountHiddenFields();
                refreshAccountAvailability();
            });
            syncAccountHiddenFields();
            refreshAccountAvailability();
        }

        if (amountField) {
            amountField.addEventListener('input', enforceAmountLimit);
            enforceAmountLimit();
        }
    });
</script>
@endpush
