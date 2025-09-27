@extends('layouts.master')

@section('title', __('expenses::expenses.index_title'))

@section('content')
    <div class="container-xxl py-4">
        <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                        <li class="breadcrumb-item active" aria-current="page">@lang('expenses::expenses.index_title')</li>
                    </ol>
                </nav>
                <h1 class="h4 mb-0">@lang('expenses::expenses.index_title')</h1>
            </div>

            <div class="ms-auto d-flex flex-wrap gap-2">
                <x-button.action href="{{ route('expenses.expenses.create') }}" variant="success">
                    <i class="bi bi-plus-lg me-1"></i>@lang('expenses::expenses.actions.create')
                </x-button.action>
                <x-button.action href="{{ route('expenses.expense-types.index') }}" variant="secondary" :outline="true">
                    <i class="bi bi-gear me-1"></i>@lang('expenses::types.index_title')
                </x-button.action>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">@lang('expenses::expenses.filters.upcoming')</div>
                        <div class="fs-4 fw-semibold">{{ number_format($stats['upcoming']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small text-danger">@lang('expenses::expenses.filters.overdue')</div>
                        <div class="fs-4 fw-semibold text-danger">{{ number_format($stats['overdue']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">@lang('expenses::expenses.filters.paid')</div>
                        <div class="fs-4 fw-semibold text-success">{{ number_format($stats['paid']) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-pills mb-3">
            <li class="nav-item">
                <a class="nav-link {{ $status === 'upcoming' ? 'active' : '' }}" href="{{ route('expenses.expenses.index', ['status' => 'upcoming']) }}">
                    @lang('expenses::expenses.filters.upcoming')
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $status === 'overdue' ? 'active' : '' }}" href="{{ route('expenses.expenses.index', ['status' => 'overdue']) }}">
                    @lang('expenses::expenses.filters.overdue')
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $status === 'paid' ? 'active' : '' }}" href="{{ route('expenses.expenses.index', ['status' => 'paid']) }}">
                    @lang('expenses::expenses.filters.paid')
                </a>
            </li>
        </ul>

        <div class="card border-0 shadow-sm">
            <x-table head-class="table-light">
                @php $today = \Illuminate\Support\Carbon::today(); @endphp
                <x-slot name="head">
                    <tr>
                        <th scope="col" style="width:60px" class="text-center">#</th>
                        <th scope="col">@lang('expenses::expenses.fields.title')</th>
                        <th scope="col">@lang('expenses::expenses.fields.expense_type_id')</th>
                        <th scope="col" class="text-end">@lang('expenses::expenses.fields.amount')</th>
                        <th scope="col">@lang('expenses::expenses.fields.currency_code')</th>
                        <th scope="col">@lang('expenses::expenses.fields.due_date')</th>
                        <th scope="col">@lang('expenses::expenses.fields.paid_at')</th>
                        <th scope="col" style="width:150px">@lang('expenses::expenses.fields.status')</th>
                        <th scope="col" class="text-end" style="width:200px">@lang('expenses::expenses.actions.manage')</th>
                    </tr>
                </x-slot>
                @forelse($expenses as $expense)
                    <tr>
                        <td class="text-center">{{ $expenses->firstItem() + $loop->index }}</td>
                        <td class="fw-semibold">{{ $expense->title }}</td>
                        <td>{{ $expense->type?->name ?? __('expenses::expenses.fields.not_available') }}</td>
                        <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                        <td>{{ $expense->currency_code }}</td>
                        <td>{{ optional($expense->due_date)->toDateString() }}</td>
                        <td>{{ optional($expense->paid_at)->toDateString() ?? __('expenses::expenses.fields.not_available') }}</td>
                        <td>
                            @if($expense->paid_at)
                                <span class="badge bg-success-subtle text-success">@lang('expenses::expenses.status_labels.paid')</span>
                            @elseif($expense->due_date && $expense->due_date->lt($today))
                                <span class="badge bg-danger-subtle text-danger">@lang('expenses::expenses.status_labels.overdue')</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning">@lang('expenses::expenses.status_labels.upcoming')</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2">
                                <x-button.action href="{{ route('expenses.expenses.edit', $expense) }}" variant="primary" :outline="true" size="sm">
                                    @lang('expenses::expenses.actions.edit')
                                </x-button.action>
                                @include('lookups::components.delete-button', [
                                    'action' => route('expenses.expenses.destroy', $expense),
                                    'confirm' => __('expenses::expenses.actions.confirm_delete'),
                                    'label' => __('expenses::expenses.actions.delete'),
                                ])
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">@lang('expenses::expenses.empty')</td>
                    </tr>
                @endforelse
            </x-table>
        </div>

        <div class="mt-3">
            {{ $expenses->links() }}
        </div>
    </div>
@endsection
