@extends('layouts.master')

@section('title', __('companies::companies.Companies'))

@section('content')
<div class="container-xxl py-4">
  <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
    <div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2">
          <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
          <li class="breadcrumb-item active" aria-current="page">{{ __('companies::companies.Companies') }}</li>
        </ol>
      </nav>
      <h1 class="h4 mb-0">{{ __('companies::companies.Companies') }}</h1>
    </div>
    <div class="ms-auto d-flex flex-wrap gap-2">
      <x-button.action href="{{ route('companies.create') }}" variant="success">
        <i class="bi bi-plus-lg me-1"></i>{{ __('companies::companies.Add Company') }}
      </x-button.action>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <x-table head-class="table-light">
      <x-slot name="head">
        <tr>
          <th style="width:60px">#</th>
          <th>{{ __('companies::companies.Company Name') }}</th>
          <th>{{ __('companies::companies.Status') }}</th>
          <th class="text-end">{{ __('companies::companies.Total Allocations') }}</th>
          <th class="text-end">{{ __('companies::companies.Disbursed Share') }}</th>
          <th class="text-end">{{ __('companies::companies.Repaid Share') }}</th>
          <th class="text-end">{{ __('companies::companies.Outstanding Share') }}</th>
          <th>{{ __('companies::companies.Active Transactions') }}</th>
          <th style="width:150px">{{ __('companies::companies.Actions') }}</th>
        </tr>
      </x-slot>

      @forelse ($companies as $company)
        @php($summary = $summaries->get($company->id, []))
        <tr>
          <td class="text-muted">{{ $loop->iteration + ($companies->currentPage() - 1) * $companies->perPage() }}</td>
          <td>
            <a href="{{ route('companies.show', $company) }}" class="text-decoration-none fw-semibold">
              {{ $company->name }}
            </a>
          </td>
          <td>
            @if($company->is_active)
              <span class="badge bg-success-subtle text-success border">{{ __('companies::companies.Active') }}</span>
            @else
              <span class="badge bg-danger-subtle text-danger border">{{ __('companies::companies.Inactive') }}</span>
            @endif
          </td>
          <td class="text-end">{{ number_format((float) ($summary['allocations_total'] ?? 0), 2) }}</td>
          <td class="text-end">{{ number_format((float) ($summary['disbursed_share'] ?? 0), 2) }}</td>
          <td class="text-end">{{ number_format((float) ($summary['repaid_share'] ?? 0), 2) }}</td>
          <td class="text-end fw-semibold">{{ number_format((float) ($summary['outstanding_share'] ?? 0), 2) }}</td>
          <td>{{ number_format((int) ($summary['active_transactions'] ?? 0)) }}</td>
          <td>
            <div class="btn-group btn-group-sm" role="group">
              <x-button.action href="{{ route('companies.show', $company) }}" variant="secondary" :outline="true" size="sm">
                {{ __('companies::companies.View') }}
              </x-button.action>
              <x-button.action href="{{ route('companies.edit', $company) }}" variant="primary" :outline="true" size="sm">
                {{ __('companies::companies.Edit') }}
              </x-button.action>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="9" class="py-5 text-center">
            <div class="text-muted mb-3">{{ __('companies::companies.No Companies Found') }}</div>
            <x-button.action href="{{ route('companies.create') }}" variant="success" size="sm">
              {{ __('companies::companies.Add First Company') }}
            </x-button.action>
          </td>
        </tr>
      @endforelse
    </x-table>

      @if($companies->hasPages())
        <div class="card-footer bg-white border-0">
          {{ $companies->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
