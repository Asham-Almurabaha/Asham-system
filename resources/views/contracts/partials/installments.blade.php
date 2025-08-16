@php
    // مجموع نسب المستثمرين
    $investorsTotalPct = $contract->investors->sum(fn($i) => (float)$i->pivot->share_percentage);
@endphp

@if($investorsTotalPct == 100)


<style>
.tooltip.wide-tooltip .tooltip-inner {
    max-width: 400px;
    white-space: pre-wrap;
    text-align: right;
}
</style>

@php
    $totalContractDue  = $contract->installments->sum('due_amount');
    $totalContractPaid = $contract->installments->sum('payment_amount');
    $remainingContract = max(0, $totalContractDue - $totalContractPaid);

    // عدد مرات الاعتذار من الملاحظات
    $excuseCount = $contract->installments->filter(function($inst) {
        return stripos($inst->notes ?? '', 'معتذر') !== false;
    })->count();

    // البحث عن أول قسط ناقصه فلوس
    $firstUnpaidInstallment = $contract->installments
        ->sortBy('installment_number')
        ->firstWhere(function($inst) {
            return $inst->payment_amount < $inst->due_amount;
        });

    $defaultPaymentAmount = $firstUnpaidInstallment
        ? max(0, $firstUnpaidInstallment->due_amount - $firstUnpaidInstallment->payment_amount)
        : $remainingContract;
@endphp

<div class="card shadow-sm mb-4">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <strong>الأقساط</strong>
        <div>
            @if($contract->installments->count())
                <span class="badge bg-light text-dark me-2">
                    مجموع الأقساط: {{ number_format($totalContractDue, 2) }} — المدفوع: {{ number_format($totalContractPaid, 2) }}
                </span>
            @endif
            @if($excuseCount > 0)
                <span class="badge bg-light text-dark">
                    🙏 مرات الاعتذار: {{ $excuseCount }}
                </span>
            @endif
        </div>
    </div>

    <div class="card-body p-0">
        {{-- زر سداد --}}
        @if($remainingContract > 0)
            <div class="p-3">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#payContractModal">
                    💰 سداد
                </button>
            </div>
        @endif

        @if($contract->installments->count())
            <table class="table table-bordered table-striped mb-0 text-center align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>تاريخ الاستحقاق</th>
                        <th>المبلغ المستحق</th>
                        <th>تاريخ الدفع</th>
                        <th>المبلغ المدفوع</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contract->installments as $i => $inst)
                        @php
                            $dueDate = \Carbon\Carbon::parse($inst->due_date);
                            $isThisMonth = $dueDate->isSameMonth(now());
                            $statusName = $inst->installmentStatus->name ?? '';
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $dueDate->format('Y-m-d') }}</td>
                            <td>{{ number_format($inst->due_amount, 2) }}</td>

                            {{-- تاريخ الدفع --}}
                            <td>
                                @if($inst->payment_amount > 0 && $inst->payment_date)
                                    {{ \Carbon\Carbon::parse($inst->payment_date)->format('Y-m-d') }}
                                @else
                                    —
                                @endif
                            </td>

                            {{-- المبلغ المدفوع --}}
                            <td>
                                @if($inst->notes)
                                    <span data-bs-toggle="tooltip" data-bs-placement="top" 
                                          data-bs-custom-class="wide-tooltip"
                                          title="{{ $inst->notes }}">
                                        {{ number_format($inst->payment_amount, 2) }}
                                    </span>
                                @else
                                    {{ number_format($inst->payment_amount, 2) }}
                                @endif
                            </td>

                            {{-- الحالة --}}
                            <td>
                                @php
                                    $b = 'secondary';
                                    if ($statusName === 'مدفوع كامل' || $statusName === 'مدفوع مبكر') $b = 'success';
                                    elseif ($statusName === 'مطلوب') $b = 'info';
                                    elseif ($statusName === 'مؤجل' || $statusName === 'مدفوع جزئي') $b = 'warning';
                                    elseif ($statusName === 'معلق') $b = 'primary';
                                    elseif ($statusName === 'متعثر' || $statusName === 'متأخر') $b = 'danger';
                                @endphp
                                <span class="badge bg-{{ $b }}">{{ $statusName ?: '—' }}</span>
                            </td>

                            {{-- الإجراءات --}}
                            <td>
                                {{-- زر التأجيل --}}
                                @if($isThisMonth && $inst->payment_amount < $inst->due_amount && $statusName !== 'مؤجل' && $statusName !== 'معتذر')
                                    <button type="button" class="btn btn-sm btn-warning defer-btn" data-id="{{ $inst->id }}">
                                        ⏳ تأجيل
                                    </button>
                                @endif

                                {{-- زر المعتذر --}}
                                @php
                                    $daysDiff = now()->diffInDays($dueDate, false); 
                                @endphp
                                @if(
                                    $inst->payment_amount < $inst->due_amount &&
                                    $statusName !== 'معتذر' &&
                                    $daysDiff >= -15
                                )
                                    <button type="button" class="btn btn-sm btn-secondary excuse-btn" data-id="{{ $inst->id }}">
                                        🙏 معتذر
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-3 text-muted">لا توجد أقساط مسجلة.</div>
        @endif
    </div>
</div>

{{-- مودال سداد --}}
<div class="modal fade" id="payContractModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="payContractForm" action="{{ route('installments.pay') }}" method="POST">
                @csrf
                <input type="hidden" name="contract_id" value="{{ $contract->id }}">
                <div class="modal-header">
                    <h5 class="modal-title">💰 سداد العقد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">المبلغ المدفوع</label>
                        <input type="number" name="payment_amount" step="0.01" class="form-control"
                            value="{{ number_format($defaultPaymentAmount, 2, '.', '') }}"
                            max="{{ $remainingContract }}" required>
                        <small class="text-muted">أقصى مبلغ مسموح: {{ number_format($remainingContract, 2) }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاريخ السداد</label>
                        <input type="text" name="payment_date" class="form-control js-date"
                            value="{{ now()->format('Y-m-d') }}" placeholder="YYYY-MM-DD" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
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

<script>
document.addEventListener("DOMContentLoaded", function () {
    flatpickr(".js-date", {
        dateFormat: "Y-m-d",
        locale: "ar",
        defaultDate: "{{ now()->format('Y-m-d') }}"
    });

    document.getElementById("payContractForm").addEventListener("submit", function(e) {
        e.preventDefault();
        let form = e.target;
        let formData = new FormData(form);
        fetch(form.action, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success){
                location.reload();
            } else {
                alert("حدث خطأ أثناء السداد");
            }
        })
        .catch(err => {
            console.error(err);
            alert("تعذر الاتصال بالخادم");
        });
        var modal = bootstrap.Modal.getInstance(document.getElementById("payContractModal"));
        modal.hide();
    });

    document.querySelectorAll(".defer-btn").forEach(function(btn) {
    btn.addEventListener("click", function() {
        let id = this.getAttribute("data-id");
        if(confirm("هل تريد تأجيل هذا القسط؟")) {
            fetch(`/installments/defer/${id}`, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    "Accept": "application/json"
                }
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    location.reload(); // إعادة تحميل الصفحة
                } else {
                    alert("حدث خطأ أثناء التأجيل");
                }
            })
            .catch(err => console.error(err));
        }
    });
});

    document.querySelectorAll(".excuse-btn").forEach(function(btn) {
        btn.addEventListener("click", function() {
            let id = this.getAttribute("data-id");
            if(confirm("هل تريد جعل هذا القسط معتذر؟")) {
                fetch(`/installments/excuse/${id}`, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                        "Accept": "application/json"
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        location.reload(); // إعادة تحميل الصفحة
                    } else {
                        alert("حدث خطأ أثناء التغيير");
                    }
                })
                .catch(err => console.error(err));
            }
        });
    });

});
</script>
@endif
