@extends('layouts.master')

@php
    $today = now()->format('Y-m-d');
    $activeMode = old('entry_mode', 'single');
    if (!in_array($activeMode, ['single', 'split'], true)) {
        $activeMode = 'single';
    }

    $oldAllocations = old('allocations', []);
    if (empty($oldAllocations)) {
        $preselectedCompany = (int) request('company_id');
        $oldAllocations = [[
            'company_id' => $preselectedCompany > 0 ? $preselectedCompany : null,
            'share_amount' => old('total_amount'),
            'share_percentage' => null,
            'notes' => null,
        ]];
    }

    $defaultAllocationRow = [
        'company_id' => null,
        'share_amount' => null,
        'share_percentage' => null,
        'notes' => null,
    ];

    $singleAllocations = $activeMode === 'split' ? [$defaultAllocationRow] : $oldAllocations;
    $splitAllocations = $activeMode === 'split' ? $oldAllocations : [$defaultAllocationRow];

    $singleBankId = $activeMode === 'single' ? old('bank_account_id') : null;
    $singleSafeId = $activeMode === 'single' ? old('safe_id') : null;
    $singleAccountValue = $singleBankId ? 'bank:' . $singleBankId : ($singleSafeId ? 'safe:' . $singleSafeId : '');

    $singleTotalAmount = old('total_amount', '0.00');
    $splitTotalAmount = $activeMode === 'split' ? old('total_amount', '0.00') : '0.00';
    $splitBankAmount = $activeMode === 'split' ? old('bank_amount', '0.00') : '0.00';
    $splitSafeAmount = $activeMode === 'split' ? old('safe_amount', '0.00') : '0.00';
@endphp

@section('title', __('companies::companies.New Transaction'))

@section('content')
<div class="pagetitle mb-3">
  <h1 class="h3 mb-1">{{ __('companies::companies.New Transaction') }}</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('company-transactions.index') }}">{{ __('companies::companies.Company Transactions') }}</a></li>
      <li class="breadcrumb-item active">{{ __('companies::companies.New Transaction') }}</li>
    </ol>
  </nav>
</div>

@if ($errors->any())
  <div class="alert alert-danger shadow-sm">{{ __('companies::companies.Validation Errors') }}</div>
@endif

<div class="card shadow-sm border-0">
  <div class="card-body p-4">
    <div class="alert alert-info d-flex justify-content-between align-items-center">
      <div>
        {{ __('companies::companies.CompanyEntryHint') }}
        <div class="small text-muted mt-1">{{ __('companies::companies.CompanyEntryModeHelp') }}</div>
      </div>
      <span class="badge rounded-pill bg-primary">{{ __('companies::companies.OfficeCategoryLabel') }}</span>
    </div>

    <ul class="nav nav-tabs" id="companyEntryTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeMode === 'single' ? 'active' : '' }}" id="company-entry-single-tab" data-bs-toggle="tab" data-bs-target="#company-entry-single" type="button" role="tab" data-mode="single">
          {{ __('companies::companies.StandardEntry') }}
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeMode === 'split' ? 'active' : '' }}" id="company-entry-split-tab" data-bs-toggle="tab" data-bs-target="#company-entry-split" type="button" role="tab" data-mode="split">
          {{ __('companies::companies.SplitEntry') }}
        </button>
      </li>
    </ul>

    <div class="tab-content pt-3" id="companyEntryTabsContent">
      <div class="tab-pane fade {{ $activeMode === 'single' ? 'show active' : '' }}" id="company-entry-single" role="tabpanel" aria-labelledby="company-entry-single-tab">
        <form action="{{ route('company-transactions.store') }}" method="POST" class="row g-3" id="companyEntrySingleForm">
          @csrf
          <input type="hidden" name="entry_mode" value="single">
          <input type="hidden" name="bank_account_id" id="single_bank_account_id" value="{{ $singleBankId }}">
          <input type="hidden" name="safe_id" id="single_safe_id" value="{{ $singleSafeId }}">
          <input type="hidden" name="bank_amount" id="single_bank_amount" value="{{ $singleBankId ? $singleTotalAmount : '0.00' }}">
          <input type="hidden" name="safe_amount" id="single_safe_amount" value="{{ $singleSafeId ? $singleTotalAmount : '0.00' }}">

          <div class="col-md-4">
            <label for="single_transaction_date" class="form-label">{{ __('companies::companies.Transaction Date') }} <span class="text-danger">*</span></label>
            <input type="date" name="transaction_date" id="single_transaction_date" value="{{ old('transaction_date', $today) }}" class="form-control js-date @error('transaction_date') is-invalid @enderror" required>
            @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="single_reference" class="form-label">{{ __('companies::companies.Reference') }}</label>
            <input type="text" name="reference" id="single_reference" value="{{ old('reference') }}" class="form-control @error('reference') is-invalid @enderror" maxlength="190">
            @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="single_total_amount" class="form-label">{{ __('companies::companies.Total Amount') }} <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0.01" name="total_amount" id="single_total_amount" value="{{ $singleTotalAmount }}" class="form-control @error('total_amount') is-invalid @enderror" required>
            @error('total_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="single_status_id" class="form-label">{{ __('companies::companies.Status') }} <span class="text-danger">*</span></label>
            <select name="company_disbursement_status_id" id="single_status_id" class="form-select @error('company_disbursement_status_id') is-invalid @enderror" required>
              <option value="">{{ __('companies::companies.Choose Status') }}</option>
              @foreach($statuses as $status)
                <option value="{{ $status->id }}" @selected((int) old('company_disbursement_status_id', $statuses->firstWhere('is_default', true)->id ?? null) === $status->id)>{{ $status->name }}</option>
              @endforeach
            </select>
            @error('company_disbursement_status_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="single_account_picker" class="form-label">{{ __('companies::companies.AccountSourcePicker') }} <span class="text-danger">*</span></label>
            <select id="single_account_picker" class="form-select" required>
              <option value="" {{ $singleAccountValue ? '' : 'selected' }}>{{ __('companies::companies.ChooseAccountSource') }}</option>
              <optgroup label="{{ __('companies::companies.Bank Accounts') }}">
                @foreach($bankAccounts as $bankAccount)
                  <option value="bank:{{ $bankAccount->id }}" @selected($singleAccountValue === 'bank:' . $bankAccount->id)>{{ $bankAccount->name }}</option>
                @endforeach
              </optgroup>
              <optgroup label="{{ __('companies::companies.Safes') }}">
                @foreach($safes as $safe)
                  <option value="safe:{{ $safe->id }}" @selected($singleAccountValue === 'safe:' . $safe->id)>{{ $safe->name }}</option>
                @endforeach
              </optgroup>
            </select>
            <div class="form-text">{{ __('companies::companies.AccountPickerHelp') }}</div>
            <div class="text-danger small mt-1 d-none" id="single_account_error"></div>
            @if($activeMode === 'single')
              @error('bank_account_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
              @error('safe_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
              @error('bank_amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
              @error('safe_amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            @endif
          </div>

          <div class="col-md-4">
            <label for="single_notes" class="form-label">{{ __('companies::companies.Notes') }}</label>
            <textarea name="notes" id="single_notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="{{ __('companies::companies.Notes Placeholder') }}">{{ old('notes') }}</textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h2 class="h5 mb-0">{{ __('companies::companies.Allocations') }}</h2>
              <x-button.action type="button" variant="secondary" :outline="true" size="sm" id="singleAddAllocationRow">
                <i class="bi bi-plus-lg"></i> {{ __('companies::companies.Add Allocation') }}
              </x-button.action>
            </div>
            @error('allocations') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror
            <div class="table-responsive">
              <table class="table table-bordered align-middle" id="singleAllocationsTable">
                <thead class="table-light">
                  <tr>
                    <th style="width:25%">{{ __('companies::companies.Company Name') }}</th>
                    <th style="width:15%">{{ __('companies::companies.Share Amount') }}</th>
                    <th style="width:15%">{{ __('companies::companies.Share Percentage') }}</th>
                    <th>{{ __('companies::companies.Notes') }}</th>
                    <th style="width:60px"></th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($singleAllocations as $index => $allocation)
                    <tr>
                      <td>
                        <select name="allocations[{{ $index }}][company_id]" class="form-select form-select-sm @error("allocations.$index.company_id") is-invalid @enderror" required>
                          <option value="">{{ __('companies::companies.Choose Company') }}</option>
                          @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected((int) ($allocation['company_id'] ?? 0) === $company->id)>{{ $company->name }}</option>
                          @endforeach
                        </select>
                        @error("allocations.$index.company_id") <div class="invalid-feedback">{{ $message }}</div> @enderror
                      </td>
                      <td>
                        <input type="number" step="0.01" min="0.01" name="allocations[{{ $index }}][share_amount]" value="{{ $allocation['share_amount'] ?? '' }}" class="form-control form-control-sm @error("allocations.$index.share_amount") is-invalid @enderror" required>
                        @error("allocations.$index.share_amount") <div class="invalid-feedback">{{ $message }}</div> @enderror
                      </td>
                      <td>
                        <input type="number" step="0.01" min="0" max="100" name="allocations[{{ $index }}][share_percentage]" value="{{ $allocation['share_percentage'] ?? '' }}" class="form-control form-control-sm @error("allocations.$index.share_percentage") is-invalid @enderror">
                        @error("allocations.$index.share_percentage") <div class="invalid-feedback">{{ $message }}</div> @enderror
                      </td>
                      <td>
                        <input type="text" name="allocations[{{ $index }}][notes]" value="{{ $allocation['notes'] ?? '' }}" class="form-control form-control-sm @error("allocations.$index.notes") is-invalid @enderror" placeholder="{{ __('companies::companies.Optional Note') }}">
                        @error("allocations.$index.notes") <div class="invalid-feedback">{{ $message }}</div> @enderror
                      </td>
                      <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-allocation" title="{{ __('companies::companies.Remove Allocation') }}">
                          <i class="bi bi-x-lg"></i>
                        </button>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

          <div class="col-12 d-flex align-items-center gap-2 mt-3">
            <x-button.action type="submit" variant="success">{{ __('companies::companies.Save Transaction') }}</x-button.action>
            <x-button.secondary href="{{ route('company-transactions.index') }}">{{ __('companies::companies.Cancel') }}</x-button.secondary>
          </div>
        </form>
      </div>

      <div class="tab-pane fade {{ $activeMode === 'split' ? 'show active' : '' }}" id="company-entry-split" role="tabpanel" aria-labelledby="company-entry-split-tab">
        <form action="{{ route('company-transactions.store') }}" method="POST" class="row g-3" id="companyEntrySplitForm">
          @csrf
          <input type="hidden" name="entry_mode" value="split">

          <div class="col-md-4">
            <label for="split_transaction_date" class="form-label">{{ __('companies::companies.Transaction Date') }} <span class="text-danger">*</span></label>
            <input type="date" name="transaction_date" id="split_transaction_date" value="{{ $activeMode === 'split' ? old('transaction_date', $today) : $today }}" class="form-control js-date @error('transaction_date') is-invalid @enderror" required>
            @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="split_reference" class="form-label">{{ __('companies::companies.Reference') }}</label>
            <input type="text" name="reference" id="split_reference" value="{{ $activeMode === 'split' ? old('reference') : '' }}" class="form-control @error('reference') is-invalid @enderror" maxlength="190">
            @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="split_total_amount" class="form-label">{{ __('companies::companies.Total Amount') }} <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0.01" name="total_amount" id="split_total_amount" value="{{ $splitTotalAmount }}" class="form-control @error('total_amount') is-invalid @enderror" required>
            @error('total_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="split_status_id" class="form-label">{{ __('companies::companies.Status') }} <span class="text-danger">*</span></label>
            <select name="company_disbursement_status_id" id="split_status_id" class="form-select @error('company_disbursement_status_id') is-invalid @enderror" required>
              <option value="">{{ __('companies::companies.Choose Status') }}</option>
              @foreach($statuses as $status)
                <option value="{{ $status->id }}" @selected((int) old('company_disbursement_status_id', $statuses->firstWhere('is_default', true)->id ?? null) === $status->id)>{{ $status->name }}</option>
              @endforeach
            </select>
            @error('company_disbursement_status_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="split_bank_account_id" class="form-label">{{ __('companies::companies.Bank Account') }}</label>
            <select name="bank_account_id" id="split_bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror">
              <option value="">{{ __('companies::companies.Choose Bank Account') }}</option>
              @foreach($bankAccounts as $bankAccount)
                <option value="{{ $bankAccount->id }}" @selected($activeMode === 'split' && (int) old('bank_account_id') === $bankAccount->id)>{{ $bankAccount->name }}</option>
              @endforeach
            </select>
            @error('bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="split_safe_id" class="form-label">{{ __('companies::companies.Safe') }}</label>
            <select name="safe_id" id="split_safe_id" class="form-select @error('safe_id') is-invalid @enderror">
              <option value="">{{ __('companies::companies.Choose Safe') }}</option>
              @foreach($safes as $safe)
                <option value="{{ $safe->id }}" @selected($activeMode === 'split' && (int) old('safe_id') === $safe->id)>{{ $safe->name }}</option>
              @endforeach
            </select>
            @error('safe_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="split_bank_amount" class="form-label">{{ __('companies::companies.BankShareAmount') }}</label>
            <input type="number" step="0.01" min="0" name="bank_amount" id="split_bank_amount" value="{{ $splitBankAmount }}" class="form-control @error('bank_amount') is-invalid @enderror">
            @error('bank_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="split_safe_amount" class="form-label">{{ __('companies::companies.SafeShareAmount') }}</label>
            <input type="number" step="0.01" min="0" name="safe_amount" id="split_safe_amount" value="{{ $splitSafeAmount }}" class="form-control @error('safe_amount') is-invalid @enderror">
            @error('safe_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="split_notes" class="form-label">{{ __('companies::companies.Notes') }}</label>
            <textarea name="notes" id="split_notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="{{ __('companies::companies.Notes Placeholder') }}">{{ $activeMode === 'split' ? old('notes') : '' }}</textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h2 class="h5 mb-0">{{ __('companies::companies.Allocations') }}</h2>
              <x-button.action type="button" variant="secondary" :outline="true" size="sm" id="splitAddAllocationRow">
                <i class="bi bi-plus-lg"></i> {{ __('companies::companies.Add Allocation') }}
              </x-button.action>
            </div>
            @error('allocations') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror
            <div class="table-responsive">
              <table class="table table-bordered align-middle" id="splitAllocationsTable">
                <thead class="table-light">
                  <tr>
                    <th style="width:25%">{{ __('companies::companies.Company Name') }}</th>
                    <th style="width:15%">{{ __('companies::companies.Share Amount') }}</th>
                    <th style="width:15%">{{ __('companies::companies.Share Percentage') }}</th>
                    <th>{{ __('companies::companies.Notes') }}</th>
                    <th style="width:60px"></th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($splitAllocations as $index => $allocation)
                    <tr>
                      <td>
                        <select name="allocations[{{ $index }}][company_id]" class="form-select form-select-sm" required>
                          <option value="">{{ __('companies::companies.Choose Company') }}</option>
                          @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected((int) ($allocation['company_id'] ?? 0) === $company->id)>{{ $company->name }}</option>
                          @endforeach
                        </select>
                      </td>
                      <td>
                        <input type="number" step="0.01" min="0.01" name="allocations[{{ $index }}][share_amount]" value="{{ $allocation['share_amount'] ?? '' }}" class="form-control form-control-sm" required>
                      </td>
                      <td>
                        <input type="number" step="0.01" min="0" max="100" name="allocations[{{ $index }}][share_percentage]" value="{{ $allocation['share_percentage'] ?? '' }}" class="form-control form-control-sm">
                      </td>
                      <td>
                        <input type="text" name="allocations[{{ $index }}][notes]" value="{{ $allocation['notes'] ?? '' }}" class="form-control form-control-sm" placeholder="{{ __('companies::companies.Optional Note') }}">
                      </td>
                      <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-allocation" title="{{ __('companies::companies.Remove Allocation') }}">
                          <i class="bi bi-x-lg"></i>
                        </button>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

          <div class="col-12 d-flex align-items-center gap-2 mt-3">
            <x-button.action type="submit" variant="success">{{ __('companies::companies.Save Transaction') }}</x-button.action>
            <x-button.secondary href="{{ route('company-transactions.index') }}">{{ __('companies::companies.Cancel') }}</x-button.secondary>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    setupSingleForm();
    setupSplitForm();

    setupAllocationsTable({
      tableId: 'singleAllocationsTable',
      addButtonId: 'singleAddAllocationRow'
    });

    setupAllocationsTable({
      tableId: 'splitAllocationsTable',
      addButtonId: 'splitAddAllocationRow'
    });
  });

  function setupSingleForm() {
    const accountPicker = document.getElementById('single_account_picker');
    const bankHidden = document.getElementById('single_bank_account_id');
    const safeHidden = document.getElementById('single_safe_id');
    const bankAmountHidden = document.getElementById('single_bank_amount');
    const safeAmountHidden = document.getElementById('single_safe_amount');
    const totalInput = document.getElementById('single_total_amount');
    const accountError = document.getElementById('single_account_error');

    function updateHidden() {
      const value = accountPicker ? accountPicker.value : '';
      if (!value) {
        if (bankHidden) bankHidden.value = '';
        if (safeHidden) safeHidden.value = '';
      } else {
        const [type, id] = value.split(':');
        if (type === 'bank') {
          if (bankHidden) bankHidden.value = id;
          if (safeHidden) safeHidden.value = '';
        } else if (type === 'safe') {
          if (safeHidden) safeHidden.value = id;
          if (bankHidden) bankHidden.value = '';
        }
      }
      updateAmounts();
    }

    function toFixed(value) {
      return Number.parseFloat(value).toFixed(2);
    }

    function updateAmounts() {
      const total = Number.parseFloat(totalInput.value || '0') || 0;
      if (bankHidden && bankHidden.value) {
        bankAmountHidden.value = toFixed(total);
        safeAmountHidden.value = '0.00';
      } else if (safeHidden && safeHidden.value) {
        safeAmountHidden.value = toFixed(total);
        bankAmountHidden.value = '0.00';
      } else {
        bankAmountHidden.value = '0.00';
        safeAmountHidden.value = '0.00';
      }
    }

    if (accountPicker) {
      accountPicker.addEventListener('change', () => {
        updateHidden();
        if (accountError) {
          accountError.classList.add('d-none');
          accountError.textContent = '';
        }
      });
      updateHidden();
    }

    if (totalInput) {
      totalInput.addEventListener('input', updateAmounts);
      totalInput.addEventListener('blur', updateAmounts);
      updateAmounts();
    }

    const form = document.getElementById('companyEntrySingleForm');
    if (form) {
      form.addEventListener('submit', (event) => {
        if (!bankHidden.value && !safeHidden.value) {
          event.preventDefault();
          event.stopPropagation();
          if (accountError) {
            accountError.textContent = '{{ __('companies::messages.transactions.account_amount_required') }}';
            accountError.classList.remove('d-none');
          }
        }
      });
    }
  }

  function setupSplitForm() {
    const totalInput = document.getElementById('split_total_amount');
    const bankAmountInput = document.getElementById('split_bank_amount');
    const safeAmountInput = document.getElementById('split_safe_amount');

    const bankInput = document.getElementById('split_bank_account_id');
    const safeInput = document.getElementById('split_safe_id');

    function toNumber(value) {
      return Number.parseFloat(String(value).replace(',', '.')) || 0;
    }

    function format(value) {
      return toNumber(value).toFixed(2);
    }

    function syncFromBank() {
      const total = toNumber(totalInput.value);
      const bank = Math.min(toNumber(bankAmountInput.value), total);
      bankAmountInput.value = bank.toFixed(2);
      const safe = Math.max(total - bank, 0);
      safeAmountInput.value = safe.toFixed(2);
    }

    function syncFromSafe() {
      const total = toNumber(totalInput.value);
      const safe = Math.min(toNumber(safeAmountInput.value), total);
      safeAmountInput.value = safe.toFixed(2);
      const bank = Math.max(total - safe, 0);
      bankAmountInput.value = bank.toFixed(2);
    }

    if (totalInput) {
      totalInput.addEventListener('input', () => {
        if (document.activeElement === bankAmountInput) {
          syncFromBank();
        } else if (document.activeElement === safeAmountInput) {
          syncFromSafe();
        } else {
          syncFromBank();
        }
      });
    }

    if (bankAmountInput) {
      bankAmountInput.addEventListener('input', syncFromBank);
      bankAmountInput.addEventListener('blur', () => {
        bankAmountInput.value = format(bankAmountInput.value);
        syncFromBank();
      });
    }

    if (safeAmountInput) {
      safeAmountInput.addEventListener('input', syncFromSafe);
      safeAmountInput.addEventListener('blur', () => {
        safeAmountInput.value = format(safeAmountInput.value);
        syncFromSafe();
      });
    }

    syncFromBank();

    const form = document.getElementById('companyEntrySplitForm');
    if (form) {
      form.addEventListener('submit', (event) => {
        const bankAmount = toNumber(bankAmountInput.value);
        const safeAmount = toNumber(safeAmountInput.value);
        const total = toNumber(totalInput.value);

        if (bankAmount + safeAmount !== total) {
          safeAmountInput.value = Math.max(total - bankAmount, 0).toFixed(2);
        }
      });
    }
  }

  function setupAllocationsTable({ tableId, addButtonId }) {
    const table = document.getElementById(tableId);
    const addButton = document.getElementById(addButtonId);
    if (!table || !addButton) {
      return;
    }

    let allocationIndex = table.querySelectorAll('tbody tr').length;

    addButton.addEventListener('click', () => {
      const tbody = table.querySelector('tbody');
      if (!tbody) {
        return;
      }

      const row = document.createElement('tr');
      row.innerHTML = `
        <td>
          <select name="allocations[${allocationIndex}][company_id]" class="form-select form-select-sm" required>
            <option value="">{{ __('companies::companies.Choose Company') }}</option>
            @foreach($companies as $company)
              <option value="{{ $company->id }}">{{ $company->name }}</option>
            @endforeach
          </select>
        </td>
        <td>
          <input type="number" step="0.01" min="0.01" name="allocations[${allocationIndex}][share_amount]" class="form-control form-control-sm" required>
        </td>
        <td>
          <input type="number" step="0.01" min="0" max="100" name="allocations[${allocationIndex}][share_percentage]" class="form-control form-control-sm">
        </td>
        <td>
          <input type="text" name="allocations[${allocationIndex}][notes]" class="form-control form-control-sm" placeholder="{{ __('companies::companies.Optional Note') }}">
        </td>
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-outline-danger remove-allocation" title="{{ __('companies::companies.Remove Allocation') }}">
            <i class="bi bi-x-lg"></i>
          </button>
        </td>
      `;
      tbody.appendChild(row);
      allocationIndex++;
    });

    table.addEventListener('click', (event) => {
      if (!(event.target instanceof HTMLElement)) {
        return;
      }
      const removeButton = event.target.closest('.remove-allocation');
      if (!removeButton) {
        return;
      }

      const row = removeButton.closest('tr');
      if (!row) {
        return;
      }

      const tbody = row.parentElement;
      if (tbody && tbody.children.length > 1) {
        row.remove();
      }
    });
  }
</script>
@endpush
