@extends('layouts.master')

@section('title', __('expenses::recurrence_periods.index_title'))

@section('content')
    <div class="container-xxl py-4">
        <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('expenses.expenses.index') }}">@lang('expenses::expenses.index_title')</a></li>
                        <li class="breadcrumb-item active" aria-current="page">@lang('expenses::recurrence_periods.index_title')</li>
                    </ol>
                </nav>
                <h1 class="h4 mb-0">@lang('expenses::recurrence_periods.index_title')</h1>
            </div>

            <div class="ms-auto d-flex flex-wrap gap-2">
                <x-button.action href="{{ route('expenses.recurrence-periods.create') }}" variant="success">
                    <i class="bi bi-plus-lg me-1"></i>@lang('expenses::recurrence_periods.actions.create')
                </x-button.action>
            </div>
        </div>

        @if($errors->has('general'))
            <div class="alert alert-danger" role="alert">
                {{ $errors->first('general') }}
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <x-table head-class="table-light">
                <x-slot name="head">
                    <tr>
                        <th scope="col" class="text-center" style="width:70px">#</th>
                        <th scope="col">@lang('expenses::recurrence_periods.fields.name')</th>
                        <th scope="col">@lang('expenses::recurrence_periods.fields.description')</th>
                        <th scope="col" class="text-center">@lang('expenses::recurrence_periods.fields.related_types')</th>
                        <th scope="col" class="text-center">@lang('expenses::recurrence_periods.fields.is_protected')</th>
                        <th scope="col" class="text-end" style="width:220px">@lang('expenses::recurrence_periods.actions.manage')</th>
                    </tr>
                </x-slot>
                @forelse($recurrencePeriods as $recurrencePeriod)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="fw-semibold">{{ $recurrencePeriod->name }}</td>
                        <td>{{ $recurrencePeriod->description ?: __('expenses::recurrence_periods.fields.not_available') }}</td>
                        <td class="text-center">{{ $recurrencePeriod->expense_types_count }}</td>
                        <td class="text-center">
                            @if($recurrencePeriod->is_protected)
                                <span class="badge bg-secondary-subtle text-muted">@lang('expenses::recurrence_periods.status.protected')</span>
                            @else
                                <span class="badge bg-success-subtle text-success">@lang('expenses::recurrence_periods.status.editable')</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2">
                                <x-button.action
                                    href="{{ route('expenses.recurrence-periods.edit', $recurrencePeriod) }}"
                                    variant="primary"
                                    :outline="true"
                                    size="sm"
                                    :disabled="$recurrencePeriod->is_protected"
                                >
                                    @lang('expenses::recurrence_periods.actions.edit')
                                </x-button.action>

                                @if(!$recurrencePeriod->is_protected && $recurrencePeriod->expense_types_count === 0)
                                    @include('lookups::components.delete-button', [
                                        'action' => route('expenses.recurrence-periods.destroy', $recurrencePeriod),
                                        'confirm' => __('expenses::recurrence_periods.actions.confirm_delete'),
                                        'label' => __('expenses::recurrence_periods.actions.delete'),
                                    ])
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            @lang('expenses::recurrence_periods.empty')
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>
    </div>
@endsection
