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

  {{-- نوع العقد + العدد + الشراء/البيع --}}
  <div class="col-md-3">
    <label for="contract_type_id" class="form-label">نوع العقد <span class="text-danger">*</span></label>
    <select name="contract_type_id" id="contract_type_id" class="form-select @error('contract_type_id') is-invalid @enderror" required>
      <option value="">اختر نوع العقد</option>
      @foreach($contractTypes as $type)
        <option value="{{ $type->id }}" {{ old('contract_type_id', ($contract->contract_type_id ?? null)) == $type->id ? 'selected' : '' }}>
          {{ $type->name }}
        </option>
      @endforeach
    </select>
    @error('contract_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-3">
    <label for="products_count" class="form-label">العدد <span class="text-danger">*</span></label>
    <input type="number" name="products_count" id="products_count"
           class="form-control @error('products_count') is-invalid @enderror"
           value="{{ old('products_count', ($contract->products_count ?? null)) }}"
           min="0" required inputmode="numeric" autocomplete="off">
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
    <input
      type="text"
      id="start_date"
      name="start_date"
      class="form-control js-date @error('start_date') is-invalid @enderror"
      value="{{ old('start_date', ($contract->start_date?->format('Y-m-d') ?? '')) }}"
      placeholder="YYYY-MM-DD"
      autocomplete="off"
      required>
    @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-6">
    <label for="first_installment_date" class="form-label">تاريخ أول قسط</label>
    <input
      type="text"
      id="first_installment_date"
      name="first_installment_date"
      class="form-control js-date @error('first_installment_date') is-invalid @enderror"
      value="{{ old('first_installment_date', ($contract->first_installment_date?->format('Y-m-d') ?? '')) }}"
      placeholder="YYYY-MM-DD"
      autocomplete="off">
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
          <th>المستثمر</th>
          <th>النسبة (%)</th>
          <th>القيمة</th>
          <th>إجراء</th>
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
              <select name="investors[{{ $i }}][id]" id="investor_id_{{ $i }}" class="form-select" aria-label="المستثمر">
                <option value="">-- اختر --</option>
                @foreach($investors as $inv)
                  <option value="{{ $inv->id }}" {{ (string)($row['id'] ?? '') === (string)$inv->id ? 'selected' : '' }}>
                    {{ $inv->name }}
                  </option>
                @endforeach
              </select>
            </td>
            <td>
              <input type="number" step="0.01" name="investors[{{ $i }}][share_percentage]"
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

  const tbody            = document.getElementById('investors-table-body');
  const addBtn           = document.getElementById('add-investor');

  if (!saleInput || !contractInput || !profitInput || !totalInput || !instValueInput || !instCountInput || !tbody) return;

  // 🔒 اقفال التعديل اليدوي على قيمة العقد والإجمالي
  makeReadOnly(contractInput);
  makeReadOnly(totalInput);
  function makeReadOnly(el){
    if (!el) return;
    el.readOnly = true;
    el.classList.add('bg-light');
    el.addEventListener('beforeinput', e => e.preventDefault());
    el.addEventListener('keydown', function(e){
      const k = e.key;
      if (k === 'Tab' || k === 'Shift' || k.startsWith('Arrow')) return;
      e.preventDefault();
    });
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

  // إجمالي العقد = قيمة العقد + ربح المستثمر
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
    recalcTotal();
    recalcAllInvestors();
    onTotalChange();
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
  }

  // المستثمرون
  function getRowIO(tr){
    return {
      select: tr.querySelector('select[name$="[id]"]'),
      pct:    tr.querySelector('input[name$="[share_percentage]"]'),
      value:  tr.querySelector('input[name$="[share_value]"]'),
    };
  }
  function recalcInvestorRow(tr, source){
    const { select, pct, value } = getRowIO(tr);
    if (!pct || !value) return;

    if (select && !select.value) { pct.value=''; value.value=''; return; }

    const base = toNumber(contractInput.value);
    if (base <= 0) { return; }

    if (source === 'pct') {
      let p = toNumber(pct.value);
      p = clamp(p, 0, 100);
      pct.value   = parseFloat(p.toFixed(2));
      value.value = fmt2((base * p) / 100);
    } else if (source === 'val') {
      let v = toNumber(value.value);
      v = v < 0 ? 0 : v;
      value.value = fmt2(v);
      const p = clamp((v / base) * 100, 0, 100);
      pct.value   = parseFloat(p.toFixed(2));
    }
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

  // إضافة/حذف صف (مع aria-labels)
  addBtn?.addEventListener('click', function(){
    const idx = tbody.querySelectorAll('tr').length;
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>
        <select name="investors[${idx}][id]" class="form-select" aria-label="المستثمر">
          <option value="">-- اختر --</option>
          @foreach($investors as $inv)
            <option value="{{ $inv->id }}">{{ $inv->name }}</option>
          @endforeach
        </select>
      </td>
      <td><input type="number" step="0.01" name="investors[${idx}][share_percentage]" class="form-control" inputmode="decimal" autocomplete="off" aria-label="نسبة المستثمر (%)"></td>
      <td><input type="number" step="0.01" name="investors[${idx}][share_value]" class="form-control" inputmode="decimal" autocomplete="off" aria-label="قيمة المستثمر"></td>
      <td><button type="button" class="btn btn-danger btn-sm remove-investor">حذف</button></td>
    `;
    tbody.appendChild(row);
    updateSelectOptions();
  });

  tbody.addEventListener('click', function (e) {
    if (e.target.classList.contains('remove-investor')) {
      e.target.closest('tr').remove();
      updateSelectOptions();
      recalcAllInvestors();
    }
  });

  // داخل الجدول: حساب/منع تكرار
  tbody.addEventListener('input', function(e){
    const tr = e.target.closest('tr');
    if (!tr) return;

    const { select, pct, value } = getRowIO(tr);
    if (select && !select.value) {
      if (pct && e.target === pct) pct.value = '';
      if (value && e.target === value) value.value = '';
      return;
    }

    if (e.target.matches('input[name$="[share_percentage]"]')) {
      recalcInvestorRow(tr, 'pct');
    } else if (e.target.matches('input[name$="[share_value]"]')) {
      recalcInvestorRow(tr, 'val');
    } else if (e.target.matches('select[name$="[id]"]')) {
      updateSelectOptions();
      recalcInvestorRow(tr, 'pct');
    }
  });

  // ربط باقي الأحداث
  ['input','change','keyup'].forEach(evt => saleInput.addEventListener(evt, copySaleToContract));
  ['input','change','keyup'].forEach(evt => profitInput.addEventListener(evt, function(){
    recalcTotal();
    onTotalChange();
  }));
  ['input','change','keyup'].forEach(evt => contractInput.addEventListener(evt, function(){
    recalcTotal();
    recalcAllInvestors();
    onTotalChange();
  }));
  ['input','change','keyup'].forEach(evt => totalInput.addEventListener(evt, onTotalChange));
  ['input','change','keyup'].forEach(evt => instValueInput.addEventListener(evt, onValueChange));
  ['input','change','keyup'].forEach(evt => instCountInput.addEventListener(evt, onCountChange));

  // تهيئة أولية
  if (!contractInput.value && saleInput.value) {
    copySaleToContract();
  } else {
    recalcTotal();
    recalcAllInvestors();
    onTotalChange();
  }
  updateSelectOptions();
});
</script>
@endpush


@push('scripts')
  
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const locale  = "{{ app()->getLocale() }}";
      const isArabic = locale === 'ar';

      const baseOpts = {
        dateFormat: 'Y-m-d',
        allowInput: true,
        locale: isArabic ? 'ar' : 'default'
      };

      const startPicker = flatpickr('#start_date', {
        ...baseOpts,
        // minDate: 'today', // لو عايز تمنع اختيار تواريخ قديمة
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

