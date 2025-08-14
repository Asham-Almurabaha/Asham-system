@extends('layouts.app')

@section('title', __('Login'))
@section('auth_subtitle', __('Enter your email & password to login'))

@section('form')
  <form method="POST"
        action="{{ route('login') }}"
        class="row g-3 needs-validation"
        novalidate>
    @csrf

    {{-- Email --}}
    <div class="col-12">
      <label for="email" class="form-label">{{ __('Email Address') }}</label>
      <div class="input-group has-validation">
        <span class="input-group-text" id="emailPrepend">@</span>
        <input  id="email"
                type="email"
                name="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email') }}"
                required
                dir="ltr"
                autocomplete="username"
                inputmode="email"
                autocapitalize="none"
                spellcheck="false"
                autofocus
                aria-describedby="emailPrepend emailHelp">
        @error('email')
          <div class="invalid-feedback d-block" id="emailHelp" aria-live="polite">
            <strong>{{ $message }}</strong>
          </div>
        @else
          <div class="invalid-feedback" id="emailHelp">{{ __('Please enter a valid email address.') }}</div>
        @enderror
      </div>
    </div>

    {{-- Password --}}
    <div class="col-12">
      <label for="password" class="form-label">{{ __('Password') }}</label>
      <div class="input-group has-validation">
        <input  id="password"
                type="password"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                required
                autocomplete="current-password"
                aria-describedby="togglePassword pwdHelp">
        <button class="btn btn-outline-secondary"
                type="button"
                id="togglePassword"
                tabindex="-1"
                aria-label="{{ __('Show/Hide password') }}">
          <i class="bi bi-eye"></i>
        </button>
        @error('password')
          <div class="invalid-feedback d-block" id="pwdHelp" aria-live="polite">
            <strong>{{ $message }}</strong>
          </div>
        @else
          <div class="invalid-feedback" id="pwdHelp">{{ __('Password is required.') }}</div>
        @enderror
      </div>
    </div>

    {{-- Remember Me --}}
    <div class="col-12">
      <div class="form-check">
        <input  id="remember"
                type="checkbox"
                name="remember"
                class="form-check-input"
                {{ old('remember') ? 'checked' : '' }}>
        <label class="form-check-label" for="remember">{{ __('Remember Me') }}</label>
      </div>
    </div>

    {{-- Actions --}}
    <div class="col-12 d-flex flex-column gap-2">
      <button class="btn btn-primary w-100" type="submit">{{ __('Login') }}</button>

      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
        @if (Route::has('password.request'))
          <a class="link-secondary small" href="{{ route('password.request') }}">
            {{ __('Forgot Your Password?') }}
          </a>
        @endif

        <p class="small mb-0">
          {{ __("Don't have account?") }}
          <a href="{{ route('register') }}">{{ __('Create an account') }}</a>
        </p>
      </div>
    </div>
  </form>
@endsection

@push('scripts')
<script>
(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    // Bootstrap validation on submit only
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });

    // Toggle password visibility
    const toggleBtn = document.getElementById('togglePassword');
    const pwdInput  = document.getElementById('password');
    if (toggleBtn && pwdInput) {
      toggleBtn.addEventListener('click', function () {
        const isText = pwdInput.type === 'text';
        pwdInput.type = isText ? 'password' : 'text';
        this.innerHTML = isText ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
      });
    }
  });
})();
</script>
@endpush
