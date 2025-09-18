@extends('layouts.master')

@section('title', __('accounts::accounts.bank_accounts.index_title'))

@section('content')
    <div class="container-xxl py-4">
        <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">@lang('sidebar.Settings')</a></li>
                        <li class="breadcrumb-item active" aria-current="page">@lang('accounts::accounts.bank_accounts.index_title')</li>
                    </ol>
                </nav>
                <h1 class="h4 mb-0">@lang('accounts::accounts.bank_accounts.index_title')</h1>
            </div>

            <div class="ms-auto d-flex flex-wrap gap-2">
                <a href="{{ route('accounts.bank-accounts.create') }}" class="btn btn-success">
                    <i class="bi bi-plus-lg me-1"></i>@lang('accounts::accounts.bank_accounts.actions.create')
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-center" style="width:70px">#</th>
                            <th scope="col">@lang('accounts::accounts.bank_accounts.fields.name')</th>
                            <th scope="col">@lang('accounts::accounts.bank_accounts.fields.bank_name')</th>
                            <th scope="col">@lang('accounts::accounts.bank_accounts.fields.account_number')</th>
                            <th scope="col">@lang('accounts::accounts.bank_accounts.fields.iban')</th>
                            <th scope="col" class="text-end">@lang('accounts::accounts.bank_accounts.fields.opening_balance')</th>
                            <th scope="col">@lang('accounts::accounts.bank_accounts.fields.currency_code')</th>
                            <th scope="col">@lang('accounts::accounts.bank_accounts.fields.is_active')</th>
                            <th scope="col" class="text-end" style="width:200px">@lang('accounts::accounts.shared.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bankAccounts as $bankAccount)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td class="fw-semibold">{{ $bankAccount->name }}</td>
                                <td>{{ $bankAccount->bank_name ?? '—' }}</td>
                                <td dir="ltr">{{ $bankAccount->account_number ?? '—' }}</td>
                                <td dir="ltr" class="text-nowrap">{{ $bankAccount->iban ?? '—' }}</td>
                                <td class="text-end">{{ number_format($bankAccount->opening_balance, 2) }}</td>
                                <td>{{ $bankAccount->currency_code }}</td>
                                <td>
                                    @if($bankAccount->is_active)
                                        <span class="badge bg-success-subtle text-success">@lang('accounts::accounts.status.active')</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-muted">@lang('accounts::accounts.status.inactive')</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="{{ route('accounts.bank-accounts.edit', $bankAccount) }}" class="btn btn-sm btn-outline-primary">
                                            @lang('accounts::accounts.shared.edit')
                                        </a>
                                        @include('lookups::components.delete-button', [
                                            'action' => route('accounts.bank-accounts.destroy', $bankAccount),
                                            'confirm' => __('accounts::accounts.bank_accounts.confirm_delete'),
                                            'label' => __('accounts::accounts.shared.delete'),
                                        ])
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    @lang('accounts::accounts.bank_accounts.empty')
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
