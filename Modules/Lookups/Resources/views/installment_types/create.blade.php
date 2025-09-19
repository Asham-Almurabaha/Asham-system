@extends('layouts.master')

@section('title', 'إضافة نوع قسط جديد')

@section('content')

<div class="pagetitle">
    <h1>إضافة نوع قسط جديد</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('Settings') }}</li>
            <li class="breadcrumb-item">{{ __('Installment Types') }}</li>
            <li class="breadcrumb-item active">{{ __('Create') }}</li>
        </ol>
    </nav>
</div><!-- End Page Title -->
<div class="col-lg-6">
    <div class="card">
        <div class="card-body p-20">
            <form action="{{ route('installment_types.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">اسم نوع القسط</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required autofocus>
                </div>

                <button type="submit" class="btn btn-outline-success">@lang('app.Save')</button>
                <a href="{{ route('installment_types.index') }}" class="btn btn-outline-secondary">@lang('app.Cancel')</a>
            </form>
        </div>
    </div>
</div>

@endsection
