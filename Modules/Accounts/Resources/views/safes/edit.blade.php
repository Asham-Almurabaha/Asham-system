@extends('layouts.master')

@section('title', __('accounts::accounts.safes.edit_title'))

@section('content')
    <div class="container py-3">
        <div class="pagetitle">
            <h1 class="h3 mb-1">@lang('accounts::accounts.safes.edit_title')</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">@lang('sidebar.Accounts')</li>
                    <li class="breadcrumb-item"><a href="{{ route('accounts.safes.index') }}">@lang('accounts::accounts.safes.index_title')</a></li>
                    <li class="breadcrumb-item active">@lang('accounts::accounts.shared.edit')</li>
                </ol>
            </nav>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('accounts.safes.update', $safe) }}" method="POST" novalidate>
                    @csrf
                    @method('PUT')
                    @include('accounts::safes._form', [
                        'safe' => $safe,
                        'submitLabel' => __('accounts::accounts.shared.update'),
                    ])
                </form>
            </div>
        </div>
    </div>
@endsection
