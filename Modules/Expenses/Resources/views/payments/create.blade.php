@extends('layouts.master')

@section('title', __('expenses::payments.title.create'))

@section('content')
    <div class="pagetitle mb-3">
        <h1 class="h3 mb-1">@lang('expenses::payments.title.create')</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('expenses.expenses.index') }}">@lang('expenses::payments.breadcrumb.index')</a></li>
                <li class="breadcrumb-item active">@lang('expenses::payments.breadcrumb.payments')</li>
            </ol>
        </nav>
    </div>

    @php
        $isSettled = $expense->outstanding_amount <= 0;
        $defaultAmount = number_format(max($expense->outstanding_amount, 0), 2, '.', '');
        $defaultPaidAt = now()->toDateString();
        $oldAmount = old('amount', $defaultAmount);
        $oldPaidAt = old('paid_at', $defaultPaidAt);
        $oldBank = old('bank_account_id');
        $oldSafe = old('safe_id');
        $oldNotes = old('notes');
    @endphp

    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">@lang('expenses::payments.summary.heading')</h2>
                    <dl class="row mb-0">
                        <dt class="col-6 text-muted">@lang('expenses::expenses.fields.title')</dt>
                        <dd class="col-6 fw-semibold text-end">{{ $expense->title }}</dd>
                        <dt class="col-6 text-muted">@lang('expenses::payments.summary.due_date')</dt>
                        <dd class="col-6 text-end">{{ optional($expense->due_date)->toDateString() ?? '—' }}</dd>
                        <dt class="col-6 text-muted">@lang('expenses::payments.summary.amount')</dt>
                        <dd class="col-6 text-end">{{ number_format($expense->amount, 2) }}</dd>
                        <dt class="col-6 text-muted">@lang('expenses::payments.summary.paid')</dt>
                        <dd class="col-6 text-end">{{ number_format($expense->paid_amount, 2) }}</dd>
                        <dt class="col-6 text-muted">@lang('expenses::payments.summary.outstanding')</dt>
                        <dd class="col-6 text-end">{{ number_format($expense->outstanding_amount, 2) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h5 mb-3">@lang('expenses::payments.form.heading')</h2>

                    @if ($isSettled)
                        <div class="alert alert-success" role="alert">
                            @lang('expenses::payments.alerts.settled')
                        </div>
                    @endif

                    <form action="{{ route('expenses.payments.store', $expense) }}" method="POST" novalidate>
                        @csrf

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="amount" class="form-label">@lang('expenses::payments.fields.amount') <span class="text-danger">*</span></label>
                                <input
                                    type="number"
                                    step="0.01"
                                    name="amount"
                                    id="amount"
                                    value="{{ $oldAmount }}"
                                    class="form-control @error('amount') is-invalid @enderror"
                                    {{ $isSettled ? 'disabled' : '' }}
                                >
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="paid_at" class="form-label">@lang('expenses::payments.fields.paid_at') <span class="text-danger">*</span></label>
                                <input
                                    type="date"
                                    name="paid_at"
                                    id="paid_at"
                                    value="{{ $oldPaidAt }}"
                                    class="form-control @error('paid_at') is-invalid @enderror"
                                    {{ $isSettled ? 'disabled' : '' }}
                                >
                                @error('paid_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="bank_account_id" class="form-label">@lang('expenses::payments.fields.bank_account_id')</label>
                                <select
                                    name="bank_account_id"
                                    id="bank_account_id"
                                    class="form-select @error('bank_account_id') is-invalid @enderror"
                                    {{ $isSettled ? 'disabled' : '' }}
                                >
                                    <option value="">@lang('expenses::payments.fields.account_placeholder')</option>
                                    @foreach($banks as $bank)
                                        <option value="{{ $bank->id }}" @selected((string) $oldBank === (string) $bank->id)>{{ $bank->name }}</option>
                                    @endforeach
                                </select>
                                @error('bank_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="safe_id" class="form-label">@lang('expenses::payments.fields.safe_id')</label>
                                <select
                                    name="safe_id"
                                    id="safe_id"
                                    class="form-select @error('safe_id') is-invalid @enderror"
                                    {{ $isSettled ? 'disabled' : '' }}
                                >
                                    <option value="">@lang('expenses::payments.fields.safe_placeholder')</option>
                                    @foreach($safes as $safe)
                                        <option value="{{ $safe->id }}" @selected((string) $oldSafe === (string) $safe->id)>{{ $safe->name }}</option>
                                    @endforeach
                                </select>
                                @error('safe_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label for="notes" class="form-label">@lang('expenses::payments.fields.notes')</label>
                                <textarea
                                    name="notes"
                                    id="notes"
                                    rows="3"
                                    class="form-control @error('notes') is-invalid @enderror"
                                    {{ $isSettled ? 'disabled' : '' }}
                                >{{ $oldNotes }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <x-button.action href="{{ route('expenses.expenses.index') }}" variant="secondary" :outline="true">
                                @lang('expenses::payments.form.cancel')
                            </x-button.action>
                            <x-button.action type="submit" variant="success" {{ $isSettled ? 'disabled' : '' }}>
                                <i class="bi bi-save2 me-1"></i>@lang('expenses::payments.form.submit')
                            </x-button.action>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5 mb-3">@lang('expenses::payments.history.heading')</h2>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">@lang('expenses::payments.history.table.paid_at')</th>
                                    <th scope="col" class="text-end">@lang('expenses::payments.history.table.amount')</th>
                                    <th scope="col">@lang('expenses::payments.history.table.account')</th>
                                    <th scope="col">@lang('expenses::payments.history.table.notes')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expense->payments as $payment)
                                    @php
                                        $account = $payment->bankAccount->name ?? $payment->safe->name ?? '—';
                                    @endphp
                                    <tr>
                                        <td>{{ optional($payment->paid_at)->toDateString() }}</td>
                                        <td class="text-end">{{ number_format($payment->amount, 2) }}</td>
                                        <td>{{ $account }}</td>
                                        <td>{{ $payment->notes ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">@lang('expenses::payments.history.empty')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
