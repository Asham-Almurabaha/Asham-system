@extends('layouts.master')

@section('title', __('expenses::types.create_title'))

@section('content')
    <div class="container py-3">
        <div class="pagetitle">
            <h1 class="h3 mb-1">@lang('expenses::types.create_title')</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('expenses.expense-types.index') }}">@lang('expenses::types.index_title')</a></li>
                    <li class="breadcrumb-item active">@lang('expenses::types.actions.create')</li>
                </ol>
            </nav>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('expenses.expense-types.store') }}" method="POST" novalidate>
                    @csrf
                    @include('expenses::expense_types._form', [
                        'submitLabel' => __('expenses::types.actions.save'),
                    ])
                </form>
            </div>
        </div>
    </div>
@endsection
