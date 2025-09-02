{{-- resources/views/reports/unpaid_customers.blade.php --}}
@extends('layouts.print-portrait')

@section('title', __('reports.Unpaid Customers This Month Report'))
@section('report_title', __('reports.Unpaid Customers This Month Report'))
{{-- @section('orientation','landscape')  {{-- افعلها لو تبغى الطباعة بالعرض --}}

@php
  /** @var \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator $rows */
  $rows  = $rows ?? collect();
  $count = $rows->count();
@endphp

@push('styles')
  <style>
    .kpi .card { box-shadow: none; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
  </style>
@endpush

@section('content')
  {{-- KPIs --}}
  <div class="row g-3 kpi mb-4">
    <div class="col-12 col-md-4">
      <div class="card">
        <div class="card-body p-3 text-center">
          <div class="small-muted">@lang('reports.Number of Customers')</div>
          <div class="fs-4 fw-bold">{{ number_format($count) }}</div>
        </div>
      </div>
    </div>
    {{-- أضف بطاقات KPIs أخرى هنا لو حابب --}}
  </div>

  {{-- الجدول --}}
  <div class="table-responsive">
    <table class="table table-striped table-bordered text-center align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:56px">#</th>
          <th class="text-start">{{ __('Customer') }}</th>
          <th>{{ __('Phone') }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $i => $c)
          <tr>
            <td>{{ is_int($i) ? $i + 1 : $loop->iteration }}</td>
            <td class="text-start">{{ $c->name }}</td>
            <td>{{ $c->phone }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="3" class="py-5 text-muted">@lang('reports.No data available.')</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection

@section('actions')
  <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">↩ @lang('app.Back')</a>
  <a href="{{ url()->current() }}" class="btn btn-outline-secondary">↺ @lang('app.Refresh')</a>
@endsection
