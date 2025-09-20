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

                <x-button.action type="submit" variant="primary" :outline="true">
                    <i class="bi bi-save2 me-1"></i> {{ __('Update') }}
                </x-button.action>
                <x-button.action href="{{ route('categories.index') }}" variant="secondary" :outline="true">{{ __('Cancel') }}</x-button.action>
            </form>
        </div>
    </div>
</div>

@endsection
