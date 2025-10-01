@extends('layouts.master')

@section('title', __('companies::companies.Add Disbursement Status'))

@section('content')
<div class="container-xxl py-4">
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">@lang('sidebar.Settings')</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('company-disbursement-statuses.index') }}">{{ __('companies::companies.Disbursement Statuses') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('companies::companies.Add Disbursement Status') }}</li>
                </ol>
            </nav>
            <h1 class="h4 mb-0">{{ __('companies::companies.Add Disbursement Status') }}</h1>
        </div>
        <div class="ms-auto">
            <x-button.action href="{{ route('company-disbursement-statuses.index') }}" variant="secondary" :outline="true">
                {{ __('companies::companies.Back to Disbursement Statuses') }}
            </x-button.action>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('company-disbursement-statuses.store') }}" method="POST" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('companies::companies.Name') }}</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">{{ __('companies::companies.Description') }}</label>
                            <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="{{ __('companies::companies.Description Placeholder') }}">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_default" name="is_default" value="1" {{ old('is_default', false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_default">{{ __('companies::companies.Default Status') }}</label>
                            <div class="form-text">{{ __('companies::companies.Default Status Help') }}</div>
                        </div>

                        <div class="d-flex gap-2">
                            <x-button.action type="submit" variant="success">
                                <i class="bi bi-check2-circle me-1"></i>{{ __('companies::companies.Save Status') }}
                            </x-button.action>
                            <x-button.action href="{{ route('company-disbursement-statuses.index') }}" variant="secondary" :outline="true">
                                {{ __('companies::companies.Cancel') }}
                            </x-button.action>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h6 text-uppercase text-muted mb-3">{{ __('companies::companies.Tips Title') }}</h2>
                    <ul class="small text-muted mb-0 ps-3">
                        <li>{{ __('companies::companies.TipDefaultInfo') }}</li>
                        <li>{{ __('companies::companies.TipUsage') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
