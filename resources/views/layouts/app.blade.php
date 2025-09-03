<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    @include('layouts.head')
</head>
<body>

  <main>
    <div class="container">
      <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
        <div class="container">
          <div class="row justify-content-center">
            <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

              {{-- شريط علوي صغير: شعار + تبديل لغة --}}
              <div class="d-flex justify-content-between align-items-center w-100 py-3">
                <a href="{{ url('/') }}" class="logo d-flex align-items-center w-auto text-decoration-none">
                  @if (!empty($setting) && !empty($setting->logo))
                    <img src="{{ asset('storage/' . $setting->logo) }}" alt="Logo" style="height: 40px;">
                  @else
                    <img src="{{ asset('assets/img/logo.png') }}" alt="Logo" style="height: 40px;">
                  @endif
                  @php
                    $isAr = app()->getLocale() === 'ar';
                    $brandBase = $setting->name ?? config('app.name', '');
                    $brandName = $isAr ? ($setting->name_ar ?? $brandBase) : ($setting->name_en ?? $brandBase);
                  @endphp
                  <span class="d-none d-lg-block ms-2 fw-semibold">
                    {{ $brandName }}
                  </span>
                </a>

                {{-- زر تبديل اللغة --}}
                <div class="ms-2">
                  @php $isAr = app()->getLocale() === 'ar'; @endphp
                  <div class="btn-group btn-group-sm" role="group" aria-label="{{ __('Switch language') }}">
                    <a href="{{ route('lang.switch', 'ar') }}"
                       class="btn btn-outline-primary {{ $isAr ? 'active disabled' : '' }}"
                       aria-disabled="{{ $isAr ? 'true' : 'false' }}"
                       title="{{ __('Arabic') }}">AR</a>
                    <a href="{{ route('lang.switch', 'en') }}"
                       class="btn btn-outline-primary {{ !$isAr ? 'active disabled' : '' }}"
                       aria-disabled="{{ !$isAr ? 'true' : 'false' }}"
                       title="{{ __('English') }}">EN</a>
                  </div>
                </div>
              </div>

              {{-- البطاقة --}}
              <div class="card w-100 shadow-sm mb-3">
                <div class="card-body">

                  {{-- عناوين ديناميكية --}}
                  <div class="pt-2 pb-3">
                    @hasSection('auth_title')
                      <h5 class="card-title text-center pb-0 fs-4" id="auth-title">@yield('auth_title')</h5>
                    @else
                      <h5 class="card-title text-center pb-0 fs-4" id="auth-title">@yield('title', __('Welcome'))</h5>
                    @endif

                    @hasSection('auth_subtitle')
                      <p class="text-center small mb-0" id="auth-subtitle">@yield('auth_subtitle')</p>
                    @else
                      <p class="text-center small mb-0" id="auth-subtitle">
                        {{ __('Please enter your credentials to continue') }}
                      </p>
                    @endif
                  </div>

                  {{-- فلاش نجاح --}}
                  @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert" aria-live="polite">
                      {{ session('success') }}
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
                    </div>
                  @endif

                  {{-- فلاش حالة عامة (مثل رابط إعادة تعيين) --}}
                  @if (session('status'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert" aria-live="polite">
                      {{ session('status') }}
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
                    </div>
                  @endif

                  {{-- قائمة أخطاء عامة (لو حبيت تظهرها في الأعلى) --}}
                  @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert" aria-live="polite">
                      <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                          <li>{{ $error }}</li>
                        @endforeach
                      </ul>
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
                    </div>
                  @endif

                  {{-- هنا يتحقن الفورم من الصفحة الفرعية (login/register) --}}
                  @yield('form')

                </div>
              </div>

              {{-- فوتر صغير --}}
              <div class="text-muted small mt-2">
                &copy; {{ date('Y') }} {{  $brandName }} 
              </div>

            </div>
          </div>
        </div>
      </section>
    </div>
  </main>

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short"></i>
  </a>

  @include('layouts.script')

  {{-- تفعيل فحص Bootstrap عند الإرسال فقط (اختياري) --}}
  <script>
    (function () {
      'use strict';
      const forms = document.querySelectorAll('.needs-validation');
      Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
          if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
          }
          form.classList.add('was-validated');
        }, false);
      });
    })();
  </script>

  @stack('scripts')
</body>
</html>
