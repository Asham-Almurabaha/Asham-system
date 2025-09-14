{{-- Contract: Images --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">
        <strong>{{ __('Images') }}</strong>
    </div>
    <div class="card-body p-0">
        <div class="row p-3">
            @if($contract->contract_image)
                <div class="col-md-4 text-center">
                    <div class="fw-semibold text-muted mb-2">{{ __('Contract Image') }}</div>
                    <img src="{{ asset('storage/'.$contract->contract_image) }}" class="img-fluid rounded shadow-sm" style="max-height: 260px;" alt="{{ __('Contract Image') }}">
                </div>
            @endif

            @if($contract->contract_customer_image)
                <div class="col-md-4 text-center">
                    <div class="fw-semibold text-muted mb-2">{{ __('Customer Contract Image') }}</div>
                    <img src="{{ asset('storage/'.$contract->contract_customer_image) }}" class="img-fluid rounded shadow-sm" style="max-height: 260px;" alt="{{ __('Customer Contract Image') }}">
                </div>
            @endif

            @if($contract->contract_guarantor_image)
                <div class="col-md-4 text-center">
                    <div class="fw-semibold text-muted mb-2">{{ __('Guarantor Contract Image') }}</div>
                    <img src="{{ asset('storage/'.$contract->contract_guarantor_image) }}" class="img-fluid rounded shadow-sm" style="max-height: 260px;" alt="{{ __('Guarantor Contract Image') }}">
                </div>
            @endif

            @if(!$contract->contract_image && !$contract->contract_customer_image && !$contract->contract_guarantor_image)
                <div class="col-12 text-muted">{{ __('No contract images uploaded.') }}</div>
            @endif
        </div>
    </div>
</div>

