@extends('layouts.master')

@section('title', __('expenses::types.edit_title'))

@section('content')
    <div class="container py-3">
        <div class="pagetitle">
            <h1 class="h3 mb-1">@lang('expenses::types.edit_title')</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('expenses.expense-types.index') }}">@lang('expenses::types.index_title')</a></li>
                    <li class="breadcrumb-item active">@lang('expenses::types.actions.edit')</li>
                </ol>
            </nav>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('expenses.expense-types.update', $expenseType) }}" method="POST" novalidate>
                    @csrf
                    @method('PUT')
                    @include('expenses::expense_types._form', [
                        'expenseType' => $expenseType,
                        'submitLabel' => __('expenses::types.actions.update'),
                    ])
                </form>
            </div>
        </div>
    </div>
@endsection
