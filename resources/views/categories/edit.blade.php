@extends('layouts.master')

@section('title', __('Edit Category'))

@section('content')

<div class="pagetitle">
    <h1>{{ __('Edit Category') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('Settings') }}</li>
            <li class="breadcrumb-item active">{{ __('Categories') }}</li>
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
            <form action="{{ route('categories.update', $category->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $category->name) }}" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('Related Transaction Statuses') }}</label>
                    <select name="transaction_statuses[]" class="form-select" multiple>
                        @foreach($transactionStatuses as $status)
                            <option value="{{ $status->id }}" {{ in_array($status->id, $selectedStatuses) ? 'selected' : '' }}>
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">{{ __('Hold Ctrl or Cmd to select multiple.') }}</small>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </form>
        </div>
    </div>
</div>

@endsection
