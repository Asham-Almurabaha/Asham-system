@extends('layouts.print-portrait')

@section('title', $title ?? __('Contracts Report'))
@section('report_title', $title ?? __('Contracts Report'))

@php
  $rows = $rows ?? collect();
  $count = is_iterable($rows) ? (method_exists($rows, 'count') ? $rows->count() : collect($rows)->count()) : 0;
  $totalValue = is_iterable($rows) ? collect($rows)->sum(fn($c)=>(float)($c->total_value ?? 0)) : 0;
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
      <div class="card"><div class="card-body p-3 text-center">
        <div class="small-muted">@lang('app.Contracts')</div>
        <div class="fs-4 fw-bold">{{ number_format($count) }}</div>
      </div></div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card"><div class="card-body p-3 text-center">
        <div class="small-muted">@lang('reports.Total Value')</div>
        <div class="fs-4 fw-bold">{{ number_format($totalValue, 2) }}</div>
      </div></div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped table-bordered text-center align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:56px">#</th>
          <th class="text-start">{{ __('Customer') }}</th>
          <th>{{ __('Status') }}</th>
          <th>{{ __('Total Contract') }}</th>
          <th>{{ __('Start Date') }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $i => $c)
          <tr>
            <td>{{ is_int($i) ? $i + 1 : $loop->iteration }}</td>
            <td class="text-start">{{ $c->customer->name ?? '-' }}</td>
            <td>{{ $c->contractStatus->name ?? ($c->status ?? '-') }}</td>
            <td>{{ number_format((float)($c->total_value ?? 0), 2) }}</td>
            <td>{{ optional($c->start_date)->format('Y-m-d') ?? ($c->start_date ? \Carbon\Carbon::parse($c->start_date)->format('Y-m-d') : '-') }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="py-5 text-muted">{{ __('reports.No data available.') }}</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection

@section('actions')
  <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary">↩ @lang('app.Back')</a>
@endsection
