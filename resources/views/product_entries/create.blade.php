@extends('layouts.master')

@section('title', __('Add New Product Entry'))

@section('content')

<div class="pagetitle">
    <h1>{{ __('Add New Product Entry') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('Settings') }}</li>
            <li class="breadcrumb-item">{{ __('Product Entries') }}</li>
            <li class="breadcrumb-item active">{{ __('Add New Product Entry') }}</li>
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
            <form action="{{ route('product_entries.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="product_id" class="form-label">{{ __('Product') }}</label>
                    <select name="product_id" id="product_id" class="form-select" required>
                        <option value="" disabled selected>{{ __('Choose') }} {{ __('Product') }}</option>
                        @foreach ($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="quantity" class="form-label">{{ __('Quantity') }}</label>
                    <input type="number" min="1" name="quantity" id="quantity" class="form-control" value="{{ old('quantity') }}" required>
                </div>

                <div class="mb-3">
                    <label for="purchase_price" class="form-label">{{ __('Purchase Price') }}</label>
                    <input type="number" min="0" step="0.01" name="purchase_price" id="purchase_price" class="form-control" value="{{ old('purchase_price') }}" required>
                </div>

                <div class="mb-3">
                    <label for="entry_date" class="form-label">{{ __('Entry Date') }}</label>
                    <input type="date" name="entry_date" id="entry_date" class="form-control" value="{{ old('entry_date', date('Y-m-d')) }}" required>
                </div>

                <button type="submit" class="btn btn-outline-success">{{ __('Save') }}</button>
                <a href="{{ route('product_entries.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </form>
        </div>
    </div>
</div>

@endsection
