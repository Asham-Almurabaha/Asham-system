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
    .badge-status { color:#fff; }
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

  <div class="table-responsive">
    <table class="table table-striped table-bordered align-middle text-center">
      <thead class="table-light">
        <tr>
          <th style="width:56px">#</th>
          <th class="text-start">@lang('app.Contract')</th>
          <th>@lang('app.Status')</th>
          <th class="text-start">{{ __('Customer') }}</th>
          <th>@lang('reports.Share %')</th>
          <th>@lang('reports.Capital')</th>
          <th>@lang('reports.Net Profit')</th>
          <th>{{ __('Paid to Investor from Customer') }}</th>
          <th>{{ __('Remaining on Customers') }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $i => $row)
          @php
            $statusTxt = $statusMap[$row['contract_id']] ?? '—';
            $badge = 'bg-secondary';
            $s = (string)$statusTxt;
            if (str_contains($s,'نشط') || str_contains($s,'Active')) $badge = 'bg-success';
            if (str_contains($s,'منتهي') || str_contains(strtolower($s),'closed')) $badge = 'bg-danger';
          @endphp
          <tr>
            <td>{{ $i+1 }}</td>
            <td class="text-start">#{{ $row['contract_id'] }}</td>
            <td><span class="badge {{ $badge }} badge-status">{{ $statusTxt }}</span></td>
            <td class="text-start">{{ $row['customer'] }}</td>
            <td>{{ number_format($row['share_pct'] ?? 0, 2) }}</td>
            <td>{{ number_format($row['share_value'] ?? 0, 2) }} <span class="small-muted">{{ $cs }}</span></td>
            <td>{{ number_format($row['profit_net'] ?? 0, 2) }} <span class="small-muted">{{ $cs }}</span></td>
            <td>{{ number_format($row['paid_to_investor_from_customer'] ?? 0, 2) }} <span class="small-muted">{{ $cs }}</span></td>
            <td>{{ number_format(max(0, $row['remaining_on_customers'] ?? 0), 2) }} <span class="small-muted">{{ $cs }}</span></td>
          </tr>
        @empty
          <tr>
            <td colspan="9" class="py-5 text-muted">{{ __('No active contracts linked to this investor.') }}</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection

@section('actions')
  <a href="{{ route('investors.show', $investor) }}" class="btn btn-outline-secondary">↩ @lang('app.Back')</a>
@endsection
