@extends('layouts.master')

@section('title', __('Edit Claim Payer'))

@section('content')
<div class="pagetitle">
    <h1>{{ __('Edit Claim Payer') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('Settings') }}</li>
            <li class="breadcrumb-item">{{ __('Claim Payers') }}</li>
            <li class="breadcrumb-item active">{{ __('Edit') }}</li>
        </ol>
    </nav>
</div><!-- End Page Title -->
<div class="col-lg-6">
    <div class="card">
        <div class="card-body p-20">
            <form action="{{ route('claim_payers.update', $claimPayer->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $claimPayer->name) }}" required autofocus>
                </div>

                <button type="submit" class="btn btn-outline-primary">{{ __('Update') }}</button>
                <a href="{{ route('claim_payers.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </form>
        </div>
    </div>
</div>
@endsection
