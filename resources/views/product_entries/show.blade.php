@extends('layouts.master')

@section('title', __('setting.Setting Details'))

@section('content')
<div class="container-fluid py-3">
    <div class="pagetitle mb-3">
        <h1>@lang('setting.Setting Details')</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">@lang('setting.Setting')</li>
                <li class="breadcrumb-item active">@lang('setting.Setting Details')</li>
            </ol>
        </nav>
    </div>

    @if ($setting)
        <div class="row justify-content-center">
            <div class="col-12 col-xl-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body pt-4">
                        <div class="row gy-3 align-items-center">
                            <div class="col-lg-3 col-md-4 text-muted fw-semibold">@lang('pages.EN Name')</div>
                            <div class="col-lg-9 col-md-8">{{ $setting->name }}</div>

                            <div class="col-lg-3 col-md-4 text-muted fw-semibold">@lang('pages.AR Name')</div>
                            <div class="col-lg-9 col-md-8">{{ $setting->name_ar }}</div>

                            <div class="col-lg-3 col-md-4 text-muted fw-semibold">@lang('setting.Logo')</div>
                            <div class="col-lg-9 col-md-8">
                                @if ($setting->logo)
                                    <img src="{{ asset('storage/'.$setting->logo) }}" alt="logo" class="img-fluid" style="max-width: 120px;">
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>

                            <div class="col-lg-3 col-md-4 text-muted fw-semibold">@lang('setting.Icon')</div>
                            <div class="col-lg-9 col-md-8">
                                @if ($setting->favicon)
                                    <img src="{{ asset('storage/'.$setting->favicon) }}" alt="favicon" class="img-fluid" style="max-width: 70px;">
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-sm-flex justify-content-sm-end mt-4">
                            <x-update-button href="{{ route('settings.edit', $setting->id) }}">
                                @lang('pages.Update')
                            </x-update-button>
                            <form action="{{ route('settings.destroy', $setting->id) }}" method="POST" class="m-0"
                                  onsubmit="return confirm('{{ __('app.Confirm Delete') }}');">
                                @csrf
                                @method('DELETE')
                                <x-delete-button type="submit">
                                    @lang('pages.Delete')
                                </x-delete-button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <p class="mb-3">لا توجد إعدادات بعد، يرجى إضافة إعداد جديد.</p>
                <x-add-button href="{{ route('settings.create') }}" variant="success">
                    @lang('pages.Add')
                </x-add-button>
            </div>
        </div>
    @endif
</div>
@endsection

@section('js')
<script>
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
</script>
@endsection
