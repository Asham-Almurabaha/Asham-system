@extends('layouts.master')

@section('title', __('expenses::types.index_title'))

@section('content')
    <div class="container-xxl py-4">
        <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('expenses.expenses.index') }}">@lang('expenses::expenses.index_title')</a></li>
                        <li class="breadcrumb-item active" aria-current="page">@lang('expenses::types.index_title')</li>
                    </ol>
                </nav>
                <h1 class="h4 mb-0">@lang('expenses::types.index_title')</h1>
            </div>

            <div class="ms-auto d-flex flex-wrap gap-2">
                <x-button.action href="{{ route('expenses.expense-types.create') }}" variant="success">
                    <i class="bi bi-plus-lg me-1"></i>@lang('expenses::types.actions.create')
                </x-button.action>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <x-table head-class="table-light">
                <x-slot name="head">
                    <tr>
                        <th scope="col" style="width:70px" class="text-center">#</th>
                        <th scope="col">@lang('expenses::types.fields.name')</th>
                        <th scope="col">@lang('expenses::types.fields.description')</th>
                        <th scope="col" class="text-end">@lang('expenses::types.fields.default_amount')</th>
                        <th scope="col">@lang('expenses::types.fields.currency_code')</th>
                        <th scope="col">@lang('expenses::types.fields.is_recurring')</th>
                        <th scope="col">@lang('expenses::types.fields.recurrence_interval')</th>
                        <th scope="col" class="text-end" style="width:220px">@lang('expenses::types.actions.manage')</th>
                    </tr>
                </x-slot>
                @forelse($types as $type)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="fw-semibold">{{ $type->name }}</td>
                        <td>{{ $type->description ?? __('expenses::types.fields.not_available') }}</td>
                        <td class="text-end">{{ number_format($type->default_amount, 2) }}</td>
                        <td>{{ $type->currency_code }}</td>
                        <td>
                            @if($type->is_recurring)
                                <span class="badge bg-success-subtle text-success">@lang('expenses::types.status.recurring')</span>
                            @else
                                <span class="badge bg-secondary-subtle text-muted">@lang('expenses::types.status.one_time')</span>
                            @endif
                        </td>
                        <td>{{ $type->recurrence_interval ?? __('expenses::types.fields.not_available') }}</td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2">
                                <x-button.action href="{{ route('expenses.expense-types.edit', $type) }}" variant="primary" :outline="true" size="sm">
                                    @lang('expenses::types.actions.edit')
                                </x-button.action>
                                @include('lookups::components.delete-button', [
                                    'action' => route('expenses.expense-types.destroy', $type),
                                    'confirm' => __('expenses::types.actions.confirm_delete'),
                                    'label' => __('expenses::types.actions.delete'),
                                ])
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            @lang('expenses::types.empty')
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>
    </div>
@endsection
