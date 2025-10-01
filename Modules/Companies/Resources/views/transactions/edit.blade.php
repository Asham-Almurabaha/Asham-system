@extends('layouts.master')

@php
    $transactionBankAmount = (float) $transaction->bank_amount;
    $transactionSafeAmount = (float) $transaction->safe_amount;
    $defaultMode = ($transactionBankAmount > 0 && $transactionSafeAmount > 0) ? 'split' : 'single';

    $activeMode = old('entry_mode', $defaultMode);
    if (!in_array($activeMode, ['single', 'split'], true)) {
        $activeMode = $defaultMode;
    }

    $oldAllocations = old('allocations');
    if (is_null($oldAllocations)) {
        $oldAllocations = $transaction->allocations->map(function ($allocation) {
            return [
                'company_id' => $allocation->company_id,
                'share_amount' => $allocation->share_amount,
                'share_percentage' => $allocation->share_percentage,
                'notes' => $allocation->notes,
            ];
        })->toArray();
    }

    if (empty($oldAllocations)) {
        $oldAllocations = [[
            'company_id' => null,
            'share_amount' => null,
            'share_percentage' => null,
            'notes' => null,
        ]];
    }

    $singleBankId = $activeMode === 'single'
        ? old('bank_account_id', $transaction->bank_account_id)
        : ($defaultMode === 'single' ? $transaction->bank_account_id : null);
    $singleSafeId = $activeMode === 'single'
        ? old('safe_id', $transaction->safe_id)
        : ($defaultMode === 'single' ? $transaction->safe_id : null);
    $singleAccountValue = $singleBankId ? 'bank:' . $singleBankId : ($singleSafeId ? 'safe:' . $singleSafeId : '');

    $singleTotalAmount = $activeMode === 'single'
        ? old('total_amount', $transaction->total_amount)
        : ($defaultMode === 'single' ? $transaction->total_amount : '0.00');

    $splitTotalAmount = $activeMode === 'split'
        ? old('total_amount', $transaction->total_amount)
        : ($defaultMode === 'split' ? $transaction->total_amount : '0.00');

    $splitBankAmount = $activeMode === 'split'
        ? old('bank_amount', $transactionBankAmount)
        : ($defaultMode === 'split' ? $transactionBankAmount : '0.00');

    $splitSafeAmount = $activeMode === 'split'
        ? old('safe_amount', $transactionSafeAmount)
        : ($defaultMode === 'split' ? $transactionSafeAmount : '0.00');

    $today = now()->format('Y-m-d');
@endphp

@section('title', __('companies::companies.Edit Transaction'))

@section('content')
<div class="pagetitle mb-3">
  <h1 class="h3 mb-1">{{ __('companies::companies.Edit Transaction') }}</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('company-transactions.index') }}">{{ __('companies::companies.Company Transactions') }}</a></li>
      <li class="breadcrumb-item"><a href="{{ route('company-transactions.show', $transaction) }}">{{ $transaction->reference ?: __('companies::companies.Transaction #', ['id' => $transaction->id]) }}</a></li>
      <li class="breadcrumb-item active">{{ __('companies::companies.Edit Transaction') }}</li>
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

    <ul class="nav nav-tabs" id="companyEditEntryTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeMode === 'single' ? 'active' : '' }}" id="company-edit-single-tab" data-bs-toggle="tab" data-bs-target="#company-edit-single" type="button" role="tab" data-mode="single">
          {{ __('companies::companies.StandardEntry') }}
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeMode === 'split' ? 'active' : '' }}" id="company-edit-split-tab" data-bs-toggle="tab" data-bs-target="#company-edit-split" type="button" role="tab" data-mode="split">
          {{ __('companies::companies.SplitEntry') }}
        </button>
      </li>
    </ul>

    <div class="tab-content pt-3" id="companyEditEntryTabsContent">
      <div class="tab-pane fade {{ $activeMode === 'single' ? 'show active' : '' }}" id="company-edit-single" role="tabpanel" aria-labelledby="company-edit-single-tab">
        <form action="{{ route('company-transactions.update', $transaction) }}" method="POST" class="row g-3" id="companyEditSingleForm">
          @csrf
          @method('PUT')
          <input type="hidden" name="entry_mode" value="single">
          <input type="hidden" name="bank_account_id" id="edit_single_bank_account_id" value="{{ $singleBankId }}">
          <input type="hidden" name="safe_id" id="edit_single_safe_id" value="{{ $singleSafeId }}">
          <input type="hidden" name="bank_amount" id="edit_single_bank_amount" value="{{ $singleBankId ? $singleTotalAmount : '0.00' }}">
          <input type="hidden" name="safe_amount" id="edit_single_safe_amount" value="{{ $singleSafeId ? $singleTotalAmount : '0.00' }}">

          <div class="col-md-4">
            <label for="edit_single_transaction_date" class="form-label">{{ __('companies::companies.Transaction Date') }} <span class="text-danger">*</span></label>
            <input type="date" name="transaction_date" id="edit_single_transaction_date" value="{{ old('transaction_date', optional($transaction->transaction_date)->format('Y-m-d') ?? $today) }}" class="form-control js-date @error('transaction_date') is-invalid @enderror" required>
            @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="edit_single_reference" class="form-label">{{ __('companies::companies.Reference') }}</label>
            <input type="text" name="reference" id="edit_single_reference" value="{{ old('reference', $transaction->reference) }}" class="form-control @error('reference') is-invalid @enderror" maxlength="190">
            @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="edit_single_total_amount" class="form-label">{{ __('companies::companies.Total Amount') }} <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0.01" name="total_amount" id="edit_single_total_amount" value="{{ $singleTotalAmount }}" class="form-control @error('total_amount') is-invalid @enderror" required>
            @error('total_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="edit_single_status_id" class="form-label">{{ __('companies::companies.Status') }} <span class="text-danger">*</span></label>
            <select name="company_disbursement_status_id" id="edit_single_status_id" class="form-select @error('company_disbursement_status_id') is-invalid @enderror" required>
              <option value="">{{ __('companies::companies.Choose Status') }}</option>
              @foreach($statuses as $status)
                <option value="{{ $status->id }}" @selected((int) old('company_disbursement_status_id', $transaction->company_disbursement_status_id) === $status->id)>{{ $status->name }}</option>
              @endforeach
            </select>
            @error('company_disbursement_status_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="edit_single_account_picker" class="form-label">{{ __('companies::companies.AccountSourcePicker') }} <span class="text-danger">*</span></label>
            <select id="edit_single_account_picker" class="form-select" required>
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
            <div class="text-danger small mt-1 d-none" id="edit_single_account_error"></div>
            @if($activeMode === 'single')
              @error('bank_account_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
              @error('safe_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
              @error('bank_amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
              @error('safe_amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            @endif
          </div>

          <div class="col-md-4">
            <label for="edit_single_notes" class="form-label">{{ __('companies::companies.Notes') }}</label>
            <textarea name="notes" id="edit_single_notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="{{ __('companies::companies.Notes Placeholder') }}">{{ old('notes', $transaction->notes) }}</textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h2 class="h5 mb-0">{{ __('companies::companies.Allocations') }}</h2>
              <x-button.action type="button" variant="secondary" :outline="true" size="sm" id="edit_single_add_allocation">
                <i class="bi bi-plus-lg"></i> {{ __('companies::companies.Add Allocation') }}
              </x-button.action>
            </div>
            @error('allocations') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror
            <div class="table-responsive">
              <table class="table table-bordered align-middle" id="edit_single_allocations_table">
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
                  @foreach($oldAllocations as $index => $allocation)
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
            <x-button.action type="submit" variant="primary">{{ __('companies::companies.Update Transaction') }}</x-button.action>
            <x-button.secondary href="{{ route('company-transactions.show', $transaction) }}">{{ __('companies::companies.Cancel') }}</x-button.secondary>
          </div>
        </form>
      </div>

      <div class="tab-pane fade {{ $activeMode === 'split' ? 'show active' : '' }}" id="company-edit-split" role="tabpanel" aria-labelledby="company-edit-split-tab">
        <form action="{{ route('company-transactions.update', $transaction) }}" method="POST" class="row g-3" id="companyEditSplitForm">
          @csrf
          @method('PUT')
          <input type="hidden" name="entry_mode" value="split">

          <div class="col-md-4">
            <label for="edit_split_transaction_date" class="form-label">{{ __('companies::companies.Transaction Date') }} <span class="text-danger">*</span></label>
            <input type="date" name="transaction_date" id="edit_split_transaction_date" value="{{ old('transaction_date', optional($transaction->transaction_date)->format('Y-m-d') ?? $today) }}" class="form-control js-date @error('transaction_date') is-invalid @enderror" required>
            @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="edit_split_reference" class="form-label">{{ __('companies::companies.Reference') }}</label>
            <input type="text" name="reference" id="edit_split_reference" value="{{ old('reference', $transaction->reference) }}" class="form-control @error('reference') is-invalid @enderror" maxlength="190">
            @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="edit_split_total_amount" class="form-label">{{ __('companies::companies.Total Amount') }} <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0.01" name="total_amount" id="edit_split_total_amount" value="{{ $splitTotalAmount }}" class="form-control @error('total_amount') is-invalid @enderror" required>
            @error('total_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="edit_split_status_id" class="form-label">{{ __('companies::companies.Status') }} <span class="text-danger">*</span></label>
            <select name="company_disbursement_status_id" id="edit_split_status_id" class="form-select @error('company_disbursement_status_id') is-invalid @enderror" required>
              <option value="">{{ __('companies::companies.Choose Status') }}</option>
              @foreach($statuses as $status)
                <option value="{{ $status->id }}" @selected((int) old('company_disbursement_status_id', $transaction->company_disbursement_status_id) === $status->id)>{{ $status->name }}</option>
              @endforeach
            </select>
            @error('company_disbursement_status_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="edit_split_bank_account_id" class="form-label">{{ __('companies::companies.Bank Account') }}</label>
            <select name="bank_account_id" id="edit_split_bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror">
              <option value="">{{ __('companies::companies.Choose Bank Account') }}</option>
              @foreach($bankAccounts as $bankAccount)
                <option value="{{ $bankAccount->id }}" @selected($activeMode === 'split' ? (int) old('bank_account_id', $transaction->bank_account_id) === $bankAccount->id : (int) $transaction->bank_account_id === $bankAccount->id)>{{ $bankAccount->name }}</option>
              @endforeach
            </select>
            @error('bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="edit_split_safe_id" class="form-label">{{ __('companies::companies.Safe') }}</label>
            <select name="safe_id" id="edit_split_safe_id" class="form-select @error('safe_id') is-invalid @enderror">
              <option value="">{{ __('companies::companies.Choose Safe') }}</option>
              @foreach($safes as $safe)
                <option value="{{ $safe->id }}" @selected($activeMode === 'split' ? (int) old('safe_id', $transaction->safe_id) === $safe->id : (int) $transaction->safe_id === $safe->id)>{{ $safe->name }}</option>
              @endforeach
            </select>
            @error('safe_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="edit_split_bank_amount" class="form-label">{{ __('companies::companies.BankShareAmount') }}</label>
            <input type="number" step="0.01" min="0" name="bank_amount" id="edit_split_bank_amount" value="{{ $splitBankAmount }}" class="form-control @error('bank_amount') is-invalid @enderror">
            @error('bank_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="edit_split_safe_amount" class="form-label">{{ __('companies::companies.SafeShareAmount') }}</label>
            <input type="number" step="0.01" min="0" name="safe_amount" id="edit_split_safe_amount" value="{{ $splitSafeAmount }}" class="form-control @error('safe_amount') is-invalid @enderror">
            @error('safe_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label for="edit_split_notes" class="form-label">{{ __('companies::companies.Notes') }}</label>
            <textarea name="notes" id="edit_split_notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="{{ __('companies::companies.Notes Placeholder') }}">{{ old('notes', $transaction->notes) }}</textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h2 class="h5 mb-0">{{ __('companies::companies.Allocations') }}</h2>
              <x-button.action type="button" variant="secondary" :outline="true" size="sm" id="edit_split_add_allocation">
                <i class="bi bi-plus-lg"></i> {{ __('companies::companies.Add Allocation') }}
              </x-button.action>
            </div>
            @error('allocations') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror
            <div class="table-responsive">
              <table class="table table-bordered align-middle" id="edit_split_allocations_table">
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
                  @foreach($oldAllocations as $index => $allocation)
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
            <x-button.action type="submit" variant="primary">{{ __('companies::companies.Update Transaction') }}</x-button.action>
            <x-button.secondary href="{{ route('company-transactions.show', $transaction) }}">{{ __('companies::companies.Cancel') }}</x-button.secondary>
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
    setupEditSingleForm();
    setupEditSplitForm();

    setupAllocationsTable({
      tableId: 'edit_single_allocations_table',
      addButtonId: 'edit_single_add_allocation'
    });

    setupAllocationsTable({
      tableId: 'edit_split_allocations_table',
      addButtonId: 'edit_split_add_allocation'
    });
  });

  function setupEditSingleForm() {
    const accountPicker = document.getElementById('edit_single_account_picker');
    const bankHidden = document.getElementById('edit_single_bank_account_id');
    const safeHidden = document.getElementById('edit_single_safe_id');
    const bankAmountHidden = document.getElementById('edit_single_bank_amount');
    const safeAmountHidden = document.getElementById('edit_single_safe_amount');
    const totalInput = document.getElementById('edit_single_total_amount');
    const accountError = document.getElementById('edit_single_account_error');

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

    const form = document.getElementById('companyEditSingleForm');
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

  function setupEditSplitForm() {
    const totalInput = document.getElementById('edit_split_total_amount');
    const bankAmountInput = document.getElementById('edit_split_bank_amount');
    const safeAmountInput = document.getElementById('edit_split_safe_amount');
    const form = document.getElementById('companyEditSplitForm');

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

    if (form) {
      form.addEventListener('submit', () => {
        const total = toNumber(totalInput.value);
        const bank = toNumber(bankAmountInput.value);
        safeAmountInput.value = Math.max(total - bank, 0).toFixed(2);
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
