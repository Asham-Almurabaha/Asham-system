@extends('layouts.master')

@section('title', __('setting.Edit Setting'))

@section('content')

<div class="pagetitle">
    <h1>@lang('setting.Edit Setting')</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">@lang('setting.Setting')</li>
            <li class="breadcrumb-item active">@lang('setting.Edit Setting')</li>
        </ol>
    </nav>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form action="{{ route('settings.update', $setting->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label>@lang('pages.EN Name')</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $setting->name) }}">
        @error('name')<div class="text-danger">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label>@lang('pages.AR Name')</label>
        <input type="text" name="name_ar" class="form-control" value="{{ old('name_ar', $setting->name_ar) }}">
        @error('name_ar')<div class="text-danger">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label>@lang('setting.Logo')</label><br>
        @if ($setting->logo)
            <img src="{{ asset('storage/'.$setting->logo) }}" style="width: 100px" class="mb-2"><br>
        @endif
        <input type="file" name="logo" class="form-control">
        @error('logo')<div class="text-danger">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label>@lang('setting.Icon')</label><br>
        @if ($setting->favicon)
            <img src="{{ asset('storage/'.$setting->favicon) }}" style="width: 50px" class="mb-2"><br>
        @endif
        <input type="file" name="favicon" class="form-control">
        @error('favicon')<div class="text-danger">{{ $message }}</div>@enderror
    </div>

    <button type="submit" class="btn btn-primary">@lang('pages.Update')</button>
    <a href="{{ route('settings.index') }}" class="btn btn-secondary">@lang('pages.Cancel')</a>
</form>

@endsection
