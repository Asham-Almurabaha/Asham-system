@extends('layouts.master')

@section('title', __('setting.Setting Details'))

@section('content')

<div class="pagetitle">
    <h1>@lang('setting.Setting Details')</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">@lang('setting.Setting')</li>
            <li class="breadcrumb-item active">@lang('setting.Setting Details')</li>
        </ol>
    </nav>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($setting)
    <section class="section profile">
        <div class="card">
            <div class="card-body pt-3">

                <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 label">@lang('pages.EN Name')</div>
                    <div class="col-lg-9 col-md-8">{{ $setting->name }}</div>
                </div>

                <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 label">@lang('pages.AR Name')</div>
                    <div class="col-lg-9 col-md-8">{{ $setting->name_ar }}</div>
                </div>

                <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 label">@lang('setting.Logo')</div>
                    <div class="col-lg-9 col-md-8">
                        @if ($setting->logo)
                            <img src="{{ asset('storage/'.$setting->logo) }}" style="width: 100px" alt="logo">
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 label">@lang('setting.Icon')</div>
                    <div class="col-lg-9 col-md-8">
                        @if ($setting->favicon)
                            <img src="{{ asset('storage/'.$setting->favicon) }}" style="width: 50px" alt="favicon">
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <a href="{{ route('settings.edit', $setting->id) }}" class="btn btn-warning">
                        @lang('pages.Update')
                    </a>

                    <form action="{{ route('settings.destroy', $setting->id) }}" method="POST" onsubmit="return confirm('@lang('app.Confirm Delete')');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            @lang('pages.Delete')
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </section>
@else
    <div class="alert alert-info">
        لا توجد إعدادات بعد، يرجى إضافة إعداد جديد.
    </div>

    <a href="{{ route('settings.create') }}" class="btn btn-success">
        @lang('pages.Add')
    </a>
@endif

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

@endsection
