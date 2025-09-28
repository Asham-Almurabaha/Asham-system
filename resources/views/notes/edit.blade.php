@extends('layouts.master')

@section('title', __('notes.actions.edit'))

@section('content')
    <div class="pagetitle mb-4">
        <h1 class="h3 mb-1">{{ __('notes.actions.edit') }}</h1>
        <p class="text-muted mb-0">{{ __('notes.subtitle') }}</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-20">
            <form action="{{ route('notes.update', $note) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                @include('notes.partials.form', ['note' => $note])
            </form>
        </div>
    </div>
@endsection
