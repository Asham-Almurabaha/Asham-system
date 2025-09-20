@extends('layouts.master')

@section('title', __('Add New Transaction Status'))

@section('content')

<div class="pagetitle">
    <h1>{{ __('Add New Transaction Status') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('Settings') }}</li>
            <li class="breadcrumb-item">{{ __('Transaction Statuses') }}</li>
            <li class="breadcrumb-item active">{{ __('Add New Transaction Status') }}</li>
        </ol>
    </nav>
</div><!-- End Page Title -->
<div class="col-lg-6">
    <div class="card">
        <div class="card-body p-20">
            <form action="{{ route('transaction_statuses.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('Status Name') }}</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="transaction_type_id" class="form-label">{{ __('Transaction Type') }}</label>
                    <select name="transaction_type_id" id="transaction_type_id" class="form-select" required>
                        <option value="" disabled selected>{{ __('Choose') }} {{ __('Transaction Type') }}</option>
                        @foreach ($types as $type)
                        <option value="{{ $type->id }}" {{ old('transaction_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <x-button type="submit" variant="success" :outline="true">
                    <i class="bi bi-check2-circle me-1"></i> {{ __('Save') }}
                </x-button>
                <x-button href="{{ route('transaction_statuses.index') }}" variant="secondary" :outline="true">{{ __('Cancel') }}</x-button>
            </form>
        </div>
    </div>
</div>

@endsection
