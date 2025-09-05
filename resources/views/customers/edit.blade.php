@extends('layouts.master')

@section('title', __('Edit Customer'))

@section('content')
<div class="container py-3" dir="rtl">

    <div class="pagetitle">
        <h1 class="h3 mb-1">{{ __('Edit Customer') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">{{ __('Customers') }}</li>
                <li class="breadcrumb-item active">{{ __('Edit') }}</li>
            </ol>
        </nav>
    </div>

    {{-- General validation alerts --}}
    @if ($errors->any())
        <div class="alert alert-danger shadow-sm">
            {{ __('There are some errors, please review the highlighted fields below.') }}
        </div>
    @endif

    <div class="card shadow-sm kpi-card">
        <div class="card-body p-4">
            <form action="{{ route('customers.update', $customer->id) }}" method="POST" enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')

                <div class="row g-3">
                    {{-- Name --}}
                    <div class="col-12">
                        <label for="name" class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $customer->name) }}"
                            required
                            autofocus
                            maxlength="190"
                            autocomplete="name"
                            placeholder="{{ __('Type the full name') }}">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- National ID --}}
                    <div class="col-md-6">
                        <label for="national_id" class="form-label">{{ __('National ID') }}</label>
                        <input
                            type="text"
                            name="national_id"
                            id="national_id"
                            class="form-control @error('national_id') is-invalid @enderror"
                            value="{{ old('national_id', $customer->national_id ?? '') }}"
                            inputmode="numeric"
                            dir="ltr"
                            maxlength="20"
                            placeholder="{{ __('Example: 1234567890') }}">
                        <div class="form-text">{{ __('Numbers only.') }}</div>
                        @error('national_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Phone --}}
                    <div class="col-md-6">
                        <label for="phone" class="form-label">{{ __('Phone') }}</label>
                        <input
                            type="text"
                            name="phone"
                            id="phone"
                            class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone', $customer->phone) }}"
                            inputmode="tel"
                            dir="ltr"
                            maxlength="25"
                            autocomplete="tel"
                            placeholder="{{ __("+9665XXXXXXXX") }}">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Email --}}
                    <div class="col-md-6">
                        <label for="email" class="form-label">{{ __('Email') }}</label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $customer->email) }}"
                            maxlength="190"
                            autocomplete="email"
                            placeholder="{{ __("name@email.com") }}">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Nationality --}}
                    <div class="col-md-6">
                        <label for="nationality_id" class="form-label">{{ __('Nationality') }}</label>
                        <select
                            name="nationality_id"
                            id="nationality_id"
                            class="form-select @error('nationality_id') is-invalid @enderror">
                            <option value="">-- {{ __('Choose') }} --</option>
                            @foreach (($nationalities ?? []) as $row)
                                @php
                                    $nid   = is_object($row) ? $row->id   : (is_array($row) ? ($row['id'] ?? null)   : null);
                                    $nname = is_object($row) ? $row->name : (is_array($row) ? ($row['name'] ?? null) : null);
                                @endphp
                                @if($nid && $nname)
                                    <option value="{{ $nid }}" @selected(old('nationality_id', $customer->nationality_id) == $nid)>{{ $nname }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('nationality_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Job Title --}}
                    <div class="col-md-6">
                        <label for="title_id" class="form-label">{{ __('Job Title') }}</label>
                        <select
                            name="title_id"
                            id="title_id"
                            class="form-select @error('title_id') is-invalid @enderror">
                            <option value="">-- {{ __('Choose') }} --</option>
                            @foreach (($titles ?? []) as $row)
                                @php
                                    $tid   = is_object($row) ? $row->id   : (is_array($row) ? ($row['id'] ?? null)   : null);
                                    $tname = is_object($row) ? $row->name : (is_array($row) ? ($row['name'] ?? null) : null);
                                @endphp
                                @if($tid && $tname)
                                    <option value="{{ $tid }}" @selected(old('title_id', $customer->title_id) == $tid)>{{ $tname }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('title_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Address --}}
                    <div class="col-12">
                        <label for="address" class="form-label">{{ __('Address') }}</label>
                        <textarea
                            name="address"
                            id="address"
                            rows="3"
                            class="form-control @error('address') is-invalid @enderror"
                            placeholder="{{ __('Type the address in detail') }}">{{ old('address', $customer->address) }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- ID Card Image + Preview --}}
                    <div class="col-md-6">
                        <label for="id_card_image" class="form-label">{{ __('ID Card Image') }}</label>

                        @if(!empty($customer->id_card_image))
                            <div class="mb-2">
                                <small class="text-muted d-block mb-1">{{ __('Current Image:') }}</small>
                                <a href="{{ asset('storage/'.$customer->id_card_image) }}" target="_blank">
                                    <img src="{{ asset('storage/'.$customer->id_card_image) }}" alt="{{ __('ID Card Image') }}" class="rounded border" style="max-height: 140px; object-fit: cover;">
                                </a>
                                <div class="text-muted small mt-1">{{ __('Uploading a new image will replace the current one.') }}</div>
                            </div>
                        @endif

                        <input
                            type="file"
                            name="id_card_image"
                            id="id_card_image"
                            class="form-control @error('id_card_image') is-invalid @enderror"
                            accept="image/*"
                            aria-describedby="idCardHelp">
                        <div id="idCardHelp" class="form-text">{{ __('Allowed extensions: jpg/png/webp — size suitable less than 2MB.') }}</div>
                        @error('id_card_image') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <div class="mt-2 d-none" id="id-preview-wrap">
                            <small class="text-muted d-block mb-1">{{ __('New Image Preview:') }}</small>
                            <img id="id-preview" src="#" alt="{{ __('Image Preview') }}" class="rounded border" style="max-height: 140px; object-fit: cover;">
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="col-md-6">
                        <label for="notes" class="form-label">{{ __('Notes') }}</label>
                        <textarea
                            name="notes"
                            id="notes"
                            rows="3"
                            class="form-control @error('notes') is-invalid @enderror"
                            placeholder="{{ __('Any additional information about the customer') }}">{{ old('notes', $customer->notes) }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-outline-primary">
                        {{ __('Update') }}
                    </button>
                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('styles')

@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Preview of the new ID card image
    const input = document.getElementById('id_card_image');
    const wrap  = document.getElementById('id-preview-wrap');
    const img   = document.getElementById('id-preview');

    input?.addEventListener('change', function(){
        const file = this.files && this.files[0];
        if (!file) { wrap?.classList.add('d-none'); return; }
        const ok = /^image\//.test(file.type);
        if (!ok) { wrap?.classList.add('d-none'); return; }

        const reader = new FileReader();
        reader.onload = e => {
            img.src = e.target.result;
            wrap.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    });

    // Hide any alert after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(el => {
            el.style.transition = 'opacity .4s ease';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 400);
        });
    }, 5000);
});
</script>
@endpush
