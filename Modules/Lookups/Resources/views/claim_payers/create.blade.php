@extends('layouts.master')

@section('title', __('Add New Claim Payer'))

@section('content')
<div class="pagetitle">
    <h1>{{ __('Add New Claim Payer') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('Settings') }}</li>
            <li class="breadcrumb-item">{{ __('Claim Payers') }}</li>
            <li class="breadcrumb-item active">{{ __('Create') }}</li>
        </ol>
    </nav>
</div><!-- End Page Title -->
<div class="col-lg-6">
    <div class="card">
        <div class="card-body p-20">
            <form action="{{ route('claim_payers.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required autofocus>
                </div>

                <x-button.action type="submit" variant="success" :outline="true">
                    <i class="bi bi-check2-circle me-1"></i> {{ __('Save') }}
                </x-button.action>
                <x-button.action href="{{ route('claim_payers.index') }}" variant="secondary" :outline="true">{{ __('Cancel') }}</x-button.action>
            </form>
        </div>
    </div>
</div>
@endsection
