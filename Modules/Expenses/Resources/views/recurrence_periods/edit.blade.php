@extends('layouts.master')

@section('title', __('expenses::recurrence_periods.edit_title'))

@section('content')
    <div class="container py-3">
        <div class="pagetitle">
            <h1 class="h3 mb-1">@lang('expenses::recurrence_periods.edit_title')</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('expenses.recurrence-periods.index') }}">@lang('expenses::recurrence_periods.index_title')</a></li>
                    <li class="breadcrumb-item active">@lang('expenses::recurrence_periods.actions.edit')</li>
                </ol>
            </nav>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('expenses.recurrence-periods.update', $recurrencePeriod) }}" method="POST" novalidate>
                    @csrf
                    @method('PUT')
                    @include('expenses::recurrence_periods._form', [
                        'recurrencePeriod' => $recurrencePeriod,
                        'submitLabel' => __('expenses::recurrence_periods.actions.update'),
                    ])
                </form>
            </div>
        </div>
    </div>
@endsection
