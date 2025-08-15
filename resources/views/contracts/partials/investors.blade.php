{{-- البطاقة: المستثمرون --}}
<div class="card shadow-sm mb-4">
    @php
        $sumPct = $contract->investors->sum(fn($i) => (float)$i->pivot->share_percentage);
        $sumVal = $contract->investors->sum(fn($i) => (float)$i->pivot->share_value);
        $investorCount = $contract->investors->count();
    @endphp

    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <strong>المستثمرون</strong>
        <div>
            @if($investorCount > 0)
            <span class="badge bg-light text-dark">
                إجمالي النسبة: {{ number_format($sumPct, 2) }}% — إجمالي المشاركة: {{ number_format($sumVal, 2) }}
            </span>
            @endif
            @if($investorCount > 0)
            <span class="badge bg-light text-dark">👤 عدد المستثمرين: {{ $investorCount }}</span>
            @endif
        </div>
    </div>

    <div class="card-body p-0">
        @if($sumPct < 100)
            <div class="p-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInvestorModal">
                    ➕ إضافة مستثمر
                </button>
            </div>
        @endif

        <div id="contract-investors-list">
            @include('contracts.partials.investors_table', ['contract' => $contract])
        </div>
    </div>
</div>

{{-- مودال إضافة المستثمر --}}
<div class="modal fade" id="addInvestorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="investors-form">
                @csrf
                <input type="hidden" name="contract_id" value="{{ $contract->id }}">
                <input type="hidden" id="contract_value" value="{{ $contract->contract_value }}">

                <div class="modal-header">
                    <h5 class="modal-title">➕ إضافة مستثمر للعقد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered text-center align-middle">
                        <thead>
                            <tr>
                                <th>المستثمر</th>
                                <th>النسبة (%)</th>
                                <th>القيمة</th>
                                <th>إجراء</th>
                            </tr>
                        </thead>
                        <tbody id="investors-table-body">
                            <tr>
                                <td>
                                    <select name="investors[0][id]" class="form-select required">
                                        <option value="">-- اختر --</option>
                                        @foreach($investors as $inv)
                                            <option value="{{ $inv->id }}">{{ $inv->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="1.00" name="investors[0][share_percentage]" class="form-control required" value="100">
                                </td>
                                <td>
                                    <input type="number" step="1.00" name="investors[0][share_value]" class="form-control required" value="{{ number_format($contract->contract_value, 2, '.', '') }}">
                                </td>
                                <td></td> {{-- لا يوجد زر حذف في الصف الأول --}}
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" id="add-investor-row" class="btn btn-outline-primary btn-sm">+ إضافة مستثمر آخر</button>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success" disabled>💾 حفظ</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    let investorIndex = 1;
    const addBtn = document.getElementById("add-investor-row");
    const tableBody = document.getElementById("investors-table-body");
    const saveBtn = document.querySelector("#investors-form button[type='submit']");

    const getContractValue = () => parseFloat(document.getElementById("contract_value")?.value || 0) || 0;

    function getUsedPercentage() {
        let totalPct = 0;
        document.querySelectorAll("#investors-table-body [name*='share_percentage']").forEach(input => {
            totalPct += parseFloat(input.value) || 0;
        });
        return parseFloat(totalPct.toFixed(2));
    }

    function toggleAddButton() {
        let lastSelect = tableBody.querySelector("tr:last-child select");
        let lastSelected = lastSelect && lastSelect.value;
        addBtn.disabled = (getUsedPercentage() >= 100 || !lastSelected);
    }

    function checkFormValidity() {
        let allSelected = true;
        let totalPct = getUsedPercentage();
        tableBody.querySelectorAll("select").forEach(sel => {
            if (!sel.value) allSelected = false;
        });
        saveBtn.disabled = !(allSelected && totalPct === 100);
    }

    function updateShareValue(row) {
        const pctInput = row.querySelector("[name*='share_percentage']");
        const valInput = row.querySelector("[name*='share_value']");
        let contractValue = getContractValue();
        let pct = parseFloat(pctInput.value) || 0;
        valInput.value = (contractValue * pct / 100).toFixed(2);
        toggleAddButton();
        checkFormValidity();
    }

    function updateSharePercentage(row) {
        const pctInput = row.querySelector("[name*='share_percentage']");
        const valInput = row.querySelector("[name*='share_value']");
        let contractValue = getContractValue();
        let val = parseFloat(valInput.value) || 0;
        pctInput.value = ((val / contractValue) * 100).toFixed(2);
        toggleAddButton();
        checkFormValidity();
    }

    function updateInvestorOptions() {
        let selectedIds = [];
        document.querySelectorAll("#investors-table-body select").forEach(sel => {
            if (sel.value) selectedIds.push(sel.value);
        });
        document.querySelectorAll("#investors-table-body select").forEach(sel => {
            sel.querySelectorAll("option").forEach(opt => {
                opt.disabled = (opt.value && selectedIds.includes(opt.value) && opt.value !== sel.value);
            });
        });
        toggleAddButton();
        checkFormValidity();
    }

    function bindEvents(row) {
        row.querySelector("[name*='share_percentage']")?.addEventListener("input", () => updateShareValue(row));
        row.querySelector("[name*='share_value']")?.addEventListener("input", () => updateSharePercentage(row));
        row.querySelector("select")?.addEventListener("change", updateInvestorOptions);
    }

    document.querySelectorAll("#investors-table-body tr").forEach(bindEvents);
    updateInvestorOptions();

    addBtn.addEventListener("click", function () {
        let remainingPct = (100 - getUsedPercentage()).toFixed(2);
        let contractValue = getContractValue();
        let defaultValue = (contractValue * remainingPct / 100).toFixed(2);

        let newRow = document.createElement("tr");
        newRow.innerHTML = `
            <td>
                <select name="investors[${investorIndex}][id]" class="form-select required">
                    <option value="">-- اختر --</option>
                    @foreach($investors as $inv)
                        <option value="{{ $inv->id }}">{{ $inv->name }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" step="1.00" name="investors[${investorIndex}][share_percentage]" value="${remainingPct}" class="form-control required"></td>
            <td><input type="number" step="1.00" name="investors[${investorIndex}][share_value]" value="${defaultValue}" class="form-control required"></td>
            <td><button type="button" class="btn btn-danger btn-sm remove-investor">حذف</button></td>
        `;
        tableBody.appendChild(newRow);
        bindEvents(newRow);
        updateInvestorOptions();
        investorIndex++;
    });

    tableBody.addEventListener("click", function (e) {
        if (e.target.classList.contains("remove-investor")) {
            e.target.closest("tr").remove();
            updateInvestorOptions();
        }
    });

    // إرسال AJAX
    document.getElementById("investors-form").addEventListener("submit", function(e) {
        e.preventDefault();
        fetch("{{ route('contracts.investors.store') }}", {
            method: "POST",
            body: new FormData(this),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                console.log(data.errors);
            }
        });
    });

    // إعادة ضبط المودال
    document.getElementById("addInvestorModal").addEventListener("hidden.bs.modal", function () {
        const form = this.querySelector("form");
        form.reset();
        tableBody.querySelectorAll("tr").forEach((row, index) => index > 0 && row.remove());
        addBtn.disabled = false;
        document.querySelectorAll("#investors-table-body select option").forEach(opt => opt.disabled = false);
        checkFormValidity();
    });
});
</script>
