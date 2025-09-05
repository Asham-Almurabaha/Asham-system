@extends('layouts.master')

@section('title', __('Edit Investor'))

@section('content')
<div class="container py-3" dir="rtl">

    <div class="pagetitle">
        <h1 class="h3 mb-1">{{ __("Edit Investor") }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">{{ __('Investors') }}</li>
                <li class="breadcrumb-item active">{{ __('Edit Investor') }}</li>
            </ol>
        </nav>
    </div>

    {{-- تنبيهات التحقق العامة --}}
    @if ($errors->any())
        <div class="alert alert-danger shadow-sm">{{ __("There are validation errors. Please check the fields.") }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('investors.update', $investor->id) }}" method="POST" enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')

                <div class="row g-3">
                    {{-- الاسم --}}
                    <div class="col-12">
                        <label for="name" class="form-label">{{ __('investors.Name') }} <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $investor->name) }}"
                            required
                            autofocus
                            maxlength="190"
                            autocomplete="name"
                            placeholder="{{ __('investors.Write the full name') }}">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- رقم الهوية --}}
                    <div class="col-md-6">
                        <label for="national_id" class="form-label">{{ __('investors.National ID') }}</label>
                        <input
                            type="text"
                            name="national_id"
                            id="national_id"
                            class="form-control @error('national_id') is-invalid @enderror"
                            value="{{ old('national_id', $investor->national_id) }}"
                            inputmode="numeric"
                            dir="ltr"
                            maxlength="20"
                            placeholder="{{ __('investors.Example: 1234567890') }}">
                        <div class="form-text">{{ __('investors.Only numbers can be entered.') }}</div>
                        @error('national_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- الهاتف --}}
                    <div class="col-md-6">
                        <label for="phone" class="form-label">{{ __('investors.Phone') }}</label>
                        <input
                            type="text"
                            name="phone"
                            id="phone"
                            class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone', $investor->phone) }}"
                            inputmode="tel"
                            dir="ltr"
                            maxlength="25"
                            autocomplete="tel"
                            placeholder="{{ __('investors.+9665XXXXXXXX') }}">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- البريد --}}
                    <div class="col-md-6">
                        <label for="email" class="form-label">{{ __('investors.Email') }}</label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $investor->email) }}"
                            maxlength="190"
                            autocomplete="email"
                            placeholder="{{ __('name@email.com') }}">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- الجنسية --}}
                    <div class="col-md-6">
                        <label for="nationality_id" class="form-label">{{ __("Nationality") }}</label>
                        <select
                            name="nationality_id"
                            id="nationality_id"
                            class="form-select @error('nationality_id') is-invalid @enderror">
                            <option value="">{{ __("-- Choose --") }}</option>
                            @foreach (($nationalities ?? []) as $row)
                                @php
                                    $nid   = is_object($row) ? $row->id   : (is_array($row) ? ($row['id'] ?? null)   : null);
                                    $nname = is_object($row) ? $row->name : (is_array($row) ? ($row['name'] ?? null) : null);
                                @endphp
                                @if($nid && $nname)
                                    <option value="{{ $nid }}" @selected(old('nationality_id', $investor->nationality_id) == $nid)>{{ $nname }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('nationality_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- الوظيفة --}}
                    <div class="col-md-6">
                        <label for="title_id" class="form-label">{{ __("Title") }}</label>
                        <select
                            name="title_id"
                            id="title_id"
                            class="form-select @error('title_id') is-invalid @enderror">
                            <option value="">{{ __("-- Choose --") }}</option>
                            @foreach (($titles ?? []) as $row)
                                @php
                                    $tid   = is_object($row) ? $row->id   : (is_array($row) ? ($row['id'] ?? null)   : null);
                                    $tname = is_object($row) ? $row->name : (is_array($row) ? ($row['name'] ?? null) : null);
                                @endphp
                                @if($tid && $tname)
                                    <option value="{{ $tid }}" @selected(old('title_id', $investor->title_id) == $tid)>{{ $tname }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('title_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- العنوان --}}
                    <div class="col-12">
                        <label for="address" class="form-label">{{ __('investors.Address') }}</label>
                        <textarea
                            name="address"
                            id="address"
                            rows="3"
                            class="form-control @error('address') is-invalid @enderror"
                            placeholder="اكتب العنوان بالتفصيل">{{ old('address', $investor->address) }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- صورة الهوية (الحالية + الجديدة) --}}
                    <div class="col-md-6">
                        <label class="form-label">صورة الهوية</label>

                        @if(!empty($investor->id_card_image))
                            <div class="mb-2">
                                <small class="text-muted d-block mb-1">{{ __('Current Image:') }}</small>
                                <a href="{{ asset('storage/'.$investor->id_card_image) }}" target="_blank">
                                    <img src="{{ asset('storage/'.$investor->id_card_image) }}" alt="صورة الهوية" class="rounded border" style="max-height: 140px; object-fit: cover;">
                                </a>
                                <div class="text-muted small mt-1">رفع صورة جديدة سيستبدل الحالية.</div>
                            </div>
                        @endif

                        <input
                            type="file"
                            name="id_card_image"
                            id="id_card_image"
                            class="form-control @error('id_card_image') is-invalid @enderror"
                            accept="image/*">
                        <div class="form-text">{{ __("Allowed extensions: jpg/png/webp — size suitable less than 2MB.") }}</div>
                        @error('id_card_image') <div class="invalid-feedback">{{ $message }}</div> @enderror>

                        <div class="mt-2 d-none" id="id-preview-wrap">
                            <small class="text-muted d-block mb-1">{{ __('investors.Preview after upload:') }}</small>
                            <img id="id-preview" src="#" alt="معاينة صورة الهوية" class="rounded border" style="max-height: 140px; object-fit: cover;">
                        </div>
                    </div>

                    {{-- صورة العقد (الحالية + الجديدة) --}}
                    <div class="col-md-6">
                        <label class="form-label">صورة العقد</label>

                        @if(!empty($investor->contract_image))
                            <div class="mb-2">
                                <small class="text-muted d-block mb-1">{{ __('Current Image:') }}</small>
                                <a href="{{ asset('storage/'.$investor->contract_image) }}" target="_blank">
                                    <img src="{{ asset('storage/'.$investor->contract_image) }}" alt="صورة العقد" class="rounded border" style="max-height: 140px; object-fit: cover;">
                                </a>
                                <div class="text-muted small mt-1">رفع صورة جديدة سيستبدل الحالية.</div>
                            </div>
                        @endif

                        <input
                            type="file"
                            name="contract_image"
                            id="contract_image"
                            class="form-control @error('contract_image') is-invalid @enderror"
                            accept="image/*">
                        <div class="form-text">{{ __("Allowed extensions: jpg/png/webp — size suitable less than 2MB.") }}</div>
                        @error('contract_image') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <div class="mt-2 d-none" id="contract-preview-wrap">
                            <small class="text-muted d-block mb-1">{{ __('investors.Preview after upload:') }}</small>
                            <img id="contract-preview" src="#" alt="معاينة صورة العقد" class="rounded border" style="max-height: 140px; object-fit: cover;">
                        </div>
                    </div>

                    {{-- نسبة حصة المكتب --}}
                    <div class="col-md-6">
                        <label for="office_share_percentage" class="form-label">{{ __("Office Share %") }}</label>
                        <input
                            type="number"
                            name="office_share_percentage"
                            id="office_share_percentage"
                            class="form-control @error('office_share_percentage') is-invalid @enderror"
                            value="{{ old('office_share_percentage', number_format((float)$investor->office_share_percentage, 2, '.', '')) }}"
                            min="0" max="100" step="0.01" inputmode="decimal" dir="ltr"
                            placeholder="{{ __("Example: 12.50") }}">
                        <div class="form-text">{{ __("Value between 0 and 100.") }}</div>
                        @error('office_share_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-save2 me-1"></i> {{ __('Update') }}
                    </button>
                    <a href="{{ route('investors.index') }}" class="btn btn-outline-secondary">
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
    // معاينات الصور الجديدة
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

    // ضبط النسبة بين 0 و 100 مع تقريب خانتين
    const pct = document.getElementById('office_share_percentage');
    pct?.addEventListener('change', () => {
        let v = parseFloat(pct.value || '0');
        if (isNaN(v)) v = 0;
        v = Math.min(100, Math.max(0, v));
        pct.value = v.toFixed(2);
    });

    // إخفاء أي alert تلقائياً
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



