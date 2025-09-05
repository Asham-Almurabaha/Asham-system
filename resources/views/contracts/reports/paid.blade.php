@extends('layouts.print-landscape')

@section('title', 'تقرير سدادات العقد #' . ($contract->contract_number ?? $contract->id))
@section('report_title', 'تقرير سدادات العقد #' . ($contract->contract_number ?? $contract->id))

@php
  use Carbon\Carbon;

  $get = function($item, $key, $default = null) {
      return data_get($item, $key, $default);
  };

  $fmtDate = function($date) {
      if (!$date) return '-';
      try {
          if ($date instanceof \DateTimeInterface) return $date->format('Y-m-d');
          return Carbon::parse($date)->format('Y-m-d');
      } catch (\Throwable $e) {
          return (string) $date;
      }
  };

  $fmtNum = fn($n) => number_format((float)$n, 2);

  // Filter only rows with paid amount > 0
  $rows = collect($paidInstallments ?? [])->filter(function ($i) use ($get) {
      return (float)$get($i, 'paid_amount', 0) > 0;
  })->values();
@endphp

@push('styles')
  <style>
    .stat .label { font-size:.9rem; color:#6c757d; }
    .stat .value { font-weight:bold; font-size:1.1rem; }
  </style>
@endpush

@section('content')
  {{-- Parties --}}
  <div class="mb-3">
    <div class="line"><strong>{{ __('Customer') }}:</strong> {{ $contract->customer->name ?? '-' }}</div>
    <div class="line"><strong>{{ __('Phone') }}:</strong> {{ $contract->customer->phone ?? '-' }}</div>
    <div class="line"><strong>{{ __('National ID') }}:</strong> {{ $contract->customer->national_id ?? '-' }}</div>
  </div>

  {{-- Summary stats --}}
  <div class="row g-2 mb-3">
    <div class="col-6 col-md-4">
      <div class="card"><div class="card-body p-2 stat">
        <div class="label">إجمالي قيمة العقد</div>
        <div class="value">{{ $fmtNum($contractTotal) }} {{ $currency }}</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4">
      <div class="card"><div class="card-body p-2 stat">
        <div class="label">إجمالي المسدد</div>
        <div class="value">{{ $fmtNum($totalPaid) }} {{ $currency }}</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4">
      <div class="card"><div class="card-body p-2 stat">
        <div class="label">المتبقي</div>
        <div class="value">{{ $fmtNum($remaining) }} {{ $currency }}</div>
      </div></div>
    </div>

    @isset($countPaidFully)
    <div class="col-6 col-md-4">
      <div class="card"><div class="card-body p-2 stat">
        <div class="label">عدد الأقساط المسددة بالكامل</div>
        <div class="value">{{ $countPaidFully }}</div>
      </div></div>
    </div>
    @endisset

    @isset($countRemaining)
    <div class="col-6 col-md-4">
      <div class="card"><div class="card-body p-2 stat">
        <div class="label">عدد الأقساط المتبقية</div>
        <div class="value">{{ $countRemaining }}</div>
      </div></div>
    </div>
    @endisset
  </div>

  {{-- Table --}}
  <div class="table-responsive mb-3">
    <table class="table table-striped table-bordered align-middle text-center">
      <thead class="table-light">
        <tr>
          <th style="width:56px">#</th>
          <th>تاريخ الاستحقاق</th>
          <th>قيمة القسط ({{ $currency }})</th>
          <th>تاريخ السداد</th>
          <th>المسدد ({{ $currency }})</th>
          <th>المتبقي على القسط ({{ $currency }})</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($rows as $idx => $ins)
          @php
            $amount = (float) $get($ins, 'amount', 0);
            $paid   = (float) $get($ins, 'paid_amount', 0);
            $still  = max(0.0, $amount - $paid);
          @endphp
          <tr>
            <td>{{ $idx + 1 }}</td>
            <td>{{ $fmtDate($get($ins, 'due_date')) }}</td>
            <td class="text-end">{{ $fmtNum($amount) }}</td>
            <td>{{ $fmtDate($get($ins, 'paid_at')) }}</td>
            <td class="text-end">{{ $fmtNum($paid) }}</td>
            <td class="text-end">{{ $fmtNum($still) }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="py-5 text-muted">@lang('reports.No data available.')</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection

@section('actions')
  <a href="{{ route('contracts.show' , $contract) }}" class="btn btn-outline-secondary">↩ @lang('app.Back')</a>
@endsection

