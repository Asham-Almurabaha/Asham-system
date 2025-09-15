@extends('layouts.print-landscape')

@section('title', __('app.Withdrawals Summary'))
@section('report_title', __('app.Withdrawals Summary'))

@php
  use Illuminate\Support\Carbon;

  $cs = $currencySymbol ?? ($data['currencySymbol'] ?? 'ر.س');

  $wCollection = $withdrawals instanceof \Illuminate\Pagination\LengthAwarePaginator
    ? collect($withdrawals->items())
    : collect($withdrawals ?? []);

  $withdrawalsCount = isset($withdrawalsCount) ? (int)$withdrawalsCount : (int)$wCollection->count();
  $withdrawalsTotal = isset($withdrawalsTotal) ? (float)$withdrawalsTotal : (float)$wCollection->sum('amount');

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
    <div class="col-6 col-md-6">
      <div class="card"><div class="card-body p-3 text-center">
        <div class="small-muted">@lang('app.Number of Withdrawals')</div>
        <div class="fs-5 fw-bold">{{ number_format($withdrawalsCount) }}</div>
      </div></div>
    </div>
    <div class="col-6 col-md-6">
      <div class="card"><div class="card-body p-3 text-center">
        <div class="small-muted">@lang('app.Total Withdrawals')</div>
        <div class="fs-5 fw-bold text-danger">
          {{ number_format($withdrawalsTotal, 2) }} <span class="small-muted">{{ $cs }}</span>
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
        @forelse($withdrawals as $i => $w)
          @php
            $statusName = optional($w->status)->name ?? optional($w->transactionStatus)->name ?? '—';
            $typeName   = optional($w->type)->name   ?? optional($w->transactionType)->name   ?? '—';
          @endphp
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ Carbon::parse($w->entry_date)->format('d-m-Y') }}</td>
            <td class="text-danger fw-semibold">
              {{ number_format($w->amount, 2) }} <span class="small-muted">{{ $cs }}</span>
            </td>
            <td>{{ $typeName }}</td>
            <td>{{ $statusName }}</td>
            <td class="text-start">{{ $w->notes ?? '—' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="py-5 text-muted">
              @lang('app.No withdrawals found for this investor in the current range.')
            </td>
          </tr>
        @endforelse
      </tbody>

      @if($withdrawals instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <tfoot>
          <tr>
            <th colspan="6" class="bg-white">
              <div class="no-print d-flex justify-content-center p-2">
                {{ $withdrawals->withQueryString()->links('pagination::bootstrap-5') }}
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

