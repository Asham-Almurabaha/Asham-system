@extends('layouts.app')

@section('title', __('Register'))
@section('auth_subtitle', __('Fill the fields below to create your account'))

@section('form')
  <form method="POST"
        action="{{ route('register') }}"
        class="row g-3 needs-validation"
        novalidate>
    @csrf

    {{-- Name --}}
    <div class="col-12">
      <label for="name" class="form-label">{{ __('Name') }}</label>
      <input  id="name"
              type="text"
              name="name"
              class="form-control @error('name') is-invalid @enderror"
              value="{{ old('name') }}"
              required
              autocomplete="name"
              autofocus
              aria-describedby="nameHelp">
      @error('name')
        <div class="invalid-feedback d-block" id="nameHelp" aria-live="polite"><strong>{{ $message }}</strong></div>
      @else
        <div class="invalid-feedback" id="nameHelp">{{ __('Please enter your name.') }}</div>
      @enderror
    </div>

    {{-- Email --}}
    <div class="col-12">
      <label for="email" class="form-label">{{ __('Email Address') }}</label>
      <div class="input-group has-validation">
        <span class="input-group-text">@</span>
        <input  id="email"
                type="email"
                name="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email') }}"
                required
                dir="ltr"
                autocomplete="email"
                inputmode="email"
                autocapitalize="none"
                spellcheck="false"
                aria-describedby="emailHelp">
        @error('email')
          <div class="invalid-feedback d-block" id="emailHelp" aria-live="polite"><strong>{{ $message }}</strong></div>
        @else
          <div class="invalid-feedback" id="emailHelp">{{ __('Please enter a valid email address.') }}</div>
        @enderror
      </div>
    </div>

    {{-- Phone --}}
    <div class="col-12">
      <label for="phone" class="form-label">{{ __('Phone Number') }}</label>
      <input  id="phone"
              type="tel"
              name="phone"
              class="form-control @error('phone') is-invalid @enderror"
              value="{{ old('phone') }}"
              required
              dir="ltr"
              autocomplete="tel"
              inputmode="tel"
              pattern="^[0-9+\-\s()]{6,}$"
              placeholder="+966 5XXXXXXXX"
              aria-describedby="phoneHelp">
      @error('phone')
        <div class="invalid-feedback d-block" id="phoneHelp" aria-live="polite"><strong>{{ $message }}</strong></div>
      @else
        <div class="invalid-feedback" id="phoneHelp">{{ __('Please enter a valid phone number.') }}</div>
      @enderror
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
                autocomplete="new-password"
                minlength="8"
                aria-describedby="togglePassword pwdHelp">
        <button class="btn btn-outline-secondary"
                type="button"
                id="togglePassword"
                tabindex="-1"
                aria-label="{{ __('Show/Hide password') }}">
          <i class="bi bi-eye"></i>
        </button>
        @error('password')
          <div class="invalid-feedback d-block" id="pwdHelp" aria-live="polite"><strong>{{ $message }}</strong></div>
        @else
          <div class="invalid-feedback" id="pwdHelp">{{ __('Password must be at least 8 characters.') }}</div>
        @enderror
      </div>
    </div>

    {{-- Confirm Password --}}
    <div class="col-12">
      <label for="password-confirm" class="form-label">{{ __('Confirm Password') }}</label>
      <div class="input-group has-validation">
        <input  id="password-confirm"
                type="password"
                name="password_confirmation"
                class="form-control"
                required
                autocomplete="new-password"
                aria-describedby="togglePasswordConfirm confirmFeedback">
        <button class="btn btn-outline-secondary"
                type="button"
                id="togglePasswordConfirm"
                tabindex="-1"
                aria-label="{{ __('Show/Hide password') }}">
          <i class="bi bi-eye"></i>
        </button>
        <div class="invalid-feedback" id="confirmFeedback">{{ __('Passwords do not match.') }}</div>
      </div>
    </div>

    {{-- Actions --}}
    <div class="col-12 d-flex flex-column gap-2">
      <button class="btn btn-primary w-100" type="submit">{{ __('Register') }}</button>
      <p class="small mb-0 text-center">
        {{ __('Already have an account?') }}
        <a href="{{ route('login') }}">{{ __('Log in') }}</a>
      </p>
    </div>
  </form>
@endsection

@push('scripts')
<script>
(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    // فحص Bootstrap عند الإرسال فقط
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
      form.addEventListener('submit', function (event) {
        validateConfirm();
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });

    // إظهار/إخفاء كلمات المرور
    bindToggle('togglePassword', 'password');
    bindToggle('togglePasswordConfirm', 'password-confirm');

    // فحص تأكيد كلمة المرور بشكل حيّ
    ['input','change','keyup'].forEach(function (evt) {
      document.getElementById('password')?.addEventListener(evt, validateConfirm);
      document.getElementById('password-confirm')?.addEventListener(evt, validateConfirm);
    });

    function bindToggle(btnId, inputId) {
      const btn = document.getElementById(btnId);
      const inp = document.getElementById(inputId);
      if (!btn || !inp) return;
      btn.addEventListener('click', function () {
        const isText = inp.type === 'text';
        inp.type = isText ? 'password' : 'text';
        this.innerHTML = isText ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
      });
    }

    function validateConfirm() {
      const pwd  = document.getElementById('password');
      const conf = document.getElementById('password-confirm');
      if (!pwd || !conf) return;
      if (conf.value && pwd.value !== conf.value) {
        conf.setCustomValidity('Mismatch');
      } else {
        conf.setCustomValidity('');
      }
    }
  });
})();
</script>
@endpush
