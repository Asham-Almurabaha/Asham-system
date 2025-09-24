@php
  // Helpers بسيطة للـ active/collapse
  $isRoute = fn($pattern) => Request::routeIs($pattern);
  $isPath  = fn($pattern) => Request::is($pattern);
  $active  = fn($cond) => $cond ? 'active' : '';
  $open    = fn($cond) => $cond ? 'show' : '';
  $coll    = fn($cond) => $cond ? '' : 'collapsed';

  // هل مجموعة الإعدادات مفتوحة؟
  $settingsOpen = $isPath('*/setting*')
      || $isRoute('settings.*')
      || $isRoute('nationalities.*')
      || $isRoute('titles.*')
      || $isRoute('customer_statuses.*')
      || $isRoute('guarantor_statuses.*')
      || $isRoute('contract_statuses.*')
      || $isRoute('claim_statuses.*')
      || $isRoute('claim_payers.*')
      || $isRoute('claimants.*')
      || $isRoute('installment_statuses.*')
      || $isRoute('installment_types.*')
      || $isRoute('categories.*')
      || $isRoute('transaction_statuses.*')
      || $isRoute('transaction_types.*')
      || $isRoute('accounts.bank-accounts.*')
      || $isRoute('accounts.safes.*')
      || $isRoute('product_types.*')
      || $isRoute('products.*')
      || $isRoute('product_entries.*')
      || $isRoute('users.*');

  // فتح مجموعة المستثمرين؟
  $investorsOpen = $isRoute('investors.*')
      || $isRoute('investor-transactions.*')
      || $isRoute('reports.investors.*');

  // فتح مجموعة العقود؟
  $contractsOpen = $isRoute('contracts.*');

  // فتح مجموعة الحسابات؟
  $accountsOpen = $isRoute('ledger.*')
      || $isRoute('investors.ledger.*')
      || $isRoute('accounts.entries.goods.*')
      || $isRoute('accounts.entries.goods.sales.*');

  // فتح مجموعة استيرادات البيانات؟
  $dataImportsOpen = $isRoute([
      'customers.import.*',
      'guarantors.import.*',
      'investors.import.*',
      'investors.ledger.import.*',
      'contracts.import.*',
      'ledger.import.*',
  ]);

  $customerManagePatterns = [
      'customers.index',
      'customers.create',
      'customers.store',
      'customers.show',
      'customers.edit',
      'customers.update',
      'customers.destroy',
      'customers.import.*',
  ];

  $customersOpen = $isRoute(array_merge(['customers.dashboard'], $customerManagePatterns));

  $guarantorManagePatterns = [
      'guarantors.index',
      'guarantors.create',
      'guarantors.store',
      'guarantors.show',
      'guarantors.edit',
      'guarantors.update',
      'guarantors.destroy',
      'guarantors.import.*',
  ];

  $guarantorsOpen = $isRoute(array_merge(['guarantors.dashboard'], $guarantorManagePatterns));
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
    <a class="nav-link {{ $coll($customersOpen) }}"
       data-bs-target="#customers-nav" data-bs-toggle="collapse" href="#"
       aria-expanded="{{ $customersOpen ? 'true' : 'false' }}">
      <i class="bi bi-people"></i><span>@lang('sidebar.Customers')</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="customers-nav" class="nav-content collapse {{ $open($customersOpen) }}" data-bs-parent="#sidebar-nav">
      <li>
        <a class="{{ $active($isRoute('customers.dashboard')) }}" href="{{ route('customers.dashboard') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Customers Dashboard')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute($customerManagePatterns)) }}" href="{{ route('customers.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Manage Customers')</span>
        </a>
      </li>
    </ul>
  </li>

  {{-- Guarantors --}}
  <li class="nav-item">
    <a class="nav-link {{ $coll($guarantorsOpen) }}"
       data-bs-target="#guarantors-nav" data-bs-toggle="collapse" href="#"
       aria-expanded="{{ $guarantorsOpen ? 'true' : 'false' }}">
      <i class="bi bi-person-bounding-box"></i><span>@lang('sidebar.Guarantors')</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="guarantors-nav" class="nav-content collapse {{ $open($guarantorsOpen) }}" data-bs-parent="#sidebar-nav">
      <li>
        <a class="{{ $active($isRoute('guarantors.dashboard')) }}" href="{{ route('guarantors.dashboard') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Guarantors Dashboard')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute($guarantorManagePatterns)) }}" href="{{ route('guarantors.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Manage Guarantors')</span>
        </a>
      </li>
    </ul>
  </li>

  {{-- المستثمرين --}}
  <li class="nav-item">
    <a class="nav-link {{ $coll($investorsOpen) }}"
       data-bs-target="#investors-nav" data-bs-toggle="collapse" href="#"
       aria-expanded="{{ $investorsOpen ? 'true' : 'false' }}">
      <i class="bi bi-briefcase"></i><span>@lang('sidebar.Investors')</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="investors-nav" class="nav-content collapse {{ $open($investorsOpen) }}" data-bs-parent="#sidebar-nav">
      <li>
        <a class="{{ $active($isRoute('investors.dashboard')) }}" href="{{ route('investors.dashboard') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Investors Dashboard')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute([
          'investors.index',
          'investors.create',
          'investors.store',
          'investors.show',
          'investors.edit',
          'investors.update',
          'investors.destroy',
          'investors.import.*',
          'investors.ledger.import.*',
          'investors.cash',
          'investors.liquidity',
          'investors.statement.*',
          'investors.withdrawals.*',
          'investors.deposits.*',
          'investors.transactions.*',
          'investor-transactions.*',
          'reports.investors.*',
        ])) }}" href="{{ route('investors.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Manage Investors')</span>
        </a>
      </li>
    </ul>
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
      <li class="nav-heading">@lang('sidebar.Investor Ledger Entries')</li>
      <li>
        <a class="{{ $active($isRoute(['investors.ledger.shortcuts', 'investors.ledger.shortcuts.capital'])) }}" href="{{ route('investors.ledger.shortcuts.capital') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Investor Capital Entry')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('investors.ledger.shortcuts.liquidity_in')) }}" href="{{ route('investors.ledger.shortcuts.liquidity_in') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Investor Liquidity Deposit')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('investors.ledger.shortcuts.liquidity_out')) }}" href="{{ route('investors.ledger.shortcuts.liquidity_out') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Investor Liquidity Withdrawal')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('investors.ledger.shortcuts.zakat')) }}" href="{{ route('investors.ledger.shortcuts.zakat') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Investor Zakat Withdrawal')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('investors.ledger.import.*')) }}" href="{{ route('investors.ledger.import.form') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Import Ledger Entries')</span>
        </a>
      </li>
      <li class="nav-heading">@lang('sidebar.Goods Entries')</li>
      <li>
        <a class="{{ $active($isRoute('accounts.entries.goods.*')) }}" href="{{ route('accounts.entries.goods.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Goods Purchase')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('accounts.entries.goods.sales.*')) }}" href="{{ route('accounts.entries.goods.sales.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Goods Sale')</span>
        </a>
      </li>
    </ul>
  </li>


  {{-- استيرادات البيانات --}}
  <li class="nav-item">
    <a class="nav-link {{ $coll($dataImportsOpen) }}"
       data-bs-target="#data-imports-nav" data-bs-toggle="collapse" href="#"
       aria-expanded="{{ $dataImportsOpen ? 'true' : 'false' }}">
      <i class="bi bi-cloud-arrow-down"></i><span>@lang('sidebar.Data Imports')</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="data-imports-nav" class="nav-content collapse {{ $open($dataImportsOpen) }}" data-bs-parent="#sidebar-nav">
      <li>
        <a class="{{ $active($isRoute('customers.import.*')) }}" href="{{ route('customers.import.form') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Import Customers')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('guarantors.import.*')) }}" href="{{ route('guarantors.import.form') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Import Guarantors')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('investors.import.*')) }}" href="{{ route('investors.import.form') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Import Investors')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('investors.ledger.import.*')) }}" href="{{ route('investors.ledger.import.form') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Import Investor Ledger')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('contracts.import.form')) }}" href="{{ route('contracts.import.form') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Import Contracts')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('contracts.import.basic.*')) }}" href="{{ route('contracts.import.basic.form') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Import Basic Contracts')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('contracts.import.investors.*')) }}" href="{{ route('contracts.import.investors.form') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Import Contract Investors')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('contracts.import.payments.*')) }}" href="{{ route('contracts.import.payments.form') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Import Contract Payments')</span>
        </a>
      </li>
      <li>
        <a class="{{ $active($isRoute('ledger.import.*')) }}" href="{{ route('ledger.import.form') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Import Ledger Entries')</span>
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

      <li class="nav-heading">@lang('sidebar.Contracts')</li>

        <li>
          <a class="{{ $active($isRoute('contract_statuses.*')) }}" href="{{ route('contract_statuses.index') }}">
            <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Contract Statuses')</span>
          </a>
        </li>

      <li class="nav-heading">@lang('sidebar.Claims')</li>

        <li>
          <a class="{{ $active($isRoute('claim_statuses.*')) }}" href="{{ route('claim_statuses.index') }}">
            <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Claim Statuses')</span>
          </a>
        </li>
        <li>
          <a class="{{ $active($isRoute('claimants.*')) }}" href="{{ route('claimants.index') }}">
            <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Claimants')</span>
          </a>
        </li>
        <li>
          <a class="{{ $active($isRoute('claim_payers.*')) }}" href="{{ route('claim_payers.index') }}">
            <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Claim Payers')</span>
          </a>
        </li>
      

      <li class="nav-heading">@lang('sidebar.Installments')</li>

        <li>
          <a class="{{ $active($isRoute('installment_types.*')) }}" href="{{ route('installment_types.index') }}">
            <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Installment Types')</span>
          </a>
        </li>
        <li>
          <a class="{{ $active($isRoute('installment_statuses.*')) }}" href="{{ route('installment_statuses.index') }}">
            <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Installment Statuses')</span>
          </a>
        </li>

      <li class="nav-heading">@lang('sidebar.Transactions & Finance')</li>

        <li>
          <a class="{{ $active($isRoute('transaction_types.*')) }}" href="{{ route('transaction_types.index') }}">
            <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Transaction Types')</span>
          </a>
        </li>
        <li>
          <a class="{{ $active($isRoute('transaction_statuses.*')) }}" href="{{ route('transaction_statuses.index') }}">
            <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Transaction Statuses')</span>
          </a>
        </li>
        <li>
          <a class="{{ $active($isRoute('categories.*')) }}" href="{{ route('categories.index') }}">
            <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Categories')</span>
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
          <a class="{{ $active($isRoute('settings.roles.index')) }}" href="{{ route('settings.roles.index') }}">
            <i class="bi bi-circle"></i><span>@lang('sidebar.Manage Roles')</span>
          </a>
        </li>
        <li>
          <a class="{{ $active($isRoute('settings.roles.permissions*')) }}" href="{{ route('settings.roles.permissions') }}">
            <i class="bi bi-circle"></i><span>@lang('sidebar.Manage Role Permissions')</span>
          </a>
        </li>
        <li>
          <a class="{{ $active($isRoute('settings.permissions.*')) }}" href="{{ route('settings.permissions.index') }}">
            <i class="bi bi-circle"></i><span>@lang('sidebar.Manage Permissions')</span>
          </a>
        </li>
        <li>
          <a class="{{ $active($isRoute('users.*')) }}" href="{{ route('users.index') }}">
            <i class="bi bi-circle"></i><span>@lang('sidebar.Assign Roles to Users')</span>
          </a>
        </li>
    </ul>
  </li>
  @endrole
</ul>
