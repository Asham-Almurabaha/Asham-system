@extends('layouts.master')

@section('title', __('companies::companies.Company Transactions'))

@section('content')
<div class="pagetitle mb-3">
  <h1 class="h3 mb-1">{{ __('companies::companies.Company Transactions') }}</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('companies.index') }}">{{ __('companies::companies.Companies') }}</a></li>
      <li class="breadcrumb-item active">{{ __('companies::companies.Company Transactions') }}</li>
    </ol>
  </nav>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body d-flex flex-wrap gap-2 align-items-center">
    <div class="btn-group" role="group">
      <x-button.action href="{{ route('company-transactions.create') }}" variant="success">
        <i class="bi bi-plus-lg"></i> {{ __('companies::companies.New Transaction') }}
      </x-button.action>
      <x-button.action href="{{ route('companies.index') }}" variant="secondary" :outline="true">
        <i class="bi bi-buildings"></i> {{ __('companies::companies.Manage Companies') }}
      </x-button.action>
    </div>

    <span class="ms-auto small text-muted">
      {{ __('companies::companies.Results Count', ['count' => number_format($transactions->total())]) }}
    </span>

    @php($filtersApplied = request()->filled('reference') || request()->filled('status_id') || request()->filled('company_id') || request()->filled('date_from') || request()->filled('date_to'))
    <x-button.action type="button" variant="secondary" :outline="true" size="sm" data-bs-toggle="collapse"
      data-bs-target="#transactionFilters" aria-expanded="{{ $filtersApplied ? 'true' : 'false' }}" aria-controls="transactionFilters">
      {{ __('companies::companies.Filter') }}
    </x-button.action>
  </div>

  <div class="collapse border-top {{ $filtersApplied ? 'show' : '' }}" id="transactionFilters">
    <div class="card-body">
      <form action="{{ route('company-transactions.index') }}" method="GET" class="row gy-2 gx-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label mb-1" for="reference">{{ __('companies::companies.Reference') }}</label>
          <input type="search" name="reference" id="reference" value="{{ request('reference') }}" class="form-control form-control-sm" placeholder="{{ __('companies::companies.Reference Placeholder') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label mb-1" for="company_id">{{ __('companies::companies.Company Name') }}</label>
          <select name="company_id" id="company_id" class="form-select form-select-sm">
            <option value="">{{ __('companies::companies.All Companies') }}</option>
            @foreach($companies as $company)
              <option value="{{ $company->id }}" @selected((int) request('company_id') === $company->id)>{{ $company->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label mb-1" for="status_id">{{ __('companies::companies.Status') }}</label>
          <select name="status_id" id="status_id" class="form-select form-select-sm">
            <option value="">{{ __('companies::companies.All Statuses') }}</option>
            @foreach($statuses as $status)
              <option value="{{ $status->id }}" @selected((int) request('status_id') === $status->id)>{{ $status->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label mb-1" for="date_from">{{ __('companies::companies.Date From') }}</label>
          <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-3">
          <label class="form-label mb-1" for="date_to">{{ __('companies::companies.Date To') }}</label>
          <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
          <x-button.action type="submit" variant="primary" size="sm" class="w-100">{{ __('companies::companies.Apply Filter') }}</x-button.action>
        </div>
        <div class="col-md-2">
          <x-button.action href="{{ route('company-transactions.index') }}" variant="secondary" :outline="true" size="sm" class="w-100">{{ __('companies::companies.Reset') }}</x-button.action>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted small">{{ __('companies::companies.Total Amount') }}</div>
        <div class="h4 mb-0">{{ number_format($totals['amount'] ?? 0, 2) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted small">{{ __('companies::companies.Disbursed Share') }}</div>
        <div class="h4 mb-0">{{ number_format($totals['disbursed'] ?? 0, 2) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted small">{{ __('companies::companies.Repaid Share') }}</div>
        <div class="h4 mb-0">{{ number_format($totals['repaid'] ?? 0, 2) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted small">{{ __('companies::companies.Outstanding Share') }}</div>
        <div class="h4 mb-0">{{ number_format($totals['outstanding'] ?? 0, 2) }}</div>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body p-0">
    <x-table head-class="table-light">
      <x-slot name="head">
        <tr>
          <th>#</th>
          <th>{{ __('companies::companies.Transaction Date') }}</th>
          <th>{{ __('companies::companies.Reference') }}</th>
          <th>{{ __('companies::companies.Status') }}</th>
          <th>{{ __('companies::companies.Account Source') }}</th>
          <th>{{ __('companies::companies.Allocations') }}</th>
          <th class="text-end">{{ __('companies::companies.Total Amount') }}</th>
          <th class="text-end">{{ __('companies::companies.Disbursed Share') }}</th>
          <th class="text-end">{{ __('companies::companies.Repaid Share') }}</th>
          <th class="text-end">{{ __('companies::companies.Outstanding Share') }}</th>
          <th style="width:150px">{{ __('companies::companies.Actions') }}</th>
        </tr>
      </x-slot>

      @forelse($transactions as $transaction)
        <tr>
          <td class="text-muted">{{ $loop->iteration + ($transactions->currentPage() - 1) * $transactions->perPage() }}</td>
          <td>{{ optional($transaction->transaction_date)->format('Y-m-d') }}</td>
          <td>{{ $transaction->reference ?: '—' }}</td>
          <td>{{ $transaction->status?->name ?? '—' }}</td>
          <td>
            @if($transaction->bankAccount)
              <span class="d-block text-muted small">{{ __('companies::companies.Bank Account') }}</span>
              <span class="fw-semibold">{{ $transaction->bankAccount->name }}</span>
            @elseif($transaction->safe)
              <span class="d-block text-muted small">{{ __('companies::companies.Safe') }}</span>
              <span class="fw-semibold">{{ $transaction->safe->name }}</span>
            @else
              <span class="text-muted">—</span>
            @endif
          </td>
          <td>
            <ul class="list-unstyled mb-0 small">
              @foreach($transaction->allocations as $allocation)
                <li>
                  <strong>{{ $allocation->company->name ?? __('companies::companies.Unknown Company') }}</strong>
                  — {{ number_format((float) $allocation->share_amount, 2) }}
                  @if(!is_null($allocation->share_percentage))
                    <span class="text-muted">({{ number_format((float) $allocation->share_percentage, 2) }}%)</span>
                  @endif
                </li>
              @endforeach
            </ul>
          </td>
          <td class="text-end">{{ number_format((float) $transaction->total_amount, 2) }}</td>
          <td class="text-end">{{ number_format((float) $transaction->disbursed_amount, 2) }}</td>
          <td class="text-end">{{ number_format((float) $transaction->repaid_amount, 2) }}</td>
          <td class="text-end fw-semibold">{{ number_format((float) $transaction->outstanding_amount, 2) }}</td>
          <td>
            <div class="btn-group btn-group-sm" role="group">
              <x-button.action href="{{ route('company-transactions.show', $transaction) }}" variant="secondary" :outline="true" size="sm">
                {{ __('companies::companies.View') }}
              </x-button.action>
              <x-button.action href="{{ route('company-transactions.edit', $transaction) }}" variant="primary" :outline="true" size="sm">
                {{ __('companies::companies.Edit') }}
              </x-button.action>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="11" class="py-5 text-center text-muted">{{ __('companies::companies.No Transactions Yet') }}</td>
        </tr>
      @endforelse
    </x-table>
  </div>

  @if($transactions->hasPages())
    <div class="card-footer bg-white">
      {{ $transactions->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
  @endif
</div>
@endsection
