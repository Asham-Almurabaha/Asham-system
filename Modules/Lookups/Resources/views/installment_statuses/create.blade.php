@extends('layouts.master')

@section('title', 'إضافة حالة قسط جديدة')

@section('content')

<div class="pagetitle">
    <h1>إضافة حالة قسط جديدة</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('Settings') }}</li>
            <li class="breadcrumb-item">{{ __('Installment Statuses') }}</li>
            <li class="breadcrumb-item active">{{ __('Create') }}</li>
        </ol>
    </nav>
</div><!-- End Page Title -->
<div class="col-lg-6">
    <div class="card">
        <div class="card-body p-20">
            <form action="{{ route('installment_statuses.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">اسم حالة القسط</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required autofocus>
                </div>

                <x-button.action type="submit" variant="success" :outline="true">
                    <i class="bi bi-check2-circle me-1"></i> @lang('app.Save')
                </x-button.action>
                <x-button.action href="{{ route('installment_statuses.index') }}" variant="secondary" :outline="true">@lang('app.Cancel')</x-button.action>
            </form>
        </div>
    </div>
</div>

@endsection
