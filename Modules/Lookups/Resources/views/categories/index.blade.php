@extends('layouts.master')

@section('title', __('Categories List'))

@section('content')
<div class="container-xxl py-4">
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">@lang('sidebar.Settings')</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Categories') }}</li>
                </ol>
            </nav>
            <h1 class="h4 mb-0">{{ __('Categories List') }}</h1>
        </div>
        <div class="ms-auto d-flex flex-wrap gap-2">
            <a href="{{ route('categories.create') }}" class="btn btn-success">
                <i class="bi bi-plus-lg me-1"></i>{{ __('Add New Category') }}
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" style="width:70px" class="text-center">#</th>
                        <th scope="col" style="width:240px">{{ __('Name') }}</th>
                        <th scope="col">{{ __('Related Transaction Statuses') }}</th>
                        <th scope="col" class="text-end" style="width:160px">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="fw-semibold">{{ $category->name }}</td>
                            <td>
                                @forelse($category->transactionStatuses as $status)
                                    <span class="badge bg-info text-dark me-1 mb-1">{{ $status->name }}</span>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('categories.edit', $category->id) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                    @include('lookups::components.delete-button', [
                                        'action' => route('categories.destroy', $category->id),
                                        'confirm' => __('Are you sure to delete this category?'),
                                    ])
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">{{ __('No categories found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
