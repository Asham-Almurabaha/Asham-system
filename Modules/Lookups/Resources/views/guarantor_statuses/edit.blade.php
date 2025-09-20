@extends('layouts.master')

@section('title', __('Edit Guarantor Status'))

@section('content')
<div class="pagetitle">
    <h1>{{ __('Edit Guarantor Status') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('Settings') }}</li>
            <li class="breadcrumb-item">{{ __('Guarantor Statuses') }}</li>
            <li class="breadcrumb-item active">{{ __('Edit') }}</li>
        </ol>
    </nav>
</div><!-- End Page Title -->
<div class="col-lg-6">
    <div class="card">
        <div class="card-body p-20">
            <form action="{{ route('guarantor_statuses.update', $guarantor_status->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $guarantor_status->name) }}" required autofocus>
                </div>

                <x-button type="submit" variant="primary" :outline="true">{{ __('Update') }}</x-button>
                <x-button href="{{ route('guarantor_statuses.index') }}" variant="secondary" :outline="true">{{ __('Cancel') }}</x-button>
            </form>
        </div>
    </div>
</div>
@endsection
