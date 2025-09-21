@extends('layouts.print-landscape')

@section('title', __('reports.Investors Outstanding Report'))
@section('report_title', __('reports.Investors Outstanding Report'))

@php
  $cs = $currencySymbol ?? 'ر.س';

  $filters = (array) ($filters ?? []);
  $selectedInvestor = (string) data_get($filters, 'investor_id', '');
  $items = collect(is_iterable($rows ?? []) ? $rows : []);

  $countAll      = $items->count();
  $grandTotals   = collect($grandTotals ?? []);
  $grandWith     = (float) $grandTotals->get('with_office', 0);
  $grandWithout  = (float) $grandTotals->get('without_office', 0);
  $grandOffice   = (float) $grandTotals->get('office_share', max(0, $grandWith - $grandWithout));
  $avgRemaining  = $countAll > 0 ? ($grandWith / $countAll) : 0;
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
    @lang('reports.Based on active contracts remaining balances for each investor.')
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
    <div class="col-12 col-md-3">
      <div class="card"><div class="card-body text-center">
        <div class="small-muted">@lang('reports.Total Investors (All)')</div>
        <div class="fs-5 fw-bold">{{ number_format($countAll) }}</div>
      </div></div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card"><div class="card-body text-center">
        <div class="small-muted">@lang('reports.Total Remaining (All Investors)')</div>
        <div class="fs-5 fw-bold text-danger">
          {{ number_format($grandWith, 2) }} <span class="small-muted">{{ $cs }}</span>
        </div>
      </div></div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card"><div class="card-body text-center">
        <div class="small-muted">@lang('reports.Total Remaining Without Office Share')</div>
        <div class="fs-5 fw-bold text-primary">
          {{ number_format($grandWithout, 2) }} <span class="small-muted">{{ $cs }}</span>
        </div>
      </div></div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card"><div class="card-body text-center">
        <div class="small-muted">@lang('reports.Office Share Portion Pending')</div>
        <div class="fs-5 fw-bold text-warning">
          {{ number_format($grandOffice, 2) }} <span class="small-muted">{{ $cs }}</span>
        </div>
      </div></div>
    </div>
    {{-- <div class="col-12 col-md-3">
      <div class="card"><div class="card-body text-center">
        <div class="small-muted">@lang('reports.Average Remaining per Investor')</div>
        <div class="fs-5 fw-bold">
          {{ number_format($avgRemaining, 2) }} <span class="small-muted">{{ $cs }}</span>
        </div>
        <div class="small-muted mt-1">@lang('reports.Office Share Portion Pending'): {{ number_format($grandOffice, 2) }} <span class="small-muted">{{ $cs }}</span></div>
      </div></div>
    </div> --}}
  </div>

  <x-table head-class="table-light" striped bordered class="text-center" :hover="false">
      <x-slot name="head">
          <tr>
            <th style="width:56px">#</th>
            <th class="text-start">@lang('app.Investor')</th>
            <th>@lang('reports.Remaining (Including Office Share)')</th>
            <th>@lang('reports.Office Share Portion')</th>
            <th>@lang('reports.Remaining (Excluding Office Share)')</th>
          </tr>
      </x-slot>
      @forelse($items as $r)
        @php
          $withOffice = (float) ($r->remaining_with_office ?? 0);
          $withoutOffice = (float) ($r->remaining_without_office ?? 0);
          $officeShare = (float) ($r->remaining_office_share ?? max(0, $withOffice - $withoutOffice));
        @endphp
        <tr>
          <td>{{ $loop->iteration }}</td>
          <td class="text-start">
            @if(Route::has('investors.show'))
              <a href="{{ route('investors.show', $r->id) }}" class="fw-bold link-dark text-decoration-none">{{ $r->name }}</a>
            @else
              <span class="fw-bold text-dark">{{ $r->name }}</span>
            @endif
          </td>
          <td class="fw-semibold text-danger">
            {{ number_format($withOffice, 2) }} <span class="small-muted">{{ $cs }}</span>
          </td>
          <td class="fw-semibold text-warning">
            {{ number_format($officeShare, 2) }} <span class="small-muted">{{ $cs }}</span>
          </td>
          <td class="fw-semibold text-primary">
            {{ number_format($withoutOffice, 2) }} <span class="small-muted">{{ $cs }}</span>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="5" class="py-5 text-muted">@lang('reports.No matching data.')</td>
        </tr>
      @endforelse
  </x-table>
@endsection

