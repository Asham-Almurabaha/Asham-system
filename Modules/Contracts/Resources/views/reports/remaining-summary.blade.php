@extends('layouts.print-landscape')

@section('title', __('contracts::contracts.Remaining Amount Summary') . ' - ' . __('app.Print'))
@section('report_title', __('contracts::contracts.Remaining Amount Summary'))

@php
  $summary = collect($summary ?? []);
  $counts = collect($counts ?? []);
  $statuses = collect($statuses ?? []);
  $currencySymbol = $currencySymbol ?? 'ر.س';
  $remainingTotal = isset($remainingTotal) ? (float) $remainingTotal : (float) $statuses->sum('remaining');
  $labels = collect($labels ?? []);
  $endedLabel = $labels->get('ended', '—');
  $pendingLabel = $labels->get('pending', '—');

  $classificationLabels = [
      'active'   => __('contracts::contracts.Active Contracts'),
      'raised'   => __('contracts::contracts.Raised Status Contracts'),
      'required' => __('contracts::contracts.Required Status Contracts'),
      'pending'  => __('contracts::contracts.Pending Contracts'),
      'ended'    => __('contracts::contracts.Ended Contracts'),
  ];

  $classificationBadges = [
      'active'   => 'bg-secondary',
      'raised'   => 'bg-danger',
      'required' => 'bg-warning text-dark',
      'pending'  => 'bg-warning text-dark',
      'ended'    => 'bg-secondary',
  ];

  $remainingTextClasses = [
      'active'   => 'text-primary',
      'raised'   => 'text-danger',
      'required' => 'text-warning',
      'pending'  => 'text-warning',
      'ended'    => 'text-muted',
  ];
@endphp

@push('styles')
  <style>
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
  </style>
@endpush

@section('content')
  <div class="mb-3 small text-muted">{{ __('contracts::contracts.Remaining Amount Printable Report Hint') }}</div>

  <div class="row g-3 kpi mb-4">
    <div class="col-12 col-md-4 col-xl-3">
      <div class="card"><div class="card-body p-3 text-center">
        <div class="small-muted">{{ __('contracts::contracts.Total Contracts — Entire System') }}</div>
        <div class="fs-5 fw-bold">{{ number_format($counts->get('total', 0)) }}</div>
      </div></div>
    </div>
    <div class="col-12 col-md-4 col-xl-3">
      <div class="card"><div class="card-body p-3 text-center">
        <div class="small-muted">{{ __('contracts::contracts.Total Remaining Amount') }}</div>
        <div class="fs-5 fw-bold text-primary">
          {{ number_format($remainingTotal, 2) }} <span class="small-muted">{{ $currencySymbol }}</span>
        </div>
      </div></div>
    </div>
    <div class="col-12 col-md-4 col-xl-3">
      <div class="card"><div class="card-body p-3 text-center">
        <div class="small-muted">{{ __('contracts::contracts.Active Contracts Remaining') }}</div>
        <div class="fs-5 fw-bold text-primary">
          {{ number_format($summary->get('active', 0), 2) }} <span class="small-muted">{{ $currencySymbol }}</span>
        </div>
        <div class="small text-muted mt-2">
          {{ __('contracts::contracts.Active Contracts') }}: {{ number_format($counts->get('active', 0)) }}
        </div>
        <div class="small text-muted">
          {{ __('contracts::contracts.Active Contracts Remaining Hint', ['ended' => $endedLabel, 'pending' => $pendingLabel]) }}
        </div>
      </div></div>
    </div>
    <div class="col-12 col-md-4 col-xl-3">
      <div class="card"><div class="card-body p-3 text-center">
        <div class="small-muted">{{ __('contracts::contracts.Raised Contracts Remaining') }}</div>
        <div class="fs-5 fw-bold text-danger">
          {{ number_format($summary->get('raised', 0), 2) }} <span class="small-muted">{{ $currencySymbol }}</span>
        </div>
        <div class="small text-muted mt-2">
          {{ __('contracts::contracts.Raised Status Contracts') }}: {{ number_format($counts->get('raised', 0)) }}
        </div>
        <div class="small text-muted">
          {{ __('contracts::contracts.Raised Contracts Remaining Hint') }}
        </div>
      </div></div>
    </div>
    <div class="col-12 col-md-4 col-xl-3">
      <div class="card"><div class="card-body p-3 text-center">
        <div class="small-muted">{{ __('contracts::contracts.Required Contracts Remaining') }}</div>
        <div class="fs-5 fw-bold text-warning">
          {{ number_format($summary->get('required', 0), 2) }} <span class="small-muted">{{ $currencySymbol }}</span>
        </div>
        <div class="small text-muted mt-2">
          {{ __('contracts::contracts.Required Status Contracts') }}: {{ number_format($counts->get('required', 0)) }}
        </div>
        <div class="small text-muted">
          {{ __('contracts::contracts.Required Contracts Remaining Hint') }}
        </div>
      </div></div>
    </div>
  </div>

  <x-table head-class="table-light" striped bordered class="text-center" :hover="false">
      <x-slot name="head">
          <tr>
            <th style="width:56px">#</th>
            <th class="text-start">{{ __('Status') }}</th>
            <th>{{ __('contracts::contracts.Contracts') }}</th>
            <th>{{ __('contracts::contracts.Remaining Contract Amount') }}</th>
            <th>{{ __('contracts::contracts.Percentage (%)') }}</th>
          </tr>
      </x-slot>
      @forelse($statuses as $status)
        @php
          $classification = $status['classification'] ?? null;
          $badgeClass = $classificationBadges[$classification] ?? 'bg-secondary';
          $classificationLabel = $classificationLabels[$classification] ?? null;
          $remainingClass = $remainingTextClasses[$classification] ?? 'text-primary';
        @endphp
        <tr>
          <td>{{ $loop->iteration }}</td>
          <td class="text-start">
            {{ $status['name'] !== '' ? $status['name'] : '—' }}
            @if($classificationLabel)
              <span class="badge {{ $badgeClass }} ms-2">{{ $classificationLabel }}</span>
            @endif
          </td>
          <td>{{ number_format($status['count'] ?? 0) }}</td>
          <td class="fw-semibold {{ $remainingClass }}">
            {{ number_format($status['remaining'] ?? 0, 2) }}
            <span class="small-muted">{{ $currencySymbol }}</span>
          </td>
          <td>
            @if(isset($status['pct']))
              {{ number_format((float) $status['pct'], 2) }}%
            @else
              —
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="5" class="py-5 text-muted">{{ __('contracts::contracts.No data for statuses.') }}</td>
        </tr>
      @endforelse
  </x-table>
@endsection
