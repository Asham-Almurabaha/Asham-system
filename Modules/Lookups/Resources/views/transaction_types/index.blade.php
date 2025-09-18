@extends('layouts.master')

@section('title', __('lookups::transaction_types.List of Transaction Types'))

@section('content')
<div class="container-xxl py-4">
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">@lang('sidebar.Settings')</a></li>
                    <li class="breadcrumb-item active" aria-current="page">@lang('lookups::sidebar.Transaction Types')</li>
                </ol>
            </nav>
            <h1 class="h4 mb-0">@lang('lookups::transaction_types.List of Transaction Types')</h1>
        </div>
        <div class="ms-auto d-flex flex-wrap gap-2">
            <a href="{{ route('transaction_types.create') }}" class="btn btn-success">
                <i class="bi bi-plus-lg me-1"></i>@lang('lookups::transaction_types.Add New Transaction Type')
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" style="width:80px" class="text-center">#</th>
                        <th scope="col">@lang('lookups::transaction_types.Name')</th>
                        <th scope="col" class="text-end" style="width:180px">@lang('lookups::transaction_types.Actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($types as $type)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="fw-semibold text-start">{{ $type->name }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('transaction_types.edit', $type->id) }}" class="btn btn-sm btn-outline-primary">@lang('lookups::transaction_types.Edit')</a>
                                    @include('lookups::components.delete-button', [
                                        'action' => route('transaction_types.destroy', $type->id),
                                        'confirm' => __('lookups::transaction_types.Are you sure you want to delete this transaction type?'),
                                        'label' => __('lookups::transaction_types.Delete'),
                                    ])
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">@lang('lookups::transaction_types.No transaction types yet.')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
