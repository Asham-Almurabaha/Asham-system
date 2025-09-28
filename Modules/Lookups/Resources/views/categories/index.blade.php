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
            <x-button.action href="{{ route('categories.create') }}" variant="success">
                <i class="bi bi-plus-lg me-1"></i>{{ __('Add New Category') }}
            </x-button.action>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <x-table head-class="table-light">
            <x-slot name="head">
                <tr>
                    <th scope="col" style="width:70px" class="text-center">#</th>
                    <th scope="col" style="width:240px">{{ __('Name') }}</th>
                    <th scope="col">{{ __('Related Transaction Statuses') }}</th>
                    <th scope="col" class="text-center" style="width:160px">@lang('lookups::messages.status.column')</th>
                    <th scope="col" class="text-end" style="width:160px">{{ __('Actions') }}</th>
                </tr>
            </x-slot>
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
                    <td class="text-center">
                        @include('lookups::components.protection-status', ['isProtected' => $category->is_protected])
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
                            <x-button.action
                                href="{{ route('categories.edit', $category->id) }}"
                                variant="primary"
                                :outline="true"
                                size="sm"
                                :disabled="$category->is_protected"
                            >{{ __('Edit') }}</x-button.action>

                            @if(! $category->is_protected)
                                @include('lookups::components.delete-button', [
                                    'action' => route('categories.destroy', $category->id),
                                    'confirm' => __('Are you sure to delete this category?'),
                                ])
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">{{ __('No categories found.') }}</td>
                </tr>
            @endforelse
        </x-table>
    </div>
</div>
@endsection
