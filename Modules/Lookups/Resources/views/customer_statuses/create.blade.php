@extends('layouts.master')

@section('title', __('Add New Customer Status'))

@section('content')
<div class="pagetitle">
    <h1>{{ __('Add New Customer Status') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('Settings') }}</li>
            <li class="breadcrumb-item">{{ __('Customer Statuses') }}</li>
            <li class="breadcrumb-item active">{{ __('Create') }}</li>
        </ol>
    </nav>
</div><!-- End Page Title -->
<div class="col-lg-6">
    <div class="card">
        <div class="card-body p-20">
            <form action="{{ route('customer_statuses.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required autofocus>
                </div>

                <x-button type="submit" variant="success" :outline="true">{{ __('Save') }}</x-button>
                <x-button href="{{ route('customer_statuses.index') }}" variant="secondary" :outline="true">{{ __('Cancel') }}</x-button>
            </form>
        </div>
    </div>
</div>
@endsection
