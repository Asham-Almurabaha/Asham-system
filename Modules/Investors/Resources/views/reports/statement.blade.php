@extends('layouts.print-landscape')

@section('title', __('statement.Investor Statement'))
@section('report_title', __('statement.Investor Statement'))

@php
  $cs = $data['currencySymbol'] ?? 'ر.س';

  $totalCapitalShare      = (float)($data['totalCapitalShare']      ?? 0);
  $totalProfitNet         = (float)($data['totalProfitNet']         ?? 0);
  $totalPaidPortion       = (float)($data['totalPaidPortionToInvestor'] ?? 0);
  $totalRemainingOnCust   = (float)($data['totalRemainingOnCustomers']  ?? 0);

  $totalCapitalShareAll   = (float)($data['totalCapitalShareAll'] ?? 0);
  $totalProfitNetAll      = (float)($data['totalProfitNetAll']    ?? 0);

  $contractsTotal         = (int)($data['contractsTotal']  ?? 0);
  $contractsActive        = (int)($data['contractsActive'] ?? 0);
  $contractsEnded         = (int)($data['contractsEnded']  ?? 0);

  $liquidity              = (float)($data['liquidity'] ?? 0);
  $initialCapital         = (float)($data['initialCapital'] ?? 0);

  $total                  = $liquidity + $totalRemainingOnCust;

  $rows                   = $data['contractBreakdown'] ?? [];
@endphp

@push('styles')
  <style>
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
    .statement-table {
      width: 100%;
      max-width: 100%;
      table-layout: fixed;
    }
    .statement-table th,
    .statement-table td {
      vertical-align: middle;
      word-break: break-word;
    }
    .statement-table .col-index { width: 6%; }
    .statement-table .col-contract { width: 12%; }
    .statement-table .col-customer { width: 20%; }
    .statement-table .col-share { width: 8%; }
    .statement-table .col-capital { width: 10%; }
    .statement-table .col-net-profit { width: 10%; }
    .statement-table .col-paid { width: 11%; }
    .statement-table .col-remaining { width: 11%; }
    .statement-table .amount { white-space: nowrap; }
  </style>
@endpush

@section('content')
  <div class="small-muted mb-2">
    @lang('app.Investor'): <strong>{{ $investor->name }}</strong>
  </div>

  <div class="row g-3 kpi mb-4">
    <div class="col-6 col-md-3">
      <div class="card"><div class="card-body p-3">
        <div class="small-muted">{{ __('Contracts') }} ({{ $contractsTotal }})</div>
        <div class="fs-6">@lang('app.Active'): <strong>{{ $contractsActive }}</strong> — @lang('app.Ended'): <strong>{{ $contractsEnded }}</strong></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card"><div class="card-body p-3">
        <div class="small-muted">@lang('reports.Initial Capital')</div>
        <div class="fs-6 fw-bold">{{ number_format($initialCapital,2) }} <span class="small-muted">{{ $cs }}</span></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card"><div class="card-body p-3">
        <div class="small-muted">@lang('reports.Current Liquidity')</div>
        <div class="fs-6 fw-bold">{{ number_format($liquidity,2) }} <span class="small-muted">{{ $cs }}</span></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card"><div class="card-body p-3">
        <div class="small-muted">{{ __('Remaining on Customers') }}</div>
        <div class="fs-6 fw-bold">{{ number_format(max(0,$totalRemainingOnCust),2) }} <span class="small-muted">{{ $cs }}</span></div>
      </div></div>
    </div>
    <div class="col-12 col-md-6">
      <div class="card"><div class="card-body p-3">
        <div class="small-muted">@lang('reports.Expected Balance After Installments')</div>
        <div class="fs-5 fw-bold">{{ number_format(max(0,$total),2) }} <span class="small-muted">{{ $cs }}</span></div>
      </div></div>
    </div>
  </div>

  <div class="row g-3 kpi mb-3">
    <div class="col-6 col-md-3">
      <div class="card"><div class="card-body p-3">
        <div class="small-muted">@lang('app.Capital (participating in all contracts)')</div>
        <div class="fs-6 fw-bold">{{ number_format($totalCapitalShareAll,2) }} <span class="small-muted">{{ $cs }}</span></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card"><div class="card-body p-3">
        <div class="small-muted">@lang('app.Net profit (from all contracts)')</div>
        <div class="fs-6 fw-bold">{{ number_format($totalProfitNetAll,2) }} <span class="small-muted">{{ $cs }}</span></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card"><div class="card-body p-3">
        <div class="small-muted">@lang('app.Capital (active contracts)')</div>
        <div class="fs-6 fw-bold">{{ number_format($totalCapitalShare,2) }} <span class="small-muted">{{ $cs }}</span></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card"><div class="card-body p-3">
        <div class="small-muted">@lang('app.Net profit (active contracts)')</div>
        <div class="fs-6 fw-bold">{{ number_format($totalProfitNet,2) }} <span class="small-muted">{{ $cs }}</span></div>
      </div></div>
    </div>
  </div>

  <x-table head-class="table-light" striped bordered class="text-center statement-table" :hover="false">
      <x-slot name="head">
          <tr>
            <th class="col-index text-center">#</th>
            <th class="col-contract text-center">{{ __('Contract Number') }}</th>
            <th class="col-customer text-start">{{ __('Customer') }}</th>
            <th class="col-share text-center">@lang('reports.Share %')</th>
            <th class="col-capital text-center">@lang('reports.Capital')</th>
            <th class="col-net-profit text-center">@lang('reports.Net Profit')</th>
            <th class="col-paid text-center">{{ __('Paid to Investor from Customer') }}</th>
            <th class="col-remaining text-center">{{ __('Remaining on Customers') }}</th>
          </tr>
      </x-slot>
      @forelse($rows as $i => $row)
        <tr>
          <td class="col-index text-center text-nowrap">{{ $i+1 }}</td>
          <td class="col-contract text-center text-nowrap">{{ $row['contract_number'] ?? ('#'.$row['contract_id']) }}</td>
          <td class="col-customer text-start">{{ $row['customer'] }}</td>
          <td class="col-share text-center text-nowrap">{{ number_format($row['share_pct'] ?? 0, 2) }}</td>
          <td class="col-capital text-center">
            <div class="amount">{{ number_format($row['share_value'] ?? 0, 2) }} <span class="small-muted">{{ $cs }}</span></div>
          </td>
          <td class="col-net-profit text-center">
            <div class="amount">{{ number_format($row['profit_net'] ?? 0, 2) }} <span class="small-muted">{{ $cs }}</span></div>
          </td>
          <td class="col-paid text-center">
            @php
              $normalizedStatus = mb_strtolower((string)($row['status_name'] ?? ''), 'UTF-8');
              $isRaisedStatus = $normalizedStatus === mb_strtolower('مرفوع فيه', 'UTF-8');
              $paidInstallments = (float)($row['paid_to_investor_from_installments'] ?? 0);
              $paidClaims = (float)($row['paid_to_investor_from_claims'] ?? 0);
            @endphp
            <div class="amount">{{ number_format($row['paid_to_investor_from_customer'] ?? 0, 2) }} <span class="small-muted">{{ $cs }}</span></div>
            @if($isRaisedStatus)
              <div class="small-muted text-center mt-1">
                {{ __('reports.Paid via Installments') }}:
                <span class="fw-semibold">{{ number_format($paidInstallments, 2) }}</span>
                <span class="small-muted">{{ $cs }}</span>
              </div>
              <div class="small-muted text-center">
                {{ __('reports.Paid via Claims') }}:
                <span class="fw-semibold">{{ number_format($paidClaims, 2) }}</span>
                <span class="small-muted">{{ $cs }}</span>
              </div>
            @endif
          </td>
          <td class="col-remaining text-center">
            <div class="amount">{{ number_format(max(0, $row['remaining_on_customers'] ?? 0), 2) }} <span class="small-muted">{{ $cs }}</span></div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="8" class="py-5 text-muted">{{ __('No active contracts linked to this investor.') }}</td>
        </tr>
      @endforelse
  </x-table>
@endsection

