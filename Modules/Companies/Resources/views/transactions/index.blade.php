@extends('layouts.master')

@php
    $pageTitle = $pageTitle ?? __('companies::companies.Company Transactions');
    $pageHeading = $pageHeading ?? $pageTitle;
    $indexRoute = $indexRoute ?? route('company-transactions.index');
    $createRoute = $createRoute ?? route('company-transactions.create');
    $createButtonLabel = $createButtonLabel ?? __('companies::companies.New Transaction');
    $showStatusFilter = $showStatusFilter ?? true;
    $fixedStatus = $fixedStatus ?? null;
    $filtersApplied = ($showStatusFilter && request()->filled('status_id'))
        || request()->filled('company_id')
        || request()->filled('date_from')
        || request()->filled('date_to');
@endphp

@section('title', $pageTitle)

@section('content')
<div class="pagetitle mb-3">
  <h1 class="h3 mb-1">{{ $pageHeading }}</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('companies.index') }}">{{ __('companies::companies.Companies') }}</a></li>
      <li class="breadcrumb-item active">{{ $pageHeading }}</li>
    </ol>
  </nav>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body d-flex flex-wrap gap-2 align-items-center  p-20">
    <span class="ms-auto small text-muted">
      {{ __('companies::companies.Results Count', ['count' => number_format($transactions->total())]) }}
    </span>
    @if($fixedStatus)
      <span class="badge bg-primary rounded-pill">{{ $fixedStatus->name }}</span>
    @endif

    <x-button.action type="button" variant="secondary" :outline="true" size="sm" data-bs-toggle="collapse"
      data-bs-target="#transactionFilters" aria-expanded="{{ $filtersApplied ? 'true' : 'false' }}" aria-controls="transactionFilters">
      {{ __('companies::companies.Filter') }}
    </x-button.action>
  </div>

  <div class="collapse border-top {{ $filtersApplied ? 'show' : '' }}" id="transactionFilters">
    <div class="card-body">
      <form action="{{ $indexRoute }}" method="GET" class="row gy-2 gx-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label mb-1" for="company_id">{{ __('companies::companies.Company Name') }}</label>
          <select name="company_id" id="company_id" class="form-select form-select-sm">
            <option value="">{{ __('companies::companies.All Companies') }}</option>
            @foreach($companies as $company)
              <option value="{{ $company->id }}" @selected((int) request('company_id') === $company->id)>{{ $company->name }}</option>
            @endforeach
          </select>
        </div>
        @if($showStatusFilter)
          <div class="col-md-3">
            <label class="form-label mb-1" for="status_id">{{ __('companies::companies.Status') }}</label>
            <select name="status_id" id="status_id" class="form-select form-select-sm">
              <option value="">{{ __('companies::companies.All Statuses') }}</option>
              @foreach($statuses as $status)
                <option value="{{ $status->id }}" @selected((int) request('status_id') === $status->id)>{{ $status->name }}</option>
              @endforeach
            </select>
          </div>
        @endif
        <div class="col-md-3">
          <label class="form-label mb-1" for="date_from">{{ __('companies::companies.Date From') }}</label>
          <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm js-date">
        </div>
        <div class="col-md-3">
          <label class="form-label mb-1" for="date_to">{{ __('companies::companies.Date To') }}</label>
          <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm js-date">
        </div>
        <div class="col-md-2">
          <x-button.action type="submit" variant="primary" size="sm" class="w-100">{{ __('companies::companies.Apply Filter') }}</x-button.action>
        </div>
        <div class="col-md-2">
          <x-button.action href="{{ $indexRoute }}" variant="secondary" :outline="true" size="sm" class="w-100">{{ __('companies::companies.Reset') }}</x-button.action>
        </div>
      </form>
    </div>
  </div>
</div>

@php
  $summaryCards = [
      [
          'label' => __('companies::companies.Total Amount'),
          'value' => number_format($totals['amount'] ?? 0, 2),
          'icon' => 'bi-cash-stack text-primary',
      ],
      [
          'label' => __('companies::companies.Disbursed Share'),
          'value' => number_format($totals['disbursed'] ?? 0, 2),
          'icon' => 'bi-arrow-down-right-circle text-success',
      ],
      [
          'label' => __('companies::companies.Repaid Share'),
          'value' => number_format($totals['repaid'] ?? 0, 2),
          'icon' => 'bi-arrow-up-right-circle text-info',
      ],
      [
          'label' => __('companies::companies.Outstanding Share'),
          'value' => number_format($totals['outstanding'] ?? 0, 2),
          'icon' => 'bi-wallet2 text-warning',
      ],
  ];
@endphp

<div class="row g-3 mb-3" dir="rtl">
  @foreach($summaryCards as $card)
    <div class="col-12 col-md-6 col-xl-3">
      <div class="kpi-card p-3 h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="kpi-icon"><i class="bi {{ $card['icon'] }} fs-4"></i></div>
          <div class="flex-grow-1">
            <div class="subnote">{{ $card['label'] }}</div>
            <div class="kpi-value fw-bold">{{ $card['value'] }}</div>
          </div>
        </div>
      </div>
    </div>
  @endforeach
</div>

<div class="card shadow-sm">
  <div class="card-body p-0">
    <x-table head-class="table-light">
      <x-slot name="head">
        <tr>
          <th>#</th>
          <th>{{ __('companies::companies.Transaction Date') }}</th>
          <th>{{ __('companies::companies.Status') }}</th>
          <th>{{ __('companies::companies.Account Source') }}</th>
          <th>{{ __('companies::companies.Allocations') }}</th>
          <th>{{ __('companies::companies.Notes') }}</th>
          <th class="text-end">{{ __('companies::companies.Total Amount') }}</th>
          
        </tr>
      </x-slot>

      @forelse($transactions as $transaction)
        <tr>
          <td class="text-muted">{{ $loop->iteration + ($transactions->currentPage() - 1) * $transactions->perPage() }}</td>
          <td>{{ optional($transaction->transaction_date)->format('Y-m-d') }}</td>
          <td>{{ $transaction->status?->name ?? '—' }}</td>
          <td>
            @php($hasSource = false)
            @if($transaction->bankAccount)
              @php($hasSource = true)
              <span class="d-block text-muted small">{{ __('companies::companies.Bank Account') }}</span>
              <span class="fw-semibold">{{ $transaction->bankAccount->name }}</span>
              <span class="d-block text-muted small">{{ number_format((float) $transaction->bank_amount, 2) }}</span>
            @endif
            @if($transaction->safe)
              @php($hasSource = true)
              <span class="d-block text-muted small mt-1">{{ __('companies::companies.Safe') }}</span>
              <span class="fw-semibold">{{ $transaction->safe->name }}</span>
              <span class="d-block text-muted small">{{ number_format((float) $transaction->safe_amount, 2) }}</span>
            @endif
            @unless($hasSource)
              <span class="text-muted">—</span>
            @endunless
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
          <td>{{ $transaction->notes ?: '—' }}</td>
          <td class="text-end">{{ number_format((float) $transaction->total_amount, 2) }}</td>

        </tr>
      @empty
        <tr>
          <td colspan="7" class="py-5 text-center text-muted">{{ __('companies::companies.No Transactions Yet') }}</td>
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
