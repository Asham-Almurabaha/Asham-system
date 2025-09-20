@extends('layouts.master')

@section('title', __('Edit Claimant'))

@section('content')
    <div class="pagetitle">
      <h1>{{ __('Edit Claimant') }}</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">{{ __('Settings') }}</li>
          <li class="breadcrumb-item">{{ __('Claimants') }}</li>
          <li class="breadcrumb-item active">{{ __('Edit Claimant') }}</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
<div class="col-lg-6">
        <div class="card">
            <div class="card-body p-20">
              <form action="{{ route('claimants.update', $claimant->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Name') }}</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $claimant->name) }}" required autofocus>
                    </div>

                    <x-button type="submit" variant="primary" :outline="true">
                        <i class="bi bi-save2 me-1"></i> {{ __('Update') }}
                    </x-button>
                    <x-button href="{{ route('claimants.index') }}" variant="secondary" :outline="true">{{ __('Cancel') }}</x-button>
                </form>
            </div>
        </div>
    </div>
@endsection
