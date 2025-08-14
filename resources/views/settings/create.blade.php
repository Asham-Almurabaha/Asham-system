@extends('layouts.master')

@section('title', __('setting.Add New Setting'))

@section('content')

<div class="pagetitle">
    <h1>@lang('setting.Add New Setting')</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">@lang('setting.Setting')</li>
            <li class="breadcrumb-item active">@lang('setting.Add New Setting')</li>
        </ol>
    </nav>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('settings.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="mb-3">
        <label for="name" class="form-label">@lang('pages.EN Name')</label>
        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
    </div>

    <div class="mb-3">
        <label for="name_ar" class="form-label">@lang('pages.AR Name')</label>
        <input type="text" class="form-control" id="name_ar" name="name_ar" value="{{ old('name_ar') }}" required>
    </div>

    <div class="mb-3">
        <label for="logo" class="form-label">@lang('setting.Logo')</label>
        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
    </div>

    <div class="mb-3">
        <label for="favicon" class="form-label">@lang('setting.Icon')</label>
        <input type="file" class="form-control" id="favicon" name="favicon" accept="image/*">
    </div>

    <button type="submit" class="btn btn-primary">@lang('pages.Save')</button>
    <a href="{{ route('settings.index') }}" class="btn btn-secondary">@lang('pages.Cancel')</a>
</form>

@endsection
