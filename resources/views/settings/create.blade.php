@extends('layouts.master')
@section('title', __('setting.Create Setting'))

@section('content')
<div class="pagetitle">
    <h1>@lang('setting.Create Setting')</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">@lang('setting.Setting')</li>
            <li class="breadcrumb-item active">@lang('pages.Add')</li>
        </ol>
    </nav>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section class="section profile">
    <div class="card">
        <div class="card-body pt-3">

            <form action="{{ route('settings.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- EN Name --}}
                <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 label">@lang('pages.EN Name')</div>
                    <div class="col-lg-9 col-md-8">
                        <input type="text"
                               name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}"
                               placeholder="@lang('pages.EN Name')"
                               required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- AR Name --}}
                <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 label">@lang('pages.AR Name')</div>
                    <div class="col-lg-9 col-md-8">
                        <input type="text"
                               name="name_ar"
                               class="form-control @error('name_ar') is-invalid @enderror"
                               value="{{ old('name_ar') }}"
                               placeholder="@lang('pages.AR Name')"
                               required>
                        @error('name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Logo --}}
                <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 label">@lang('setting.Logo')</div>
                    <div class="col-lg-9 col-md-8">
                        <input type="file"
                               name="logo"
                               class="form-control @error('logo') is-invalid @enderror"
                               accept="image/*">
                        @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        {{-- Preview --}}
                        <div class="mt-2">
                            <img id="logoPreview" src="#" alt="" style="display:none;width:100px;height:auto;border:1px solid #eee;border-radius:6px;">
                        </div>
                    </div>
                </div>

                {{-- Favicon --}}
                <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 label">@lang('setting.Icon')</div>
                    <div class="col-lg-9 col-md-8">
                        <input type="file"
                               name="favicon"
                               class="form-control @error('favicon') is-invalid @enderror"
                               accept="image/*">
                        @error('favicon') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        {{-- Preview --}}
                        <div class="mt-2">
                            <img id="faviconPreview" src="#" alt="" style="display:none;width:50px;height:50px;border:1px solid #eee;border-radius:6px;">
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success">
                        @lang('pages.Save')
                    </button>
                    <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                        @lang('pages.Cancel')
                    </a>
                </div>
            </form>

        </div>
    </div>
</section>

{{-- صور المعاينة --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const logoInput = document.querySelector('input[name="logo"]');
    const logoPreview = document.getElementById('logoPreview');
    const faviconInput = document.querySelector('input[name="favicon"]');
    const faviconPreview = document.getElementById('faviconPreview');

    function bindPreview(input, imgEl) {
        if (!input || !imgEl) return;
        input.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file) { imgEl.style.display = 'none'; return; }
            const reader = new FileReader();
            reader.onload = e => {
                imgEl.src = e.target.result;
                imgEl.style.display = 'inline-block';
            };
            reader.readAsDataURL(file);
        });
    }

    bindPreview(logoInput, logoPreview);
    bindPreview(faviconInput, faviconPreview);
});
</script>
@endsection
