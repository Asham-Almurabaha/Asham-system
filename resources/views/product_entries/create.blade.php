@extends('layouts.master')

@section('title', __('Add New Product Entry'))

@section('content')
<div class="container-fluid py-3">
    <div class="pagetitle mb-3">
        <h1>{{ __('Add New Product Entry') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">{{ __('Settings') }}</li>
                <li class="breadcrumb-item">{{ __('Product Entries') }}</li>
                <li class="breadcrumb-item active">{{ __('Add New Product Entry') }}</li>
            </ol>
        </nav>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xl-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="{{ route('product_entries.store') }}" method="POST" class="row g-3">
                        @csrf

                        <div class="col-12">
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

                        <div class="col-12 col-md-6">
                            <label for="quantity" class="form-label">{{ __('Quantity') }}</label>
                            <input type="number" min="1" name="quantity" id="quantity" class="form-control"
                                   value="{{ old('quantity') }}" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="purchase_price" class="form-label">{{ __('Purchase Price') }}</label>
                            <input type="number" min="0" step="0.01" name="purchase_price" id="purchase_price" class="form-control"
                                   value="{{ old('purchase_price') }}" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="entry_date" class="form-label">{{ __('Entry Date') }}</label>
                            <input type="date" name="entry_date" id="entry_date" class="form-control"
                                   value="{{ old('entry_date', date('Y-m-d')) }}" required>
                        </div>

                        <div class="col-12">
                            <div class="d-grid gap-2 d-sm-flex justify-content-sm-end">
                                <x-save-button>
                                    {{ __('Save') }}
                                </x-save-button>
                                <x-button href="{{ route('product_entries.index') }}" variant="secondary" :outline="true">
                                    {{ __('Cancel') }}
                                </x-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
