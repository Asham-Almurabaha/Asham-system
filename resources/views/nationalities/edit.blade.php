@extends('layouts.master')

@section('title', 'تعديل الجنسية')

@section('content')

    <div class="pagetitle">
        <h1>تعديل الجنسية</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">{{ __('Settings') }}</li>
                <li class="breadcrumb-item">{{ __('Nationalities') }}</li>
                <li class="breadcrumb-item active">{{ __('Edit') }}</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="col-lg-6">
        <div class="card">
            <div class="card-body p-20">
                <form action="{{ route('nationalities.update', $nationality->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">اسم الجنسية</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $nationality->name) }}" required autofocus>
                    </div>

                    <button type="submit" class="btn btn-primary">تحديث</button>
                    <a href="{{ route('nationalities.index') }}" class="btn btn-outline-secondary">@lang('app.Cancel')</a>
                </form>
            </div>
        </div>
    </div>

@endsection
