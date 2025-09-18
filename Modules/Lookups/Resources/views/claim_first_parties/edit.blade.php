@extends('layouts.master')

@section('title', __('Edit Claim First Party'))

@section('content')
    <div class="pagetitle">
      <h1>{{ __('Edit Claim First Party') }}</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">{{ __('Settings') }}</li>
          <li class="breadcrumb-item">{{ __('Claim First Parties') }}</li>
          <li class="breadcrumb-item active">{{ __('Edit Claim First Party') }}</li>
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
              <form action="{{ route('claim_first_parties.update', $claimFirstParty->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Name') }}</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $claimFirstParty->name) }}" required autofocus>
                    </div>

                    <button type="submit" class="btn btn-outline-success">{{ __('Update') }}</button>
                    <a href="{{ route('claim_first_parties.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </form>
            </div>
        </div>
    </div>
@endsection
