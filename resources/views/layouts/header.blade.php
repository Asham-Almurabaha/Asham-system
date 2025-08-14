@php
  $logo    = $setting->logo ?? null;
  $name    = $setting->name ?? config('app.name', 'اسم الشركة');
  $homeUrl = url('/');
  $locale  = app()->getLocale();
  $currentLocaleBadge = strtoupper($locale); // AR أو EN
@endphp

<div class="d-flex align-items-center justify-content-between w-100 pe-3">
  <a href="{{ $homeUrl }}" class="logo d-flex align-items-center text-decoration-none">
    @if ($logo)
      <img src="{{ asset('storage/'.$logo) }}" alt="Logo" style="height: 40px;">
    @else
      <img src="{{ asset('assets/img/logo.png') }}" alt="Logo" style="height: 40px;">
    @endif
    <span class="d-none d-lg-block ms-2 fw-semibold">{{ $name }}</span>
  </a>

  <button class="btn p-0 border-0 bg-transparent" type="button" aria-label="{{ __('Toggle sidebar') }}">
    <i class="bi bi-list toggle-sidebar-btn fs-4"></i>
  </button>
</div>

<nav class="header-nav ms-auto ps-3">
  <ul class="d-flex align-items-center mb-0">

    {{-- بحث للجوال (اختياري) --}}
    <li class="nav-item d-block">
      <a class="nav-link nav-icon" href="#" data-bs-toggle="collapse" data-bs-target="#header-search"
         aria-expanded="false" aria-controls="header-search" title="{{ __('Search') }}">
        <i class="bi bi-search"></i>
      </a>
    </li>

    {{-- تبديل اللغة --}}
    <li class="nav-item dropdown">
      <a class="nav-link nav-icon d-flex align-items-center" href="#" role="button"
         data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('Switch language') }}">
        <i class="bi bi-translate me-1"></i>
        <span class="badge bg-primary badge-number">{{ $currentLocaleBadge }}</span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
        <li>
          @if($locale === 'ar')
            <a class="dropdown-item d-flex align-items-center active disabled" href="#" aria-disabled="true">
              <span class="me-2">🇸🇦</span> <span>{{ __('Arabic') }}</span>
            </a>
          @else
            <a class="dropdown-item d-flex align-items-center" href="{{ route('lang.switch', 'ar') }}">
              <span class="me-2">🇸🇦</span> <span>{{ __('Arabic') }}</span>
            </a>
          @endif
        </li>
        <li>
          @if($locale === 'en')
            <a class="dropdown-item d-flex align-items-center active disabled" href="#" aria-disabled="true">
              <span class="me-2">🇺🇸</span> <span>{{ __('English') }}</span>
            </a>
          @else
            <a class="dropdown-item d-flex align-items-center" href="{{ route('lang.switch', 'en') }}">
              <span class="me-2">🇺🇸</span> <span>{{ __('English') }}</span>
            </a>
          @endif
        </li>
      </ul>
    </li>

    @if (Auth::check())
      <li class="nav-item dropdown pe-3">
        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#"
           data-bs-toggle="dropdown" aria-expanded="false">
          <img src="{{ asset('assets/img/profile-img.jpg') }}" alt="Profile" class="rounded-circle" width="36" height="36">
          <span class="d-none d-md-block dropdown-toggle ps-2">{{ Auth::user()->name }}</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
          <li class="dropdown-header">
            <div class="fw-semibold">{{ Auth::user()->name }}</div>
            <small class="text-muted">{{ __('Web User') }}</small>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item d-flex align-items-center" href="{{ route('logout') }}"
               onclick="event.preventDefault();document.getElementById('logout-form').submit();">
              <i class="bi bi-box-arrow-right me-2"></i>
              <span>{{ __('Logout') }}</span>
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
              @csrf
            </form>
          </li>
        </ul>
      </li>
    @endif

  </ul>
</nav>
