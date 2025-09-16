@extends('layouts.master')

@section('title', __('accounts::accounts.bank_accounts.create_title'))

@section('content')
    <div class="container py-3">
        <div class="pagetitle">
            <h1 class="h3 mb-1">@lang('accounts::accounts.bank_accounts.create_title')</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">@lang('sidebar.Accounts')</li>
                    <li class="breadcrumb-item"><a href="{{ route('accounts.bank-accounts.index') }}">@lang('accounts::accounts.bank_accounts.index_title')</a></li>
                    <li class="breadcrumb-item active">@lang('accounts::accounts.shared.add')</li>
                </ol>
            </nav>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('accounts.bank-accounts.store') }}" method="POST" novalidate>
                    @csrf
                    @include('accounts::bank_accounts._form', ['submitLabel' => __('accounts::accounts.shared.save')])
                </form>
            </div>
        </div>
    </div>
@endsection
