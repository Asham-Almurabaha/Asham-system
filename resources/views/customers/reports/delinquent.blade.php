{{-- resources/views/reports/delinquent_customers.blade.php --}}
@extends('layouts.print-portrait')

@section('title', __('reports.Delinquent Customers Report'))
@section('report_title', __('reports.Delinquent Customers Report'))
{{-- @section('orientation','landscape')  {{-- فعّلها لو تبغى العرض أفقي --}}

@php
  /** @var \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator $rows */
  $rows = $rows ?? collect();
@endphp

@push('styles')
  <style>
    /* نفس سلوك الطباعة المتعدد الصفحات */
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
  </style>
@endpush

@section('content')
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
  {{-- زر الطباعة موجود أصلاً في الـlayout --}}
@endsection
