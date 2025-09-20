@extends('layouts.master')

@section('title', __('investors::investors.Add Investor'))

@section('content')
<div class="container py-3" dir="rtl">

    <div class="pagetitle">
        <h1 class="h3 mb-1">{{ __('investors::investors.Add Investor') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">@lang('investors::investors.Investors')</li>
                <li class="breadcrumb-item active">@lang('investors::investors.Add Investor')</li>
            </ol>
        </nav>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger shadow-sm">{{ __('investors::investors.Validation errors message') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('investors.store') }}" method="POST" enctype="multipart/form-data" novalidate>
                @csrf

                <div class="row g-3">
                    <div class="col-12">
                        <label for="name" class="form-label">{{ __('investors::investors.Name') }} <span class="text-danger">*</span></label>
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
                            placeholder="{{ __('investors::investors.Write the full name') }}">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="national_id" class="form-label">{{ __('investors::investors.National ID') }}</label>
                        <input
                            type="text"
                            name="national_id"
                            id="national_id"
                            class="form-control @error('national_id') is-invalid @enderror"
                            value="{{ old('national_id') }}"
                            inputmode="numeric"
                            dir="ltr"
                            maxlength="20"
                            placeholder="{{ __('investors::investors.Example: 1234567890') }}">
                        <div class="form-text">{{ __('investors::investors.Only numbers can be entered.') }}</div>
                        @error('national_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label">{{ __('investors::investors.Phone') }}</label>
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
                            placeholder="{{ __('investors::investors.+9665XXXXXXXX') }}">
                        <div class="form-text">{{ __('investors::investors.It is preferable to enter the international code.') }}</div>
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">{{ __('investors::investors.Email') }}</label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}"
                            maxlength="190"
                            autocomplete="email"
                            placeholder="{{ __('name@email.com') }}">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="nationality_id" class="form-label">{{ __('investors::investors.Nationality') }}</label>
                        <select
                            name="nationality_id"
                            id="nationality_id"
                            class="form-select @error('nationality_id') is-invalid @enderror">
                            <option value="">{{ __('-- Choose --') }}</option>
                            @foreach (($nationalities ?? []) as $Nationality)
                                @if(is_object($Nationality))
                                    <option value="{{ $Nationality->id }}" @selected(old('nationality_id') == $Nationality->id)>{{ $Nationality->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('nationality_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="title_id" class="form-label">{{ __('investors::investors.Job Title') }}</label>
                        <select
                            name="title_id"
                            id="title_id"
                            class="form-select @error('title_id') is-invalid @enderror">
                            <option value="">{{ __('-- Choose --') }}</option>
                            @foreach (($titles ?? []) as $title)
                                @if(is_object($title))
                                    <option value="{{ $title->id }}" @selected(old('title_id') == $title->id)>{{ $title->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('title_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label for="address" class="form-label">{{ __('investors::investors.Address') }}</label>
                        <textarea
                            name="address"
                            id="address"
                            rows="3"
                            class="form-control @error('address') is-invalid @enderror"
                            placeholder="{{ __('investors::investors.Write the address in detail') }}">{{ old('address') }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="id_card_image" class="form-label">{{ __('investors::investors.ID Card Image') }}</label>
                        <input
                            type="file"
                            name="id_card_image"
                            id="id_card_image"
                            class="form-control @error('id_card_image') is-invalid @enderror"
                            accept="image/*">
                        @error('id_card_image') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <div class="mt-2 d-none" id="id-preview-wrap">
                            <small class="text-muted d-block mb-1">{{ __('investors::investors.Preview after upload:') }}</small>
                            <img id="id-preview" src="#" alt="{{ __('investors::investors.ID Card Image') }}" class="rounded border" style="max-height: 140px; object-fit: cover;">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="contract_image" class="form-label">{{ __('investors::investors.Contract Image') }}</label>
                        <input
                            type="file"
                            name="contract_image"
                            id="contract_image"
                            class="form-control @error('contract_image') is-invalid @enderror"
                            accept="image/*">
                        @error('contract_image') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <div class="mt-2 d-none" id="contract-preview-wrap">
                            <small class="text-muted d-block mb-1">{{ __('investors::investors.Preview after upload:') }}</small>
                            <img id="contract-preview" src="#" alt="{{ __('investors::investors.Contract Image') }}" class="rounded border" style="max-height: 140px; object-fit: cover;">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="investment_start_date" class="form-label">{{ __('investors::investors.Investment Start Date') }}</label>
                        <input
                            type="date"
                            name="investment_start_date"
                            id="investment_start_date"
                            class="form-control js-date @error('investment_start_date') is-invalid @enderror"
                            placeholder="{{ __('YYYY-MM-DD') }}"
                            value="{{ old('investment_start_date') }}"
                            dir="ltr">
                        @error('investment_start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="office_share_percentage" class="form-label">{{ __('investors::investors.Office Share %') }} (%)</label>
                        <input
                            type="number"
                            name="office_share_percentage"
                            id="office_share_percentage"
                            class="form-control @error('office_share_percentage') is-invalid @enderror"
                            value="{{ old('office_share_percentage', '0') }}"
                            min="0" max="100" step="0.01" inputmode="decimal" dir="ltr"
                            placeholder="{{ __('investors::investors.Example: 12.50') }}">
                        <div class="form-text">{{ __('investors::investors.The value is between 0 and 100.') }}</div>
                        @error('office_share_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <x-button.action type="submit" variant="success" :outline="true">
                        <i class="bi bi-check2-circle me-1"></i> @lang('app.Save')
                    </x-button.action>
                    <x-button.action href="{{ route('investors.index') }}" variant="secondary" :outline="true">
                        @lang('app.Cancel')
                    </x-button.action>
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
    function bindPreview(inputId, wrapId, imgId){
        const input = document.getElementById(inputId);
        const wrap  = document.getElementById(wrapId);
        const img   = document.getElementById(imgId);
        input?.addEventListener('change', function(){
            const file = this.files && this.files[0];
            if (!file || !/^image\//.test(file.type)) { wrap?.classList.add('d-none'); return; }
            const reader = new FileReader();
            reader.onload = e => { img.src = e.target.result; wrap.classList.remove('d-none'); };
            reader.readAsDataURL(file);
        });
    }
    bindPreview('id_card_image','id-preview-wrap','id-preview');
    bindPreview('contract_image','contract-preview-wrap','contract-preview');

    const pct = document.getElementById('office_share_percentage');
    pct?.addEventListener('change', () => {
        let v = parseFloat(pct.value || '0');
        if (isNaN(v)) v = 0;
        v = Math.min(100, Math.max(0, v));
        pct.value = v.toFixed(2);
    });

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



