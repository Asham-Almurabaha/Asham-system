@extends('layouts.master')

@section('title', __('accounts::accounts.bank_accounts.edit_title'))

@section('content')
    <div class="container py-3">
        <div class="pagetitle">
            <h1 class="h3 mb-1">@lang('accounts::accounts.bank_accounts.edit_title')</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">@lang('sidebar.Accounts')</li>
                    <li class="breadcrumb-item"><a href="{{ route('accounts.bank-accounts.index') }}">@lang('accounts::accounts.bank_accounts.index_title')</a></li>
                    <li class="breadcrumb-item active">@lang('accounts::accounts.shared.edit')</li>
                </ol>
            </nav>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('accounts.bank-accounts.update', $bankAccount) }}" method="POST" novalidate>
                    @csrf
                    @method('PUT')
                    @include('accounts::bank_accounts._form', [
                        'bankAccount' => $bankAccount,
                        'submitLabel' => __('accounts::accounts.shared.update'),
                    ])
                </form>
            </div>
        </div>
    </div>
@endsection
