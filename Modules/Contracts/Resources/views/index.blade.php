@extends('layouts.master')

@section('title', __('Contracts List'))

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ __('Contracts List') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">{{ __('Contracts') }}</li>
        </ol>
    </nav>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="card shadow-sm mb-3" dir="rtl">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">
        <div class="btn-group" role="group" aria-label="{{ __('Contract Actions') }}">
            <a href="{{ route('contracts.create') }}" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> {{ __('Add New Contract') }}
            </a>
            @role('admin')
            <div class="btn-group">
                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-upload"></i> {{ __('Import Excel') }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end text-end shadow mt-2">
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('contracts.import.form') }}">
                            <i class="bi bi-upload"></i>
                            <span>{{ __('Import Excel') }}</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('contracts.import.basic.form') }}">
                            <i class="bi bi-upload"></i>
                            <span>{{ __('Import Basic Excel') }}</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('contracts.import.investors.form') }}">
                            <i class="bi bi-upload"></i>
                            <span>{{ __('Import Investors Excel') }}</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('contracts.import.payments.form') }}">
                            <i class="bi bi-upload"></i>
                            <span>{{ __('Import Payments Excel') }}</span>
                        </a>
                    </li>
                </ul>
            </div>
            @endrole
        </div>

        <span class="ms-auto small text-muted">
            {{ __('Results') }}: <strong>{{ $contracts->total() }}</strong>
        </span>

        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#filterBar" aria-expanded="false" aria-controls="filterBar">
            {{ __('Advanced Filter') }}
        </button>
    </div>

    <div class="collapse @if(request()->hasAny(['customer','contract_number','investor_id','status','from','to'])) show @endif border-top" id="filterBar">
        <div class="card-body">
            <form action="{{ route('contracts.index') }}" method="GET" class="row gy-2 gx-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label mb-1">{{ __('Customer') }}</label>
                    <input type="text" name="customer" value="{{ request('customer') }}" class="form-control form-control-sm" placeholder="{{ __('Customer Name') }}">
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label mb-1">{{ __('Investor') }}</label>
                    <select name="investor_id" class="form-select form-select-sm">
                        <option value="">{{ __('All') }}</option>
                        <option value="_none" @selected(request('investor_id') === '_none')>{{ __('Without Investor') }}</option>
                        @foreach($investors as $inv)
                            <option value="{{ $inv->id }}" @selected((string)request('investor_id') === (string)$inv->id)>
                                {{ $inv->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <label class="form-label mb-1">{{ __('Contract Number') }}</label>
                    <input type="text" name="contract_number" value="{{ request('contract_number') }}" class="form-control form-control-sm" placeholder="{{ __('Contract Number') }}">
                </div>

                <div class="col-6 col-md-1">
                    <label class="form-label mb-1">{{ __('Contract Status') }}</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">{{ __('All') }}</option>
                        @foreach($contractStatuses as $status)
                            <option value="{{ $status->id }}" @selected(request('status') == $status->id)>{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">{{ __('From Date') }}</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm js-date" placeholder="{{ __('YYYY-MM-DD') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">{{ __('To Date') }}</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm js-date" placeholder="{{ __('YYYY-MM-DD') }}">
                </div>

                <div class="col-12 col-md-1 d-flex gap-2">
                    <button class="btn btn-primary btn-sm w-100">{{ __('Search') }}</button>
                    <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary btn-sm w-100">{{ __('Clear') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0">
                <thead class="table-light position-sticky top-0">
                    <tr>
                        <th style="width:60px">#</th>
                        <th>{{ __('Contract Number') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Guarantor') }}</th>
                        <th>{{ __('Product Type') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Total Contract') }}</th>
                        <th>{{ __('Investor Profit') }}</th>
                        <th style="min-width:160px;">{{ __('Investors') }}</th>
                        <th>{{ __('Start Date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contracts as $contract)
                        @php
                            $statusName = $contract->contractStatus->name ?? '-';
                            $badge = match($statusName) {
                                'نشط' => 'secondary',
                                'معلق' => 'warning',
                                'بدون مستثمر' => 'danger',
                                default => 'success'
                            };

                            $count = $contract->investors->count();
                            $sep   = app()->getLocale() === 'ar' ? '، ' : ', ';
                            $tip   = $contract->investors
                                    ->map(fn($i) => ($i->name ?? ('#'.$i->id)).' '.number_format($i->pivot->share_percentage,2).'%')
                                    ->join($sep);
                        @endphp
                        <tr>
                            <td class="text-muted">
                                {{ $loop->iteration + ($contracts->currentPage() - 1) * $contracts->perPage() }}
                            </td>
                            <td class="text-start">
                                <a href="{{ route('contracts.show', $contract) }}" class="text-decoration-none text-dark hover-primary fw-bold">
                                    {{ $contract->contract_number ?? '—' }}
                                </a>
                            </td>
                            <td class="text-center">{{ $contract->customer->name ?? '-' }}</td>
                            <td class="text-center">{{ $contract->guarantor->name ?? '-' }}</td>
                            <td>{{ $contract->productType->name ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $badge }} d-inline-flex align-items-center gap-1">
                                    {{ $statusName }}
                                </span>
                            </td>
                            <td>{{ number_format($contract->total_value, 0) }}</td>
                            <td>{{ number_format($contract->investor_profit, 0) }}</td>
                            <td class="text-center">
                                @if($count)
                                    <span class="badge bg-info text-dark" data-bs-toggle="tooltip" title="{{ $tip }}">
                                        {{ $count }} {{ __('Investor') }}
                                    </span>
                                @else
                                    <span class="badge bg-danger" title="0.00%">
                                        0 {{ __('Investor') }}
                                    </span>
                                @endif
                            </td>
                            <td>{{ optional($contract->start_date)->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-5">
                                <div class="text-muted">
                                    {{ __('No matching contracts for your search.') }}
                                    <a href="{{ route('contracts.index') }}" class="ms-1">{{ __('View All') }}</a>
                                </div>
                                <div class="mt-3">
                                    <a href="{{ route('contracts.create') }}" class="btn btn-sm btn-success">
                                        + {{ __('Add First Contract') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($contracts->hasPages())
    <div class="card-footer bg-white">
        {{ $contracts->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el, {container: 'body'}));
    });
</script>
@endpush
