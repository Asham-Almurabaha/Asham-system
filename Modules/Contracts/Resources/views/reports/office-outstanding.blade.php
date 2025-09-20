@extends('layouts.print-landscape')

@section('title', __('reports.Contracts Office Profit Outstanding'))
@section('report_title', __('reports.Contracts Office Profit Outstanding'))

@php
  $rows = collect($rows ?? []);
  $currencySymbol = $currencySymbol ?? 'ر.س';
  $totals = collect($totals ?? []);

  $countContracts = $rows->count();
  $totalDue       = (float) $totals->get('due', 0);
  $totalPaid      = (float) $totals->get('paid', 0);
  $totalRemaining = (float) $totals->get('remaining', 0);
@endphp

@push('styles')
  <style>
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
  </style>
@endpush

@section('content')
  <div class="row g-3 kpi mb-4">
    <div class="col-12 col-md-3">
      <div class="card"><div class="card-body p-3 text-center">
        <div class="small-muted">@lang('app.Contracts')</div>
        <div class="fs-5 fw-bold">{{ number_format($countContracts) }}</div>
      </div></div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card"><div class="card-body p-3 text-center">
        <div class="small-muted">@lang('reports.Total Office Profit')</div>
        <div class="fs-5 fw-bold text-warning">
          {{ number_format($totalDue, 2) }} <span class="small-muted">{{ $currencySymbol }}</span>
        </div>
      </div></div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card"><div class="card-body p-3 text-center">
        <div class="small-muted">@lang('reports.Office Profit Paid')</div>
        <div class="fs-5 fw-bold text-success">
          {{ number_format($totalPaid, 2) }} <span class="small-muted">{{ $currencySymbol }}</span>
        </div>
      </div></div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card"><div class="card-body p-3 text-center">
        <div class="small-muted">@lang('reports.Office Profit Remaining')</div>
        <div class="fs-5 fw-bold text-danger">
          {{ number_format($totalRemaining, 2) }} <span class="small-muted">{{ $currencySymbol }}</span>
        </div>
      </div></div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped table-bordered text-center align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:56px">#</th>
          <th>{{ __('Contract Number') }}</th>
          <th class="text-start">{{ __('Customer') }}</th>
          <th>{{ __('Status') }}</th>
          <th>{{ __('reports.Total Office Profit') }}</th>
          <th>{{ __('reports.Office Profit Paid') }}</th>
          <th>{{ __('reports.Office Profit Remaining') }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $contract)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td>
              @php($contractNumber = $contract->contract_number ?? $contract->id ?? null)
              @if($contractNumber !== null && $contractNumber !== '')
                <a href="{{ route('contracts.show', $contract) }}" class="text-decoration-none fw-bold text-dark">
                  {{ $contractNumber }}
                </a>
              @else
                -
              @endif
            </td>
            <td class="text-start">
              @php($customer = $contract->customer)
              @if($customer)
                <a href="{{ route('customers.show', $customer) }}" class="text-decoration-none fw-bold text-dark">
                  {{ $customer->name ?? '-' }}
                </a>
              @else
                -
              @endif
            </td>
            <td>{{ $contract->contractStatus->name ?? ($contract->status ?? '-') }}</td>
            <td class="fw-semibold text-warning">
              {{ number_format((float) ($contract->office_due ?? 0), 2) }}
              <span class="small-muted">{{ $currencySymbol }}</span>
            </td>
            <td class="fw-semibold text-success">
              {{ number_format((float) ($contract->office_paid ?? 0), 2) }}
              <span class="small-muted">{{ $currencySymbol }}</span>
            </td>
            <td class="fw-semibold text-danger">
              {{ number_format((float) ($contract->office_remaining ?? 0), 2) }}
              <span class="small-muted">{{ $currencySymbol }}</span>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="py-5 text-muted">{{ __('reports.No data available.') }}</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection

@section('actions')
  <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary">↩ @lang('app.Back')</a>
  <x-refresh-button :href="url()->current()" />
@endsection
