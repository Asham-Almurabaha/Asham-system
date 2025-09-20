@extends('layouts.master')

@section('title', __('accounts::accounts.safes.index_title'))

@section('content')
    <div class="container-xxl py-4">
        <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">@lang('sidebar.Settings')</a></li>
                        <li class="breadcrumb-item active" aria-current="page">@lang('accounts::accounts.safes.index_title')</li>
                    </ol>
                </nav>
                <h1 class="h4 mb-0">@lang('accounts::accounts.safes.index_title')</h1>
            </div>

            <div class="ms-auto d-flex flex-wrap gap-2">
                <a href="{{ route('accounts.safes.create') }}" class="btn btn-success">
                    <i class="bi bi-plus-lg me-1"></i>@lang('accounts::accounts.safes.actions.create')
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <x-table head-class="table-light">
                <x-slot name="head">
                    <tr>
                        <th scope="col" class="text-center" style="width:70px">#</th>
                        <th scope="col">@lang('accounts::accounts.safes.fields.name')</th>
                        <th scope="col">@lang('accounts::accounts.safes.fields.location')</th>
                        <th scope="col" class="text-end">@lang('accounts::accounts.safes.fields.opening_balance')</th>
                        <th scope="col">@lang('accounts::accounts.safes.fields.currency_code')</th>
                        <th scope="col">@lang('accounts::accounts.safes.fields.is_active')</th>
                        <th scope="col" class="text-end" style="width:200px">@lang('accounts::accounts.shared.actions')</th>
                    </tr>
                </x-slot>
                @forelse($safes as $safe)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="fw-semibold">{{ $safe->name }}</td>
                        <td>{{ $safe->location ?? '—' }}</td>
                        <td class="text-end">{{ number_format($safe->opening_balance, 2) }}</td>
                        <td>{{ $safe->currency_code }}</td>
                        <td>
                            @if($safe->is_active)
                                <span class="badge bg-success-subtle text-success">@lang('accounts::accounts.status.active')</span>
                            @else
                                <span class="badge bg-secondary-subtle text-muted">@lang('accounts::accounts.status.inactive')</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2">
                                <a href="{{ route('accounts.safes.edit', $safe) }}" class="btn btn-sm btn-outline-primary">
                                    @lang('accounts::accounts.shared.edit')
                                </a>
                                @include('lookups::components.delete-button', [
                                    'action' => route('accounts.safes.destroy', $safe),
                                    'confirm' => __('accounts::accounts.safes.confirm_delete'),
                                    'label' => __('accounts::accounts.shared.delete'),
                                ])
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            @lang('accounts::accounts.safes.empty')
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>
    </div>
@endsection
