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
            </div>
        </div>

        <div class="row g-3 mb-3" dir="rtl">
            <div class="col-12 col-md-4">
                <div class="kpi-card p-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon"><i class="bi bi-calendar-event fs-4 text-primary"></i></div>
                        <div class="flex-grow-1">
                            <div class="subnote">@lang('expenses::expenses.filters.upcoming')</div>
                            <div class="kpi-value fw-bold">{{ number_format($stats['upcoming'], 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="kpi-card p-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon"><i class="bi bi-exclamation-octagon fs-4 text-danger"></i></div>
                        <div class="flex-grow-1">
                            <div class="subnote">@lang('expenses::expenses.filters.overdue')</div>
                            <div class="kpi-value fw-bold text-danger">{{ number_format($stats['overdue'], 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="kpi-card p-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon"><i class="bi bi-cash-coin fs-4 text-success"></i></div>
                        <div class="flex-grow-1">
                            <div class="subnote">@lang('expenses::expenses.filters.paid')</div>
                            <div class="kpi-value fw-bold text-success">{{ number_format($stats['paid'], 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $filters = (array) ($filters ?? []);
        @endphp

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-light border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-semibold">@lang('expenses::expenses.filters.title')</span>
                <span class="small text-muted">@lang('expenses::expenses.filters.results', ['count' => $expenses->total()])</span>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('expenses.expenses.index') }}" class="row gy-3 gx-3 align-items-end" id="filtersForm">
                    <div class="col-12 col-md-4 col-xl-2">
                        <label class="form-label small text-muted" for="filterStatus">@lang('expenses::expenses.filters.status')</label>
                        <select id="filterStatus" name="status" class="form-select form-select-sm">
                            <option value="" @selected(($filters['status'] ?? '') === '')>@lang('expenses::expenses.filters.all')</option>
                            <option value="upcoming" @selected(($filters['status'] ?? 'upcoming') === 'upcoming')>@lang('expenses::expenses.filters.upcoming')</option>
                            <option value="overdue" @selected(($filters['status'] ?? '') === 'overdue')>@lang('expenses::expenses.filters.overdue')</option>
                            <option value="paid" @selected(($filters['status'] ?? '') === 'paid')>@lang('expenses::expenses.filters.paid')</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4 col-xl-2">
                        <label class="form-label small text-muted" for="filterType">@lang('expenses::expenses.filters.type')</label>
                        <select id="filterType" name="expense_type_id" class="form-select form-select-sm">
                            <option value="">@lang('expenses::expenses.filters.all')</option>
                            @foreach($types as $id => $name)
                                <option value="{{ $id }}" @selected((string) ($filters['expense_type_id'] ?? '') === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                  
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-sm-end">
                            <x-button.action type="submit" variant="primary" size="sm" class="w-100">
                                <i class="bi bi-funnel"></i>
                                <span class="ms-1">@lang('expenses::expenses.filters.apply')</span>
                            </x-button.action>
                            <x-button.action href="{{ route('expenses.expenses.index') }}" variant="secondary" :outline="true" size="sm" class="w-100">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span class="ms-1">@lang('expenses::expenses.filters.reset')</span>
                            </x-button.action>
                        </div>
                    </div>
                </form>
            </div>
        </div>

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
                        <td>{{ optional($expense->type)->name ?? __('expenses::expenses.fields.not_available') }}</td>
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
