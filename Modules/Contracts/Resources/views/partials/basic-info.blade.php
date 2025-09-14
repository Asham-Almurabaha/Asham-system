{{-- Contract: Basic Information --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <strong>{{ __('Basic Information') }}</strong>
    </div>
    <div class="card-body p-0">
        <div class="row g-3 p-3">
            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">{{ __('Contract Number') }}</div>
                <div class="fw-bold text-dark" style="font-size: 1.1rem;">
                    {{ $contract->contract_number ?? '-' }}
                </div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">{{ __('Customer') }}</div>
                <div>
                  @if($contract->customer)
                    <a href="{{ route('customers.show', $contract->customer->id) }}" class="text-reset text-decoration-none">
                      {{ $contract->customer->name }}
                    </a>
                  @else
                    {{ __('Undefined') }}
                  @endif
                </div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">{{ __('Guarantor') }}</div>
                <div>
                  @if($contract->guarantor)
                    <a href="{{ route('guarantors.show', $contract->guarantor->id) }}" class="text-reset text-decoration-none">
                      {{ $contract->guarantor->name }}
                    </a>
                  @else
                    {{ __('Undefined') }}
                  @endif
                </div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">{{ __('Status') }}</div>
                @php
                    $statusName = $contract->contractStatus->name ?? __('Undefined');
                    $badge = 'secondary';
                    // Keep existing mapping logic as-is when available
                @endphp
                <span class="badge bg-{{ $badge }}">{{ $statusName }}</span>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">{{ __('Product Type') }}</div>
                <div>{{ $contract->productType->name ?? __('Undefined') }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">{{ __('Products Count') }}</div>
                <div>{{ $contract->products_count }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">{{ __('Purchase Price') }}</div>
                <div>{{ number_format($contract->purchase_price, 2) }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">{{ __('Sale Price') }}</div>
                <div>{{ number_format($contract->sale_price, 2) }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">{{ __('Investor Profit') }}</div>
                <div>{{ number_format($contract->investor_profit, 0) }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">{{ __('Total Value') }}</div>
                <div>{{ number_format($contract->total_value, 0) }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">{{ __('Installment Type') }}</div>
                <div>{{ $contract->installmentType->name ?? __('Undefined') }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">{{ __('Installment Value') }}</div>
                <div>{{ number_format($contract->installment_value, 2) }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">{{ __('Installments Count') }}</div>
                <div>{{ $contract->installments_count }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">{{ __('Start Date') }}</div>
                <div>{{ optional($contract->start_date)->format('Y-m-d') ?? '-' }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">{{ __('First Installment Date') }}</div>
                <div>{{ $contract->first_installment_date ? optional($contract->first_installment_date)->format('Y-m-d') : '-' }}</div>
            </div>
        </div>
    </div>
</div>

