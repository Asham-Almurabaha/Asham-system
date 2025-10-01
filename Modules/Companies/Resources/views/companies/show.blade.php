@extends('layouts.master')

@section('title', $company->name)

@section('content')
<div class="pagetitle mb-3">
  <h1 class="h3 mb-1">{{ $company->name }}</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('companies.index') }}">{{ __('companies::companies.Companies') }}</a></li>
      <li class="breadcrumb-item active">{{ $company->name }}</li>
    </ol>
  </nav>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
  <x-button.action href="{{ route('companies.edit', $company) }}" variant="primary">
    <i class="bi bi-pencil"></i> {{ __('companies::companies.Edit Company') }}
  </x-button.action>
  <x-button.action href="{{ route('company-transactions.create', ['company_id' => $company->id]) }}" variant="success">
    <i class="bi bi-currency-exchange"></i> {{ __('companies::companies.New Transaction') }}
  </x-button.action>
  <x-button.action href="{{ route('company-transactions.index', ['company_id' => $company->id]) }}" variant="secondary" :outline="true">
    <i class="bi bi-list-ul"></i> {{ __('companies::companies.View Transactions') }}
  </x-button.action>
  <form action="{{ route('companies.destroy', $company) }}" method="POST" onsubmit="return confirm('{{ __('companies::companies.Delete Confirmation') }}');">
    @csrf
    @method('DELETE')
    <x-button.action type="submit" variant="danger" :outline="true">
      <i class="bi bi-trash"></i> {{ __('companies::companies.Delete Company') }}
    </x-button.action>
  </form>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted small">{{ __('companies::companies.Total Allocations') }}</div>
        <div class="h4 mb-1">{{ number_format($summary['allocations_total'] ?? 0, 2) }}</div>
        <div class="text-muted small">{{ __('companies::companies.Active Transactions') }}: {{ number_format($summary['active_transactions'] ?? 0) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted small">{{ __('companies::companies.Disbursed Share') }}</div>
        <div class="h4 mb-0">{{ number_format($summary['disbursed_share'] ?? 0, 2) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted small">{{ __('companies::companies.Repaid Share') }}</div>
        <div class="h4 mb-0">{{ number_format($summary['repaid_share'] ?? 0, 2) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted small">{{ __('companies::companies.Outstanding Share') }}</div>
        <div class="h4 mb-0">{{ number_format($summary['outstanding_share'] ?? 0, 2) }}</div>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white border-0">
    <h2 class="h5 mb-0">{{ __('companies::companies.Company Details') }}</h2>
  </div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="text-muted small">{{ __('companies::companies.Company Name') }}</div>
        <div class="fw-semibold">{{ $company->name }}</div>
      </div>
      <div class="col-md-6">
        <div class="text-muted small">{{ __('companies::companies.Status') }}</div>
        <div>
          @if($company->is_active)
            <span class="badge bg-success-subtle text-success border">{{ __('companies::companies.Active') }}</span>
          @else
            <span class="badge bg-danger-subtle text-danger border">{{ __('companies::companies.Inactive') }}</span>
          @endif
        </div>
      </div>
      <div class="col-md-6">
        <div class="text-muted small">{{ __('companies::companies.Created At') }}</div>
        <div>{{ optional($company->created_at)->format('Y-m-d H:i') }}</div>
      </div>
      <div class="col-12">
        <div class="text-muted small">{{ __('companies::companies.Notes') }}</div>
        <div class="border rounded p-3 bg-light">{{ $company->notes ?: __('companies::companies.No Notes') }}</div>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm border-0">
  <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
    <h2 class="h5 mb-0">{{ __('companies::companies.Recent Transactions') }}</h2>
    <span class="small text-muted">{{ __('companies::companies.Results Count', ['count' => number_format($transactions->total())]) }}</span>
  </div>
  <div class="card-body p-0">
    <x-table head-class="table-light">
      <x-slot name="head">
        <tr>
          <th>#</th>
          <th>{{ __('companies::companies.Transaction Date') }}</th>
          <th>{{ __('companies::companies.Status') }}</th>
          <th class="text-end">{{ __('companies::companies.Total Amount') }}</th>
          <th class="text-end">{{ __('companies::companies.Allocated Share') }}</th>
          <th class="text-end">{{ __('companies::companies.Disbursed Share') }}</th>
          <th class="text-end">{{ __('companies::companies.Repaid Share') }}</th>
          <th class="text-end">{{ __('companies::companies.Outstanding Share') }}</th>
          <th style="width:120px">{{ __('companies::companies.Actions') }}</th>
        </tr>
      </x-slot>

      @forelse($transactions as $transaction)
        @php($summaryRow = $transactionSummaries->get($transaction->id, []))
        <tr>
          <td class="text-muted">{{ $loop->iteration + ($transactions->currentPage() - 1) * $transactions->perPage() }}</td>
          <td>{{ optional($transaction->transaction_date)->format('Y-m-d') }}</td>
          <td>{{ $transaction->status?->name ?? '—' }}</td>
          <td class="text-end">{{ number_format((float) $transaction->total_amount, 2) }}</td>
          <td class="text-end">
            {{ number_format((float) ($summaryRow['share_amount'] ?? 0), 2) }}
            @if(!empty($summaryRow['share_percentage']))
              <span class="text-muted small d-block">{{ number_format($summaryRow['share_percentage'], 2) }}%</span>
            @endif
          </td>
          <td class="text-end">{{ number_format((float) ($summaryRow['disbursed'] ?? 0), 2) }}</td>
          <td class="text-end">{{ number_format((float) ($summaryRow['repaid'] ?? 0), 2) }}</td>
          <td class="text-end fw-semibold">{{ number_format((float) ($summaryRow['outstanding'] ?? 0), 2) }}</td>
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
          <td colspan="9" class="py-5 text-center text-muted">{{ __('companies::companies.No Transactions Yet') }}</td>
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
