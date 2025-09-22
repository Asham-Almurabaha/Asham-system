@extends('layouts.app')

@section('title', __('errors.404.title'))
@section('auth_title', __('errors.404.heading'))
@section('auth_subtitle', __('errors.404.subtitle'))

@section('form')
  <div class="w-100 d-flex flex-column align-items-center text-center gap-3 py-2">
    <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
      <span class="fw-bold text-warning fs-1">{{ __('errors.404.code') }}</span>
    </div>
    <p class="mb-0 text-muted">
      {{ __('errors.404.message') }}
    </p>
    <div class="d-flex flex-column gap-2 w-100">
      <x-button.action href="{{ url()->previous() }}" variant="secondary" :outline="true" :block="true">
        {{ __('errors.404.actions.back') }}
      </x-button.action>
      <x-button.action href="{{ url('/') }}" variant="primary" :outline="true" :block="true">
        {{ __('errors.404.actions.home') }}
      </x-button.action>
    </div>
  </div>
@endsection
