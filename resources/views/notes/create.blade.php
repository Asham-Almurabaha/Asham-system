@extends('layouts.master')

@section('title', __('notes.actions.new'))

@section('content')
    <div class="pagetitle mb-4">
        <h1 class="h3 mb-1">{{ __('notes.actions.new') }}</h1>
        <p class="text-muted mb-0">{{ __('notes.subtitle') }}</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('notes.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @include('notes.partials.form')
            </form>
        </div>
    </div>
@endsection
