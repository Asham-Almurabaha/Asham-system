@extends('layouts.master')

@section('title', __('Categories List'))

@section('content')

<div class="pagetitle">
    <h1>{{ __('Categories List') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('Settings') }}</li>
            <li class="breadcrumb-item active">{{ __('Categories') }}</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

<div class="card d-inline-block mb-3">
    <div class="card-body p-20">
        <a href="{{ route('categories.create') }}" class="btn btn-success">{{ __('Add New Category') }}</a>
    </div>
</div>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body p-20">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th class="col-1">#</th>
                        <th class="col-4">{{ __('Name') }}</th>
                        <th class="col-5">{{ __('Related Transaction Statuses') }}</th>
                        <th class="col-2">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <th scope="row">{{ $loop->iteration }}</th>
                            <td>{{ $category->name }}</td>
                            <td>
                                @foreach($category->transactionStatuses as $status)
                                    <span class="badge bg-info text-dark me-1">{{ $status->name }}</span>
                                @endforeach
                            </td>
                            <td>
                                <a href="{{ route('categories.edit', $category->id) }}" class="btn btn-primary btn-sm me-1">{{ __('Edit') }}</a>
                                <form action="{{ route('categories.destroy', $category->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure to delete this category?') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">{{ __('No categories found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
