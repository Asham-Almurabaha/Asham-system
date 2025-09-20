@extends('layouts.master')

@section('title', __('Add New Category'))

@section('content')

<div class="pagetitle">
    <h1>{{ __('Add New Category') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('Settings') }}</li>
            <li class="breadcrumb-item active">{{ __('Categories') }}</li>
        </ol>
    </nav>
</div><!-- End Page Title -->
<div class="col-lg-6">
    <div class="card">
        <div class="card-body p-20">
            <form action="{{ route('categories.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('Related Transaction Statuses') }}</label>
                    <select name="transaction_statuses[]" class="form-select" multiple>
                        @foreach($transactionStatuses as $status)
                            <option value="{{ $status->id }}" {{ (collect(old('transaction_statuses'))->contains($status->id)) ? 'selected' : '' }}>
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">{{ __('Hold Ctrl or Cmd to select multiple.') }}</small>
                </div>

                <x-button type="submit" variant="success" :outline="true">{{ __('Save') }}</x-button>
                <x-button href="{{ route('categories.index') }}" variant="secondary" :outline="true">{{ __('Cancel') }}</x-button>
            </form>
        </div>
    </div>
</div>

@endsection
