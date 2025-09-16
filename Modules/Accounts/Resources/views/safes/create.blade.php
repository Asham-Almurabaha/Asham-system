@extends('layouts.master')

@section('title', __('accounts::accounts.safes.create_title'))

@section('content')
    <div class="container py-3">
        <div class="pagetitle">
            <h1 class="h3 mb-1">@lang('accounts::accounts.safes.create_title')</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">@lang('sidebar.Accounts')</li>
                    <li class="breadcrumb-item"><a href="{{ route('accounts.safes.index') }}">@lang('accounts::accounts.safes.index_title')</a></li>
                    <li class="breadcrumb-item active">@lang('accounts::accounts.shared.add')</li>
                </ol>
            </nav>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('accounts.safes.store') }}" method="POST" novalidate>
                    @csrf
                    @include('accounts::safes._form', ['submitLabel' => __('accounts::accounts.shared.save')])
                </form>
            </div>
        </div>
    </div>
@endsection
