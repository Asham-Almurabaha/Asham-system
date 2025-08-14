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
@endphp

<div class="card shadow-sm mb-4">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <strong>الأقساط</strong>
        @if($contract->installments->count())
            <span class="badge bg-light text-dark">
                مجموع الأقساط: {{ number_format($totalContractDue, 2) }}
                — المدفوع: {{ number_format($totalContractPaid, 2) }}
            </span>
        @endif
    </div>

    <div class="card-body p-0">
        {{-- زر سداد واحد قبل الجدول --}}
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
                                    $s = $inst->installmentStatus->name ?? '—';
                                    $b = 'secondary';
                                    if ($s === 'مدفوع كامل' || $s === 'مدفوع مبكر') $b = 'success';
                                    elseif ($s === 'مطلوب') $b = 'info';
                                    elseif ($s === 'مؤجل' || $s === 'مدفوع جزئي') $b = 'warning';
                                    elseif ($s === 'معلق') $b = 'primary';
                                    elseif ($s === 'متعثر' || $s === 'متأخر') $b = 'danger';
                                @endphp
                                <span class="badge bg-{{ $b }}">{{ $s }}</span>
                            </td>

                            {{-- الإجراءات --}}
                            <td>
                                {{-- زر التأجيل --}}
                                @if($isThisMonth && $inst->payment_amount < $inst->due_amount)
                                    <button type="button" 
                                            class="btn btn-sm btn-warning defer-btn" 
                                            data-id="{{ $inst->id }}">
                                        ⏳ تأجيل
                                    </button>
                                @endif

                                {{-- زر معتذر --}}
                                @if($inst->payment_amount < $inst->due_amount)
                                    <button type="button" 
                                            class="btn btn-sm btn-secondary excuse-btn" 
                                            data-id="{{ $inst->id }}">
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

{{-- مودال سداد واحد --}}
<div class="modal fade" id="payContractModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('installments.pay') }}" method="POST">
                @csrf
                <input type="hidden" name="contract_id" value="{{ $contract->id }}">

                <div class="modal-header">
                    <h5 class="modal-title">💰 سداد العقد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">المبلغ المدفوع</label>
                        <input 
                            type="number" 
                            name="payment_amount" 
                            step="0.01" 
                            class="form-control" 
                            value="{{ number_format($remainingContract, 2, '.', '') }}" 
                            max="{{ $remainingContract }}"
                            required
                        >
                        <small class="text-muted">أقصى مبلغ مسموح: {{ number_format($remainingContract, 2) }}</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">تاريخ السداد</label>
                        <input 
                            type="date" 
                            name="payment_date" 
                            class="form-control" 
                            value="{{ now()->format('Y-m-d') }}" 
                            required
                        >
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
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
