@extends('layouts.master')

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

@php
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
@endphp

<div class="card shadow-sm border-0">
  <div class="card-body p-4">
    <form action="{{ route('company-transactions.update', $transaction) }}" method="POST" novalidate>
      @csrf
      @method('PUT')

      <div class="row g-3">
        <div class="col-md-4">
          <label for="transaction_date" class="form-label">{{ __('companies::companies.Transaction Date') }} <span class="text-danger">*</span></label>
          <input type="date" name="transaction_date" id="transaction_date" value="{{ old('transaction_date', optional($transaction->transaction_date)->format('Y-m-d')) }}" class="form-control @error('transaction_date') is-invalid @enderror" required>
          @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
          <label for="reference" class="form-label">{{ __('companies::companies.Reference') }}</label>
          <input type="text" name="reference" id="reference" value="{{ old('reference', $transaction->reference) }}" class="form-control @error('reference') is-invalid @enderror" maxlength="190">
          @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
          <label for="total_amount" class="form-label">{{ __('companies::companies.Total Amount') }} <span class="text-danger">*</span></label>
          <input type="number" step="0.01" min="0.01" name="total_amount" id="total_amount" value="{{ old('total_amount', $transaction->total_amount) }}" class="form-control @error('total_amount') is-invalid @enderror" required>
          @error('total_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
          <label for="company_disbursement_status_id" class="form-label">{{ __('companies::companies.Status') }} <span class="text-danger">*</span></label>
          <select name="company_disbursement_status_id" id="company_disbursement_status_id" class="form-select @error('company_disbursement_status_id') is-invalid @enderror" required>
            <option value="">{{ __('companies::companies.Choose Status') }}</option>
            @foreach($statuses as $status)
              <option value="{{ $status->id }}" @selected((int) old('company_disbursement_status_id', $transaction->company_disbursement_status_id) === $status->id)>{{ $status->name }}</option>
            @endforeach
          </select>
          @error('company_disbursement_status_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
          <label for="bank_account_id" class="form-label">{{ __('companies::companies.Bank Account') }}</label>
          <select name="bank_account_id" id="bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror">
            <option value="">{{ __('companies::companies.Choose Bank Account') }}</option>
            @foreach($bankAccounts as $bankAccount)
              <option value="{{ $bankAccount->id }}" @selected((int) old('bank_account_id', $transaction->bank_account_id) === $bankAccount->id)>{{ $bankAccount->name }}</option>
            @endforeach
          </select>
          @error('bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
          <label for="safe_id" class="form-label">{{ __('companies::companies.Safe') }}</label>
          <select name="safe_id" id="safe_id" class="form-select @error('safe_id') is-invalid @enderror">
            <option value="">{{ __('companies::companies.Choose Safe') }}</option>
            @foreach($safes as $safe)
              <option value="{{ $safe->id }}" @selected((int) old('safe_id', $transaction->safe_id) === $safe->id)>{{ $safe->name }}</option>
            @endforeach
          </select>
          @error('safe_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
          <label for="notes" class="form-label">{{ __('companies::companies.Notes') }}</label>
          <textarea name="notes" id="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="{{ __('companies::companies.Notes Placeholder') }}">{{ old('notes', $transaction->notes) }}</textarea>
          @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      </div>

      <hr class="my-4">

      <div>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h2 class="h5 mb-0">{{ __('companies::companies.Allocations') }}</h2>
          <x-button.action type="button" variant="secondary" :outline="true" size="sm" id="addAllocationRow">
            <i class="bi bi-plus-lg"></i> {{ __('companies::companies.Add Allocation') }}
          </x-button.action>
        </div>

        @error('allocations') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror

        <div class="table-responsive">
          <table class="table table-bordered align-middle" id="allocationsTable">
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

      <div class="d-flex align-items-center gap-2 mt-4">
        <x-button.action type="submit" variant="primary">{{ __('companies::companies.Update Transaction') }}</x-button.action>
        <x-button.secondary href="{{ route('company-transactions.show', $transaction) }}">{{ __('companies::companies.Cancel') }}</x-button.secondary>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.querySelector('#allocationsTable tbody');
    const addRowBtn = document.getElementById('addAllocationRow');
    let allocationIndex = {{ count($oldAllocations) }};

    addRowBtn.addEventListener('click', () => {
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
      tableBody.appendChild(row);
      allocationIndex++;
    });

    tableBody.addEventListener('click', (event) => {
      if (event.target.closest('.remove-allocation')) {
        const rows = tableBody.querySelectorAll('tr');
        if (rows.length <= 1) {
          alert('{{ __('companies::companies.Allocations Minimum Notice') }}');
          return;
        }
        event.target.closest('tr').remove();
      }
    });
  });
</script>
@endpush
