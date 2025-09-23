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
            <form id="create-guarantor-form" action="{{ route('guarantors.store') }}" method="POST" enctype="multipart/form-data" novalidate>
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
                        <label for="national_id" class="form-label">{{ __('guarantors::messages.National ID Number') }} <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            name="national_id"
                            id="national_id"
                            class="form-control @error('national_id') is-invalid @enderror"
                            value="{{ old('national_id') }}"
                            inputmode="numeric"
                            dir="ltr"
                            maxlength="10"
                            pattern="^[12]\d{9}$"
                            required
                            aria-required="true"
                            placeholder="{{ __('guarantors::messages.Example: 1234567890') }}">
                        <div class="form-text">{{ __('guarantors::messages.Only numbers can be entered.') }}</div>
                        @php $nationalIdError = $errors->first('national_id'); @endphp
                        <div class="invalid-feedback" data-feedback-for="national_id" @if($nationalIdError) data-server-error="true" @endif>{{ $nationalIdError }}</div>
                    </div>

                    {{-- الهاتف --}}
                    <div class="col-md-6">
                        <label for="phone" class="form-label">{{ __('guarantors::messages.Phone') }} <span class="text-danger">*</span></label>
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
                            pattern="^(?:05\d{8}|\+?9665\d{8}|009665\d{8})$"
                            required
                            aria-required="true"
                            placeholder="{{ __('guarantors::messages.+9665XXXXXXXX') }}">
                        <div class="form-text">{{ __('guarantors::messages.It is preferable to enter the international code.') }}</div>
                        @php $phoneError = $errors->first('phone'); @endphp
                        <div class="invalid-feedback" data-feedback-for="phone" @if($phoneError) data-server-error="true" @endif>{{ $phoneError }}</div>
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

                    {{-- العنوان --}}
                    <div class="col-12">
                        <label for="address" class="form-label">{{ __('guarantors::messages.Address') }}</label>
                        <textarea
                            name="address"
                            id="address"
                            rows="1"
                            class="form-control single-line-textarea @error('address') is-invalid @enderror"
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
                            rows="1"
                            class="form-control single-line-textarea @error('notes') is-invalid @enderror"
                            placeholder="{{ __('guarantors::messages.Any additional information about the guarantor') }}">{{ old('notes') }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <x-button.action type="submit" variant="success" :outline="true">
                        <i class="bi bi-check2-circle me-1"></i> {{ __('guarantors::messages.Save') }}
                    </x-button.action>
                    <x-button.action href="{{ route('guarantors.index') }}" variant="secondary" :outline="true">
                        {{ __('guarantors::messages.Cancel') }}
                    </x-button.action>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    .single-line-textarea {
        height: calc(1.5em + .75rem + 2px);
        min-height: calc(1.5em + .75rem + 2px);
        resize: vertical;
    }
</style>
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

    const form = document.getElementById('create-guarantor-form');
    if (form) {
        const nationalInput = form.querySelector('#national_id');
        const phoneInput    = form.querySelector('#phone');
        const nationalPattern = /^[12]\d{9}$/;
        const phonePattern    = /^(?:05\d{8}|\+?9665\d{8}|009665\d{8})$/;
        const messages = {
            nationalRequired: @json(__('guarantors::messages.validation.national_id_required')),
            nationalFormat: @json(__('guarantors::messages.validation.national_id_format')),
            phoneRequired: @json(__('guarantors::messages.validation.phone_required')),
            phoneFormat: @json(__('guarantors::messages.validation.phone_format')),
        };

        const getFeedback = (inputEl) => (inputEl ? form.querySelector(`[data-feedback-for="${inputEl.id}"]`) : null);

        const clearError = (inputEl) => {
            if (!inputEl) { return true; }
            const feedback = getFeedback(inputEl);
            if (feedback) {
                feedback.textContent = '';
                feedback.removeAttribute('data-server-error');
            }
            inputEl.classList.remove('is-invalid');
            inputEl.setCustomValidity('');
            return true;
        };

        const applyError = (inputEl, message) => {
            if (!inputEl) { return false; }
            const feedback = getFeedback(inputEl);
            if (feedback) {
                feedback.textContent = message;
                feedback.removeAttribute('data-server-error');
            }
            inputEl.classList.add('is-invalid');
            inputEl.setCustomValidity(message);
            return false;
        };

        const checkField = (inputEl, pattern, requiredMsg, formatMsg) => {
            if (!inputEl) { return true; }
            const value = inputEl.value.trim();
            if (!value) {
                return applyError(inputEl, requiredMsg);
            }
            if (!pattern.test(value)) {
                return applyError(inputEl, formatMsg);
            }
            return clearError(inputEl);
        };

        const handleInput = (inputEl, pattern, formatMsg) => {
            if (!inputEl) { return; }
            inputEl.addEventListener('input', () => {
                const feedback = getFeedback(inputEl);
                if (feedback) {
                    feedback.removeAttribute('data-server-error');
                }
                inputEl.setCustomValidity('');

                if (!inputEl.value.trim()) {
                    inputEl.classList.remove('is-invalid');
                    if (feedback) {
                        feedback.textContent = '';
                    }
                    return;
                }

                if (pattern.test(inputEl.value.trim())) {
                    clearError(inputEl);
                } else if (inputEl.classList.contains('is-invalid') && feedback) {
                    feedback.textContent = formatMsg;
                }
            });
        };

        handleInput(nationalInput, nationalPattern, messages.nationalFormat);
        handleInput(phoneInput, phonePattern, messages.phoneFormat);

        form.addEventListener('submit', (event) => {
            const nationalOk = checkField(nationalInput, nationalPattern, messages.nationalRequired, messages.nationalFormat);
            const phoneOk    = checkField(phoneInput, phonePattern, messages.phoneRequired, messages.phoneFormat);

            if (!nationalOk || !phoneOk) {
                event.preventDefault();
                event.stopPropagation();
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                    if (typeof firstInvalid.reportValidity === 'function') {
                        firstInvalid.reportValidity();
                    }
                }
            }
        });
    }

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

