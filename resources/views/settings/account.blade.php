@extends('layouts.master')
@section('title', __('setting.Account Settings'))

@section('content')
<div class="container py-3">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
      <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">@lang('sidebar.Settings')</a></li>
      <li class="breadcrumb-item active" aria-current="page">@lang('setting.Account Settings')</li>
    </ol>
  </nav>

  <div class="row g-3">
    <div class="col-12 col-xxl-10 mx-auto">
      <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-3">
        <div>
          <h4 class="mb-1">@lang('setting.Account Settings')</h4>
          <p class="text-muted mb-0">@lang('setting.Account Settings Subtitle')</p>
        </div>
        <div class="d-flex align-items-center gap-3 text-muted small">
          <div>
            <i class="bi bi-person-circle me-1"></i>{{ $user->name }}
          </div>
          <div class="d-none d-md-inline">|</div>
          <div>
            <i class="bi bi-envelope-open me-1"></i>{{ $user->email }}
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-6">
          <div class="card shadow-sm h-100">
            <div class="card-header border-0 bg-transparent pb-0">
              <h5 class="card-title mb-1">@lang('setting.Profile Information')</h5>
              <p class="text-muted small mb-0">@lang('setting.Profile Information Description')</p>
            </div>
            <div class="card-body">
              <form method="POST" action="{{ route('settings.account.profile.update') }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-12">
                  <label for="account-name" class="form-label">@lang('general.Name') <span class="text-danger">*</span></label>
                  <input id="account-name"
                         type="text"
                         name="name"
                         value="{{ old('name', $user->name) }}"
                         class="form-control @error('name') is-invalid @enderror"
                         maxlength="255"
                         required
                         autocomplete="name">
                  @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @else
                    <div class="form-text">@lang('setting.Profile Name Help')</div>
                  @enderror
                </div>

                <div class="col-12">
                  <label for="account-email" class="form-label">@lang('general.Email') <span class="text-danger">*</span></label>
                  <input id="account-email"
                         type="email"
                         name="email"
                         value="{{ old('email', $user->email) }}"
                         class="form-control @error('email') is-invalid @enderror"
                         maxlength="255"
                         required
                         autocomplete="email">
                  @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @else
                    <div class="form-text">@lang('setting.Profile Email Help')</div>
                  @enderror
                </div>

                <div class="col-12 d-flex flex-wrap gap-2">
                  <x-button.save>
                    @lang('setting.Update Profile Button')
                  </x-button.save>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card shadow-sm h-100">
            <div class="card-header border-0 bg-transparent pb-0">
              <h5 class="card-title mb-1">@lang('setting.Password Update')</h5>
              <p class="text-muted small mb-0">@lang('setting.Password Update Description')</p>
            </div>
            <div class="card-body">
              <form method="POST" action="{{ route('settings.account.password.update') }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-12">
                  <label for="current-password" class="form-label">@lang('setting.Current Password') <span class="text-danger">*</span></label>
                  <div class="input-group has-validation">
                    <input id="current-password"
                           type="password"
                           name="current_password"
                           class="form-control @error('current_password') is-invalid @enderror"
                           required
                           autocomplete="current-password"
                           aria-describedby="toggleCurrentPassword">
                    <x-button.action type="button"
                                     variant="secondary"
                                     :outline="true"
                                     id="toggleCurrentPassword"
                                     aria-controls="current-password"
                                     aria-label="{{ __('Show password') }}"
                                     title="{{ __('Show password') }}"
                                     data-show-label="{{ __('Show password') }}"
                                     data-hide-label="{{ __('Hide password') }}"
                                     data-password-toggle-target="current-password">
                      <i class="bi bi-eye" aria-hidden="true"></i>
                    </x-button.action>
                    @error('current_password')
                      <div class="invalid-feedback d-block">{{ $message }}</div>
                    @else
                      <div class="invalid-feedback">@lang('setting.Current Password Help')</div>
                    @enderror
                  </div>
                </div>

                <div class="col-12">
                  <label for="new-password" class="form-label">@lang('setting.New Password') <span class="text-danger">*</span></label>
                  <div class="input-group has-validation">
                    <input id="new-password"
                           type="password"
                           name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           required
                           minlength="8"
                           autocomplete="new-password"
                           aria-describedby="toggleNewPassword">
                    <x-button.action type="button"
                                     variant="secondary"
                                     :outline="true"
                                     id="toggleNewPassword"
                                     aria-controls="new-password"
                                     aria-label="{{ __('Show password') }}"
                                     title="{{ __('Show password') }}"
                                     data-show-label="{{ __('Show password') }}"
                                     data-hide-label="{{ __('Hide password') }}"
                                     data-password-toggle-target="new-password">
                      <i class="bi bi-eye" aria-hidden="true"></i>
                    </x-button.action>
                    @error('password')
                      <div class="invalid-feedback d-block">{{ $message }}</div>
                    @else
                      <div class="invalid-feedback">@lang('setting.New Password Help')</div>
                    @enderror
                  </div>
                </div>

                <div class="col-12">
                  <label for="confirm-password" class="form-label">@lang('setting.Confirm New Password') <span class="text-danger">*</span></label>
                  <div class="input-group has-validation">
                    <input id="confirm-password"
                           type="password"
                           name="password_confirmation"
                           class="form-control @error('password_confirmation') is-invalid @enderror"
                           required
                           autocomplete="new-password"
                           aria-describedby="toggleConfirmPassword">
                    <x-button.action type="button"
                                     variant="secondary"
                                     :outline="true"
                                     id="toggleConfirmPassword"
                                     aria-controls="confirm-password"
                                     aria-label="{{ __('Show password') }}"
                                     title="{{ __('Show password') }}"
                                     data-show-label="{{ __('Show password') }}"
                                     data-hide-label="{{ __('Hide password') }}"
                                     data-password-toggle-target="confirm-password">
                      <i class="bi bi-eye" aria-hidden="true"></i>
                    </x-button.action>
                    @error('password_confirmation')
                      <div class="invalid-feedback d-block">{{ $message }}</div>
                    @else
                      <div class="invalid-feedback">@lang('setting.Confirm Password Help')</div>
                    @enderror
                  </div>
                </div>

                <div class="col-12">
                  <div class="alert alert-warning border-start border-3 mb-0">
                    <i class="bi bi-shield-lock me-1"></i>
                    @lang('setting.Password Hint')
                  </div>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2">
                  <x-button.save>
                    @lang('setting.Update Password Button')
                  </x-button.save>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
  @once('password-toggle-script')
    @include('components.password-toggle-script')
  @endonce
@endpush
