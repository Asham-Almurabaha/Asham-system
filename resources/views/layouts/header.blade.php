@php
  $logo    = $setting->logo ?? null;
  $name    = $setting->name ?? config('app.name', 'اسم الشركة');
  $homeUrl = url('/');
  $locale  = app()->getLocale();
  $localeParts = explode('_', $locale, 2);
  $localeRoot = strtolower($localeParts[0] ?? $locale);
  $rtlLocales = ['ar', 'he', 'fa', 'ur'];
  $isRtl = in_array($localeRoot, $rtlLocales, true);
  $direction = $isRtl ? 'rtl' : 'ltr';
  $textAlignmentClass = $isRtl ? 'text-end' : 'text-start';
  $currentLocaleBadge = strtoupper($locale); // AR أو EN
  $notificationsData = $headerNotifications ?? [];
  $notificationsTotal = (int) ($notificationsData['total'] ?? 0);
  $zakatNotifications = $notificationsData['zakat'] ?? ['count' => 0, 'items' => []];
  $zakatCount = (int) ($zakatNotifications['count'] ?? 0);
  $zakatItems = collect($zakatNotifications['items'] ?? []);
  $notesNotifications = $notificationsData['notes'] ?? ['count' => 0, 'items' => []];
  $notesCount = (int) ($notesNotifications['count'] ?? 0);
  $notesItems = collect($notesNotifications['items'] ?? []);
  $canViewNotes = auth()->user()?->can('notes.index') ?? false;

  if (!$canViewNotes) {
    $notesCount = 0;
    $notesItems = collect();
  }
  $searchMinChars = 2;
  $searchConfig = [
    'endpoint' => route('global-search'),
    'min_length' => $searchMinChars,
    'placeholder' => __('search.placeholder'),
    'empty' => __('search.empty_state'),
    'min_length_message' => __('search.min_length', ['count' => $searchMinChars]),
    'loading' => __('search.loading'),
    'error' => __('search.error'),
    'no_results' => __('search.no_results'),
    'open' => __('search.open_record'),
  ];
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
      <button type="button" class="nav-link nav-icon btn btn-link p-0 border-0" data-bs-toggle="dropdown" data-bs-auto-close="outside"
         aria-expanded="false" aria-haspopup="true" aria-controls="header-notifications-menu"
         aria-label="{{ __('notifications.title') }}" title="{{ __('notifications.title') }}" id="header-notifications-toggle">
        <i class="bi bi-bell" aria-hidden="true"></i>
        @if($notificationsTotal > 0)
          <span class="badge bg-danger badge-number">{{ $notificationsTotal }}</span>
        @endif
      </button>
      <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications shadow-sm" role="menu"
          aria-labelledby="header-notifications-toggle" id="header-notifications-menu">
        <li class="dropdown-header notifications-header">
          <div>
            <span class="notifications-heading">{{ __('notifications.title') }}</span>
            <span class="notifications-subheading">{{ __('notifications.subtitle') }}</span>
          </div>
          <span class="badge bg-primary-subtle text-primary notifications-count">
            {{ trans_choice('notifications.total_count', $notificationsTotal, ['count' => number_format($notificationsTotal)]) }}
          </span>
        </li>

        @if($canViewNotes && $notesItems->isNotEmpty())
          <li class="dropdown-header d-flex align-items-center justify-content-between pt-3 pb-2">
            <span class="notifications-heading small text-uppercase fw-semibold">{{ __('notifications.notes_title') }}</span>
            <span class="badge bg-info-subtle text-info">
              {{ trans_choice('notifications.notes_due_count', $notesCount, ['count' => number_format($notesCount)]) }}
            </span>
          </li>
          @foreach($notesItems as $item)
            @php
              $reminderAt = $item['reminder_at'] ? \Illuminate\Support\Carbon::parse($item['reminder_at'])->locale($locale) : null;
              $formattedReminder = $reminderAt ? $reminderAt->translatedFormat('Y-m-d H:i') : null;
              $diffDays = $item['diff_days'] ?? null;
              $statusMessage = null;

              if (!empty($item['is_due'])) {
                  if (!empty($item['is_overdue']) && $diffDays !== null && $diffDays > 0) {
                      $statusMessage = trans_choice('notes.notifications.overdue', (int) $diffDays, ['count' => number_format((int) $diffDays)]);
                  } else {
                      $statusMessage = __('notes.notifications.due_now');
                  }
              } elseif ($reminderAt) {
                  if ($diffDays !== null) {
                      $daysUntil = abs((int) $diffDays);
                      if ($daysUntil === 0) {
                          $statusMessage = __('notes.notifications.due_today');
                      } elseif ($daysUntil > 0) {
                          $statusMessage = trans_choice('notes.notifications.upcoming', $daysUntil, ['count' => number_format($daysUntil)]);
                      }
                  }
              }
            @endphp
            <li class="notification-item">
              <a class="notification-link" href="{{ route('notes.edit', $item['id']) }}">
                <span class="notification-icon" aria-hidden="true">
                  <i class="bi bi-stickies"></i>
                </span>
                <div class="notification-content">
                  <span class="notification-title">{{ $item['title'] }}</span>
                  @if($formattedReminder)
                    <span class="notification-meta">{{ __('notes.notifications.reminder_on', ['date' => $formattedReminder]) }}</span>
                  @endif
                  @if($statusMessage)
                    <span class="notification-status">{{ $statusMessage }}</span>
                  @endif
                </div>
              </a>
            </li>
          @endforeach
        @endif

        @if($zakatItems->isNotEmpty())
          <li class="dropdown-header d-flex align-items-center justify-content-between pt-3 pb-2">
            <span class="notifications-heading small text-uppercase fw-semibold">{{ __('notifications.zakat_due_title') }}</span>
            <span class="badge bg-warning-subtle text-warning">
              {{ trans_choice('notifications.zakat_due_count', $zakatCount, ['count' => number_format($zakatCount)]) }}
            </span>
          </li>
          @foreach ($zakatItems as $item)
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
          @endforeach
        @endif

        @if($notesItems->isEmpty() && $zakatItems->isEmpty())
          <li class="notifications-empty">{{ __('notifications.no_notifications') }}</li>
        @endif

        <li class="dropdown-footer notifications-footer">
          <div class="d-flex flex-column gap-2">
            @if($canViewNotes)
              <a class="notifications-footer-link" href="{{ route('notes.index') }}">{{ __('notes.notifications.view_all') }}</a>
            @endif
            <a class="notifications-footer-link" href="{{ route('investors.index') }}">{{ __('notifications.view_all') }}</a>
          </div>
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

<div id="header-search"
     class="header-search collapse"
     dir="{{ $direction }}"
     data-endpoint="{{ $searchConfig['endpoint'] }}"
     data-min-length="{{ $searchConfig['min_length'] }}"
     data-direction="{{ $direction }}"
     data-empty="{{ e($searchConfig['empty']) }}"
     data-min-length-message="{{ e($searchConfig['min_length_message']) }}"
     data-loading="{{ e($searchConfig['loading']) }}"
     data-error="{{ e($searchConfig['error']) }}"
     data-no-results="{{ e($searchConfig['no_results']) }}"
     data-open-label="{{ e($searchConfig['open']) }}">
  <div class="header-search-card card shadow-sm border-0">
    <div class="card-body p-3">
      <div class="d-flex align-items-center gap-2 mb-3">
        <div class="flex-grow-1">
          <div class="position-relative">
            <span class="header-search-input-icon" aria-hidden="true"><i class="bi bi-search"></i></span>
            <input type="search"
                   class="form-control header-search-input"
                   data-role="input"
                   dir="{{ $direction }}"
                   autocomplete="off"
                   spellcheck="false"
                   placeholder="{{ $searchConfig['placeholder'] }}"
                   aria-label="{{ __('Search') }}">
          </div>
        </div>
        <button type="button"
                class="btn btn-outline-secondary d-flex align-items-center justify-content-center"
                data-bs-toggle="collapse"
                data-bs-target="#header-search"
                aria-label="{{ __('general.Cancel') }}">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="header-search-status small text-muted {{ $textAlignmentClass }}" data-role="status" dir="{{ $direction }}">
        {{ $searchConfig['empty'] }}
      </div>

      <ul class="list-group list-group-flush header-search-results mt-3 {{ $textAlignmentClass }}" data-role="results" dir="{{ $direction }}" hidden></ul>
    </div>
  </div>
</div>
