@extends('layouts.master')

@section('title', $transaction->reference ?: __('companies::companies.Transaction #', ['id' => $transaction->id]))

@section('content')
<div class="pagetitle mb-3">
  <h1 class="h3 mb-1">{{ $transaction->reference ?: __('companies::companies.Transaction #', ['id' => $transaction->id]) }}</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('company-transactions.index') }}">{{ __('companies::companies.Company Transactions') }}</a></li>
      <li class="breadcrumb-item active">{{ $transaction->reference ?: __('companies::companies.Transaction #', ['id' => $transaction->id]) }}</li>
    </ol>
  </nav>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
  <x-button.action href="{{ route('company-transactions.edit', $transaction) }}" variant="primary">
    <i class="bi bi-pencil"></i> {{ __('companies::companies.Edit Transaction') }}
  </x-button.action>
  <x-button.action href="{{ route('company-transactions.create', ['company_id' => optional($transaction->allocations->first())->company_id]) }}" variant="success">
    <i class="bi bi-plus-lg"></i> {{ __('companies::companies.New Transaction') }}
  </x-button.action>
  <form action="{{ route('company-transactions.destroy', $transaction) }}" method="POST" onsubmit="return confirm('{{ __('companies::companies.Delete Transaction Confirmation') }}');">
    @csrf
    @method('DELETE')
    <x-button.action type="submit" variant="danger" :outline="true">
      <i class="bi bi-trash"></i> {{ __('companies::companies.Delete Transaction') }}
    </x-button.action>
  </form>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted small">{{ __('companies::companies.Total Amount') }}</div>
        <div class="h4 mb-0">{{ number_format((float) $transaction->total_amount, 2) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted small">{{ __('companies::companies.Disbursed Share') }}</div>
        <div class="h4 mb-0">{{ number_format((float) $transaction->disbursed_amount, 2) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted small">{{ __('companies::companies.Repaid Share') }}</div>
        <div class="h4 mb-0">{{ number_format((float) $transaction->repaid_amount, 2) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted small">{{ __('companies::companies.Outstanding Share') }}</div>
        <div class="h4 mb-0">{{ number_format((float) $transaction->outstanding_amount, 2) }}</div>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white border-0">
    <h2 class="h5 mb-0">{{ __('companies::companies.Transaction Details') }}</h2>
  </div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-4">
        <div class="text-muted small">{{ __('companies::companies.Transaction Date') }}</div>
        <div class="fw-semibold">{{ optional($transaction->transaction_date)->format('Y-m-d') }}</div>
      </div>
      <div class="col-md-4">
        <div class="text-muted small">{{ __('companies::companies.Reference') }}</div>
        <div class="fw-semibold">{{ $transaction->reference ?: '—' }}</div>
      </div>
      <div class="col-md-4">
        <div class="text-muted small">{{ __('companies::companies.Status') }}</div>
        <div class="fw-semibold">{{ $transaction->status?->name ?? '—' }}</div>
      </div>
      <div class="col-md-4">
        <div class="text-muted small">{{ __('companies::companies.Account Source') }}</div>
        <div>
          @if($transaction->bankAccount)
            <span class="d-block text-muted small">{{ __('companies::companies.Bank Account') }}</span>
            <span class="fw-semibold">{{ $transaction->bankAccount->name }}</span>
          @elseif($transaction->safe)
            <span class="d-block text-muted small">{{ __('companies::companies.Safe') }}</span>
            <span class="fw-semibold">{{ $transaction->safe->name }}</span>
          @else
            <span class="text-muted">—</span>
          @endif
        </div>
      </div>
      <div class="col-md-4">
        <div class="text-muted small">{{ __('companies::companies.Created At') }}</div>
        <div>{{ optional($transaction->created_at)->format('Y-m-d H:i') }}</div>
      </div>
      <div class="col-12">
        <div class="text-muted small">{{ __('companies::companies.Notes') }}</div>
        <div class="border rounded p-3 bg-light">{{ $transaction->notes ?: __('companies::companies.No Notes') }}</div>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm border-0">
  <div class="card-header bg-white border-0">
    <h2 class="h5 mb-0">{{ __('companies::companies.Allocations') }}</h2>
  </div>
  <div class="card-body p-0">
    <x-table head-class="table-light">
      <x-slot name="head">
        <tr>
          <th>{{ __('companies::companies.Company Name') }}</th>
          <th class="text-end">{{ __('companies::companies.Share Amount') }}</th>
          <th class="text-end">{{ __('companies::companies.Share Percentage') }}</th>
          <th class="text-end">{{ __('companies::companies.Disbursed Share') }}</th>
          <th class="text-end">{{ __('companies::companies.Repaid Share') }}</th>
          <th class="text-end">{{ __('companies::companies.Outstanding Share') }}</th>
          <th>{{ __('companies::companies.Notes') }}</th>
        </tr>
      </x-slot>

      @foreach($transaction->allocations as $allocation)
        @php($summary = $shareSummaries->get($allocation->id, []))
        <tr>
          <td>{{ $allocation->company->name ?? __('companies::companies.Unknown Company') }}</td>
          <td class="text-end">{{ number_format((float) $allocation->share_amount, 2) }}</td>
          <td class="text-end">{{ is_null($allocation->share_percentage) ? '—' : number_format((float) $allocation->share_percentage, 2) . '%' }}</td>
          <td class="text-end">{{ number_format((float) ($summary['disbursed'] ?? 0), 2) }}</td>
          <td class="text-end">{{ number_format((float) ($summary['repaid'] ?? 0), 2) }}</td>
          <td class="text-end fw-semibold">{{ number_format((float) ($summary['outstanding'] ?? 0), 2) }}</td>
          <td>{{ $allocation->notes ?: '—' }}</td>
        </tr>
      @endforeach
    </x-table>
  </div>
</div>
@endsection
