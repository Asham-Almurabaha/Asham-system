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
  $installmentsNotifications = $notificationsData['installments'] ?? ['count' => 0, 'items' => []];
  $installmentsCount = (int) ($installmentsNotifications['count'] ?? 0);
  $installmentsItems = collect($installmentsNotifications['items'] ?? []);
  $debtsNotifications = $notificationsData['debts'] ?? ['count' => 0, 'items' => []];
  $debtsCount = (int) ($debtsNotifications['count'] ?? 0);
  $debtsItems = collect($debtsNotifications['items'] ?? []);
  $expensesNotifications = $notificationsData['expenses'] ?? ['count' => 0, 'items' => []];
  $expensesCount = (int) ($expensesNotifications['count'] ?? 0);
  $expensesItems = collect($expensesNotifications['items'] ?? []);
  $notesNotifications = $notificationsData['notes'] ?? ['count' => 0, 'items' => []];
  $notesCount = (int) ($notesNotifications['count'] ?? 0);
  $notesItems = collect($notesNotifications['items'] ?? []);
  $canViewNotes = auth()->user()?->can('notes.index') ?? false;
  $canViewContracts = auth()->user()?->can('contracts.index') ?? false;
  $canViewDebts = auth()->user()?->can('debts.index') ?? false;
  $canViewExpenses = auth()->user()?->can('expenses.expenses.index') ?? false;

  if (!$canViewContracts) {
    $installmentsCount = 0;
    $installmentsItems = collect();
  }

  if (!$canViewDebts) {
    $debtsCount = 0;
    $debtsItems = collect();
  }

  if (!$canViewExpenses) {
    $expensesCount = 0;
    $expensesItems = collect();
  }

  if (!$canViewNotes) {
    $notesCount = 0;
    $notesItems = collect();
  }

  $notificationsTotal = 0;

  if ($canViewContracts) {
    $notificationsTotal += $installmentsCount;
  }

  if ($canViewDebts) {
    $notificationsTotal += $debtsCount;
  }

  if ($canViewExpenses) {
    $notificationsTotal += $expensesCount;
  }

  if ($canViewNotes) {
    $notificationsTotal += $notesCount;
  }

  $notificationsTotal += $zakatCount;

  $formatDate = static function (?string $date, string $locale, string $format = 'Y-m-d') {
    if (empty($date)) {
      return null;
    }

    try {
      return \Illuminate\Support\Carbon::parse($date)->locale($locale)->translatedFormat($format);
    } catch (\Throwable $e) {
      return $date;
    }
  };

  $formatNumber = static function ($number, int $decimals = 2) {
    return number_format((float) $number, $decimals);
  };

  $buildStatus = static function (?int $daysOverdue, ?int $daysUntilDue, string $type) {
    $daysOverdue = $daysOverdue !== null ? (int) $daysOverdue : null;
    $daysUntilDue = $daysUntilDue !== null ? (int) $daysUntilDue : null;

    if ($daysOverdue !== null && $daysOverdue > 0) {
      return [
        'type' => 'overdue',
        'label' => trans_choice("notifications.{$type}_status_overdue", $daysOverdue, ['count' => number_format($daysOverdue)]),
      ];
    }

    if ($daysUntilDue !== null && $daysUntilDue > 0) {
      return [
        'type' => 'upcoming',
        'label' => trans_choice("notifications.{$type}_status_upcoming", $daysUntilDue, ['count' => number_format($daysUntilDue)]),
      ];
    }

    if (($daysOverdue === 0 && $daysOverdue !== null) || ($daysUntilDue === 0 && $daysUntilDue !== null)) {
      return [
        'type' => 'today',
        'label' => __('notifications.' . $type . '_status_due_today'),
      ];
    }

    return null;
  };

  $notificationSections = [];

  if ($canViewContracts && $installmentsItems->isNotEmpty()) {
    $notificationSections[] = [
      'key' => 'installments',
      'item_class' => 'installment',
      'title' => __('notifications.installments_title'),
      'badge_variant' => 'primary',
      'count_label' => trans_choice('notifications.installments_count', $installmentsCount, ['count' => number_format($installmentsCount)]),
      'items' => $installmentsItems->map(function ($item) use ($locale, $formatDate, $formatNumber) {
        $remainingAmount = $formatNumber($item['remaining_amount'] ?? $item['due_amount'] ?? 0);
        $currency = $item['currency'] ?? config('app.currency_symbol', 'ر.س');
        $dueDate = $formatDate($item['due_date'] ?? null, $locale);
        $meta = [];

        if (!empty($item['contract_number'])) {
          $meta[] = __('notifications.installments_contract_number', ['number' => $item['contract_number']]);
        }

        if (!empty($item['installment_number'])) {
          $meta[] = __('notifications.installments_installment_number', ['number' => number_format((int) $item['installment_number'])]);
        }

        $meta[] = __('notifications.installments_remaining_amount', [
          'amount' => $remainingAmount,
          'currency' => $currency,
        ]);

        $statusLabel = $dueDate
          ? __('notifications.installments_due_today', ['date' => $dueDate])
          : __('notifications.debts_status_due_today');

        return [
          'url' => route('contracts.show', $item['contract_id']),
          'icon' => 'bi-calendar-event',
          'title' => $item['customer_name'] ?? __('notifications.installments_unknown_customer'),
          'meta' => $meta,
          'status' => [
            'type' => 'today',
            'label' => $statusLabel,
          ],
        ];
      })->values()->all(),
    ];
  }

  if ($canViewDebts && $debtsItems->isNotEmpty()) {
    $notificationSections[] = [
      'key' => 'debts',
      'item_class' => 'debt',
      'title' => __('notifications.debts_title'),
      'badge_variant' => 'danger',
      'count_label' => trans_choice('notifications.debts_count', $debtsCount, ['count' => number_format($debtsCount)]),
      'items' => $debtsItems->map(function ($item) use ($locale, $formatDate, $formatNumber, $buildStatus) {
        $dueDate = $formatDate($item['due_date'] ?? null, $locale);
        $status = $buildStatus($item['days_overdue'] ?? null, $item['days_until_due'] ?? null, 'debts');

        return [
          'url' => route('debts.edit', $item['id']),
          'icon' => 'bi-cash-coin',
          'title' => $item['title'] ?? __('notifications.debts_unknown_party'),
          'meta' => array_values(array_filter([
            __('notifications.debts_amount_remaining', [
              'amount' => $formatNumber($item['remaining_amount'] ?? 0),
              'currency' => $item['currency'] ?? config('app.currency_symbol', 'ر.س'),
            ]),
            $dueDate ? __('notifications.debts_due_date', ['date' => $dueDate]) : null,
          ])),
          'status' => $status,
        ];
      })->values()->all(),
    ];
  }

  if ($canViewExpenses && $expensesItems->isNotEmpty()) {
    $notificationSections[] = [
      'key' => 'expenses',
      'item_class' => 'expense',
      'title' => __('notifications.expenses_title'),
      'badge_variant' => 'success',
      'count_label' => trans_choice('notifications.expenses_count', $expensesCount, ['count' => number_format($expensesCount)]),
      'items' => $expensesItems->map(function ($item) use ($locale, $formatDate, $formatNumber, $buildStatus) {
        $dueDate = $formatDate($item['due_date'] ?? null, $locale);
        $status = $buildStatus($item['days_overdue'] ?? null, $item['days_until_due'] ?? null, 'expenses');

        return [
          'url' => route('expenses.expenses.show', $item['id']),
          'icon' => 'bi-wallet2',
          'title' => $item['title'],
          'meta' => array_values(array_filter([
            __('notifications.expenses_amount', [
              'amount' => $formatNumber($item['amount'] ?? 0),
              'currency' => $item['currency'] ?? config('app.currency_symbol', 'ر.س'),
            ]),
            __('notifications.expenses_type', ['type' => $item['type'] ?? __('notifications.expenses_unknown_type')]),
            $dueDate ? __('notifications.expenses_due_date', ['date' => $dueDate]) : null,
          ])),
          'status' => $status,
        ];
      })->values()->all(),
    ];
  }

  if ($canViewNotes && $notesItems->isNotEmpty()) {
    $notificationSections[] = [
      'key' => 'notes',
      'item_class' => 'note',
      'title' => __('notifications.notes_title'),
      'badge_variant' => 'info',
      'count_label' => trans_choice('notifications.notes_due_count', $notesCount, ['count' => number_format($notesCount)]),
      'items' => $notesItems->map(function ($item) use ($locale, $formatDate) {
        $reminderAt = $formatDate($item['reminder_at'] ?? null, $locale, 'Y-m-d H:i');
        $diffDays = $item['diff_days'] ?? null;
        $status = null;

        if (!empty($item['is_due'])) {
          if (!empty($item['is_overdue']) && $diffDays !== null && $diffDays > 0) {
            $status = [
              'type' => 'overdue',
              'label' => trans_choice('notes.notifications.overdue', (int) $diffDays, ['count' => number_format((int) $diffDays)]),
            ];
          } else {
            $status = [
              'type' => 'today',
              'label' => __('notes.notifications.due_now'),
            ];
          }
        } elseif ($diffDays !== null && $diffDays < 0) {
          $daysUntil = abs((int) $diffDays);
          $status = [
            'type' => 'upcoming',
            'label' => trans_choice('notes.notifications.upcoming', $daysUntil, ['count' => number_format($daysUntil)]),
          ];
        }

        return [
          'url' => route('notes.edit', $item['id']),
          'icon' => 'bi-stickies',
          'title' => $item['title'],
          'meta' => array_values(array_filter([
            $reminderAt ? __('notes.notifications.reminder_on', ['date' => $reminderAt]) : null,
          ])),
          'status' => $status,
        ];
      })->values()->all(),
    ];
  }

  if ($zakatItems->isNotEmpty()) {
    $notificationSections[] = [
      'key' => 'zakat',
      'item_class' => 'zakat',
      'title' => __('notifications.zakat_due_title'),
      'badge_variant' => 'warning',
      'count_label' => trans_choice('notifications.zakat_due_count', $zakatCount, ['count' => number_format($zakatCount)]),
      'items' => $zakatItems->map(function ($item) use ($locale, $formatDate, $formatNumber) {
        $dueDate = $formatDate($item['due_date'] ?? null, $locale);
        $daysOverdue = $item['days_overdue'] ?? null;
        $status = null;

        if ($daysOverdue !== null) {
          $daysOverdue = (int) $daysOverdue;
          $status = [
            'type' => $daysOverdue > 0 ? 'overdue' : 'today',
            'label' => trans_choice('notifications.zakat_due_days_overdue', $daysOverdue, ['days' => number_format($daysOverdue)]),
          ];
        }

        return [
          'url' => route('investors.show', $item['id']),
          'icon' => 'bi-exclamation-triangle',
          'title' => $item['name'],
          'meta' => array_values(array_filter([
            __('notifications.zakat_due_amount', [
              'amount' => $formatNumber($item['amount'] ?? 0),
              'currency' => $item['currency'] ?? config('app.currency_symbol', 'ر.س'),
            ]),
            $dueDate ? __('notifications.zakat_due_due_date', ['date' => $dueDate]) : null,
          ])),
          'status' => $status,
        ];
      })->values()->all(),
    ];
  }

  $badgeClassMap = [
    'primary' => 'bg-primary-subtle text-primary',
    'success' => 'bg-success-subtle text-success',
    'warning' => 'bg-warning-subtle text-warning',
    'danger' => 'bg-danger-subtle text-danger',
    'info' => 'bg-info-subtle text-info',
  ];

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

        @if(empty($notificationSections))
          <li class="notifications-empty">{{ __('notifications.no_notifications') }}</li>
        @else
          <li class="notifications-body p-0">
            <div class="notifications-scroll" role="presentation">
              <ul class="notifications-list list-unstyled mb-0">
                @foreach($notificationSections as $section)
                  <li class="dropdown-header d-flex align-items-center justify-content-between pt-3 pb-2">
                    <span class="notifications-heading small text-uppercase fw-semibold">{{ $section['title'] }}</span>
                    @php($badgeClass = $badgeClassMap[$section['badge_variant']] ?? 'bg-primary-subtle text-primary')
                    <span class="badge {{ $badgeClass }}">
                      {{ $section['count_label'] }}
                    </span>
                  </li>
                  @foreach($section['items'] as $item)
                    <li class="notification-item notification-item--{{ $section['item_class'] }}">
                      <a class="notification-link" href="{{ $item['url'] }}">
                        <span class="notification-icon" aria-hidden="true">
                          <i class="bi {{ $item['icon'] }}"></i>
                        </span>
                        <div class="notification-content">
                          <span class="notification-title">{{ $item['title'] }}</span>
                          @foreach($item['meta'] as $meta)
                            <span class="notification-meta">{{ $meta }}</span>
                          @endforeach
                          @if(!empty($item['status']['label']))
                            @php($statusType = $item['status']['type'] ?? 'default')
                            <span class="notification-status notification-status--{{ $statusType }}">{{ $item['status']['label'] }}</span>
                          @endif
                        </div>
                      </a>
                    </li>
                  @endforeach
                @endforeach
              </ul>
            </div>
          </li>
        @endif
      </ul>
    </li>

    {{-- تبديل اللغة --}}
    <li class="nav-item">
      <a class="nav-link nav-icon d-flex align-items-center"
        href="{{ route('lang.switch', $locale === 'ar' ? 'en' : 'ar') }}"
        aria-label="{{ __('app.switch_language') }}"
        title="{{ __('app.switch_language') }}">
        <i class="bi bi-translate me-1"></i>
        <span>{{ strtoupper($locale) }}</span>
      </a>
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
