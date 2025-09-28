@extends('layouts.master')

@section('title', __('expenses::recurrence_periods.create_title'))

@section('content')
    <div class="container py-3">
        <div class="pagetitle">
            <h1 class="h3 mb-1">@lang('expenses::recurrence_periods.create_title')</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('expenses.recurrence-periods.index') }}">@lang('expenses::recurrence_periods.index_title')</a></li>
                    <li class="breadcrumb-item active">@lang('expenses::recurrence_periods.actions.create')</li>
                </ol>
            </nav>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('expenses.recurrence-periods.store') }}" method="POST" novalidate>
                    @csrf
                    @include('expenses::recurrence_periods._form', [
                        'submitLabel' => __('expenses::recurrence_periods.actions.save'),
                    ])
                </form>
            </div>
        </div>
    </div>
@endsection
