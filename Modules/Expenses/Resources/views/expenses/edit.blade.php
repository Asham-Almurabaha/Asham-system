@extends('layouts.master')

@section('title', __('expenses::expenses.edit_title'))

@section('content')
    <div class="container py-3">
        <div class="pagetitle">
            <h1 class="h3 mb-1">@lang('expenses::expenses.edit_title')</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('expenses.expenses.index') }}">@lang('expenses::expenses.index_title')</a></li>
                    <li class="breadcrumb-item active">@lang('expenses::expenses.actions.edit')</li>
                </ol>
            </nav>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('expenses.expenses.update', $expense) }}" method="POST" novalidate>
                    @csrf
                    @method('PUT')
                    @include('expenses::expenses._form', [
                        'expense' => $expense,
                        'types' => $types,
                        'submitLabel' => __('expenses::expenses.actions.update'),
                    ])
                </form>
            </div>
        </div>
    </div>
@endsection
