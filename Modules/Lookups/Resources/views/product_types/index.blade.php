@extends('layouts.master')

@section('title', __('Product Types List'))

@section('content')
    <div class="pagetitle">
        <h1>{{ __('Product Types List') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">{{ __('Settings') }}</li>
                <li class="breadcrumb-item active">{{ __('Product Types') }}</li>
            </ol>
        </nav>
    </div>

    <div class="card d-inline-block mb-3">
        <div class="card-body p-20">
            <a href="{{ route('product_types.create') }}" class="btn btn-success">{{ __('Add New Product Type') }}</a>
        </div>
    </div>

    <div class="col-lg-12">
        <div class="card">
            <div class="card-body p-20">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th scope="col" class="col-1">{{ __('#') }}</th>
                            <th scope="col" class="col-7">{{ __('Product Type Name') }}</th>
                            <th scope="col" class="col-4">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productTypes as $productType)
                            <tr>
                                <th scope="row">{{ $loop->iteration }}</th>
                                <td class="text-start">{{ $productType->name }}</td>
                                <td>
                                    <a href="{{ route('product_types.edit', $productType) }}" class="btn btn-primary btn-sm me-1">{{ __('Edit') }}</a>
                                    <form action="{{ route('product_types.destroy', $productType) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure to delete this product type?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">{{ __('No product types found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
