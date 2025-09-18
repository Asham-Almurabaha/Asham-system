@extends('layouts.master')

@section('title', __('contracts::claims.add_claim'))

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ __('contracts::claims.add_claim') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('contract-claims.index') }}">{{ __('contracts::claims.claims') }}</a></li>
            <li class="breadcrumb-item active">{{ __('contracts::claims.add_claim') }}</li>
        </ol>
    </nav>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('contract-claims.store') }}" method="POST" class="vstack gap-3">
            @csrf

            @include('contracts::claims._form', ['claim' => null, 'contracts' => $contracts])

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('contract-claims.index') }}" class="btn btn-outline-secondary">{{ __('contracts::claims.back') }}</a>
                <button type="submit" class="btn btn-success">{{ __('contracts::claims.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
