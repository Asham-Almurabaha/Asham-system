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
      || $isRoute('contract_statuses.*')
      || $isRoute('claim_first_parties.*')
      || $isRoute('guarantor_statuses.*')
      || $isRoute('contract_statuses.*')
      || $isRoute('installment_statuses.*')
      || $isRoute('installment_types.*')
      || $isRoute('transaction_statuses.*')
      || $isRoute('transaction_types.*')
      || $isRoute('categories.*')
      || $isRoute('accounts.bank-accounts.*')
      || $isRoute('accounts.safes.*')
      || $isRoute('product_types.*')
      || $isRoute('products.*')
      || $isRoute('product_entries.*')
      || $isRoute('users.*');

  // فتح مجموعة العقود؟
  $contractsOpen = $isRoute('contracts.*');

  // فتح مجموعة الحسابات؟
  $accountsOpen = $isRoute('ledger.*');
@endphp

<ul class="sidebar-nav" id="sidebar-nav">

  {{-- لوحة التحكم --}}
  <li class="nav-item">
    <a class="nav-link {{ $coll($isRoute('dashboard')) }} {{ $active($isRoute('dashboard')) }}"
       href="{{ route('dashboard') }}">
      <i class="bi bi-speedometer2"></i><span>@lang('sidebar.Dashboard')</span>
    </a>
  </li>

  {{-- Customers --}}
  <li class="nav-item">
    <a class="nav-link {{ $coll($isRoute('customers.*')) }} {{ $active($isRoute('customers.*')) }}"
       href="{{ route('customers.index') }}">
      <i class="bi bi-people"></i><span>@lang('sidebar.Customers')</span>
    </a>
  </li>

  {{-- Guarantors --}}
  <li class="nav-item">
    <a class="nav-link {{ $coll($isRoute('guarantors.*')) }} {{ $active($isRoute('guarantors.*')) }}"
       href="{{ route('guarantors.index') }}">
      <i class="bi bi-person-bounding-box"></i><span>@lang('sidebar.Guarantors')</span>
    </a>
  </li>

  {{-- المستثمرين --}}
  <li class="nav-item">
    <a class="nav-link {{ $coll($isRoute('investors.*')) }} {{ $active($isRoute('investors.*')) }}"
       href="{{ route('investors.index') }}">
      <i class="bi bi-briefcase"></i><span>@lang('sidebar.Investors')</span>
    </a>
  </li>

  {{-- العقود --}}
  <li class="nav-item">
    <a class="nav-link {{ $coll($contractsOpen) }}"
       data-bs-target="#contracts-nav" data-bs-toggle="collapse" href="#"
       aria-expanded="{{ $contractsOpen ? 'true' : 'false' }}">
      <i class="bi bi-file-earmark-text"></i><span>@lang('sidebar.Contracts')</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="contracts-nav" class="nav-content collapse {{ $open($contractsOpen) }}" data-bs-parent="#sidebar-nav">
      <li>
        <a class="{{ $active($isRoute('contracts.dashboard')) }}" href="{{ route('contracts.dashboard') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Contracts Dashboard')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute([
          'contracts.index',
          'contracts.create',
          'contracts.store',
          'contracts.show',
          'contracts.edit',
          'contracts.update',
          'contracts.destroy',
          'contracts.images.update',
          'contracts.import.*',
          'contracts.export.*',
          'contracts.print',
          'contracts.closure',
          'contracts.paid',
          'contracts.investors.store'
        ])) }}" href="{{ route('contracts.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Manage Contracts')</span>
        </a>
      </li>
      
    </ul>
  </li>

  {{-- المطالبات --}}
  <li class="nav-item">
    <a class="nav-link {{ $coll($isRoute('contract-claims.*')) }} {{ $active($isRoute('contract-claims.*')) }}"
       href="{{ route('contract-claims.index') }}">
      <i class="bi bi-exclamation-octagon"></i><span>@lang('sidebar.Claims')</span>
    </a>
  </li>

  {{-- الحسابات --}}
  <li class="nav-item">
    <a class="nav-link {{ $coll($accountsOpen) }}"
       data-bs-target="#accounts-nav" data-bs-toggle="collapse" href="#"
       aria-expanded="{{ $accountsOpen ? 'true' : 'false' }}">
      <i class="bi bi-wallet2"></i><span>@lang('sidebar.Accounts')</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="accounts-nav" class="nav-content collapse {{ $open($accountsOpen) }}" data-bs-parent="#sidebar-nav">
      <li>
        <a class="{{ $active($isRoute(['ledger.index'])) }}" href="{{ route('ledger.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Ledger')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute(['ledger.create', 'ledger.store'])) }}" href="{{ route('ledger.create') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Add Ledger Entry')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute(['ledger.transfer.create', 'ledger.transfer.store'])) }}" href="{{ route('ledger.transfer.create') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Internal Transfer')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute(['ledger.split.create', 'ledger.split.store'])) }}" href="{{ route('ledger.split.create') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Split Ledger Entry')</span>
        </a>
      </li>
    </ul>
  </li>

  

  {{-- الإعدادات (قابلة للطي) --}}
  @role('admin')
  <li class="nav-item">
    <a class="nav-link {{ $coll($settingsOpen) }}"
       data-bs-target="#settings-nav" data-bs-toggle="collapse" href="#"
       aria-expanded="{{ $settingsOpen ? 'true' : 'false' }}">
      <i class="bi bi-gear"></i><span>@lang('sidebar.Settings')</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="settings-nav" class="nav-content collapse {{ $open($settingsOpen) }}" data-bs-parent="#sidebar-nav">
      <li class="nav-heading">@lang('sidebar.General Settings')</li>
      <li>
        <a class="{{ $active($isRoute('settings.index')) }}" href="{{ route('settings.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.General Setting')</span>
        </a>
      </li>

      <li class="nav-heading">@lang('sidebar.People & Customers')</li>
      <li>
        <a class="{{ $active($isRoute('nationalities.*')) }}" href="{{ route('nationalities.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Nationalities')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('titles.*')) }}" href="{{ route('titles.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Titles')</span>
        </a>
      </li>

      <li class="nav-heading">@lang('sidebar.Statuses')</li>
      <li>
        <a class="{{ $active($isRoute('customer_statuses.*')) }}" href="{{ route('customer_statuses.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Customer Statuses')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('guarantor_statuses.*')) }}" href="{{ route('guarantor_statuses.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Guarantor Statuses')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('contract_statuses.*')) }}" href="{{ route('contract_statuses.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Contract Statuses')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('claim_statuses.*')) }}" href="{{ route('claim_statuses.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Claim Statuses')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('claim_payment_statuses.*')) }}" href="{{ route('claim_payment_statuses.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Claim Payment Statuses')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('claim_first_parties.*')) }}" href="{{ route('claim_first_parties.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Claim First Parties')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('installment_statuses.*')) }}" href="{{ route('installment_statuses.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Installment Statuses')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('transaction_statuses.*')) }}" href="{{ route('transaction_statuses.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Transaction Statuses')</span>
        </a>
      </li>

      <li class="nav-heading">@lang('sidebar.Installments')</li>
      <li>
        <a class="{{ $active($isRoute('installment_types.*')) }}" href="{{ route('installment_types.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Installment Types')</span>
        </a>
      </li>

      <li class="nav-heading">@lang('sidebar.Transactions & Finance')</li>
      <li>
        <a class="{{ $active($isRoute('categories.*')) }}" href="{{ route('categories.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Categories')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('transaction_types.*')) }}" href="{{ route('transaction_types.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Transaction Types')</span>
        </a>
      </li>

      <li class="nav-heading">@lang('sidebar.Accounts Group')</li>
      <li>
        <a class="{{ $active($isRoute('accounts.bank-accounts.*')) }}" href="{{ route('accounts.bank-accounts.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Bank Accounts')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('accounts.safes.*')) }}" href="{{ route('accounts.safes.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Treasury Accounts')</span>
        </a>
      </li>

      <li class="nav-heading">@lang('sidebar.Products')</li>
      <li>
        <a class="{{ $active($isRoute('product_types.*')) }}" href="{{ route('product_types.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Product Types')</span>
        </a>
      </li>

      <li class="nav-heading">@lang('sidebar.Users and Permissions')</li>
      <li>
        <a class="{{ $active($isRoute('users.*')) }}" href="{{ route('users.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Assign Roles to Users')</span>
        </a>
      </li>
    </ul>
  </li>
  @endrole
</ul>
