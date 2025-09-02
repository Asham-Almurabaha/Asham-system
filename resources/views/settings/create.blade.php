@extends('layouts.master')
@section('title', __('sidebar.Create Setting'))

@section('content')
<div class="container py-3">
  <div class="row">
    <div class="col-lg-8 mx-auto">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">@lang('sidebar.Create Setting')</h5>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('settings.store') }}" enctype="multipart/form-data" class="row g-3">
            @csrf

            {{-- Owner Name --}}
            <div class="col-md-12">
              <label class="form-label">@lang('sidebar.Owner Name') <span class="text-danger">*</span></label>
              <input type="text"
                     name="owner_name"
                     class="form-control @error('owner_name') is-invalid @enderror"
                     value="{{ old('owner_name') }}"
                     maxlength="50" required>
              @error('owner_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              <div class="form-text">@lang('sidebar.Maximum 50 characters')</div>
            </div>

            {{-- Name (EN) --}}
            <div class="col-md-6">
              <label class="form-label">@lang('sidebar.Name (EN)') <span class="text-danger">*</span></label>
              <input type="text"
                     name="name"
                     class="form-control @error('name') is-invalid @enderror"
                     value="{{ old('name') }}"
                     maxlength="50" required>
              @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              <div class="form-text">@lang('sidebar.Maximum 50 characters')</div>
            </div>

            {{-- Name (AR) --}}
            <div class="col-md-6">
              <label class="form-label">@lang('sidebar.Name (AR)') <span class="text-danger">*</span></label>
              <input type="text"
                     name="name_ar"
                     class="form-control @error('name_ar') is-invalid @enderror"
                     value="{{ old('name_ar') }}"
                     maxlength="50" required>
              @error('name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
              <div class="form-text">@lang('sidebar.Maximum 50 characters')</div>
            </div>

            {{-- Logo --}}
            <div class="col-md-6">
              <label class="form-label">@lang('sidebar.Logo (PNG/JPG/WEBP/SVG)')</label>
              <input type="file"
                     name="logo"
                     class="form-control @error('logo') is-invalid @enderror"
                     accept=".png,.jpg,.jpeg,.gif,.webp,.svg">
              @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
              <div class="form-text">@lang('sidebar.Limit 4MB')</div>
            </div>

            {{-- Favicon --}}
            <div class="col-md-6">
              <label class="form-label">@lang('sidebar.Favicon (ICO/PNG/JPG/WEBP/SVG)')</label>
              <input type="file"
                     name="favicon"
                     class="form-control @error('favicon') is-invalid @enderror"
                     accept=".ico,.png,.jpg,.jpeg,.gif,.webp,.svg">
              @error('favicon') <div class="invalid-feedback">{{ $message }}</div> @enderror
              <div class="form-text">@lang('sidebar.Limit 2MB')</div>
            </div>

            <div class="col-12 d-flex gap-2">
              <button class="btn btn-outline-success">
                <i class="bi bi-check2 me-1"></i>@lang('app.Save')
              </button>
              <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary">@lang('app.Cancel')</a>
            </div>
          </form>
        </div>
      </div>

      <div class="alert alert-warning mt-3 mb-0">
        <i class="bi bi-info-circle me-1"></i>
        @lang('sidebar.You can leave the upload fields empty if you do not want to change the images. Use the delete option to remove current images.')
      </div>
    </div>
  </div>
</div>
@endsection
