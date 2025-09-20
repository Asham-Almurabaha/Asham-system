@php
  $logo    = $setting->logo ?? null;
  $name    = $setting->name ?? config('app.name', 'اسم الشركة');
  $homeUrl = url('/');
  $locale  = app()->getLocale();
  $currentLocaleBadge = strtoupper($locale); // AR أو EN
  $notificationsData = $headerNotifications ?? [];
  $notificationsTotal = (int) ($notificationsData['total'] ?? 0);
  $zakatNotifications = $notificationsData['zakat'] ?? ['count' => 0, 'items' => []];
  $zakatCount = (int) ($zakatNotifications['count'] ?? 0);
  $zakatItems = collect($zakatNotifications['items'] ?? []);
@endphp

<div class="d-flex align-items-center justify-content-between w-100 pe-3">
  <a href="{{ $homeUrl }}" class="logo d-flex align-items-center text-decoration-none" aria-label="{{ __('Home') }}">
    @if ($logo)
      <img src="{{ asset('storage/'.$logo) }}" alt="Logo" style="height: 40px;">
    @else
      <img src="{{ asset('assets/img/logo.png') }}" alt="Logo" style="height: 40px;">
    @endif
    <span class="d-none d-lg-block ms-2 fw-semibold">{{ $locale === 'ar' ? ($setting->name_ar ?? $name) : ($setting->name_en ?? $name) }}</span>
  </a>

  <x-button.action type="button" variant="link" class="p-0 border-0 bg-transparent text-dark" aria-label="{{ __('Toggle sidebar') }}">
    <i class="bi bi-list toggle-sidebar-btn fs-4"></i>
  </x-button.action>
</div>

<nav class="header-nav ms-auto ps-3">
  <ul class="d-flex align-items-center mb-0">

    {{-- بحث للجوال (اختياري) --}}
    <li class="nav-item d-block">
      <a class="nav-link nav-icon" href="#" data-bs-toggle="collapse" data-bs-target="#header-search"
         aria-expanded="false" aria-controls="header-search" aria-label="{{ __('Search') }}" title="{{ __('Search') }}">
        <i class="bi bi-search"></i>
      </a>
    </li>

    {{-- التنبيهات --}}
    <li class="nav-item dropdown">
      <a class="nav-link nav-icon" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"
         aria-label="{{ __('notifications.title') }}" title="{{ __('notifications.title') }}">
        <i class="bi bi-bell"></i>
        @if($notificationsTotal > 0)
          <span class="badge bg-danger badge-number">{{ $notificationsTotal }}</span>
        @endif
      </a>
      <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications shadow-sm">
        <li class="dropdown-header notifications-header">
          <div>
            <span class="notifications-heading">{{ __('notifications.title') }}</span>
            <span class="notifications-subheading">{{ __('notifications.zakat_due_title') }}</span>
          </div>
          <span class="badge bg-primary-subtle text-primary notifications-count">
            {{ trans_choice('notifications.zakat_due_count', $zakatCount, ['count' => number_format($zakatCount)]) }}
          </span>
        </li>

        @forelse ($zakatItems as $item)
          @php
            $dueDate = \Illuminate\Support\Carbon::parse($item['due_date'])->locale($locale);
            $formattedDate = $dueDate->translatedFormat('Y-m-d');
            $daysOverdue = $item['days_overdue'];
          @endphp
          <li class="notification-item">
            <a class="notification-link" href="{{ route('investors.show', $item['id']) }}">
              <span class="notification-icon" aria-hidden="true">
                <i class="bi bi-exclamation-triangle"></i>
              </span>
              <div class="notification-content">
                <span class="notification-title">{{ $item['name'] }}</span>
                <span class="notification-meta">
                  {{ __('notifications.zakat_due_amount', ['amount' => number_format((float) $item['amount'], 2), 'currency' => $item['currency']]) }}
                </span>
                <span class="notification-meta">
                  {{ __('notifications.zakat_due_due_date', ['date' => $formattedDate]) }}
                </span>
                @if(!is_null($daysOverdue) && $daysOverdue >= 0)
                  <span class="notification-status">
                    {{ trans_choice('notifications.zakat_due_days_overdue', (int) $daysOverdue, ['days' => number_format((int) $daysOverdue)]) }}
                  </span>
                @endif
              </div>
            </a>
          </li>
        @empty
          <li class="notifications-empty">{{ __('notifications.no_notifications') }}</li>
        @endforelse

        <li class="dropdown-footer notifications-footer">
          <a class="notifications-footer-link" href="{{ route('investors.index') }}">{{ __('notifications.view_all') }}</a>
        </li>
      </ul>
    </li>

    {{-- تبديل اللغة --}}
    <li class="nav-item dropdown">
      <a class="nav-link nav-icon d-flex align-items-center" href="#" role="button"
         data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Switch language') }}" title="{{ __('Switch language') }}">
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
      <li class="nav-item dropdown ">
        <a class="nav-link nav-profile dropdown-toggle d-flex align-items-center" href="#"
           data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('User menu') }}">
          <img src="{{ asset('assets/img/profile-img.jpg') }}" alt="Profile" class="rounded-circle" width="36" height="36">
          <span class="d-none d-md-block ps-2">{{ Auth::user()->name }}</span>
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
