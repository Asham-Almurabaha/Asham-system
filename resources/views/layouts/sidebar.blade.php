@php
  // Helpers بسيطة للـ active/collapse
  $isRoute = fn($pattern) => Request::routeIs($pattern);
  $isPath  = fn($pattern) => Request::is($pattern);
  $active  = fn($cond) => $cond ? 'active' : '';
  $open    = fn($cond) => $cond ? 'show' : '';
  $coll    = fn($cond) => $cond ? '' : 'collapsed';

  // هل مجموعة الإعدادات مفتوحة؟
  $settingsOpen = $isPath('*/setting*')
      || $isRoute('settings.*') || $isRoute('nationalities.*') || $isRoute('titles.*')
      || $isRoute('contract_statuses.*') || $isRoute('contract_types.*')
      || $isRoute('installment_statuses.*') || $isRoute('installment_types.*')
      || $isRoute('products.*') || $isRoute('product_entries.*')
      || $isRoute('bank_cash_accounts.*') || $isRoute('transaction_types.*') || $isRoute('transaction_statuses.*')
      || $isRoute('categories.*');
@endphp

<ul class="sidebar-nav" id="sidebar-nav">

  {{-- لوحة التحكم --}}
  <li class="nav-item">
    <a class="nav-link {{ $coll($isRoute('dashboard')) }} {{ $active($isRoute('dashboard')) }}"
       href="{{ route('dashboard') }}">
      <i class="bi bi-speedometer2"></i><span>لوحة التحكم</span>
    </a>
  </li>

  {{-- العملاء --}}
  <li class="nav-item">
    <a class="nav-link {{ $coll($isRoute('customers.*')) }} {{ $active($isRoute('customers.*')) }}"
       href="{{ route('customers.index') }}">
      <i class="bi bi-people"></i><span>العملاء</span>
    </a>
  </li>

  {{-- الكفلاء --}}
  <li class="nav-item">
    <a class="nav-link {{ $coll($isRoute('guarantors.*')) }} {{ $active($isRoute('guarantors.*')) }}"
       href="{{ route('guarantors.index') }}">
      <i class="bi bi-person-bounding-box"></i><span>الكفلاء</span>
    </a>
  </li>

  {{-- المستثمرين --}}
  <li class="nav-item">
    <a class="nav-link {{ $coll($isRoute('investors.*')) }} {{ $active($isRoute('investors.*')) }}"
       href="{{ route('investors.index') }}">
      <i class="bi bi-briefcase"></i><span>المستثمرين</span>
    </a>
  </li>

  {{-- العقود --}}
  <li class="nav-item">
    <a class="nav-link {{ $coll($isRoute('contracts.*')) }} {{ $active($isRoute('contracts.*')) }}"
       href="{{ route('contracts.index') }}">
      <i class="bi bi-file-earmark-text"></i><span>العقود</span>
    </a>
  </li>

  {{-- الإعدادات (قابلة للطي) --}}
  <li class="nav-item">
    <a class="nav-link {{ $coll($settingsOpen) }}"
       data-bs-target="#settings-nav" data-bs-toggle="collapse" href="#"
       aria-expanded="{{ $settingsOpen ? 'true' : 'false' }}">
      <i class="bi bi-gear"></i><span>@lang('sidebar.Settings')</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="settings-nav" class="nav-content collapse {{ $open($settingsOpen) }}" data-bs-parent="#sidebar-nav">
      <li class="nav-heading">الإعدادات العامة</li>
      <li>
        <a class="{{ $active($isRoute('settings.index')) }}" href="{{ route('settings.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.General Setting')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('nationalities.*')) }}" href="{{ route('nationalities.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Nationalities')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('titles.*')) }}" href="{{ route('titles.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.titles')</span>
        </a>
      </li>

      <li class="nav-heading">إعدادات العقود</li>
      <li>
        <a class="{{ $active($isRoute('contract_statuses.*')) }}" href="{{ route('contract_statuses.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Contract Statuses')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('contract_types.*')) }}" href="{{ route('contract_types.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Contract Types')</span>
        </a>
      </li>

      <li class="nav-heading">إعدادات الأقساط</li>
      <li>
        <a class="{{ $active($isRoute('installment_statuses.*')) }}" href="{{ route('installment_statuses.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Installment Statuses')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('installment_types.*')) }}" href="{{ route('installment_types.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Installment Types')</span>
        </a>
      </li>

      <li class="nav-heading">إعدادات البضائع</li>
      <li>
        <a class="{{ $active($isRoute('products.*')) }}" href="{{ route('products.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Products')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('product_entries.*')) }}" href="{{ route('product_entries.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Product Entries')</span>
        </a>
      </li>

      <li class="nav-heading">إعدادات الحسابات</li>
      <li>
        <a class="{{ $active($isRoute('bank_cash_accounts.*')) }}" href="{{ route('bank_cash_accounts.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Bank Cash Accounts')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('transaction_types.*')) }}" href="{{ route('transaction_types.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Transaction Types')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('transaction_statuses.*')) }}" href="{{ route('transaction_statuses.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Transaction Statuses')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('categories.*')) }}" href="{{ route('categories.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Categories')</span>
        </a>
      </li>
    </ul>
  </li>
</ul>
