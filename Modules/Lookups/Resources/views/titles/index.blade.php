@extends('layouts.master')

@section('title', __('Titles List'))

@section('content')
<div class="container-xxl py-4">
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">@lang('sidebar.Settings')</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Titles') }}</li>
                </ol>
            </nav>
            <h1 class="h4 mb-0">{{ __('Titles List') }}</h1>
        </div>
        <div class="ms-auto d-flex flex-wrap gap-2">
            <x-button href="{{ route('titles.create') }}" variant="success">
                <i class="bi bi-plus-lg me-1"></i>{{ __('Add New Title') }}
            </x-button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <x-table head-class="table-light">
            <x-slot name="head">
                <tr>
                    <th scope="col" style="width:80px" class="text-center">#</th>
                    <th scope="col">{{ __('Name') }}</th>
                    <th scope="col" class="text-end" style="width:180px">{{ __('Actions') }}</th>
                </tr>
            </x-slot>
            @forelse($titles as $title)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="fw-semibold text-start">{{ $title->name }}</td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
                            <x-button href="{{ route('titles.edit', $title->id) }}" variant="primary" :outline="true" size="sm">{{ __('Edit') }}</x-button>
                            @include('lookups::components.delete-button', [
                                'action' => route('titles.destroy', $title->id),
                                'confirm' => __('Are you sure to delete this title?'),
                            ])
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center text-muted py-4">{{ __('No titles found.') }}</td>
                </tr>
            @endforelse
        </x-table>
    </div>
</div>
@endsection
