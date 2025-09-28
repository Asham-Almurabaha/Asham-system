@extends('layouts.master')

@section('title', __('expenses::expenses.index_title'))

@section('content')
    <div class="pagetitle mb-3">
        <h1 class="h3 mb-1">@lang('expenses::expenses.index_title')</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                <li class="breadcrumb-item active">@lang('expenses::expenses.index_title')</li>
            </ol>
        </nav>
    </div>

    {{-- زر إنشاء --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">
            <x-button.action href="{{ route('expenses.expenses.create') }}" variant="success">
                <i class="bi bi-plus-lg me-1"></i>@lang('expenses::expenses.actions.create')
            </x-button.action>
        </div>
    </div>

    {{-- KPI --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3">
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
                            <div class="kpi-icon"><i class="bi bi-collection fs-4 text-success"></i></div>
                            <div class="flex-grow-1">
                                <div class="subnote">@lang('expenses::expenses.filters.total')</div>
                                <div class="kpi-value fw-bold text-success">{{ number_format($stats['total'], 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- /.row -->
        </div><!-- /.card-body -->
    </div><!-- /.card -->

    {{-- الجدول --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <x-table head-class="table-light">
                @php $today = \Illuminate\Support\Carbon::today(); @endphp

                <x-slot name="head">
                    <tr>
                        <th scope="col" style="width:60px" class="text-center">#</th>
                        <th scope="col" class="text-start">@lang('expenses::expenses.fields.title')</th>
                        <th scope="col" class="text-start">@lang('expenses::expenses.fields.expense_type_id')</th>
                        <th scope="col" class="text-end">@lang('expenses::expenses.fields.amount')</th>
                        <th scope="col" class="text-end">@lang('expenses::expenses.fields.paid_amount')</th>
                        <th scope="col" class="text-end">@lang('expenses::expenses.fields.outstanding_amount')</th>
                        <th scope="col">@lang('expenses::expenses.fields.due_date')</th>
                        <th scope="col" style="width:150px">@lang('expenses::expenses.fields.status')</th>
                        <th scope="col" class="text-end" style="width:260px">@lang('expenses::expenses.actions.manage')</th>
                    </tr>
                </x-slot>

                @forelse($expenses as $expense)
                    <tr>
                        <td class="text-center">{{ $expenses->firstItem() + $loop->index }}</td>
                        <td class="text-start fw-semibold">{{ $expense->title }}</td>
                        <td class="text-start">{{ optional($expense->type)->name ?? __('expenses::expenses.fields.not_available') }}</td>
                        <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                        <td class="text-end">{{ number_format($expense->paid_amount, 2) }}</td>
                        <td class="text-end">{{ number_format($expense->outstanding_amount, 2) }}</td>
                        <td>{{ optional($expense->due_date)->toDateString() }}</td>
                        <td>
                            @if ($expense->outstanding_amount <= 0)
                                <span class="badge bg-success-subtle text-success">@lang('expenses::expenses.status_labels.settled')</span>
                            @elseif ($expense->due_date && $expense->due_date->lt($today))
                                <span class="badge bg-danger-subtle text-danger">@lang('expenses::expenses.status_labels.overdue')</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning">@lang('expenses::expenses.status_labels.upcoming')</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2">
                                <x-button.action href="{{ route('expenses.payments.create', $expense) }}" variant="dark" :outline="true" size="sm">
                                    <i class="bi bi-wallet2 me-1"></i>@lang('expenses::expenses.actions.payments')
                                </x-button.action>
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

        @if ($expenses->hasPages())
            <div class="card-footer bg-white">
                {{ $expenses->links() }}
            </div>
        @endif
    </div>
@endsection
