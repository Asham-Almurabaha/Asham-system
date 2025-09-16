@extends('layouts.master')

@section('title', 'تعديل حالة القسط')

@section('content')

<div class="pagetitle">
    <h1>تعديل حالة القسط</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('Settings') }}</li>
            <li class="breadcrumb-item">{{ __('Installment Statuses') }}</li>
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
            <form action="{{ route('installment_statuses.update', $installmentStatus->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">اسم حالة القسط</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $installmentStatus->name) }}" required autofocus>
                </div>

                <button type="submit" class="btn btn-outline-primary">@lang('app.Update')</button>
                <a href="{{ route('installment_statuses.index') }}" class="btn btn-outline-secondary">@lang('app.Cancel')</a>
            </form>
        </div>
    </div>
</div>

@endsection
