@extends('layouts.master')

@section('title', __('accounts::accounts.safes.index_title'))

@section('content')
    <div class="pagetitle">
        <h1>@lang('accounts::accounts.safes.index_title')</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">@lang('sidebar.Accounts')</li>
                <li class="breadcrumb-item active">@lang('accounts::accounts.safes.index_title')</li>
            </ol>
        </nav>
    </div>

    <div class="card d-inline-block mb-3">
        <div class="card-body p-20">
            <a href="{{ route('accounts.safes.create') }}" class="btn btn-success">
                @lang('accounts::accounts.safes.actions.create')
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-20">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th scope="col" class="text-center" style="width:60px;">#</th>
                            <th scope="col">@lang('accounts::accounts.safes.fields.name')</th>
                            <th scope="col">@lang('accounts::accounts.safes.fields.location')</th>
                            <th scope="col" class="text-end">@lang('accounts::accounts.safes.fields.opening_balance')</th>
                            <th scope="col">@lang('accounts::accounts.safes.fields.currency_code')</th>
                            <th scope="col">@lang('accounts::accounts.safes.fields.is_active')</th>
                            <th scope="col" class="text-center">@lang('accounts::accounts.shared.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($safes as $safe)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td class="fw-semibold">{{ $safe->name }}</td>
                                <td>{{ $safe->location ?? '—' }}</td>
                                <td class="text-end">{{ number_format($safe->opening_balance, 2) }}</td>
                                <td>{{ $safe->currency_code }}</td>
                                <td>
                                    @if($safe->is_active)
                                        <span class="badge bg-success-subtle text-success">@lang('accounts::accounts.status.active')</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-muted">@lang('accounts::accounts.status.inactive')</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="{{ route('accounts.safes.edit', $safe) }}" class="btn btn-primary btn-sm">
                                            @lang('accounts::accounts.shared.edit')
                                        </a>
                                        @include('lookups::components.delete-button', [
                                            'action' => route('accounts.safes.destroy', $safe),
                                            'confirm' => __('accounts::accounts.safes.confirm_delete'),
                                            'label' => __('accounts::accounts.shared.delete'),
                                        ])
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    @lang('accounts::accounts.safes.empty')
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
