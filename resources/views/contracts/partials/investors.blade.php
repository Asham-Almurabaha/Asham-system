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
      <form id="investors-form" action="{{ route('contracts.investors.store') }}" method="POST" novalidate>
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
          <button type="submit" class="btn btn-success" disabled>
            <span class="save-text">💾 حفظ</span>
            <span class="spinner-border spinner-border-sm align-middle ms-2 d-none" role="status" aria-hidden="true"></span>
          </button>
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
    const saveTxt     = saveBtn?.querySelector(".save-text");
    const saveSpin    = saveBtn?.querySelector(".spinner-border");
    const helperRem   = document.getElementById("helper-remaining");
    const outsideRem  = document.getElementById("outside-remaining");
    const contractVal = parseInt(document.getElementById("contract_value").value || "0", 10) || 0;

    const modalEl = document.getElementById("addInvestorModal");

    function qsa(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }

    function getOldPercentage() {
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
      return rem;
    }

    function syncValFromPct(pct) { return Math.round((contractVal * pct) / 100); }

    function syncPctFromVal(val) {
      if (contractVal <= 0) return 1;
      return Math.max(1, Math.min(100, Math.round((val / contractVal) * 100)));
    }

    function clampPctForRow(tr, value) {
      const maxAvail = remainingWithout(tr);
      let pct = isNaN(value) ? 1 : value;
      if (pct < 1) pct = 1;
      if (pct > maxAvail) pct = maxAvail;
      return pct;
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
        if (btn) btn.disabled = (idx === 0);
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
      const totalPct = oldPct + newPct;

      let allSelected = true;
      getNewRows().forEach(tr => {
        const sel = tr.querySelector("select");
        if (!sel || !sel.value) allSelected = false;
      });

      const selectedNew   = getSelectedNewIds();
      const hasDuplicate  = (new Set(selectedNew)).size !== selectedNew.length;
      const intersectsOld = selectedNew.some(id => getExistingIds().includes(id));

      if (saveBtn) {
        saveBtn.disabled = !(allSelected && !hasDuplicate && !intersectsOld && totalPct === 100);
      }

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

      pctInput.addEventListener("input", () => {
        let pct = parseInt(pctInput.value || "0", 10) || 0;
        if (pct < 0) pct = 0;
        if (pct > 100) pct = 100;
        pctInput.value = pct;
        valInput.value = syncValFromPct(pct);
        checkFormValidity();
      });

      pctInput.addEventListener("blur", () => {
        let pct = parseInt(pctInput.value || "0", 10) || 1;
        pct = clampPctForRow(tr, pct);
        pctInput.value = pct;
        valInput.value = syncValFromPct(pct);
        checkFormValidity();
      });

      valInput.addEventListener("input", () => {
        let val = parseInt(valInput.value || "0", 10) || 0;
        if (val < 0) val = 0;
        valInput.value = val;
        const pct = syncPctFromVal(val);
        pctInput.value = pct;
        checkFormValidity();
      });

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
      getNewRows().forEach(tr => tr.remove());

      const oldPct    = getOldPercentage();
      const remaining = Math.max(0, 100 - oldPct);

      if (remaining > 0) {
        addNewRow(remaining, 0);
      }

      enforceFirstNewRowDeleteDisabled();
      updateOptionsDisable();
      showRemainingHelper();
      checkFormValidity();
    }

    modalEl.addEventListener("show.bs.modal", resetNewRowsAndBuildDefault);

    modalEl.addEventListener("hidden.bs.modal", () => {
      getNewRows().forEach(tr => tr.remove());
      updateOutsideRemaining();
    });

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

    tableBody.addEventListener("click", function (e) {
      if (e.target.classList.contains("remove-investor") && !e.target.disabled) {
        e.target.closest("tr").remove();
        enforceFirstNewRowDeleteDisabled();
        updateOptionsDisable();
        showRemainingHelper();
        checkFormValidity();
      }
    });

    /*** حفظ — محاولة AJAX أولاً ثم fallback لسبميت عادي عند أي Redirect/405/مش JSON ***/
    const form = document.getElementById("investors-form");

    function setSaving(isSaving) {
      if (!saveBtn) return;
      saveBtn.disabled = true; // يفضل يفضل Disabled أثناء الحفظ
      if (saveTxt && saveSpin) {
        saveSpin.classList.toggle("d-none", !isSaving);
      }
    }

    async function onSubmit(e) {
      e.preventDefault();

      const url   = form.getAttribute("action");
      const token = document.querySelector('meta[name="csrf-token"]')?.content;
      const fd    = new FormData(form);

      setSaving(true);

      try {
        const res = await fetch(url, {
          method: "POST",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json",
            ...(token ? { "X-CSRF-TOKEN": token } : {})
          },
          body: fd,
          redirect: "follow"
        });

        // لو السيرفر عمل Redirect (مثلاً redirect()->back() أو ->route(...))
        if (res.redirected) {
          window.location.href = res.url;
          return;
        }

        // لو الوضع أدى لـ 405/301/302 نتيجة تتبّع/سياسة السيرفر → نفّذ Submit عادي
        if ([301, 302, 303, 307, 308, 405].includes(res.status)) {
          form.removeEventListener("submit", onSubmit);
          form.submit();
          return;
        }

        const ct = res.headers.get("content-type") || "";

        if (!res.ok) {
          // حاول نقرأ JSON للأخطاء، وإلا نص خام
          let payload = null;
          try {
            payload = ct.includes("application/json") ? await res.json() : await res.text();
          } catch {}
          console.error("Server error:", payload || res.status);
          alert("تعذّر حفظ المستثمرين. تأكد من صحة البيانات.");
          setSaving(false);
          return;
        }

        // نجاح: لو JSON فيه success=true نعمل reload، وإلا كمان reload كتحديث
        let data = null;
        try {
          data = ct.includes("application/json") ? await res.json() : null;
        } catch {}

        if (!data || data.success) {
          location.reload();
        } else {
          console.warn("Validation errors:", data.errors || data);
          alert("مطلوب استكمال البيانات بشكل صحيح.");
          setSaving(false);
        }

      } catch (err) {
        console.error(err);
        // أي خطأ شبكة/كورسي → fallback للسبميت العادي
        form.removeEventListener("submit", onSubmit);
        form.submit();
      }
    }

    form.addEventListener("submit", onSubmit);

    // تحديث بادج المتبقي خارج المودال أول ما نحمّل الصفحة
    updateOutsideRemaining();
  });
</script>
