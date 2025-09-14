@extends('layouts.print-landscape')

@section('title', __('reports.Customers With Active Contracts'))
@section('report_title', __('reports.Customers With Active Contracts'))

@php
  /** @var \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator $rows */
  $rows  = $rows ?? collect();
  $count = $rows->count();
@endphp

@push('styles')
  <style>
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
  </style>
@endpush

@section('content')
  <div class="row g-3 kpi mb-4">
    <div class="col-12 col-md-4">
      <div class="card">
        <div class="card-body p-3 text-center">
          <div class="small-muted">@lang('reports.Number of Customers')</div>
          <div class="fs-4 fw-bold">{{ number_format($count) }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped table-bordered text-center align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:56px">#</th>
          <th class="text-start">{{ __('Customer') }}</th>
          <th>{{ __('Phone') }}</th>
          <th>{{ __('Active Contracts') }}</th>
          <th>{{ __('reports.Total Remaining in Active Contracts') }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $i => $c)
          <tr>
            <td>{{ is_int($i) ? $i + 1 : $loop->iteration }}</td>
            <td class="text-start">
              <a href="{{ route('customers.show', $c) }}" class="text-decoration-none fw-bold text-dark hover-primary">{{ $c->name }}</a>
            </td>
            <td>{{ $c->phone }}</td>
            <td>{{ $c->active_contracts ?? 1 }}</td>
            <td>{{ number_format($c->active_remaining_total ?? 0, 2) }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="py-5 text-muted">@lang('reports.No data available.')</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection

@section('actions')
  <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">↩ @lang('app.Back')</a>
@endsection
