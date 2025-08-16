@extends('layouts.master')
@section('title', __('setting.Settings'))

@section('content')
<div class="pagetitle">
    <h1>@lang('setting.Settings')</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">@lang('setting.Setting')</li>
            <li class="breadcrumb-item active">@lang('pages.List')</li>
        </ol>
    </nav>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('settings.create') }}" class="btn btn-success">
        @lang('pages.Add')
    </a>
</div>

@if($settings->isEmpty())
    <div class="alert alert-info">
        لا توجد إعدادات بعد، يرجى إضافة إعداد جديد.
    </div>
@else
    <section class="section profile">
        <div class="row g-3">
            @foreach($settings as $setting)
                <div class="col-12">
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
                                        <img src="{{ asset('storage/'.$setting->favicon) }}" style="width: 50px;height:50px" alt="favicon">
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <a href="{{ route('settings.show', $setting->id) }}" class="btn btn-primary">
                                    @lang('pages.Show')
                                </a>
                                <a href="{{ route('settings.edit', $setting->id) }}" class="btn btn-warning">
                                    @lang('pages.Update')
                                </a>
                                <form action="{{ route('settings.destroy', $setting->id) }}" method="POST"
                                      onsubmit="return confirm('هل أنت متأكد من حذف الإعداد؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">
                                        @lang('pages.Delete')
                                    </button>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
@endsection
