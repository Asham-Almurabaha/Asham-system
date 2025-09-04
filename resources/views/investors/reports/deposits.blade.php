@extends('layouts.print-landscape')

@section('title', __('reports.Deposits Summary'))
@section('report_title', __('reports.Deposits Summary'))

@php
  use Illuminate\Support\Carbon;

  $cs = $currencySymbol ?? ($data['currencySymbol'] ?? 'ر.س');

  $dCollection = $deposits instanceof \Illuminate\Pagination\LengthAwarePaginator
    ? collect($deposits->items())
    : collect($deposits ?? []);

  $depositsCount = isset($depositsCount) ? (int)$depositsCount : (int)$dCollection->count();
  $depositsTotal = isset($depositsTotal) ? (float)$depositsTotal : (float)$dCollection->sum('amount');

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
      <div class="card"><div class="card-body text-center">
        <div class="small-muted">@lang('reports.Number of Deposits')</div>
        <div class="fs-5 fw-bold">{{ number_format($depositsCount) }}</div>
      </div></div>
    </div>
    <div class="col-6 col-md-6">
      <div class="card"><div class="card-body text-center">
        <div class="small-muted">@lang('reports.Total Deposits')</div>
        <div class="fs-5 fw-bold text-success">
          {{ number_format($depositsTotal, 2) }} <span class="small-muted">{{ $cs }}</span>
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
          <th>@lang('app.Notes')</th>
        </tr>
      </thead>
      <tbody>
        @forelse($deposits as $i => $d)
          @php
            $statusName = optional($d->status)->name ?? optional($d->transactionStatus)->name ?? '—';
            $typeName   = optional($d->type)->name   ?? optional($d->transactionType)->name   ?? '—';
          @endphp
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ Carbon::parse($d->entry_date)->format('d-m-Y') }}</td>
            <td class="text-success fw-semibold">
              {{ number_format($d->amount, 2) }} <span class="small-muted">{{ $cs }}</span>
            </td>
            <td>{{ $typeName }}</td>
            <td>{{ $statusName }}</td>
            <td class="text-start">{{ $d->notes ?? '—' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="py-5 text-muted">@lang('reports.No deposits recorded for this investor in the current range.')</td>
          </tr>
        @endforelse
      </tbody>
      @if($deposits instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <tfoot>
          <tr>
            <th colspan="6" class="bg-white">
              <div class="no-print d-flex justify-content-center p-2">
                {{ $deposits->withQueryString()->links('pagination::bootstrap-5') }}
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

