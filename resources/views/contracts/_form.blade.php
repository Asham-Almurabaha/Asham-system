<div class="row g-3">
  {{-- العميل + الكفيل --}}
  <div class="col-md-6">
    <label for="customer_id" class="form-label">العميل <span class="text-danger">*</span></label>
    <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
      <option value="">اختر العميل</option>
      @foreach($customers as $customer)
        <option value="{{ $customer->id }}" {{ old('customer_id', ($contract->customer_id ?? null)) == $customer->id ? 'selected' : '' }}>
          {{ $customer->name }}
        </option>
      @endforeach
    </select>
    @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-6">
    <label for="guarantor_id" class="form-label">الكفيل</label>
    <select name="guarantor_id" id="guarantor_id" class="form-select @error('guarantor_id') is-invalid @enderror">
      <option value="">بدون كفيل</option>
      @foreach($guarantors as $guarantor)
        <option value="{{ $guarantor->id }}" {{ old('guarantor_id', ($contract->guarantor_id ?? null)) == $guarantor->id ? 'selected' : '' }}>
          {{ $guarantor->name }}
        </option>
      @endforeach
    </select>
    @error('guarantor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  @php
      $types = $productTypes ?? collect();
  @endphp

  <div class="col-md-3">
    <label for="product_type_id" class="form-label">نوع البضاعة <span class="text-danger">*</span></label>
    <select name="product_type_id" id="product_type_id" class="form-select @error('product_type_id') is-invalid @enderror" required>
      <option value="">اختر نوع البضاعة</option>
      @foreach($types as $type)
        <option value="{{ $type->id }}" {{ old('product_type_id', ($product->product_type_id ?? null)) == $type->id ? 'selected' : '' }}>
          {{ $type->name }}
        </option>
      @endforeach
    </select>
    @error('cproduct_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-3">
    <div class="d-flex justify-content-between align-items-center">
      <label for="products_count" class="form-label mb-0">العدد <span class="text-danger">*</span></label>
      <span id="available_count_badge" class="badge bg-light text-dark">المتاح: —</span>
    </div>
    <input type="number" name="products_count" id="products_count"
           class="form-control mt-2 @error('products_count') is-invalid @enderror"
           value="{{ old('products_count', ($contract->products_count ?? null)) }}"
           min="0" required inputmode="numeric" autocomplete="off" aria-describedby="available_count_hint">
    <small id="available_count_hint" class="text-muted">اختر نوع البضاعة لعرض العدد المتاح.</small>
    @error('products_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-3">
    <label for="purchase_price" class="form-label">سعر شراء البضائع <span class="text-danger">*</span></label>
    <input type="number" step="0.01" name="purchase_price" id="purchase_price"
           class="form-control @error('purchase_price') is-invalid @enderror"
           value="{{ old('purchase_price', ($contract->purchase_price ?? null)) }}"
           required inputmode="decimal" autocomplete="off">
    @error('purchase_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-3">
    <label for="sale_price" class="form-label">سعر البيع للمستثمر <span class="text-danger">*</span></label>
    <input type="number" step="1" name="sale_price" id="sale_price"
           class="form-control @error('sale_price') is-invalid @enderror"
           value="{{ old('sale_price', ($contract->sale_price ?? null)) }}"
           required inputmode="numeric" autocomplete="off">
    @error('sale_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- قيمة العقد / ربح المستثمر / الإجمالي --}}
  <div class="col-md-4">
    <label for="contract_value" class="form-label">قيمة العقد <span class="text-danger">*</span></label>
    <input type="number" step="1" name="contract_value" id="contract_value"
           class="form-control @error('contract_value') is-invalid @enderror bg-light"
           value="{{ old('contract_value', ($contract->contract_value ?? null)) }}"
           required inputmode="numeric" autocomplete="off">
    @error('contract_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-4">
    <label for="investor_profit" class="form-label">ربح المستثمر <span class="text-danger">*</span></label>
    <input type="number" step="1" name="investor_profit" id="investor_profit"
           class="form-control @error('investor_profit') is-invalid @enderror"
           value="{{ old('investor_profit', ($contract->investor_profit ?? null)) }}"
           required inputmode="numeric" autocomplete="off">
    @error('investor_profit') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-4">
    <label for="total_value" class="form-label">إجمالي قيمة العقد <span class="text-danger">*</span></label>
    <input type="number" step="1" name="total_value" id="total_value"
           class="form-control @error('total_value') is-invalid @enderror bg-light"
           value="{{ old('total_value', ($contract->total_value ?? null)) }}"
           required inputmode="numeric" autocomplete="off">
    @error('total_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- الأقساط --}}
  <div class="col-md-4">
    <label for="installment_type_id" class="form-label">نوع القسط <span class="text-danger">*</span></label>
    <select name="installment_type_id" id="installment_type_id" class="form-select @error('installment_type_id') is-invalid @enderror" required>
      <option value="">اختر نوع القسط</option>
      @foreach($installmentTypes as $type)
        <option value="{{ $type->id }}" {{ old('installment_type_id', ($contract->installment_type_id ?? null)) == $type->id ? 'selected' : '' }}>
          {{ $type->name }}
        </option>
      @endforeach
    </select>
    @error('installment_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-4">
    <label for="installment_value" class="form-label">قيمة القسط <span class="text-danger">*</span></label>
    <input type="number" step="0.01" name="installment_value" id="installment_value"
           class="form-control @error('installment_value') is-invalid @enderror"
           value="{{ old('installment_value', ($contract->installment_value ?? null)) }}"
           required inputmode="decimal" autocomplete="off">
    @error('installment_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-4">
    <label for="installments_count" class="form-label">عدد الأقساط <span class="text-danger">*</span></label>
    <input type="number" name="installments_count" id="installments_count"
           class="form-control @error('installments_count') is-invalid @enderror"
           value="{{ old('installments_count', ($contract->installments_count ?? null)) }}"
           min="1" required inputmode="numeric" autocomplete="off">
    @error('installments_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- التواريخ (Flatpickr) --}}
  <div class="row g-3">
    <div class="col-md-6">
      <label for="start_date" class="form-label">تاريخ بداية العقد <span class="text-danger">*</span></label>
      <input type="text" id="start_date" name="start_date"
             class="form-control js-date @error('start_date') is-invalid @enderror"
             value="{{ old('start_date', ($contract->start_date?->format('Y-m-d') ?? '')) }}"
             placeholder="YYYY-MM-DD" autocomplete="off" required>
      @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
      <label for="first_installment_date" class="form-label">تاريخ أول قسط</label>
      <input type="text" id="first_installment_date" name="first_installment_date"
             class="form-control js-date @error('first_installment_date') is-invalid @enderror"
             value="{{ old('first_installment_date', ($contract->first_installment_date?->format('Y-m-d') ?? '')) }}"
             placeholder="YYYY-MM-DD" autocomplete="off">
      @error('first_installment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- الصور --}}
  <div class="col-md-12 mb-3">
    <label for="contract_image" class="form-label">صورة العقد</label>
    <input type="file" name="contract_image" id="contract_image"
           class="form-control @error('contract_image') is-invalid @enderror" accept="image/*">
    @error('contract_image') <div class="invalid-feedback">{{ $message }}</div> @enderror

    @isset($contract)
      @if(!empty($contract->contract_image))
        <div class="mt-2">
          <small class="text-muted d-block mb-1">الصورة الحالية:</small>
          <img src="{{ asset('storage/'.$contract->contract_image) }}" alt="صورة العقد" style="max-height: 180px;">
          <div class="text-muted mt-1">رفع صورة جديدة سيستبدل الحالية.</div>
        </div>
      @endif
    @endisset
  </div>

  <div class="col-md-6 mb-3">
    <label for="contract_customer_image" class="form-label">صورة سند الأمر (العميل)</label>
    <input type="file" name="contract_customer_image" id="contract_customer_image"
           class="form-control @error('contract_customer_image') is-invalid @enderror" accept="image/*">
    @error('contract_customer_image') <div class="invalid-feedback">{{ $message }}</div> @enderror

    @isset($contract)
      @if(!empty($contract->contract_customer_image))
        <div class="mt-2">
          <small class="text-muted d-block mb-1">الصورة الحالية:</small>
          <img src="{{ asset('storage/'.$contract->contract_customer_image) }}" alt="سند الأمر (العميل)" style="max-height: 180px;">
          <div class="text-muted mt-1">رفع صورة جديدة سيستبدل الحالية.</div>
        </div>
      @endif
    @endisset
  </div>

  <div class="col-md-6 mb-3">
    <label for="contract_guarantor_image" class="form-label">صورة سند الأمر (الكفيل)</label>
    <input type="file" name="contract_guarantor_image" id="contract_guarantor_image"
           class="form-control @error('contract_guarantor_image') is-invalid @enderror" accept="image/*">
    @error('contract_guarantor_image') <div class="invalid-feedback">{{ $message }}</div> @enderror

    @isset($contract)
      @if(!empty($contract->contract_guarantor_image))
        <div class="mt-2">
          <small class="text-muted d-block mb-1">الصورة الحالية:</small>
          <img src="{{ asset('storage/'.$contract->contract_guarantor_image) }}" alt="سند الأمر (الكفيل)" style="max-height: 180px;">
          <div class="text-muted mt-1">رفع صورة جديدة سيستبدل الحالية.</div>
        </div>
      @endif
    @endisset
  </div>

  {{-- المستثمرون --}}
  <div class="col-md-12">
    <h6 class="form-label">المستثمرون</h6>
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
        @php
          $oldInvestors = old('investors');
          $rows = [];

          if (is_array($oldInvestors)) {
            $rows = $oldInvestors;
          } else {
            if (isset($contract) && $contract->relationLoaded('investors')) {
              foreach ($contract->investors as $i => $inv) {
                $rows[] = [
                  'id' => $inv->id,
                  'share_percentage' => $inv->pivot->share_percentage,
                  'share_value' => $inv->pivot->share_value,
                ];
              }
            }
          }

          if (empty($rows)) {
            $rows[] = ['id' => '', 'share_percentage' => '', 'share_value' => ''];
          }
        @endphp

        @foreach($rows as $i => $row)
          <tr>
            <td>
              <div class="d-grid gap-2 text-start">
                <select name="investors[{{ $i }}][id]" id="investor_id_{{ $i }}" class="form-select investor-select" aria-label="المستثمر" data-row="{{ $i }}">
                  <option value="">-- اختر --</option>
                  @foreach($investors as $inv)
                    <option value="{{ $inv->id }}" {{ (string)($row['id'] ?? '') === (string)$inv->id ? 'selected' : '' }}>
                      {{ $inv->name }}
                    </option>
                  @endforeach
                </select>

                {{-- ويدجت السيولة --}}
                <div class="liq-box p-2 rounded border position-relative">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill bg-secondary js-cash-state">—</span>
                    <div class="flex-grow-1">
                      <div class="d-flex justify-content-between small text-muted">
                        <span>السيولة المتاحة</span>
                        <span class="js-cash-updated">—</span>
                      </div>
                      <div class="progress mt-1" style="height:6px;">
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

            <td>
              <input type="number" step="0.01" min="0" max="100"
                     name="investors[{{ $i }}][share_percentage]"
                     class="form-control" inputmode="decimal" autocomplete="off"
                     value="{{ $row['share_percentage'] ?? '' }}" aria-label="نسبة المستثمر (%)">
            </td>

            <td>
              <input type="number" step="0.01" name="investors[{{ $i }}][share_value]"
                     class="form-control" inputmode="decimal" autocomplete="off"
                     value="{{ $row['share_value'] ?? '' }}" aria-label="قيمة المستثمر">
            </td>

            <td>
              <button type="button" class="btn btn-danger btn-sm remove-investor">حذف</button>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <button type="button" id="add-investor" class="btn btn-outline-primary btn-sm">+ إضافة مستثمر</button>
  </div>
</div>

@push('styles')

@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  // عناصر أساسية
  const saleInput        = document.getElementById('sale_price');
  const contractInput    = document.getElementById('contract_value');
  const profitInput      = document.getElementById('investor_profit');
  const totalInput       = document.getElementById('total_value');

  const instValueInput   = document.getElementById('installment_value');
  const instCountInput   = document.getElementById('installments_count');

  const productTypeSelect  = document.getElementById('product_type_id');
  const productsCountInput = document.getElementById('products_count');
  const availableBadge     = document.getElementById('available_count_badge');

  const tbody            = document.getElementById('investors-table-body');
  const addBtn           = document.getElementById('add-investor');

  if (!saleInput || !contractInput || !profitInput || !totalInput || !instValueInput || !instCountInput || !tbody) return;

  // 🔒 منع تعديل يدوي لقيمة العقد/الإجمالي
  makeReadOnly(contractInput); makeReadOnly(totalInput);
  function makeReadOnly(el){
    if (!el) return;
    el.readOnly = true; el.classList.add('bg-light');
    el.addEventListener('beforeinput', e => e.preventDefault());
    el.addEventListener('keydown', function(e){ const k=e.key; if (k==='Tab'||k==='Shift'||k.startsWith('Arrow')) return; e.preventDefault(); });
    el.addEventListener('paste', e => e.preventDefault());
    el.addEventListener('drop',  e => e.preventDefault());
    el.addEventListener('wheel', e => e.preventDefault(), { passive:false });
  }

  // أدوات أرقام
  function toNumber(v){
    if (v == null) return 0;
    v = String(v).trim();
    if (!v) return 0;
    if (v.indexOf(',') > -1 && v.indexOf('.') > -1) {
      const lc = v.lastIndexOf(','), ld = v.lastIndexOf('.');
      v = (lc > ld) ? v.replace(/\./g,'').replace(',','.') : v.replace(/,/g,'');
    } else {
      v = (v.indexOf(',') > -1 && v.indexOf('.') === -1) ? v.replace(/\./g,'').replace(',','.') : v.replace(/,/g,'');
    }
    const n = parseFloat(v);
    return isNaN(n) ? 0 : n;
  }
  function clamp(n,min,max){ return Math.min(Math.max(n,min),max); }
  function fmt2(n){ return Number.isFinite(n) ? n.toFixed(2) : ''; }
  function fmtInt(n){ return Number.isFinite(n) ? String(Math.round(n)) : ''; }
  const fmtCurrency = new Intl.NumberFormat('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  // إجمالي العقد
  function recalcTotal(){
    const contractVal  = toNumber(contractInput.value);
    const investorProf = toNumber(profitInput.value);
    const total        = contractVal + investorProf;
    totalInput.value   = (String(contractInput.value).trim() !== '' || String(profitInput.value).trim() !== '')
                         ? fmtInt(total) : '';
  }

  // نسخ سعر البيع إلى قيمة العقد
  function copySaleToContract(){
    const sale = toNumber(saleInput.value);
    contractInput.value = sale ? fmtInt(sale) : '';
    recalcTotal(); recalcAllInvestors(); onTotalChange();
  }

  // الأقساط (ترابط تبادلي)
  let lastChanged = null; // 'value' | 'count' | null
  function setCountFromValue(){
    const total = toNumber(totalInput.value);
    const val   = toNumber(instValueInput.value);
    if (total <= 0 || val <= 0) { instCountInput.value = ''; return; }
    instCountInput.value = Math.ceil(total / val);
  }
  function setValueFromCount(){
    const total = toNumber(totalInput.value);
    const cnt   = Math.max(1, parseInt(toNumber(instCountInput.value), 10) || 0);
    if (total <= 0 || cnt <= 0) { instValueInput.value = ''; return; }
    instValueInput.value = fmt2(total / cnt);
  }
  function onValueChange(){ lastChanged = 'value'; setCountFromValue(); }
  function onCountChange(){ lastChanged = 'count'; setValueFromCount(); }
  function onTotalChange(){
    if (lastChanged === 'value')      setCountFromValue();
    else if (lastChanged === 'count') setValueFromCount();
    else {
      if (String(instValueInput.value).trim() !== '') setCountFromValue();
      else if (String(instCountInput.value).trim() !== '') setValueFromCount();
    }
    updateNeededForAll();
  }

  // ===== ويدجت السيولة (المستثمر) =====
  const CASH_URL_TPL = "{{ url('/investors') }}/{id}/cash";
  const cashCache = Object.create(null);

  async function fetchInvestorCash(investorId){
    if (!investorId) return null;
    if (cashCache[investorId] !== undefined) return cashCache[investorId];
    try{
      const url = CASH_URL_TPL.replace('{id}', investorId);
      const res = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials:'same-origin' });
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
      select: tr.querySelector('select[name$="[id]"]'),
      pct:    tr.querySelector('input[name$="[share_percentage]"]'),
      value:  tr.querySelector('input[name$="[share_value]"]'),
      cashBox:   tr.querySelector('.liq-box'),
      cashState: tr.querySelector('.js-cash-state'),
      cashBar:   tr.querySelector('.js-cash-bar'),
      cashText:  tr.querySelector('.js-cash-text'),
      neededText:tr.querySelector('.js-needed-text'),
      updated:   tr.querySelector('.js-cash-updated'),
      spinner:   tr.querySelector('.js-cash-spinner'),
    };
  }

  // حسابات حدود المستثمر
  function sumOtherPercent(tr){
    const base = toNumber(contractInput.value);
    let sum = 0;
    tbody.querySelectorAll('tr').forEach(r=>{
      if (r === tr) return;
      const io = getRowIO(r);
      let p = toNumber(io.pct?.value || '');
      if (!p && base>0) {
        const v = toNumber(io.value?.value || '');
        if (v>0) p = (v/base)*100;
      }
      sum += p;
    });
    return Math.max(0, sum);
  }
  function remainingPercent(tr){ return clamp(100 - sumOtherPercent(tr), 0, 100); }
  function investorCashForRow(tr){
    const io = getRowIO(tr);
    const raw = Number(io.cashText?.dataset.raw);
    return isFinite(raw) ? raw : NaN;
  }
  function maxPctForRow(tr, base){
    let maxPct = remainingPercent(tr);
    const cash = investorCashForRow(tr);
    if (isFinite(cash) && base>0){
      maxPct = Math.min(maxPct, (cash/base)*100);
    }
    return clamp(maxPct, 0, 100);
  }
  function maxValueForRow(tr, base){
    const remPct = remainingPercent(tr);
    let remVal = base * (remPct/100);
    remVal = Math.min(remVal, base);
    const cash = investorCashForRow(tr);
    if (isFinite(cash)) remVal = Math.min(remVal, cash);
    return Math.max(0, remVal);
  }

  // المطلوب للمستثمر
  function neededForRow(tr){
    const { pct, value } = getRowIO(tr);
    const base = toNumber(contractInput.value);
    const byVal = toNumber(value?.value || 0);
    if (byVal > 0) return byVal;
    const p = toNumber(pct?.value || 0);
    if (base > 0 && p > 0) return (base * p)/100;
    return 0;
  }

  function paintCoverage(io, cash, need){
    const coverage = need > 0 ? (cash/need)*100 : (cash>0 ? 100 : 0);
    const pct = Math.max(0, Math.min(coverage, 200));
    if (io.cashBar){
      io.cashBar.style.width = pct + '%';
      io.cashBar.className = 'progress-bar js-cash-bar';
      if (need === 0 && cash === 0) io.cashBar.classList.add('bg-secondary');
      else if (pct >= 100) io.cashBar.classList.add('bg-success');
      else if (pct >= 50)  io.cashBar.classList.add('bg-info');
      else if (pct > 0)    io.cashBar.classList.add('bg-warning');
      else                 io.cashBar.classList.add('bg-danger');
    }
    if (io.cashState){
      io.cashState.className = 'badge rounded-pill js-cash-state';
      if (need === 0 && cash === 0){ io.cashState.classList.add('bg-secondary'); io.cashState.textContent='—'; }
      else if (pct >= 100){ io.cashState.classList.add('bg-success'); io.cashState.textContent='مُغطّى'; }
      else if (pct >= 50){  io.cashState.classList.add('bg-info');    io.cashState.textContent='جيد'; }
      else if (pct > 0){    io.cashState.classList.add('bg-warning'); io.cashState.textContent='منخفض'; }
      else {                io.cashState.classList.add('bg-danger');  io.cashState.textContent='لا توجد سيولة'; }
      io.cashState.title = `تغطية: ${pct.toFixed(2)}%`;
    }
  }

  function setCashUI(tr, cash){
    const io = getRowIO(tr);
    const need = neededForRow(tr);

    if (io.spinner) io.spinner.classList.add('d-none');

    if (cash === null){
      if (io.cashText){ io.cashText.textContent = 'غير متاح'; io.cashText.dataset.raw = '0'; }
      if (io.updated){  io.updated.textContent  = '—'; }
      if (io.neededText){ io.neededText.textContent = need ? fmtCurrency.format(need) : '—'; }
      if (io.cashBar){ io.cashBar.style.width='0%'; io.cashBar.className='progress-bar js-cash-bar bg-danger'; }
      if (io.cashState){ io.cashState.className='badge rounded-pill js-cash-state bg-danger'; io.cashState.textContent='خطأ'; }
    } else {
      if (io.cashText){
        io.cashText.textContent = fmtCurrency.format(cash);
        io.cashText.dataset.raw = String(cash);
      }
      if (io.updated){
        const now = new Date();
        io.updated.textContent = `آخر تحديث ${now.toLocaleTimeString('ar-EG')}`;
      }
      if (io.neededText){ io.neededText.textContent = need ? fmtCurrency.format(need) : '—'; }
      paintCoverage(io, cash, need);
    }

    const { pct, value } = io;
    if (pct && String(pct.value).trim() !== '')      recalcInvestorRow(tr, 'pct', {formatActive:false});
    else if (value && String(value.value).trim() !== '') recalcInvestorRow(tr, 'val', {formatActive:false});
  }

  function setCashLoading(tr, isLoading=true){
    const io = getRowIO(tr);
    if (io.spinner) io.spinner.classList.toggle('d-none', !isLoading);
    if (isLoading){
      if (io.cashText)  io.cashText.textContent = '...';
      if (io.updated)   io.updated.textContent  = '—';
      if (io.cashBar){ io.cashBar.style.width='0%'; io.cashBar.className='progress-bar js-cash-bar bg-secondary'; }
      if (io.cashState){ io.cashState.className='badge rounded-pill js-cash-state bg-secondary'; io.cashState.textContent='...'; }
    }
  }

  async function loadCashForRow(tr){
    const { select } = getRowIO(tr);
    if (!select) return;
    const id = select.value;
    if (!id){ setCashUI(tr, 0); return; }
    setCashLoading(tr, true);
    const cash = await fetchInvestorCash(id);
    setCashUI(tr, cash);
  }

  function updateNeededForAll(){
    tbody.querySelectorAll('tr').forEach(tr => {
      const io = getRowIO(tr);
      const need = neededForRow(tr);
      if (io.neededText) io.neededText.textContent = need ? fmtCurrency.format(need) : '—';
      const cashRaw = Number(io.cashText?.dataset.raw || 0);
      if (!isNaN(cashRaw)) paintCoverage(io, cashRaw, need);
    });
  }

  function recalcInvestorRow(tr, source, {formatActive=false} = {}){
    const { select, pct, value } = getRowIO(tr);
    if (!pct || !value) return;

    if (select && !select.value) { pct.value=''; value.value=''; updateNeededForAll(); return; }

    const base = toNumber(contractInput.value);
    if (base <= 0) { updateNeededForAll(); return; }

    if (source === 'pct'){
      let p = toNumber(pct.value);
      if (p > 100) { p = 100; pct.value = '100'; }
      if (p < 0 || !isFinite(p)) { p = 0; pct.value = '0'; }

      const maxP = maxPctForRow(tr, base);
      if (p > maxP){
        p = maxP;
        pct.value = p.toFixed(2);
        pct.title = 'تم تقليل النسبة للحد الأقصى المسموح (المتبقي/السيولة).';
      } else { pct.title=''; }
      p = clamp(p, 0, 100);

      value.value = fmt2((base * p) / 100);
      if (formatActive) pct.value = p.toFixed(2);

    } else if (source === 'val'){
      let v = toNumber(value.value);
      v = v < 0 ? 0 : v;

      const maxV = maxValueForRow(tr, base);
      if (v > maxV){
        v = maxV;
        value.title = 'تم تقليل القيمة للحد الأقصى المسموح (المتبقي/السيولة/قيمة البيع).';
      } else { value.title = ''; }
      if (formatActive) value.value = fmt2(v);
      else value.value = String(v);

      const p = base > 0 ? clamp((v / base) * 100, 0, 100) : 0;
      pct.value = p.toFixed(2);
    }

    const need = neededForRow(tr);
    const io = getRowIO(tr);
    if (io.neededText) io.neededText.textContent = need ? fmtCurrency.format(need) : '—';
    const cashRaw = Number(io.cashText?.dataset.raw || 0);
    if (!isNaN(cashRaw)) paintCoverage(io, cashRaw, need);
  }

  function recalcAllInvestors(){
    const rows = Array.from(tbody.querySelectorAll('tr'));
    rows.forEach(tr => {
      const { select, pct, value } = getRowIO(tr);
      if (select && !select.value) {
        if (pct) pct.value = '';
        if (value) value.value = '';
        return;
      }
      if (pct && String(pct.value).trim() !== '')        recalcInvestorRow(tr, 'pct');
      else if (value && String(value.value).trim() !== '') recalcInvestorRow(tr, 'val');
    });
    updateNeededForAll();
  }

  function updateSelectOptions(){
    const selects = Array.from(tbody.querySelectorAll('select[name$="[id]"]'));
    const chosen  = selects.map(s => s.value).filter(v => v !== '');
    selects.forEach(sel => {
      const current = sel.value;
      sel.querySelectorAll('option').forEach(opt => {
        if (opt.value === '' || opt.value === current) opt.disabled = false;
        else opt.disabled = chosen.includes(opt.value);
      });
    });
  }

  // ====== العدد المتاح حسب نوع البضاعة (من الـ Controller) ======
  const AVAIL_URL_TPL = @json(route('product-types.available', ['productType' => '__ID__']));
  const availableCache = Object.create(null);

  async function fetchAvailableData(typeId){
    if (!typeId) return null;
    if (availableCache[typeId] !== undefined) return availableCache[typeId];
    try{
      const url = AVAIL_URL_TPL.replace('__ID__', encodeURIComponent(typeId));
      const res = await fetch(url, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      availableCache[typeId] = data;
      return data;
    }catch(e){
      console.error('Availability fetch failed:', e);
      availableCache[typeId] = { success:false, message: e.message };
      return availableCache[typeId];
    }
  }

  function setAvailableUI(payload){
    if (!availableBadge || !productsCountInput) return;

    if (!payload || payload.success !== true){
      const msg = (payload && payload.message) ? payload.message : 'تعذّر جلب المتاح';
      availableBadge.textContent = 'خطأ: ' + msg;
      availableBadge.className = 'badge bg-danger text-white';
      productsCountInput.removeAttribute('max');
      return;
    }

    // نقرأ المتاح فقط (يدعم الصيغتين: available أو stock.available)
    const avail = Number(payload.available ?? payload.stock?.available ?? 0);
    const safeAvail = Number.isFinite(avail) ? Math.max(0, Math.floor(avail)) : 0;

    // عرض المتاح فقط
    availableBadge.textContent = 'المتاح: ' + safeAvail.toLocaleString('ar-EG');
    availableBadge.className = 'badge bg-light text-dark';

    // ضبط الحد الأقصى للحقل
    productsCountInput.setAttribute('max', String(safeAvail));
    let cur = parseInt(productsCountInput.value || '0', 10) || 0;
    if (cur > safeAvail) productsCountInput.value = String(safeAvail);
  }

  async function reloadAvailability(){
    const typeId = productTypeSelect?.value || '';
    if (!typeId){
      setAvailableUI({success:true, available:0, stock:{in:0, out:0, available:0}});
      productsCountInput.value = productsCountInput.value || '0';
      productsCountInput.setAttribute('max','0');
      return;
    }
    if (availableBadge){
      availableBadge.textContent = 'جاري التحميل...';
      availableBadge.className = 'badge bg-secondary text-white';
    }
    const payload = await fetchAvailableData(typeId);
    setAvailableUI(payload);
  }

  // قيد فوري على حقل العدد
  productsCountInput?.addEventListener('input', () => {
    const maxAttr = productsCountInput.getAttribute('max');
    const max = maxAttr ? parseInt(maxAttr,10) : Infinity;
    let v = parseInt(productsCountInput.value || '0', 10) || 0;
    if (v < 0) v = 0;
    if (isFinite(max) && v > max) v = max;
    productsCountInput.value = String(v);
  });
  productsCountInput?.addEventListener('blur', () => {
    const maxAttr = productsCountInput.getAttribute('max');
    const max = maxAttr ? parseInt(maxAttr,10) : Infinity;
    let v = parseInt(productsCountInput.value || '0', 10) || 0;
    if (v < 0) v = 0;
    if (isFinite(max) && v > max) v = max;
    productsCountInput.value = String(v);
  });

  productTypeSelect?.addEventListener('change', reloadAvailability);

  // إضافة/حذف صف مستثمر
  addBtn?.addEventListener('click', function(){
    const idx = tbody.querySelectorAll('tr').length;
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>
        <div class="d-grid gap-2 text-start">
          <select name="investors[${idx}][id]" class="form-select investor-select" aria-label="المستثمر" data-row="${idx}">
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
                <div class="progress mt-1" style="height:6px;">
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
      <td>
        <input type="number" step="0.01" min="0" max="100"
               name="investors[${idx}][share_percentage]"
               class="form-control" inputmode="decimal" autocomplete="off" aria-label="نسبة المستثمر (%)">
      </td>
      <td>
        <input type="number" step="0.01" name="investors[${idx}][share_value]"
               class="form-control" inputmode="decimal" autocomplete="off" aria-label="قيمة المستثمر">
      </td>
      <td><button type="button" class="btn btn-danger btn-sm remove-investor">حذف</button></td>
    `;
    tbody.appendChild(row);
    updateSelectOptions();
  });

  // أحداث الجدول
  tbody.addEventListener('click', function (e) {
    if (e.target.classList.contains('remove-investor')) {
      e.target.closest('tr').remove();
      updateSelectOptions();
      recalcAllInvestors();
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

  // إدخال حي
  tbody.addEventListener('input', function(e){
    const tr = e.target.closest('tr'); if (!tr) return;
    const { select, pct, value } = getRowIO(tr);

    if (select && !select.value) {
      if (pct && e.target === pct) pct.value = '';
      if (value && e.target === value) value.value = '';
      updateNeededForAll();
      return;
    }

    if (e.target.matches('input[name$="[share_percentage]"]')) {
      let raw = toNumber(e.target.value);
      if (raw > 100) e.target.value = '100';
      if (raw < 0)   e.target.value = '0';
      recalcInvestorRow(tr, 'pct', {formatActive:false});
    } else if (e.target.matches('input[name$="[share_value]"]')) {
      recalcInvestorRow(tr, 'val', {formatActive:false});
    } else if (e.target.matches('select[name$="[id]"]')) {
      updateSelectOptions();
      recalcInvestorRow(tr, 'pct', {formatActive:false});
      loadCashForRow(tr);
    }
  });

  // تنسيق عند الخروج من الحقل
  tbody.addEventListener('blur', function(e){
    const tr = e.target.closest('tr'); if (!tr) return;
    if (e.target.matches('input[name$="[share_percentage]"]')) {
      recalcInvestorRow(tr, 'pct', {formatActive:true});
    } else if (e.target.matches('input[name$="[share_value]"]')) {
      recalcInvestorRow(tr, 'val', {formatActive:true});
    }
  }, true);

  // ربط باقي الأحداث
  ['input','change','keyup'].forEach(evt => saleInput.addEventListener(evt, copySaleToContract));
  ['input','change','keyup'].forEach(evt => profitInput.addEventListener(evt, function(){ recalcTotal(); onTotalChange(); }));
  ['input','change','keyup'].forEach(evt => contractInput.addEventListener(evt, function(){ recalcTotal(); recalcAllInvestors(); onTotalChange(); }));
  ['input','change','keyup'].forEach(evt => totalInput.addEventListener(evt, onTotalChange));
  ['input','change','keyup'].forEach(evt => instValueInput.addEventListener(evt, onValueChange));
  ['input','change','keyup'].forEach(evt => instCountInput.addEventListener(evt, onCountChange));

  // تهيئة أولية
  if (!contractInput.value && saleInput.value) { copySaleToContract(); }
  else { recalcTotal(); recalcAllInvestors(); onTotalChange(); }

  updateSelectOptions();

  // تحميل السيولة للمختارين
  (function loadCashForAllSelected(){
    tbody.querySelectorAll('tr').forEach(tr => loadCashForRow(tr));
  })();

  // تهيئة العدد المتاح أول مرة (لو النوع مختار)
  if (productTypeSelect) reloadAvailability();
});
</script>
@endpush

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const locale  = "{{ app()->getLocale() }}";
    const isArabic = locale === 'ar';
    const baseOpts = { dateFormat: 'Y-m-d', allowInput: true, locale: isArabic ? 'ar' : 'default' };

    const startPicker = flatpickr('#start_date', {
      ...baseOpts,
      onChange: function (dates) {
        if (dates && dates.length) {
          const start = dates[0];
          firstPicker?.set('minDate', start);
        }
      }
    });

    const firstPicker = flatpickr('#first_installment_date', {
      ...baseOpts,
      minDate: document.getElementById('start_date')?.value || null
    });

    if (isArabic) {
      document.querySelectorAll('.js-date').forEach(el => {
        el.setAttribute('dir', 'rtl');
        el.style.textAlign = 'center';
      });
    }
  });
</script>
@endpush

