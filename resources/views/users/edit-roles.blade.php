@extends('layouts.master')

@section('title', __('users.Edit User Roles'))

@section('content')
<div class="container-xxl py-4" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
  <div class="mb-3">
    <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary">
      @lang('users.Back to List')
    </a>
  </div>

  <div class="row g-4">
    <div class="col-12 col-lg-8">
      <form method="POST" action="{{ route('users.roles.update', $user) }}">
        @csrf
        @method('PUT')

        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white border-0">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
              <div>
                <div class="text-muted text-uppercase small fw-semibold">@lang('users.Manage Access For')</div>
                <h5 class="mb-0">{{ $user->name }}</h5>
                <span class="text-muted small">{{ $user->email }}</span>
              </div>
              <div class="text-end">
                <div class="text-muted text-uppercase small fw-semibold">@lang('users.Current Roles')</div>
                <div class="d-flex flex-wrap gap-1 justify-content-end">
                  @forelse ($current as $role)
                    <span class="badge bg-primary-subtle text-primary border">{{ $role }}</span>
                  @empty
                    <span class="badge bg-light text-muted border">@lang('users.No Roles Assigned')</span>
                  @endforelse
                </div>
              </div>
            </div>
          </div>

          <div class="card-body">
            <p class="text-muted small mb-4">@lang('users.Assign Roles Help')</p>

            <div class="mb-4">
              <h6 class="text-uppercase text-muted fs-6 mb-3">@lang('users.Available Roles')</h6>
              <div class="row g-3">
                @foreach ($roles as $roleName => $label)
                  <div class="col-12 col-md-6">
                    <div class="form-check border rounded p-3 h-100">
                      <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $roleName }}" id="role_{{ $roleName }}" @checked(in_array($roleName, $current))>
                      <label class="form-check-label d-block" for="role_{{ $roleName }}">
                        <span class="fw-semibold text-capitalize">{{ str_replace('-', ' ', $label) }}</span>
                        <small class="d-block text-muted">@lang('users.Role Checkbox Helper')</small>
                      </label>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>

            <div class="border-top pt-4">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h6 class="text-uppercase text-muted fs-6 mb-0">@lang('users.Exceptional Permissions')</h6>
                <span class="badge bg-info-subtle text-info fw-semibold">@lang('users.Exceptional Permissions Hint')</span>
              </div>
              <p class="text-muted small mb-3">@lang('users.Exceptional Permissions Help')</p>

              <div class="row g-3">
                @forelse ($permissions as $permission)
                  @php
                    $isInherited = in_array($permission->name, $inheritedPermissions);
                    $isDirect = in_array($permission->name, $directPermissions);
                    $inputId = 'permission_' . \Illuminate\Support\Str::slug($permission->name);
                    $label = \Illuminate\Support\Str::headline(str_replace('.', ' ', $permission->name));
                  @endphp
                  <div class="col-12 col-md-6 col-xl-4">
                    <div class="form-check border rounded p-3 h-100 {{ $isInherited ? 'bg-body-tertiary' : '' }}">
                      <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="{{ $inputId }}" @checked($isInherited || $isDirect) @disabled($isInherited)>
                      <label class="form-check-label d-block" for="{{ $inputId }}">
                        <span class="fw-semibold">{{ $label }}</span>
                        @if ($isInherited)
                          <small class="d-block text-muted">@lang('users.Inherited From Role')</small>
                        @elseif ($isDirect)
                          <small class="d-block text-success">@lang('users.Exceptional Permission Applied')</small>
                        @else
                          <small class="d-block text-muted">@lang('users.Exceptional Permission Available')</small>
                        @endif
                      </label>
                    </div>
                  </div>
                @empty
                  <div class="col-12">
                    <div class="alert alert-light border mb-0" role="alert">
                      @lang('users.No Permissions Found')
                    </div>
                  </div>
                @endforelse
              </div>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <x-save-button>
            @lang('users.Save')
          </x-save-button>
          <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
            @lang('users.Cancel')
          </a>
        </div>
      </form>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-20">
          <h6 class="text-uppercase text-muted fs-6 mb-3">@lang('users.Access Overview')</h6>
          <p class="text-muted small mb-4">@lang('users.Access Overview Help')</p>

          <div class="mb-4">
            <div class="text-muted text-uppercase small fw-semibold mb-2">@lang('users.Role Permissions')</div>
            <div class="d-flex flex-wrap gap-1">
              @forelse ($inheritedPermissions as $permission)
                <span class="badge bg-secondary-subtle text-secondary border">{{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $permission)) }}</span>
              @empty
                <span class="text-muted small">@lang('users.No Role Permissions')</span>
              @endforelse
            </div>
          </div>

          <div class="border-top pt-4">
            <div class="text-muted text-uppercase small fw-semibold mb-2">@lang('users.Exceptional Permissions Label')</div>
            <div class="d-flex flex-wrap gap-1">
              @forelse ($directPermissions as $permission)
                <span class="badge bg-success-subtle text-success border">{{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $permission)) }}</span>
              @empty
                <span class="text-muted small">@lang('users.No Exceptional Permissions')</span>
              @endforelse
            </div>
          </div>
        </div>
      </div>

      <div class="alert alert-info border-0 shadow-sm small" role="alert">
        <div class="fw-semibold mb-1">@lang('users.Tip Title')</div>
        <div>@lang('users.Tip Description')</div>
      </div>
    </div>
  </div>
</div>
@endsection
