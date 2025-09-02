@extends('layouts.master')

@section('title', __('transaction_types.Add Transaction Type'))

@section('content')

<div class="pagetitle">
    <h1>@lang('transaction_types.Add Transaction Type')</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">@lang('sidebar.Settings')</li>
            <li class="breadcrumb-item">@lang('sidebar.Transaction Types')</li>
            <li class="breadcrumb-item active">@lang('transaction_types.Add Transaction Type')</li>
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
            <form action="{{ route('transaction_types.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">@lang('transaction_types.Transaction Type Name')</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">@lang('transaction_types.Description (Optional)')</label>
                    <textarea name="description" id="description" class="form-control">{{ old('description') }}</textarea>
                </div>

                <button type="submit" class="btn btn-outline-success">@lang('transaction_types.Save')</button>
                <a href="{{ route('transaction_types.index') }}" class="btn btn-outline-secondary">@lang('transaction_types.Cancel')</a>
            </form>
        </div>
    </div>
</div>

@endsection
