@extends('layouts.master')

@section('title', 'دفتر القيود')

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">دفتر القيود</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">@lang('sidebar.Accounts')</li>
            <li class="breadcrumb-item active">@lang('sidebar.Ledger')</li>
        </ol>
    </nav>
</div>

{{-- شريط أدوات سريع --}}
<div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">
        <div class="btn-group" role="group" aria-label="Ledger actions">
            <x-button.action href="{{ route('ledger.dashboard') }}" variant="dark" :outline="true">
                <i class="bi bi-speedometer2"></i> {{ __('ledger::ledger.View Dashboard') }}
            </x-button.action>
        </div>

        <span class="ms-auto small text-muted">
            النتائج: <strong>{{ $entries->total() }}</strong>
        </span>

        <x-button.action type="button" variant="secondary" :outline="true" size="sm" data-bs-toggle="collapse" data-bs-target="#filterBar" aria-expanded="false">
            تصفية متقدمة
        </x-button.action>
    </div>

    <div class="collapse @if(($filters['party_category'] ?? '') || ($filters['investor_id'] ?? '') || ($filters['status_id'] ?? '') || ($filters['account_type'] ?? '') || ($filters['from'] ?? '') || ($filters['to'] ?? '')) show @endif border-top" id="filterBar">
        <div class="card-body">
            <form method="GET" action="{{ route('ledger.index') }}" class="row gy-2 gx-2 align-items-end" id="filtersForm">
                <div class="col-12 col-md-2">
                    <label class="form-label mb-1">@lang('ledger::ledger.Category')</label>
                    <select name="party_category" id="party_category" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        <option value="investors" @selected(($filters['party_category'] ?? '') === 'investors')>المستثمرون</option>
                        <option value="office"    @selected(($filters['party_category'] ?? '') === 'office')>المكتب</option>
                        <option value="companies" @selected(($filters['party_category'] ?? '') === 'companies')>{{ __('ledger::ledger.Companies') }}</option>
                    </select>
                </div>

                <div class="col-12 col-md-2" id="investorWrap">
                    <label class="form-label mb-1">@lang('ledger::ledger.Investor')</label>
                    <select name="investor_id" id="investor_id" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        @foreach($investors as $inv)
                            <option value="{{ $inv->id }}" @selected((string)($filters['investor_id'] ?? '') === (string)$inv->id)>{{ $inv->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <label class="form-label mb-1">@lang('ledger::ledger.Status')</label>
                    <select name="status_id" id="status_id" class="form-select form-select-sm">
                        <option value="">الكل</option>

                        <optgroup label="حالات المستثمرين" data-cat="investors">
                            @foreach($statusesInvestors as $st)
                                @if(($st->transaction_type_id ?? null) != 3)
                                    <option value="{{ $st->id }}" data-cat="investors" data-type="{{ $st->transaction_type_id }}" @selected((string)($filters['status_id'] ?? '') === (string)$st->id)>{{ $st->name }}</option>
                                @endif
                            @endforeach
                        </optgroup>

                        <optgroup label="حالات المكتب" data-cat="office">
                            @foreach($statusesOffice as $st)
                                @if(($st->transaction_type_id ?? null) != 3)
                                    <option value="{{ $st->id }}" data-cat="office" data-type="{{ $st->transaction_type_id }}" @selected((string)($filters['status_id'] ?? '') === (string)$st->id)>{{ $st->name }}</option>
                                @endif
                            @endforeach
                        </optgroup>

                        @if(($statusesCompanies ?? collect())->isNotEmpty())
                            <optgroup label="حالات الشركات" data-cat="companies">
                                @foreach($statusesCompanies as $st)
                                    @if(($st->transaction_type_id ?? null) != 3)
                                        <option value="{{ $st->id }}" data-cat="companies" data-type="{{ $st->transaction_type_id }}" @selected((string)($filters['status_id'] ?? '') === (string)$st->id)>{{ $st->name }}</option>
                                    @endif
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">@lang('ledger::ledger.Account Type')</label>
                    <select name="account_type" id="account_type" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        <option value="bank" @selected(($filters['account_type'] ?? '') === 'bank')>حساب بنكي</option>
                        <option value="safe" @selected(($filters['account_type'] ?? '') === 'safe')>خزنة</option>
                    </select>
                </div>

                <div class="col-6 col-md-1 js-date">
                    <label class="form-label mb-1">@lang('ledger::ledger.From')</label>
                    <input type="date" name="from" id="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-1 js-date">
                    <label class="form-label mb-1">@lang('ledger::ledger.To')</label>
                    <input type="date" name="to" id="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
                </div>

                <div class="col-12 col-md-2 d-flex gap-2">
                    <x-button.action href="{{ route('ledger.index') }}" variant="secondary" :outline="true" size="sm" id="btnClear">مسح</x-button.action>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- الجدول --}}
<div class="card shadow-sm">
    <div class="card-body p-0">
        <x-table head-class="table-light" class="text-center">
            <x-slot name="head">
                <tr>
                    <th style="width:120px">التاريخ</th>
                    <th>الجهة</th>
                    <th>الحالة</th>
                    <th>النوع</th>
                    <th>الاتجاه</th>
                    <th class="text-end">المبلغ</th>
                    <th>الحساب</th>
                </tr>
            </x-slot>
            @php
                $isOffice = function($e){
                    return (($e->is_office ?? null) === 1) || (($e->is_office ?? null) === true);
                };
            @endphp
            @forelse($entries as $e)
                <tr>
                    <td>{{ $e->entry_date?->format('Y-m-d') }}</td>
                    <td>
                        @php $isCompany = !empty($e->company_transaction_id); @endphp
                        @if($isCompany)
                            <span class="badge bg-info text-dark">{{ __('ledger::ledger.Companies') }}</span>
                        @elseif($isOffice($e))
                            <span class="badge bg-secondary">{{ __('ledger::ledger.Office') }}</span>
                        @else
                            {{ $e->investor->name ?? '-' }}
                        @endif
                    </td>
                    <td>
                        @php $statusText = $e->status->name ?? '-'; @endphp
                        @if(!empty($e->notes))
                            <span data-bs-toggle="tooltip" data-bs-container="body" data-bs-placement="top" title="{{ $e->notes }}">
                                {{ $statusText }}
                            </span>
                        @else
                            {{ $statusText }}
                        @endif
                    </td>
                    <td>{{ $e->type->name ?? '-' }}</td>
                    <td>
                        @if($e->direction === 'in')
                            <span class="badge bg-success">داخل</span>
                        @else
                            <span class="badge bg-danger">خارج</span>
                        @endif
                    </td>
                    <td class="text-end fw-semibold">
                        @if($e->direction === 'out') - @endif
                        {{ number_format($e->amount, 2) }}
                    </td>
                    <td>
                        @if($e->bankAccount)
                            <i class="bi bi-bank"></i> {{ $e->bankAccount->name }}
                        @elseif($e->safe)
                            <i class="bi bi-safe2"></i> {{ $e->safe->name }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-5 text-muted">لا توجد قيود مطابقة للبحث.</td>
                </tr>
            @endforelse
        </x-table>

        @if($entries->hasPages())
        <div class="mt-3 p-3">
            {{ $entries->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

@endpush

@include('ledger::ledger.partials.filter-script')

