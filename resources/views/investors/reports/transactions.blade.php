@extends('layouts.print-landscape')

@section('title', __('reports.Deposits / Withdrawals Summary'))
@section('report_title', __('reports.Deposits / Withdrawals Summary'))

@php
  use Illuminate\Support\Carbon;

  $cs = $currencySymbol ?? 'ر.س';

  $items = $transactions instanceof \Illuminate\Pagination\LengthAwarePaginator
    ? $transactions->items()
    : (array) $transactions;

  $countAll        = (int) ($transactionsCount ?? 0);
  $depositsTotal   = (float) ($depositsTotal    ?? 0.0);
  $withdrawalsTotal= (float) ($withdrawalsTotal ?? 0.0);
  $netTotal        = $depositsTotal - $withdrawalsTotal;

  $from = request('from');
  $to   = request('to');
@endphp

@push('styles')
  <style>
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
  </style>
@endpush

@section('content')
  <div class="small-muted mb-2">
    @lang('app.Investor'): <strong>{{ $investor->name }}</strong>
    @if($from || $to)
      — @lang('app.Period'):
      <strong>{{ $from ? Carbon::parse($from)->format('d-m-Y') : '—' }}</strong>
      @lang('app.to')
      <strong>{{ $to ? Carbon::parse($to)->format('d-m-Y') : '—' }}</strong>
    @endif
  </div>

  <div class="row g-3 kpi mb-4">
    <div class="col-12 col-md-3">
      <div class="card"><div class="card-body p-3 text-center">
        <div class="small-muted">@lang('reports.Total Transactions')</div>
        <div class="fs-5 fw-bold">{{ number_format($countAll) }}</div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card"><div class="card-body p-3 text-center">
        <div class="small-muted">@lang('reports.Total Deposits')</div>
        <div class="fs-5 fw-bold text-success">
          {{ number_format($depositsTotal, 2) }} <span class="small-muted">{{ $cs }}</span>
        </div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card"><div class="card-body p-3 text-center">
        <div class="small-muted">@lang('app.Total Withdrawals')</div>
        <div class="fs-5 fw-bold text-danger">
          {{ number_format($withdrawalsTotal, 2) }} <span class="small-muted">{{ $cs }}</span>
        </div>
      </div></div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card"><div class="card-body p-3 text-center">
        <div class="small-muted">@lang('reports.Deposits minus Withdrawals')</div>
        <div class="fs-5 fw-bold {{ $netTotal >= 0 ? 'text-success' : 'text-danger' }}">
          {{ number_format($netTotal, 2) }} <span class="small-muted">{{ $cs }}</span>
        </div>
      </div></div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped table-bordered text-center align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:56px">#</th>
          <th>@lang('app.Date')</th>
          <th>@lang('app.Amount')</th>
          <th>@lang('app.Type')</th>
          <th>@lang('app.Status')</th>
          <th class="text-start">@lang('app.Notes')</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $i => $e)
          @php
            $statusName = optional($e->status)->name ?? optional($e->transactionStatus)->name ?? '—';
            $typeName   = optional($e->type)->name   ?? optional($e->transactionType)->name   ?? '—';
            $isDeposit  = (string)$e->direction === 'in';
            $amountCls  = $isDeposit ? 'text-success' : 'text-danger';
          @endphp
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ Carbon::parse($e->entry_date)->format('d-m-Y') }}</td>
            <td class="fw-semibold {{ $amountCls }}">
              {{ number_format($e->amount, 2) }} <span class="small-muted">{{ $cs }}</span>
            </td>
            <td>{{ $typeName }}</td>
            <td>{{ $statusName }}</td>
            <td class="text-start">{{ $e->notes ?? '—' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="py-5 text-muted">@lang('reports.No matching transactions in the report.')</td>
          </tr>
        @endforelse
      </tbody>

      @if($transactions instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <tfoot>
          <tr>
            <th colspan="6" class="bg-white">
              <div class="no-print d-flex justify-content-center p-2">
                {{ $transactions->withQueryString()->links('pagination::bootstrap-5') }}
              </div>
            </th>
          </tr>
        </tfoot>
      @endif
    </table>
  </div>
@endsection

@section('actions')
  <a href="{{ route('investors.show', $investor) }}" class="btn btn-outline-secondary">↩ @lang('app.Back')</a>
@endsection

