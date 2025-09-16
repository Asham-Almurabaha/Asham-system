@extends('layouts.master')

@section('title', __('Edit'))

@section('content')

<div class="pagetitle">
    <h1>{{ __('Edit') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('Settings') }}</li>
            <li class="breadcrumb-item">{{ __('Transaction Statuses') }}</li>
            <li class="breadcrumb-item active">{{ __('Edit') }}</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

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
            <form action="{{ route('transaction_statuses.update', $transactionStatus->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('Status Name') }}</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $transactionStatus->name) }}" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="transaction_type_id" class="form-label">{{ __('Transaction Type') }}</label>
                    <select name="transaction_type_id" id="transaction_type_id" class="form-select" required>
                        <option value="" disabled>{{ __('Choose') }} {{ __('Transaction Type') }}</option>
                        @foreach ($types as $type)
                        <option value="{{ $type->id }}" {{ (old('transaction_type_id', $transactionStatus->transaction_type_id) == $type->id) ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-outline-primary">{{ __('Update') }}</button>
                <a href="{{ route('transaction_statuses.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </form>
        </div>
    </div>
</div>

@endsection
