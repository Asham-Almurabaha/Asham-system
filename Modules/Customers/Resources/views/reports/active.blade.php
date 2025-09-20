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

  <x-table head-class="table-light" striped bordered class="text-center" :hover="false">
      <x-slot name="head">
          <tr>
            <th style="width:56px">#</th>
            <th class="text-start">{{ __('Customer') }}</th>
            <th>{{ __('Phone') }}</th>
            <th>{{ __('Active Contracts') }}</th>
            <th>{{ __('reports.Total Remaining in Active Contracts') }}</th>
          </tr>
      </x-slot>
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
  </x-table>
@endsection

@section('actions')
  <x-button href="{{ route('customers.index') }}" variant="secondary" :outline="true">↩ @lang('app.Back')</x-button>
@endsection
