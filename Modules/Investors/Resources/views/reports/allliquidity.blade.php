@extends('layouts.print-landscape')

@section('title', __('reports.Current Investors Liquidity Report'))
@section('report_title', __('reports.Current Investors Liquidity Report'))

@php
  $cs = $currencySymbol ?? 'ر.س';

  $filters = (array) ($filters ?? []);
  $selectedInvestor = (string) data_get($filters, 'investor_id', '');
  $items = collect(is_iterable($rows ?? []) ? $rows : []);
  $countAll = $items->count();
  $investorOptions = collect($investors ?? []);
@endphp

@push('styles')
  <style>
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
  </style>
@endpush

@section('content')
  <div class="small-muted mb-2">
    @lang('reports.Based on investor transactions: deposits minus withdrawals per investor.')
  </div>

  <div class="toolbar soft p-3 mb-3 no-print">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-12 col-md-6 col-lg-4">
        <label class="form-label mb-1 small">@lang('reports.Search by name')</label>
        <select name="investor_id" class="form-select">
          <option value="">@lang('reports.All Investors')</option>
          @foreach($investorOptions as $investor)
            <option value="{{ $investor->id }}" @selected((string) $selectedInvestor === (string) $investor->id)>{{ $investor->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12 col-md-6 col-lg-4 d-flex gap-2">
        <x-button.action type="submit" variant="primary" class="flex-fill"><i class="bi bi-search"></i> {{ __('Search') }}</x-button.action>
        <x-button.action href="{{ url()->current() }}" variant="secondary" :outline="true" class="flex-fill">{{ __('Clear') }}</x-button.action>
      </div>
    </form>
  </div>

  <div class="row g-3 kpi mb-4">
    <div class="col-12 col-md-6">
      <div class="card"><div class="card-body text-center">
        <div class="small-muted">@lang('reports.Total Investors (All)')</div>
        <div class="fs-5 fw-bold">{{ number_format($countAll) }}</div>
      </div></div>
    </div>
    <div class="col-12 col-md-6">
      <div class="card"><div class="card-body text-center">
        <div class="small-muted">@lang('reports.Total Liquidity (All)')</div>
        <div class="fs-5 fw-bold {{ ($grandTotal??0)>=0 ? 'text-success' : 'text-danger' }}">
          {{ number_format((float)($grandTotal ?? 0), 2) }} <span class="small-muted">{{ $cs }}</span>
        </div>
      </div></div>
    </div>
  </div>

  <x-table head-class="table-light" striped bordered class="text-center" :hover="false">
      <x-slot name="head">
          <tr>
            <th style="width:56px">#</th>
            <th class="text-start">@lang('app.Investor')</th>
            <th>@lang('reports.Active Contracts')</th>
            <th>@lang('reports.Current Liquidity')</th>
          </tr>
      </x-slot>
              @forelse($items as $r)
                @php
                  $liq  = (float) ($r->liquidity ?? 0);
                  $activeContracts = collect($r->active_contract_numbers ?? []);
                @endphp
                <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td class="text-start">
                    <div class="fw-semibold">
                      @if(Route::has('investors.show'))
                        <a href="{{ route('investors.show', $r->id) }}" class="fw-bold text-dark text-decoration-none">{{ $r->name }}</a>
                      @else
                        <span class="fw-bold text-dark">{{ $r->name }}</span>
                      @endif
                    </div>
                  </td>
                  <td>{{ number_format($activeContracts->count()) }}</td>
                  <td class="fw-bold {{ $liq>=0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($liq, 2) }} <span class="small-muted">{{ $cs }}</span>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="py-5 text-muted">@lang('reports.No matching data.')</td>
                </tr>
              @endforelse
  </x-table>
@endsection

