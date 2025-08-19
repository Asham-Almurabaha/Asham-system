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

    // خصم السداد المبكر
    $discountAmount = (float) ($contract->discount_amount ?? 0);

    // تأمين المتغيرات لو مش متبوعة من الكنترولر
    $banks = $banks ?? collect();
    $safes = $safes ?? collect();
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
            @if($discountAmount > 0)
                <span class="badge bg-light text-dark me-2">
                    🟡 خصم السداد المبكر: {{ number_format($discountAmount, 2) }}
                </span>
            @endif
        </div>
    </div>

    @php
        $contractStatusName = $contract->contractStatus->name ?? '';
    @endphp

    <div class="card-body p-0">
        <div class="p-3">
            @if($remainingContract > 0 && !in_array($contractStatusName, ['سداد مبكر']) && (float)$discountAmount <= 0)
            {{-- زر سداد --}}
            @if($remainingContract > 0)
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#payContractModal">
                    💰 سداد
                </button>
            @endif
            {{-- زر سداد مبكر --}}
                <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#earlySettleModal">
                    ⚡ سداد مبكر
                </button>
            @endif
        </div>
    </div>

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
                    @if(($banks->count() === 0) && ($safes->count() === 0))
                        <div class="alert alert-warning">
                            لا توجد حسابات بنكية أو خزائن مضافة بعد. الرجاء إضافة مصدر تحصيل من الإعدادات المالية.
                        </div>
                    @endif

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

                    {{-- مُلتقط الحساب (بنكي/خزنة) + حقول مخفية --}}
                    <div class="mb-3">
                        <label class="form-label" for="account_picker_pay">الحساب</label>
                        <select id="account_picker_pay" class="form-select" {{ ($banks->count()||$safes->count()) ? 'required' : 'disabled' }}>
                            <option value="" disabled selected>اختر حسابًا</option>
                            <optgroup label="الحسابات البنكية">
                                @foreach ($banks as $bank)
                                    <option value="bank:{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="الخزن">
                                @foreach ($safes as $safe)
                                    <option value="safe:{{ $safe->id }}">{{ $safe->name }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                        <input type="hidden" name="bank_account_id" id="bank_account_id_pay">
                        <input type="hidden" name="safe_id"         id="safe_id_pay">
                        <div class="form-text">اختر بنكًا أو خزنة — لا يمكن الجمع بينهما في نفس السداد.</div>
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

{{-- مودال سداد مبكر --}}
<div class="modal fade" id="earlySettleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="earlySettleForm" action="{{ route('installments.early_settle', $contract->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">⚡ سداد مبكر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">قيمة الخصم (ريال)</label>
                        <input type="number" name="discount_amount" step="0.01" min="0" class="form-control" value="0" required>
                        <small class="text-muted d-block mt-1">
                            سيتم حفظ قيمة الخصم في العقد وتحديث الإجمالي تلقائيًا، وتعيين الحالة: <strong>مدفوع مبكر</strong>.
                        </small>
                    </div>

                    {{-- مصدر التحصيل للسداد المبكر --}}
                    <hr class="my-3">
                    <div class="mb-1 fw-semibold">مصدر التحصيل</div>
                    <div class="mb-3">
                        <label class="form-label" for="account_picker_early">الحساب</label>
                        <select id="account_picker_early" class="form-select" {{ ($banks->count()||$safes->count()) ? 'required' : 'disabled' }}>
                            <option value="" disabled selected>اختر حسابًا</option>
                            <optgroup label="الحسابات البنكية">
                                @foreach ($banks as $bank)
                                    <option value="bank:{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="الخزن">
                                @foreach ($safes as $safe)
                                    <option value="safe:{{ $safe->id }}">{{ $safe->name }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                        <input type="hidden" name="bank_account_id" id="bank_account_id_early">
                        <input type="hidden" name="safe_id"         id="safe_id_early">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">💾 حفظ</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // مزامنة مختصر مُلتقط الحساب مع الحقول المخفية
    function syncAccountHiddenGeneric(pickerId, bankHiddenId, safeHiddenId){
        const picker = document.getElementById(pickerId);
        const bankH  = document.getElementById(bankHiddenId);
        const safeH  = document.getElementById(safeHiddenId);
        if (!picker || !bankH || !safeH) return;
        const val = picker.value || '';
        if (!val){ bankH.value=''; safeH.value=''; return; }
        const parts = val.split(':');
        const type = parts[0], id = parts[1];
        if (type === 'bank'){ bankH.value = id; safeH.value = ''; }
        else if (type === 'safe'){ safeH.value = id; bankH.value = ''; }
        else { bankH.value=''; safeH.value=''; }
    }

    document.addEventListener("DOMContentLoaded", function () {
        flatpickr(".js-date", {
            dateFormat: "Y-m-d",
            locale: "ar",
            defaultDate: "{{ now()->format('Y-m-d') }}"
        });

        // مزامنة على التغيير الفوري
        const accPay   = document.getElementById('account_picker_pay');
        const accEarly = document.getElementById('account_picker_early');
        if (accPay)   accPay.addEventListener('change',  () => syncAccountHiddenGeneric('account_picker_pay','bank_account_id_pay','safe_id_pay'));
        if (accEarly) accEarly.addEventListener('change',() => syncAccountHiddenGeneric('account_picker_early','bank_account_id_early','safe_id_early'));

        // سداد عادي
        const payForm = document.getElementById("payContractForm");
        if (payForm){
            payForm.addEventListener("submit", function(e) {
                e.preventDefault();

                // تأكد من ضبط الحقول قبل جمع FormData
                syncAccountHiddenGeneric('account_picker_pay','bank_account_id_pay','safe_id_pay');

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
                        alert(data.message || "حدث خطأ أثناء السداد");
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("تعذر الاتصال بالخادم");
                });
                var modal = bootstrap.Modal.getInstance(document.getElementById("payContractModal"));
                modal && modal.hide();
            });
        }

        // تأجيل
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
                            location.reload();
                        } else {
                            alert("حدث خطأ أثناء التأجيل");
                        }
                    })
                    .catch(err => console.error(err));
                }
            });
        });

        // معتذر
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
                            location.reload();
                        } else {
                            alert("حدث خطأ أثناء التغيير");
                        }
                    })
                    .catch(err => console.error(err));
                }
            });
        });
    });

    // سداد مبكر
    const earlyForm = document.getElementById("earlySettleForm");
    if (earlyForm) {
        earlyForm.addEventListener("submit", function(e) {
            e.preventDefault();

            // مزامنة الحساب قبل جمع البيانات
            syncAccountHiddenGeneric('account_picker_early','bank_account_id_early','safe_id_early');

            let form = e.target;
            let formData = new FormData(form);
            fetch(form.action, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    "Accept": "application/json"
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || "حدث خطأ أثناء السداد المبكر");
                }
            })
            .catch(err => {
                console.error(err);
                alert("تعذر الاتصال بالخادم");
            });

            const modal = bootstrap.Modal.getInstance(document.getElementById("earlySettleModal"));
            modal && modal.hide();
        });
    }
</script>

@endif
