@extends('layouts.print-landscape')

@section('title', __('reports.Customers and Contracts Report'))
@section('report_title', __('reports.Customers and Contracts Report'))
{{-- @section('orientation','landscape')  {{-- فعّلها لو تبغى العرض أفقي --}}

@push('styles')
  {{-- أي ستايلات إضافية خاصة بهذا التقرير --}}
@endpush

@php
  /** @var \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator $rows */
  $rows = $rows ?? collect();
@endphp

@section('content')
  <div class="table-responsive">
    <table class="table table-striped table-bordered text-center align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:56px">#</th>
          <th class="text-start">{{ __('Customer') }}</th>
          <th>{{ __('Total Contracts') }}</th>
          <th>{{ __('Active Contracts') }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $i => $c)
          <tr>
            <td>{{ is_int($i) ? $i + 1 : $loop->iteration }}</td>
            <td class="text-start">{{ $c->name }}</td>
            <td>{{ $c->contracts_count }}</td>
            <td>{{ $c->active_contracts }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="py-5 text-muted">@lang('reports.No data available.')</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection

@section('actions')
  <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">↩ @lang('app.Back')</a>
@endsection