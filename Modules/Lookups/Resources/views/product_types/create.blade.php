@extends('layouts.master')

@section('title', __('Add New Product Type'))

@section('content')
    <div class="pagetitle">
        <h1>{{ __('Add New Product Type') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">{{ __('Settings') }}</li>
                <li class="breadcrumb-item active">{{ __('Product Types') }}</li>
            </ol>
        </nav>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="col-lg-6">
        <div class="card">
            <div class="card-body p-20">
                <form action="{{ route('product_types.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Product Type Name') }}</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">@lang('lookups::transaction_types.Description (Optional)')</label>
                        <textarea name="description" id="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-outline-success">{{ __('Save') }}</button>
                    <a href="{{ route('product_types.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </form>
            </div>
        </div>
    </div>
@endsection
