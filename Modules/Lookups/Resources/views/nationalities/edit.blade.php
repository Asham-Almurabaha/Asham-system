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

                    <x-button type="submit" variant="primary" :outline="true">
                        <i class="bi bi-save2 me-1"></i> تحديث
                    </x-button>
                    <x-button href="{{ route('nationalities.index') }}" variant="secondary" :outline="true">@lang('app.Cancel')</x-button>
                </form>
            </div>
        </div>
    </div>

@endsection
