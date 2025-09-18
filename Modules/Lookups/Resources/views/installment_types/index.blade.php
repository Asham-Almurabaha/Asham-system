@extends('layouts.master')

@section('title', __('Installment Types List'))

@section('content')
<div class="container-xxl py-4">
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">@lang('sidebar.Settings')</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Installment Types') }}</li>
                </ol>
            </nav>
            <h1 class="h4 mb-0">{{ __('Installment Types List') }}</h1>
        </div>
        <div class="ms-auto d-flex flex-wrap gap-2">
            <a href="{{ route('installment_types.create') }}" class="btn btn-success">
                <i class="bi bi-plus-lg me-1"></i>{{ __('Add New Installment Type') }}
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" style="width:80px" class="text-center">#</th>
                        <th scope="col">{{ __('Name') }}</th>
                        <th scope="col" class="text-end" style="width:180px">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($types as $type)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="fw-semibold text-start">{{ $type->name }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('installment_types.edit', $type->id) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                    @include('lookups::components.delete-button', [
                                        'action' => route('installment_types.destroy', $type->id),
                                        'confirm' => __('Are you sure to delete this installment type?'),
                                    ])
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">{{ __('No installment types found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
