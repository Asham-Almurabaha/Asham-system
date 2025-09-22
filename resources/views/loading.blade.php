@extends('layouts.app')

@section('title', __('loading.title'))
@section('auth_title', __('loading.heading'))
@section('auth_subtitle', __('loading.subtitle'))

@section('form')
  <div class="w-100 d-flex flex-column align-items-center text-center gap-3 py-4" aria-live="polite">
    <div class="spinner-border text-primary" role="status">
      <span class="visually-hidden">{{ __('loading.status') }}</span>
    </div>
    <p class="mb-0 text-muted">
      {{ __('loading.description') }}
    </p>
  </div>
@endsection
