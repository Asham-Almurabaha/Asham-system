@extends('layouts.master')

@section('title', __('guarantors::messages.Add New Guarantor'))

@section('content')
<div class="container py-3" dir="rtl">

    <div class="pagetitle">
        <h1 class="h3 mb-1">{{ __('guarantors::messages.Add New Guarantor') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">{{ __('guarantors::messages.Guarantors') }}</li>
                <li class="breadcrumb-item active">{{ __('guarantors::messages.Add Guarantor') }}</li>
            </ol>
        </nav>
    </div>

    {{-- تنبيهات التحقق العامة --}}
    @if ($errors->any())
        <div class="alert alert-danger shadow-sm">
            {{ __('guarantors::messages.There are some errors, please review the highlighted fields below.') }}
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('guarantors.store') }}" method="POST" enctype="multipart/form-data" novalidate>
                @csrf

                <div class="row g-3">
                    {{-- الاسم --}}
                    <div class="col-12">
                        <label for="name" class="form-label">{{ __('guarantors::messages.Name') }} <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}"
                            required
                            autofocus
                            maxlength="190"
                            autocomplete="name"
                            placeholder="{{ __('guarantors::messages.Write the full name') }}">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- رقم الهوية --}}
                    <div class="col-md-6">
                        <label for="national_id" class="form-label">{{ __('guarantors::messages.National ID Number') }}</label>
                        <input
                            type="text"
                            name="national_id"
                            id="national_id"
                            class="form-control @error('national_id') is-invalid @enderror"
                            value="{{ old('national_id') }}"
                            inputmode="numeric"
                            dir="ltr"
                            maxlength="20"
                            placeholder="{{ __('guarantors::messages.Example: 1234567890') }}">
                        <div class="form-text">{{ __('guarantors::messages.Only numbers can be entered.') }}</div>
                        @error('national_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- الهاتف --}}
                    <div class="col-md-6">
                        <label for="phone" class="form-label">{{ __('guarantors::messages.Phone') }}</label>
                        <input
                            type="text"
                            name="phone"
                            id="phone"
                            class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone') }}"
                            inputmode="tel"
                            dir="ltr"
                            maxlength="25"
                            autocomplete="tel"
                            placeholder="{{ __('guarantors::messages.+9665XXXXXXXX') }}">
                        <div class="form-text">{{ __('guarantors::messages.It is preferable to enter the international code.') }}</div>
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- البريد --}}
                    <div class="col-md-6">
                        <label for="email" class="form-label">{{ __('guarantors::messages.Email Address') }}</label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}"
                            maxlength="190"
                            autocomplete="email"
                            placeholder="{{ __('guarantors::messages.name@email.com') }}">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- الجنسية --}}
                    <div class="col-md-6">
                        <label for="nationality_id" class="form-label">{{ __('guarantors::messages.Nationality') }}</label>
                        <select
                            name="nationality_id"
                            id="nationality_id"
                            class="form-select @error('nationality_id') is-invalid @enderror">
                            <option value="">-- {{ __('guarantors::messages.Choose') }} --</option>
                            @foreach (($nationalities ?? []) as $Nationality)
                                @if(is_object($Nationality))
                                    <option value="{{ $Nationality->id }}" @selected(old('nationality_id') == $Nationality->id)>{{ $Nationality->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('nationality_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- الوظيفة --}}
                    <div class="col-md-6">
                        <label for="title_id" class="form-label">{{ __('guarantors::messages.Job Title') }}</label>
                        <select
                            name="title_id"
                            id="title_id"
                            class="form-select @error('title_id') is-invalid @enderror">
                            <option value="">-- {{ __('guarantors::messages.Choose') }} --</option>
                            @foreach (($titles ?? []) as $title)
                                @if(is_object($title))
                                    <option value="{{ $title->id }}" @selected(old('title_id') == $title->id)>{{ $title->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('title_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- حالة الكفيل --}}
                    <div class="col-md-6">
                        <label for="guarantor_status_id" class="form-label">{{ __('guarantors::messages.Guarantor Status') }}</label>
                        <select
                            name="guarantor_status_id"
                            id="guarantor_status_id"
                            class="form-select @error('guarantor_status_id') is-invalid @enderror">
                            <option value="">-- {{ __('guarantors::messages.Choose') }} --</option>
                            @foreach (($guarantorStatuses ?? []) as $status)
                                @if(is_object($status))
                                    <option value="{{ $status->id }}" @selected(old('guarantor_status_id') == $status->id)>{{ $status->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('guarantor_status_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- العنوان --}}
                    <div class="col-12">
                        <label for="address" class="form-label">{{ __('guarantors::messages.Address') }}</label>
                        <textarea
                            name="address"
                            id="address"
                            rows="3"
                            class="form-control @error('address') is-invalid @enderror"
                            placeholder="{{ __('guarantors::messages.Write the address in detail') }}">{{ old('address') }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- صورة الهوية + معاينة --}}
                    <div class="col-md-6">
                        <label for="id_card_image" class="form-label">{{ __('guarantors::messages.ID Card Image') }}</label>
                        <input
                            type="file"
                            name="id_card_image"
                            id="id_card_image"
                            class="form-control @error('id_card_image') is-invalid @enderror"
                            accept="image/*"
                            aria-describedby="idCardHelp">
                        <div id="idCardHelp" class="form-text">{{ __('guarantors::messages.Allowed extensions: jpg/png/webp — suitable size less than 2MB.') }}</div>
                        @error('id_card_image') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <div class="mt-2 d-none" id="id-preview-wrap">
                            <small class="text-muted d-block mb-1">{{ __('guarantors::messages.Preview:') }}</small>
                            <img id="id-preview" src="#" alt="{{ __('guarantors::messages.Preview of the new image') }}" class="rounded border" style="max-height: 140px; object-fit: cover;">
                        </div>
                    </div>

                    {{-- ملاحظات --}}
                    <div class="col-md-6">
                        <label for="notes" class="form-label">{{ __('guarantors::messages.Notes') }}</label>
                        <textarea
                            name="notes"
                            id="notes"
                            rows="3"
                            class="form-control @error('notes') is-invalid @enderror"
                            placeholder="{{ __('guarantors::messages.Any additional information about the guarantor') }}">{{ old('notes') }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <x-button type="submit" variant="success" :outline="true">
                        <i class="bi bi-check2-circle me-1"></i> {{ __('guarantors::messages.Save') }}
                    </x-button>
                    <x-button href="{{ route('guarantors.index') }}" variant="secondary" :outline="true">
                        {{ __('guarantors::messages.Cancel') }}
                    </x-button>
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
    // معاينة صورة الهوية قبل الرفع
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

    // إخفاء أي تنبيه بعد 5 ثوانٍ
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

