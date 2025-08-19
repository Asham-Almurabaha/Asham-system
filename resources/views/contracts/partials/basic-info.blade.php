{{-- البطاقة: البيانات الأساسية --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <strong>البيانات الأساسية</strong>
    </div>
    <div class="card-body p-0">
        <div class="row g-3 p-3">
            {{-- رقم العقد --}}
            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">رقم العقد</div>
                <div class="fw-bold text-primary" style="font-size: 1.1rem;">
                    {{ $contract->contract_number ?? '—' }}
                </div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">العميل</div>
                <div>{{ $contract->customer->name ?? '—' }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">الكفيل</div>
                <div>{{ $contract->guarantor->name ?? '—' }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">حالة العقد</div>
                @php
                    $statusName = $contract->contractStatus->name ?? '—';
                    $badge = 'secondary';
                    if ($statusName === 'نشط') $badge = 'success';
                    elseif ($statusName === 'معلق') $badge = 'warning';
                    elseif ($statusName === 'بدون مستثمر') $badge = 'danger';
                @endphp
                <span class="badge bg-{{ $badge }}">{{ $statusName }}</span>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">نوع البضاعة</div>
                <div>{{ $contract->productType->name ?? '—' }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">عدد البضائع</div>
                <div>{{ $contract->products_count }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">سعر شراء البضائع</div>
                <div>{{ number_format($contract->purchase_price, 2) }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">سعر البيع للمستثمر</div>
                <div>{{ number_format($contract->sale_price, 2) }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">ربح المستثمر</div>
                <div>{{ number_format($contract->investor_profit, 0) }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">إجمالي قيمة العقد</div>
                <div>{{ number_format($contract->total_value, 0) }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">نوع القسط</div>
                <div>{{ $contract->installmentType->name ?? '—' }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">قيمة القسط</div>
                <div>{{ number_format($contract->installment_value, 2) }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">عدد الأقساط</div>
                <div>{{ $contract->installments_count }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">تاريخ بداية العقد</div>
                <div>{{ optional($contract->start_date)->format('Y-m-d') ?? '—' }}</div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">تاريخ أول قسط</div>
                <div>{{ $contract->first_installment_date ? optional($contract->first_installment_date)->format('Y-m-d') : '—' }}</div>
            </div>
        </div>
    </div>
</div>
