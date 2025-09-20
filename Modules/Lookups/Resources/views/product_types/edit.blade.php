@extends('layouts.master')

@section('title', __('Edit Product Type'))

@section('content')
    <div class="pagetitle">
        <h1>{{ __('Edit Product Type') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">{{ __('Settings') }}</li>
                <li class="breadcrumb-item">{{ __('Product Types') }}</li>
                <li class="breadcrumb-item active">{{ __('Edit Product Type') }}</li>
            </ol>
        </nav>
    </div>
<div class="col-lg-6">
        <div class="card">
            <div class="card-body p-20">
                <form action="{{ route('product_types.update', $productType) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Product Type Name') }}</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $productType->name) }}" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">@lang('lookups::transaction_types.Description (Optional)')</label>
                        <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $productType->description) }}</textarea>
                    </div>

                    <x-button.action type="submit" variant="primary" :outline="true">
                        <i class="bi bi-save2 me-1"></i> {{ __('Update') }}
                    </x-button.action>
                    <x-button.action href="{{ route('product_types.index') }}" variant="secondary" :outline="true">{{ __('Cancel') }}</x-button.action>
                </form>
            </div>
        </div>
    </div>
@endsection
