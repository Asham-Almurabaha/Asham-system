@extends('layouts.master')

@section('title', __('companies::companies.Disbursement Statuses'))

@section('content')
<div class="container-xxl py-4">
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">@lang('sidebar.Settings')</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('companies::companies.Disbursement Statuses') }}</li>
                </ol>
            </nav>
            <h1 class="h4 mb-0">{{ __('companies::companies.Manage Disbursement Statuses') }}</h1>
        </div>
        <div class="ms-auto d-flex flex-wrap gap-2">
            <x-button.action href="{{ route('company-disbursement-statuses.create') }}" variant="success">
                <i class="bi bi-plus-lg me-1"></i>{{ __('companies::companies.Add Disbursement Status') }}
            </x-button.action>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <x-table head-class="table-light">
            <x-slot name="head">
                <tr>
                    <th scope="col" style="width:80px" class="text-center">#</th>
                    <th scope="col">{{ __('companies::companies.Name') }}</th>
                    <th scope="col">{{ __('companies::companies.Description') }}</th>
                    <th scope="col" style="width:160px" class="text-center">{{ __('companies::companies.Default Status') }}</th>
                    <th scope="col" style="width:180px" class="text-end">{{ __('companies::companies.Actions') }}</th>
                </tr>
            </x-slot>

            @forelse ($statuses as $status)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $status->name }}</td>
                    <td>{{ $status->description ?: '—' }}</td>
                    <td class="text-center">
                        @if ($status->is_default)
                            <span class="badge bg-success-subtle text-success fw-semibold">{{ __('companies::companies.Default Badge') }}</span>
                        @else
                            <span class="text-muted">{{ __('companies::companies.Not Default') }}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
                            <x-button.action href="{{ route('company-disbursement-statuses.edit', $status) }}" variant="primary" :outline="true" size="sm">
                                {{ __('companies::companies.Edit') }}
                            </x-button.action>
                            <form action="{{ route('company-disbursement-statuses.destroy', $status) }}" method="POST" onsubmit="return confirm('{{ __('companies::companies.Delete Status Confirmation') }}')">
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
                    <td colspan="5" class="text-center text-muted py-4">
                        <div class="mb-2">{{ __('companies::companies.No Disbursement Statuses Found') }}</div>
                        <x-button.action href="{{ route('company-disbursement-statuses.create') }}" variant="primary" :outline="true">
                            {{ __('companies::companies.Create First Disbursement Status') }}
                        </x-button.action>
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>
</div>
@endsection
