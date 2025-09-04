{{-- resources/views/reports/unpaid_customers.blade.php --}}
@extends('layouts.print-landscape')

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
          <th>{{ __('reports.Unpaid Installments This Month Count') }}</th>
          <th>{{ __('reports.Unpaid Installments This Month Total') }}</th>
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
            <td>{{ (int)($c->unpaid_month_count ?? 0) }}</td>
            <td>{{ number_format((float)($c->unpaid_month_total ?? 0), 2) }}</td>
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
