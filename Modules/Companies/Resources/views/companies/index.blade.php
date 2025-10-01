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
            <th>{{ __('companies::companies.Company Name') }}</th>
            <th>{{ __('companies::companies.Status') }}</th>
            <th class="text-end" style="width:200px">{{ __('companies::companies.Actions') }}</th>
          </tr>
        </x-slot>

        @forelse ($companies as $company)
          <tr>
            <td class="fw-semibold">{{ $company->name }}</td>
            <td>
              @if($company->is_active)
                <span class="badge bg-success-subtle text-success border">{{ __('companies::companies.Active') }}</span>
              @else
                <span class="badge bg-danger-subtle text-danger border">{{ __('companies::companies.Inactive') }}</span>
              @endif
            </td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <x-button.action href="{{ route('companies.edit', $company) }}" variant="primary" :outline="true" size="sm">
                  {{ __('companies::companies.Edit') }}
                </x-button.action>
                <form action="{{ route('companies.destroy', $company) }}" method="POST" onsubmit="return confirm('{{ __('companies::companies.Delete Confirmation') }}')">
                  @csrf
                  @method('DELETE')
                  <x-button.action type="submit" variant="danger" :outline="true" size="sm">
                    {{ __('companies::companies.Delete') }}
                  </x-button.action>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="3" class="py-5 text-center">
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
