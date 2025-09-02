{{-- البطاقة: المستثمرون --}}
<div class="card shadow-sm mb-4">
    @php
        $sumPct = $contract->investors->sum(fn($i) => (float)$i->pivot->share_percentage);
        $sumVal = $contract->investors->sum(fn($i) => (float)$i->pivot->share_value);
        $investorCount = $contract->investors->count();
    @endphp

    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <strong>المستثمرون</strong>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            @if($investorCount > 0)
                <span class="badge bg-light text-dark">
                    إجمالي النسبة: {{ number_format($sumPct, 0) }}% — إجمالي المشاركة: {{ number_format($sumVal, 0) }}
                </span>
                <span class="badge bg-light text-dark">👤 عدد المستثمرين: {{ $investorCount }}</span>
            @endif
            {{-- <span id="outside-remaining" class="badge bg-warning text-dark">المتبقي: —%</span> --}}
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
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span id="helper-remaining" class="badge bg-info text-dark">المتبقي: —%</span>
          </div>

          <table class="table table-bordered text-center align-middle">
            <thead>
              <tr>
                <th style="width:40%">المستثمر / السيولة</th>
                <th style="width:20%">النسبة (%)</th>
                <th style="width:25%">القيمة</th>
                <th style="width:15%">إجراء</th>
              </tr>
            </thead>
            <tbody id="investors-table-body">
              {{-- المستثمرون الحاليون (Disabled) --}}
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
            <span class="save-text">💾 @lang('app.Save')</span>
            <span class="spinner-border spinner-border-sm align-middle ms-2 d-none" role="status" aria-hidden="true"></span>
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('app.Cancel')</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- شكل ويدجت السيولة --}}


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

    /* ===== سيولة المستثمر (Ajax اختياري) ===== */
    const CASH_URL_TPL = "{{ url('/investors') }}/{id}/cash";
    const cashCache = Object.create(null);
    async function fetchInvestorCash(investorId){
      if (!investorId) return null;
      if (cashCache[investorId] !== undefined) return cashCache[investorId];
      try{
        const url = CASH_URL_TPL.replace('{id}', investorId);
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        const val = Number(data.cash ?? data.balance ?? 0);
        cashCache[investorId] = isFinite(val) ? val : 0;
        return cashCache[investorId];
      }catch(e){
        cashCache[investorId] = null;
        return null;
      }
    }

    function getRowIO(tr){
      return {
        select: tr.querySelector('select'),
        pct:    tr.querySelector('[name*="share_percentage"]'),
        value:  tr.querySelector('[name*="share_value"]'),
        cashText:  tr.querySelector('.js-cash-text'),
        cashBar:   tr.querySelector('.js-cash-bar'),
        cashState: tr.querySelector('.js-cash-state'),
        neededText:tr.querySelector('.js-needed-text'),
        updated:   tr.querySelector('.js-cash-updated'),
        spinner:   tr.querySelector('.js-cash-spinner'),
      };
    }

    /* ===== حسابات النسب/القيم والمتبقي ===== */
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
      return Math.max(0, 100 - used);
    }

    function syncValFromPct(pct) { return Math.round((contractVal * pct) / 100); }
    function syncPctFromVal(val) {
      if (contractVal <= 0) return 0;
      return Math.max(0, Math.min(100, Math.round((val / contractVal) * 100)));
    }

    function investorCashForRow(tr){
      const raw = Number(tr.querySelector('.js-cash-text')?.dataset.raw);
      return isFinite(raw) ? raw : NaN;
    }

    // الحدود القصوى: لا تتجاوز النسبة المتبقية ولا قيمة العقد المتبقية؛ ولو فيه سيولة المستثمر تقلّم الحد.
    function maxPctForRow(tr){
      const remPct = remainingWithout(tr);
      const cash = investorCashForRow(tr);
      if (isFinite(cash) && contractVal > 0){
        const cashPct = (cash / contractVal) * 100;
        return Math.max(0, Math.min(remPct, cashPct, 100));
      }
      return Math.max(0, Math.min(remPct, 100));
    }
    function maxValForRow(tr){
      const remPct = remainingWithout(tr);
      let maxByRemain = Math.round(contractVal * (remPct / 100));
      let out = maxByRemain;
      const cash = investorCashForRow(tr);
      if (isFinite(cash)) out = Math.min(out, Math.round(cash));
      return Math.max(0, out);
    }

    // تحديث خصائص max على الحقول لحظيًا
    function updateRowMaxConstraints(tr){
      const { pct, value } = getRowIO(tr);
      if (!pct || !value) return;
      const mp = Math.floor(maxPctForRow(tr));
      const mv = maxValForRow(tr);
      pct.setAttribute('max', String(Math.max(0, mp)));
      value.setAttribute('max', String(Math.max(0, mv)));
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

    function checkFormValidity() {
      updateOptionsDisable();

      // حدّث قيود max للكل
      getNewRows().forEach(tr => updateRowMaxConstraints(tr));

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
        // لن نُمكّن الحفظ إلا إذا أصبح الإجمالي = 100%، وكل الصفوف مختارة وصحيحة
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

      // تجميل التغطية/المطلوب
      getNewRows().forEach(tr=>{
        const io = getRowIO(tr);
        const pct = parseInt(io.pct?.value || "0", 10) || 0;
        const need = Math.round((contractVal * pct)/100);
        if (io.neededText) io.neededText.textContent = need ? need.toLocaleString('ar-EG') : '—';
        const cashRaw = Number(io.cashText?.dataset.raw || 0);
        if (io.cashBar && io.cashState){
          const coverage = need>0 ? (cashRaw/need)*100 : (cashRaw>0?100:0);
          const p = Math.max(0, Math.min(coverage, 200));
          io.cashBar.style.width = p + '%';
          io.cashBar.className = 'progress-bar js-cash-bar ' + (need===0&&cashRaw===0?'bg-secondary': p>=100?'bg-success': p>=50?'bg-info': p>0?'bg-warning':'bg-danger');
          io.cashState.className = 'badge rounded-pill js-cash-state ' + (need===0&&cashRaw===0?'bg-secondary': p>=100?'bg-success': p>=50?'bg-info': p>0?'bg-warning':'bg-danger');
          io.cashState.textContent = (need===0&&cashRaw===0)?'—': p>=100?'مُغطّى': p>=50?'جيد': p>0?'منخفض':'لا توجد سيولة';
          io.cashState.title = `تغطية: ${p.toFixed(2)}%`;
        }
      });
    }

    function bindRowEvents(tr) {
      const { pct, value, select } = getRowIO(tr);
      if (pct){ pct.min = "0"; pct.max = "100"; pct.step = "1"; }
      if (value){ value.min = "0"; value.step = "1"; }

      updateRowMaxConstraints(tr);

      pct?.addEventListener("input", () => {
        // منع > 100 وأيضًا > المتبقي
        let p = parseInt(pct.value || "0", 10) || 0;
        if (p < 0) p = 0;
        if (p > 100) p = 100;
        const mp = Math.floor(maxPctForRow(tr));
        if (mp <= 0) p = 0; else if (p > mp) p = mp;
        pct.value = p;
        value.value = syncValFromPct(p);
        checkFormValidity();
      });

      pct?.addEventListener("blur", () => {
        let p = parseInt(pct.value || "0", 10) || 0;
        if (p > 100) p = 100;
        const mp = Math.floor(maxPctForRow(tr));
        if (mp <= 0) { p = 0; value.value = 0; }
        else { if (p > mp) p = mp; if (p < 0) p = 0; value.value = syncValFromPct(p); }
        pct.value = p;
        checkFormValidity();
      });

      value?.addEventListener("input", () => {
        let v = parseInt(value.value || "0", 10) || 0;
        if (v < 0) v = 0;
        const mv = maxValForRow(tr);
        if (mv <= 0) v = 0; else if (v > mv) v = mv;
        value.value = v;
        pct.value = syncPctFromVal(v);
        // حد أقصى للنسبة 100
        if (parseInt(pct.value||"0",10) > 100) pct.value = "100";
        checkFormValidity();
      });

      value?.addEventListener("blur", () => {
        let v = parseInt(value.value || "0", 10) || 0;
        const mv = maxValForRow(tr);
        if (mv <= 0) v = 0; else if (v > mv) v = mv;
        value.value = v;
        let p = syncPctFromVal(v);
        if (p > 100) p = 100;
        const mp = Math.floor(maxPctForRow(tr));
        pct.value = (mp <= 0) ? 0 : Math.min(p, mp);
        value.value = syncValFromPct(parseInt(pct.value||"0",10));
        checkFormValidity();
      });

      select?.addEventListener("change", () => {
        checkFormValidity();
        const id = select.value;
        if (!id) return;
        (async ()=>{
          try{
            const cash = await fetchInvestorCash(id);
            const io = getRowIO(tr);
            if (io.cashText){
              const val = (cash===null)?0:cash;
              io.cashText.dataset.raw = String(val);
              io.cashText.textContent = (cash===null)?'غير متاح': val.toLocaleString('ar-EG',{minimumFractionDigits:2,maximumFractionDigits:2});
            }
          }catch{}
          updateRowMaxConstraints(tr);
          checkFormValidity();
        })();
      });
    }

    function addNewRow(defaultPct, index) {
      const defaultVal = syncValFromPct(defaultPct);
      const tr = document.createElement("tr");
      tr.classList.add("new-row");
      tr.innerHTML = `
        <td>
          <div class="d-grid gap-2 text-start">
            <select name="investors[${index}][id]" class="form-select required">
              <option value="">-- اختر --</option>
              @foreach($investors as $inv)
                <option value="{{ $inv->id }}">{{ $inv->name }}</option>
              @endforeach
            </select>
            <div class="liq-box p-2 rounded border position-relative">
              <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill bg-secondary js-cash-state">—</span>
                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between small text-muted">
                    <span>السيولة المتاحة</span>
                    <span class="js-cash-updated">—</span>
                  </div>
                  <div class="progress mt-1">
                    <div class="progress-bar js-cash-bar" role="progressbar" style="width:0%"></div>
                  </div>
                  <div class="mt-1 d-flex align-items-center flex-wrap gap-2">
                    <span class="fw-semibold js-cash-text" data-raw="0">—</span>
                    <span class="text-muted">ريال</span>
                    <span class="text-muted small">| المطلوب: <span class="js-needed-text">—</span> ريال</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary js-copy-cash" title="نسخ القيمة">نسخ</button>
                  </div>
                </div>
              </div>
              <div class="spinner-border spinner-border-sm text-secondary position-absolute top-0 end-0 m-2 d-none js-cash-spinner" role="status" aria-hidden="true"></div>
            </div>
          </div>
        </td>
        <td><input type="number" step="1" min="0" max="100" name="investors[${index}][share_percentage]" class="form-control required" value="${defaultPct}"></td>
        <td><input type="number" step="1" min="0" name="investors[${index}][share_value]" class="form-control required" value="${defaultVal}"></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-investor">حذف</button></td>
      `;
      tableBody.appendChild(tr);
      bindRowEvents(tr);
      updateRowMaxConstraints(tr);
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

      // تحديث قيود الصفوف القديمة (للاحتياط)
      qsa('tr.existing-row', tableBody).forEach(tr => updateRowMaxConstraints(tr));
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
      if (e.target.classList.contains('js-copy-cash')) {
        const tr = e.target.closest('tr');
        const val = tr.querySelector('.js-cash-text')?.dataset.raw || '';
        if (val !== '') {
          navigator.clipboard.writeText(val).then(()=> {
            e.target.textContent = 'نُسخ';
            setTimeout(()=> e.target.textContent='نسخ', 900);
          });
        }
      }
    });

    /* حفظ Ajax مع fallback */
    const form = document.getElementById("investors-form");
    function setSaving(isSaving) {
      if (!saveBtn) return;
      saveBtn.disabled = true;
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
          headers: { "X-Requested-With": "XMLHttpRequest", "Accept":"application/json", ...(token?{"X-CSRF-TOKEN":token}:{}) },
          body: fd, redirect: "follow"
        });
        if (res.redirected) { window.location.href = res.url; return; }
        if ([301,302,303,307,308,405].includes(res.status)) { form.removeEventListener("submit", onSubmit); form.submit(); return; }
        const ct = res.headers.get("content-type") || "";
        if (!res.ok) { alert("تعذّر الحفظ. تأكد من صحة البيانات."); setSaving(false); return; }
        const data = ct.includes("application/json") ? await res.json() : null;
        if (!data || data.success) location.reload();
        else { alert("مطلوب استكمال البيانات بشكل صحيح."); setSaving(false); }
      } catch (err) {
        form.removeEventListener("submit", onSubmit);
        form.submit();
      }
    }
    form.addEventListener("submit", onSubmit);

    // تحديث بادج المتبقي خارج المودال عند تحميل الصفحة
    updateOutsideRemaining();
  });
</script>

