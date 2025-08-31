@extends('layouts.master')

@section('title', __('users.Edit User Roles'))

@section('content')
<div class="container-xxl py-4" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

  <div class="mb-3">
    <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary">
      @lang('users.Back to List')
    </a>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
      <div class="d-flex align-items-center">
        <i class="bi bi-people me-2"></i>
        <strong>@lang('users.Edit Roles:')</strong>
        <span class="ms-2">{{ $user->name }} <small class="text-muted"><{{ $user->email }}></small></span>
      </div>
    </div>

    <div class="card-body">
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('users.roles.update', $user) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
          <label class="form-label">@lang('users.Available Roles')</label>
          <div class="row">
            @foreach ($roles as $roleName => $label)
              <div class="col-12 col-md-4 mb-2">
                <div class="form-check">
                  <input class="form-check-input"
                         type="checkbox"
                         name="roles[]"
                         value="{{ $roleName }}"
                         id="role_{{ $roleName }}"
                         @checked(in_array($roleName, $current))>
                  <label class="form-check-label" for="role_{{ $roleName }}">
                    {{ $label }}
                  </label>
                </div>
              </div>
            @endforeach
          </div>
          <div class="form-text">@lang('users.Choose roles then save.')</div>
        </div>

        <div class="d-flex gap-2">
          <button class="btn btn-primary">
            @lang('users.Save')
          </button>
          <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
            @lang('users.Cancel')
          </a>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection
