
{{-- البطاقة: الصور --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">
        <strong>الصور</strong>
    </div>
    <div class="card-body p-0">
        <div class="row p-3">
            @if($contract->contract_image)
                <div class="col-md-4 text-center">
                    <div class="fw-semibold text-muted mb-2">📄 صورة العقد</div>
                    <img src="{{ asset('storage/'.$contract->contract_image) }}" class="img-fluid rounded shadow-sm" style="max-height: 260px;" alt="صورة العقد">
                </div>
            @endif

            @if($contract->contract_customer_image)
                <div class="col-md-4 text-center">
                    <div class="fw-semibold text-muted mb-2">👤 سند الأمر (العميل)</div>
                    <img src="{{ asset('storage/'.$contract->contract_customer_image) }}" class="img-fluid rounded shadow-sm" style="max-height: 260px;" alt="سند الأمر (العميل)">
                </div>
            @endif

            @if($contract->contract_guarantor_image)
                <div class="col-md-4 text-center">
                    <div class="fw-semibold text-muted mb-2">🤝 سند الأمر (الكفيل)</div>
                    <img src="{{ asset('storage/'.$contract->contract_guarantor_image) }}" class="img-fluid rounded shadow-sm" style="max-height: 260px;" alt="سند الأمر (الكفيل)">
                </div>
            @endif

            @if(!$contract->contract_image && !$contract->contract_customer_image && !$contract->contract_guarantor_image)
                <div class="col-12 text-muted">لا توجد صور مرفقة لهذا العقد.</div>
            @endif
        </div>
    </div>
</div>
