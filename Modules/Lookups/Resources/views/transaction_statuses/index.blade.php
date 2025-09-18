@extends('layouts.master')

@section('title', __('Transaction Statuses List'))

@section('content')
<div class="container-xxl py-4">
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">@lang('sidebar.Settings')</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Transaction Statuses') }}</li>
                </ol>
            </nav>
            <h1 class="h4 mb-0">{{ __('Transaction Statuses List') }}</h1>
        </div>
        <div class="ms-auto d-flex flex-wrap gap-2">
            <a href="{{ route('transaction_statuses.create') }}" class="btn btn-success">
                <i class="bi bi-plus-lg me-1"></i>{{ __('Add New Transaction Status') }}
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" style="width:80px" class="text-center">#</th>
                        <th scope="col" style="width:260px">{{ __('Status Name') }}</th>
                        <th scope="col">{{ __('Transaction Type') }}</th>
                        <th scope="col" class="text-end" style="width:200px">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statuses as $status)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="fw-semibold">{{ $status->name }}</td>
                            <td class="text-start">{{ $status->transactionType->name ?? '-' }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('transaction_statuses.edit', $status->id) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                    @include('lookups::components.delete-button', [
                                        'action' => route('transaction_statuses.destroy', $status->id),
                                        'confirm' => __('Are you sure to delete this status?'),
                                    ])
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">{{ __('No statuses found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
