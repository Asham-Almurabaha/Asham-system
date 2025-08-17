@extends('layouts.master')
@section('title', __('Create Settings'))

@section('content')
<div class="container py-3">
  <div class="row">
    <div class="col-lg-8 mx-auto">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">{{ __('إنشاء إعداد جديد') }}</h5>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('settings.store') }}" enctype="multipart/form-data" class="row g-3">
            @csrf

            <div class="col-md-6">
              <label class="form-label">{{ __('Name (EN)') }} <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                     value="{{ old('name') }}" maxlength="255" required>
              @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">{{ __('Name (AR)') }} <span class="text-danger">*</span></label>
              <input type="text" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror"
                     value="{{ old('name_ar') }}" maxlength="255" required>
              @error('name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">{{ __('Logo (PNG/JPG/WEBP/SVG)') }}</label>
              <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror"
                     accept=".png,.jpg,.jpeg,.gif,.webp,.svg">
              @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
              <div class="form-text">{{ __('الحد 4MB') }}</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">{{ __('Favicon (ICO/PNG/JPG/WEBP/SVG)') }}</label>
              <input type="file" name="favicon" class="form-control @error('favicon') is-invalid @enderror"
                     accept=".ico,.png,.jpg,.jpeg,.gif,.webp,.svg">
              @error('favicon') <div class="invalid-feedback">{{ $message }}</div> @enderror
              <div class="form-text">{{ __('الحد 2MB') }}</div>
            </div>

            <div class="col-12 d-flex gap-2">
              <button class="btn btn-success">
                <i class="bi bi-check2 me-1"></i>{{ __('حفظ') }}
              </button>
              <a href="{{ route('settings.index') }}" class="btn btn-light">{{ __('إلغاء') }}</a>
            </div>
          </form>
        </div>
      </div>

      <div class="alert alert-warning mt-3 mb-0">
        <i class="bi bi-info-circle me-1"></i>
        {{ __('يمكن حفظ سجل إعداد واحد فقط. لو كان هناك إعداد محفوظ سيتم تحويلك تلقائيًا إلى صفحة العرض.') }}
      </div>
    </div>
  </div>
</div>
@endsection
