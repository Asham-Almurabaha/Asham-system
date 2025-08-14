@php
    // المتبقي من العقد كله
    $totalContractDue = $contract->installments->sum('due_amount');
    $totalContractPaid = $contract->installments->sum('payment_amount');
    $remainingContract = max(0, $totalContractDue - $totalContractPaid);

    // المتبقي من القسط الحالي
    $remainingInstallment = max(0, $inst->due_amount - $inst->payment_amount);

    // تحديد القيمة الافتراضية
    $isLastInstallment = $inst->installment_number == $contract->installments->max('installment_number');
    if ($isLastInstallment) {
        $defaultAmount = $remainingContract;
    } elseif ($inst->payment_amount > 0 && $remainingInstallment > 0) {
        $defaultAmount = $remainingInstallment;
    } else {
        $defaultAmount = $inst->due_amount;
    }
@endphp

<div class="modal fade" id="payModal{{ $inst->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('installments.pay', $inst->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">💰 سداد القسط #{{ $index }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    {{-- المبلغ المدفوع --}}
                    <div class="mb-3">
                        <label class="form-label">المبلغ المدفوع</label>
                        <input 
                            type="number" 
                            name="payment_amount" 
                            step="0.01" 
                            class="form-control" 
                            value="{{ number_format($defaultAmount, 2, '.', '') }}" 
                            max="{{ $remainingContract }}"
                            required
                        >
                        <small class="text-muted">أقصى مبلغ مسموح: {{ number_format($remainingContract, 2) }}</small>
                    </div>

                    {{-- تاريخ السداد --}}
                    <div class="row g-3">
                        <div class="col-md-6 position-relative">
                            <label for="payment_date_{{ $inst->id }}" class="form-label">تاريخ السداد <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    id="payment_date_{{ $inst->id }}"
                                    name="payment_date"
                                    class="form-control js-date @error('payment_date') is-invalid @enderror"
                                    value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                                    placeholder="اختر التاريخ"
                                    autocomplete="off"
                                    required>
                                <span class="input-group-text bg-white">📅</span>
                                @error('payment_date') 
                                    <div class="invalid-feedback">{{ $message }}</div> 
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- ملاحظات اختيارية --}}
                    <div class="mb-3 mt-3">
                        <label class="form-label">ملاحظات (اختياري)</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">💾 حفظ</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const isArabic = "{{ app()->getLocale() }}" === 'ar';

            const baseOpts = {
                dateFormat: 'Y-m-d',
                allowInput: true,
                locale: isArabic ? 'ar' : 'default',
                disableMobile: true
            };

            function initDatePickers(context = document) {
                context.querySelectorAll('.js-date').forEach(function(el) {
                    if (el._flatpickr) {
                        el._flatpickr.destroy();
                    }
                    flatpickr(el, baseOpts);

                    if (isArabic) {
                        el.setAttribute('dir', 'rtl');
                        el.style.textAlign = 'center';
                    }
                });
            }

            // تشغيل عند تحميل الصفحة
            initDatePickers();

            // إعادة التشغيل لما المودال يفتح
            document.querySelectorAll('.modal').forEach(function(modal) {
                modal.addEventListener('shown.bs.modal', function () {
                    initDatePickers(modal);
                });
            });
        });
    </script>
@endpush
