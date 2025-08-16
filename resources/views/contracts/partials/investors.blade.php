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
                    إجمالي النسبة: {{ number_format($sumPct, 0) }}% — إجمالي المشاركة: {{ number_format($sumVal, 0) }}
                </span>
                <span class="badge bg-light text-dark">👤 عدد المستثمرين: {{ $investorCount }}</span>
            @endif
        </div>
    </div>

    <div class="card-body p-0">
        @if($sumPct < 100)
            <div class="p-3 d-flex align-items-center gap-2">
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
        <input type="hidden" id="contract_value" value="{{ (int)$contract->contract_value }}">

        <div class="modal-header">
          <h5 class="modal-title">➕ إضافة مستثمر للعقد</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="d-flex justify-content-between align-items-center mb-2"></div>

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
              {{-- المستثمرون القدام (Disabled) --}}
              @foreach($contract->investors as $inv)
              <tr class="existing-row">
                <td>
                  <select class="form-select" disabled>
                    @foreach($investors as $allInv)
                      <option value="{{ $allInv->id }}" {{ $allInv->id == $inv->id ? 'selected' : '' }}>
                        {{ $allInv->name }}
                      </option>
                    @endforeach
                  </select>
                </td>
                <td>
                  {{-- نميز حقل النسبة القديم بعلامة data-type="pct" --}}
                  <input type="number" step="1" class="form-control" data-type="pct"
                         value="{{ (int)round($inv->pivot->share_percentage) }}" disabled>
                </td>
                <td>
                  <input type="number" step="1" class="form-control"
                         value="{{ (int)round($inv->pivot->share_value) }}" disabled>
                </td>
                <td><button type="button" class="btn btn-danger btn-sm" disabled>حذف</button></td>
              </tr>
              @endforeach
            </tbody>
          </table>

          <div class="d-flex gap-2">
            <button type="button" id="add-investor-row" class="btn btn-outline-primary btn-sm">+ إضافة مستثمر آخر</button>
          </div>
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
  const tableBody   = document.getElementById("investors-table-body");
  const addBtn      = document.getElementById("add-investor-row");
  const saveBtn     = document.querySelector("#investors-form button[type='submit']");
  const helperRem   = document.getElementById("helper-remaining");
  const outsideRem  = document.getElementById("outside-remaining");
  const contractVal = parseInt(document.getElementById("contract_value").value || "0", 10) || 0;

  const modalEl = document.getElementById("addInvestorModal");

  function qsa(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }

  function getOldPercentage() {
    // نجمع فقط حقول النسبة في الصفوف القديمة (المقفولة) عن طريق data-type="pct"
    return qsa('tr.existing-row input[data-type="pct"]', tableBody)
      .reduce((sum, input) => sum + (parseInt(input.value || "0", 10) || 0), 0);
  }

  function getNewRows() { return qsa("tr.new-row", tableBody); }

  function getNewPercentage() {
    return getNewRows().reduce((sum, tr) => {
      const pct = parseInt(tr.querySelector('[name*="share_percentage"]')?.value || "0", 10) || 0;
      return sum + pct;
    }, 0);
  }

  function getExistingIds() {
    // قيم المستثمرين القدام (المختارة في الـ select المقفول)
    return qsa("tr.existing-row select option:checked", tableBody)
      .map(o => o.value).filter(Boolean);
  }

  function getSelectedNewIds() {
    return getNewRows().map(tr => tr.querySelector("select")?.value).filter(Boolean);
  }

  function remainingWithout(trExcluded = null) {
    const oldPct = getOldPercentage();
    const newPctExcept = getNewRows().reduce((sum, tr) => {
      if (tr === trExcluded) return sum;
      const pct = parseInt(tr.querySelector('[name*="share_percentage"]')?.value || "0", 10) || 0;
      return sum + pct;
    }, 0);
    const used = oldPct + newPctExcept;
    const rem  = Math.max(0, 100 - used);
    return rem; // عدد صحيح
  }

  function syncValFromPct(pct) {
    // قيمة المشاركة = نسبة من قيمة العقد -> نقرب لأقرب عدد صحيح
    return Math.round((contractVal * pct) / 100);
  }

  function syncPctFromVal(val) {
    if (contractVal <= 0) return 1;
    return Math.max(1, Math.min(100, Math.round((val / contractVal) * 100)));
  }

  function clampPctForRow(tr, value) {
    const maxAvail = remainingWithout(tr);
    let pct = isNaN(value) ? 1 : value;
    if (pct < 1) pct = 1; // لا صفر
    if (pct > maxAvail) pct = maxAvail; // لا يزيد عن المتبقي
    return pct; // صحيح
  }

  function updateOptionsDisable() {
    const existingIds = new Set(getExistingIds());
    const selectedNew = new Set(getSelectedNewIds());
    getNewRows().forEach(tr => {
      const sel = tr.querySelector("select");
      const current = sel.value;
      qsa("option", sel).forEach(opt => {
        if (!opt.value) return;
        const shouldDisable =
          existingIds.has(opt.value) || (selectedNew.has(opt.value) && opt.value !== current);
        opt.disabled = shouldDisable;
      });
    });
  }

  function enforceFirstNewRowDeleteDisabled() {
    const newRows = getNewRows();
    newRows.forEach((tr, idx) => {
      const btn = tr.querySelector(".remove-investor");
      if (btn) btn.disabled = (idx === 0); // أول صف جديد مقفول حذفه
    });
  }

  function showRemainingHelper() {
    const oldPct = getOldPercentage();
    const newPct = getNewPercentage();
    const rem = Math.max(0, 100 - (oldPct + newPct));
    if (helperRem) helperRem.textContent = `المتبقي: ${rem}%`;
  }

  function updateOutsideRemaining() {
    if (!outsideRem) return;
    const oldPct = getOldPercentage();
    outsideRem.textContent = `المتبقي: ${Math.max(0, 100 - oldPct)}%`;
  }

  function checkFormValidity() {
    updateOptionsDisable();

    const oldPct   = getOldPercentage();
    const newPct   = getNewPercentage();
    const totalPct = oldPct + newPct; // كلّه أعداد صحيحة

    // لازم كل الصفوف الجديدة يكون لها مستثمر مختار
    let allSelected = true;
    getNewRows().forEach(tr => {
      const sel = tr.querySelector("select");
      if (!sel || !sel.value) allSelected = false;
    });

    // لا تكرار للمستثمرين الجدد ولا استخدام مستثمر قديم
    const selectedNew   = getSelectedNewIds();
    const hasDuplicate  = (new Set(selectedNew)).size !== selectedNew.length;
    const intersectsOld = selectedNew.some(id => getExistingIds().includes(id));

    // زر الحفظ: total == 100 && كل الصفوف مختارة && لا تكرار && لا تقاطع مع القدام
    if (saveBtn) {
      saveBtn.disabled = !(allSelected && !hasDuplicate && !intersectsOld && totalPct === 100);
    }

    // زر الإضافة: لازم آخر صف جديد يكون مختار ومستوفي الشروط، وإجمالي < 100
    const newRows = getNewRows();
    const lastNew = newRows.length ? newRows[newRows.length - 1] : null;
    const lastSelected = lastNew ? (lastNew.querySelector("select")?.value) : "";
    if (addBtn) {
      addBtn.disabled = !(lastSelected && !hasDuplicate && !intersectsOld && totalPct < 100);
    }

    enforceFirstNewRowDeleteDisabled();
    showRemainingHelper();
  }

  function bindRowEvents(tr) {
    const pctInput = tr.querySelector('[name*="share_percentage"]');
    const valInput = tr.querySelector('[name*="share_value"]');
    const select   = tr.querySelector("select");

    // أثناء الكتابة: نحدها بين 0..100 بدون قصّ للمتبقي، والقيم صحيحة
    pctInput.addEventListener("input", () => {
      let pct = parseInt(pctInput.value || "0", 10) || 0;
      if (pct < 0) pct = 0;
      if (pct > 100) pct = 100;
      pctInput.value = pct; // صحيح
      valInput.value = syncValFromPct(pct); // صحيح
      checkFormValidity();
    });

    // عند الخروج: قصّ إلى المتبقي وحد أدنى 1
    pctInput.addEventListener("blur", () => {
      let pct = parseInt(pctInput.value || "0", 10) || 1;
      pct = clampPctForRow(tr, pct);
      pctInput.value = pct;
      valInput.value = syncValFromPct(pct);
      checkFormValidity();
    });

    // القيمة: أثناء الكتابة نحولها لنسبة صحيحة بدون قصّ للمتبقي
    valInput.addEventListener("input", () => {
      let val = parseInt(valInput.value || "0", 10) || 0;
      if (val < 0) val = 0;
      valInput.value = val;
      const pct = syncPctFromVal(val);
      pctInput.value = pct;
      checkFormValidity();
    });

    // عند الخروج من القيمة: نشتق النسبة ونقصّها حسب المتبقي ثم نعيد احتساب القيمة
    valInput.addEventListener("blur", () => {
      let val = parseInt(valInput.value || "0", 10) || 1;
      if (val <= 0) val = 1;
      let pct = syncPctFromVal(val);
      pct = clampPctForRow(tr, pct);
      pctInput.value = pct;
      valInput.value = syncValFromPct(pct);
      checkFormValidity();
    });

    select.addEventListener("change", checkFormValidity);
  }

  function addNewRow(defaultPct, index) {
    const defaultVal = syncValFromPct(defaultPct);
    const tr = document.createElement("tr");
    tr.classList.add("new-row");
    tr.innerHTML = `
      <td>
        <select name="investors[${index}][id]" class="form-select required">
          <option value="">-- اختر --</option>
          @foreach($investors as $inv)
            <option value="{{ $inv->id }}">{{ $inv->name }}</option>
          @endforeach
        </select>
      </td>
      <td><input type="number" step="1" min="1" name="investors[${index}][share_percentage]" class="form-control required" value="${defaultPct}"></td>
      <td><input type="number" step="1" min="1" name="investors[${index}][share_value]" class="form-control required" value="${defaultVal}"></td>
      <td><button type="button" class="btn btn-danger btn-sm remove-investor">حذف</button></td>
    `;
    tableBody.appendChild(tr);
    bindRowEvents(tr);
  }

  function resetNewRowsAndBuildDefault() {
    // امسح الصفوف الجديدة فقط
    getNewRows().forEach(tr => tr.remove());

    const oldPct    = getOldPercentage();
    const remaining = Math.max(0, 100 - oldPct); // صحيح

    if (remaining > 0) {
      // أول صف جديد فقط، وحذفه سيُقفل لاحقاً
      addNewRow(remaining, 0);
    }

    enforceFirstNewRowDeleteDisabled();
    updateOptionsDisable();
    showRemainingHelper();
    checkFormValidity();
  }

  // عند فتح المودال: ابني الحالة الافتراضية حسب الموجود
  modalEl.addEventListener("show.bs.modal", resetNewRowsAndBuildDefault);

  // عند الإغلاق: اعمل reset بصري (نشيل الصفوف الجديدة) عشان لما يفتح تاني يبنيها حسب الحالة
  modalEl.addEventListener("hidden.bs.modal", () => {
    getNewRows().forEach(tr => tr.remove());
    // تحديث بادج المتبقي في الكارت الخارجي
    updateOutsideRemaining();
  });

  // زر الإضافة
  addBtn?.addEventListener("click", function () {
    const oldPct = getOldPercentage();
    const newPct = getNewPercentage();
    const remaining = Math.max(0, 100 - (oldPct + newPct));
    if (remaining <= 0) return;
    const idx = getNewRows().length;
    addNewRow(remaining, idx);
    enforceFirstNewRowDeleteDisabled();
    updateOptionsDisable();
    showRemainingHelper();
    checkFormValidity();
  });

  // زر الحذف (للصفوف الجديدة فقط)
  tableBody.addEventListener("click", function (e) {
    if (e.target.classList.contains("remove-investor") && !e.target.disabled) {
      e.target.closest("tr").remove();
      enforceFirstNewRowDeleteDisabled();
      updateOptionsDisable();
      showRemainingHelper();
      checkFormValidity();
    }
  });

  // حفظ
  document.getElementById("investors-form").addEventListener("submit", function (e) {
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
    })
    .catch(console.error);
  });

  // تحديث بادج المتبقي خارج المودال أول ما نحمّل الصفحة
  updateOutsideRemaining();
});
</script>
