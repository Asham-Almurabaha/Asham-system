@extends('layouts.master')

@section('title', __('companies::companies.Edit Company'))

@section('content')
<div class="pagetitle mb-3">
  <h1 class="h3 mb-1">{{ __('companies::companies.Edit Company') }}</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('companies.index') }}">{{ __('companies::companies.Companies') }}</a></li>
      <li class="breadcrumb-item active">{{ __('companies::companies.Edit Company') }}</li>
    </ol>
  </nav>
</div>

@if ($errors->any())
  <div class="alert alert-danger shadow-sm">{{ __('companies::companies.Validation Errors') }}</div>
@endif

<div class="card shadow-sm border-0">
  <div class="card-body p-4">
    <form action="{{ route('companies.update', $company) }}" method="POST" novalidate>
      @csrf
      @method('PUT')

      <div class="row g-3">
        <div class="col-md-6">
          <label for="name" class="form-label">{{ __('companies::companies.Company Name') }} <span class="text-danger">*</span></label>
          <input type="text" name="name" id="name" value="{{ old('name', $company->name) }}"
            class="form-control @error('name') is-invalid @enderror" maxlength="190" required>
          @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
          <label class="form-label">{{ __('companies::companies.Status') }}</label>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $company->is_active))>
            <label class="form-check-label" for="is_active">{{ __('companies::companies.Mark Active') }}</label>
          </div>
          @error('is_active') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
          <label for="notes" class="form-label">{{ __('companies::companies.Notes') }}</label>
          <textarea name="notes" id="notes" rows="4" class="form-control @error('notes') is-invalid @enderror" placeholder="{{ __('companies::companies.Notes Placeholder') }}">{{ old('notes', $company->notes) }}</textarea>
          @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="d-flex align-items-center gap-2 mt-4">
        <x-button.action type="submit" variant="primary">{{ __('companies::companies.Update Company') }}</x-button.action>
        <x-button.secondary href="{{ route('companies.index') }}">{{ __('companies::companies.Cancel') }}</x-button.secondary>
      </div>
    </form>
  </div>
</div>
@endsection
