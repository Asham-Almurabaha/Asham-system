@extends('layouts.master')

@section('title', __('debts::messages.edit_title'))

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ __('debts::messages.edit_title') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">@lang('sidebar.Accounts')</li>
            <li class="breadcrumb-item"><a href="{{ route('debts.index') }}">{{ __('debts::messages.page_title') }}</a></li>
            <li class="breadcrumb-item active">{{ __('debts::messages.edit_title') }}</li>
        </ol>
    </nav>
</div>

@if ($errors->any())
    <div class="alert alert-danger shadow-sm">{{ __('debts::messages.validation_error') }}</div>
@endif

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('debts.update', $debt) }}" class="vstack gap-3">
            @csrf
            @method('PUT')

            @include('debts::partials.form')

            <div class="d-flex justify-content-end gap-2">
                <x-button.action href="{{ route('debts.index') }}" variant="secondary" :outline="true">{{ __('debts::messages.buttons.cancel') }}</x-button.action>
                <x-button.action type="submit" variant="primary">{{ __('debts::messages.buttons.update') }}</x-button.action>
            </div>
        </form>
    </div>
</div>
@endsection

@include('debts::partials.form-script')
