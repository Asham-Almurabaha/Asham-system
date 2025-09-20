@extends('layouts.master')

@section('title', __('Edit'))

@section('content')
<div class="container-fluid py-3">
    <div class="pagetitle mb-3">
        <h1>{{ __('Edit') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">{{ __('Settings') }}</li>
                <li class="breadcrumb-item">{{ __('Product Entries') }}</li>
                <li class="breadcrumb-item active">{{ __('Edit') }}</li>
            </ol>
        </nav>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xl-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="{{ route('product_entries.update', $productEntry->id) }}" method="POST" class="row g-3">
                        @csrf
                        @method('PUT')

                        <div class="col-12">
                            <label for="product_id" class="form-label">{{ __('Product') }}</label>
                            <select name="product_id" id="product_id" class="form-select" required>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" {{ old('product_id', $productEntry->product_id) == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="quantity" class="form-label">{{ __('Quantity') }}</label>
                            <input type="number" min="1" name="quantity" id="quantity" class="form-control"
                                   value="{{ old('quantity', $productEntry->quantity) }}" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="purchase_price" class="form-label">{{ __('Purchase Price') }}</label>
                            <input type="number" min="0" step="0.01" name="purchase_price" id="purchase_price" class="form-control"
                                   value="{{ old('purchase_price', $productEntry->purchase_price) }}" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="entry_date" class="form-label">{{ __('Entry Date') }}</label>
                            <input type="date" name="entry_date" id="entry_date" class="form-control"
                                   value="{{ old('entry_date', $productEntry->entry_date->format('Y-m-d')) }}" required>
                        </div>

                        <div class="col-12">
                            <div class="d-grid gap-2 d-sm-flex justify-content-sm-end">
                                <x-update-button>
                                    {{ __('Update') }}
                                </x-update-button>
                                <x-secondary-button href="{{ route('product_entries.index') }}">
                                    {{ __('Cancel') }}
                                </x-secondary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
