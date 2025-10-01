@extends('layouts.master')

@section('title', __('companies::companies.Companies'))

@section('content')
<div class="pagetitle mb-3">
  <h1 class="h3 mb-1">{{ __('companies::companies.Companies') }}</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item active">{{ __('companies::companies.Companies') }}</li>
    </ol>
  </nav>
</div>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted small">{{ __('companies::companies.Total Companies') }}</div>
        <div class="h4 mb-0">{{ number_format($totalCompanies) }}</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted small">{{ __('companies::companies.Active Companies') }}</div>
        <div class="h4 mb-0 text-success">{{ number_format($activeCompanies) }}</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted small">{{ __('companies::companies.Inactive Companies') }}</div>
        <div class="h4 mb-0 text-danger">{{ number_format($inactiveCompanies) }}</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted small">{{ __('companies::companies.Current Page Outstanding') }}</div>
        <div class="h4 mb-0">{{ number_format($pageOutstanding, 2) }}</div>
        <div class="text-muted small">{{ __('companies::companies.Disbursed vs Repaid', ['disbursed' => number_format($pageDisbursed, 2), 'repaid' => number_format($pageRepaid, 2)]) }}</div>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body d-flex flex-wrap gap-2 align-items-center">
    <div class="btn-group" role="group">
      <x-button.action href="{{ route('companies.create') }}" variant="success">
        <i class="bi bi-plus-lg"></i> {{ __('companies::companies.Add Company') }}
      </x-button.action>
      <x-button.action href="{{ route('company-transactions.create') }}" variant="primary">
        <i class="bi bi-currency-exchange"></i> {{ __('companies::companies.New Transaction') }}
      </x-button.action>
      <x-button.action href="{{ route('company-transactions.index') }}" variant="dark" :outline="true">
        <i class="bi bi-list-ul"></i> {{ __('companies::companies.View Transactions') }}
      </x-button.action>
    </div>

    <span class="ms-auto small text-muted">
      {{ __('companies::companies.Results Count', ['count' => number_format($companies->total())]) }}
    </span>

    <x-button.action type="button" variant="secondary" :outline="true" size="sm"
      data-bs-toggle="collapse" data-bs-target="#companyFilters"
      aria-expanded="{{ request()->has(['q','status']) ? 'true' : 'false' }}" aria-controls="companyFilters">
      {{ __('companies::companies.Filter') }}
    </x-button.action>
  </div>

  <div class="collapse border-top {{ request()->has(['q','status']) ? 'show' : '' }}" id="companyFilters">
    <div class="card-body">
      <form action="{{ route('companies.index') }}" method="GET" class="row gy-2 gx-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label mb-1" for="q">{{ __('companies::companies.Search Label') }}</label>
          <input type="search" name="q" id="q" value="{{ request('q') }}" class="form-control form-control-sm"
            placeholder="{{ __('companies::companies.Search Placeholder') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label mb-1" for="status">{{ __('companies::companies.Status') }}</label>
          <select name="status" id="status" class="form-select form-select-sm">
            <option value="">{{ __('companies::companies.All Statuses') }}</option>
            <option value="active" @selected(request('status') === 'active')>{{ __('companies::companies.Active') }}</option>
            <option value="inactive" @selected(request('status') === 'inactive')>{{ __('companies::companies.Inactive') }}</option>
          </select>
        </div>
        <div class="col-md-2">
          <x-button.action type="submit" variant="primary" size="sm" class="w-100">
            {{ __('companies::companies.Apply Filter') }}
          </x-button.action>
        </div>
        <div class="col-md-2">
          <x-button.action href="{{ route('companies.index') }}" variant="secondary" :outline="true" size="sm" class="w-100">
            {{ __('companies::companies.Reset') }}
          </x-button.action>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body p-0">
    <x-table head-class="table-light position-sticky top-0">
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
  </div>

  @if($companies->hasPages())
    <div class="card-footer bg-white">
      {{ $companies->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
  @endif
</div>
@endsection
