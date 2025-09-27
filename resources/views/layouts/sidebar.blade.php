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

  // فتح مجموعة تصدير البيانات؟
  $dataExportsOpen = $isRoute([
      'customers.export',
      'guarantors.export',
      'investors.export',
      'investors.ledger.export',
      'ledger.export',
      'contracts.export.*',
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

  $investorManagePatterns = [
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
      'ajax.investors.liquidity',
  ];

  $contractsManagePatterns = [
      'contracts.index',
      'contracts.create',
      'contracts.store',
      'contracts.show',
      'contracts.edit',
      'contracts.update',
      'contracts.destroy',
      'contracts.images.update',
      'contracts.refresh-statuses',
      'contracts.import.*',
      'contracts.export.*',
      'contracts.print',
      'contracts.closure',
      'contracts.paid',
      'contracts.investors.store',
      'contracts.notes.*',
      'installments.*',
  ];

  $contractClaimsPatterns = [
      'contract-claims.*',
      'contract-claims.update-status',
      'contract-claims.apply-discount',
      'contract-claims.reopen',
      'contract-claims.payments.store',
  ];

  $accountsLedgerPatterns = [
      'ledger.index',
      'ledger.create',
      'ledger.store',
      'ledger.transfer.*',
      'ledger.split.*',
      'ledger.dashboard',
      'ledger.export',
      'ledger.import.*',
  ];

  $accountsOfficeShortcutPatterns = [
      'ledger.office.shortcuts',
      'ledger.office.shortcuts.mukataba',
      'ledger.office.shortcuts.sales_diff',
      'ledger.office.shortcuts.account_deposit',
      'ledger.office.shortcuts.account_withdrawal',
      'ledger.office.shortcuts.opening_balance',
  ];

  $investorLedgerPatterns = [
      'investors.ledger.shortcuts',
      'investors.ledger.shortcuts.*',
      'investors.ledger.create',
      'investors.ledger.split.*',
      'investors.ledger.export',
      'investors.ledger.import.*',
  ];

  $goodsEntryPatterns = [
      'accounts.entries.goods.*',
      'accounts.entries.goods.sales.*',
  ];

  $accountsNavPatterns = array_merge(
      $accountsLedgerPatterns,
      $accountsOfficeShortcutPatterns,
      $investorLedgerPatterns,
      $goodsEntryPatterns
  );

  $dataImportPatterns = [
      'customers.import.*',
      'guarantors.import.*',
      'investors.import.*',
      'investors.ledger.import.*',
      'contracts.import.*',
      'ledger.import.*',
  ];

  $dataExportPatterns = [
      'customers.export',
      'guarantors.export',
      'investors.export',
      'investors.ledger.export',
      'ledger.export',
      'contracts.export.*',
  ];

  $settingsPermissionPatterns = [
      'settings.*',
      'settings.account.*',
      'nationalities.*',
      'titles.*',
      'customer_statuses.*',
      'guarantor_statuses.*',
      'contract_statuses.*',
      'claim_statuses.*',
      'claimants.*',
      'claim_payers.*',
      'installment_statuses.*',
      'installment_types.*',
      'transaction_statuses.*',
      'transaction_types.*',
      'categories.*',
      'accounts.bank-accounts.*',
      'accounts.safes.*',
      'product_types.*',
      'products.*',
      'settings.roles.*',
      'settings.roles.permissions*',
      'settings.permissions.*',
      'users.*',
  ];

  $settingsGeneralPatterns = ['settings.index', 'settings.account.*'];
  $settingsPeoplePatterns = [
      'nationalities.*',
      'titles.*',
      'customer_statuses.*',
      'guarantor_statuses.*',
  ];
  $settingsContractPatterns = ['contract_statuses.*'];
  $settingsClaimsPatterns = ['claim_statuses.*', 'claimants.*', 'claim_payers.*'];
  $settingsInstallmentPatterns = ['installment_types.*', 'installment_statuses.*'];
  $settingsTransactionPatterns = ['transaction_types.*', 'transaction_statuses.*', 'categories.*'];
  $settingsAccountsPatterns = ['accounts.bank-accounts.*', 'accounts.safes.*'];
  $settingsProductsPatterns = ['product_types.*', 'products.*'];
  $settingsUsersPatterns = [
      'settings.roles.*',
      'settings.roles.permissions*',
      'settings.permissions.*',
      'users.*',
  ];
@endphp

<ul class="sidebar-nav" id="sidebar-nav">

  {{-- لوحة التحكم --}}
  @routecanany(['dashboard', 'view-dashboard'])
  <li class="nav-item">
    <a class="nav-link {{ $coll($isRoute('dashboard')) }} {{ $active($isRoute('dashboard')) }}"
       href="{{ route('dashboard') }}">
      <i class="bi bi-speedometer2"></i><span>@lang('sidebar.Dashboard')</span>
    </a>
  </li>
  @endroutecanany

  {{-- Customers --}}
  @routecanany(array_merge(['customers.dashboard'], $customerManagePatterns))
  <li class="nav-item">
    <a class="nav-link {{ $coll($customersOpen) }}"
       data-bs-target="#customers-nav" data-bs-toggle="collapse" href="#"
       aria-expanded="{{ $customersOpen ? 'true' : 'false' }}">
      <i class="bi bi-people"></i><span>@lang('sidebar.Customers')</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="customers-nav" class="nav-content collapse {{ $open($customersOpen) }}" data-bs-parent="#sidebar-nav">
      @routecanany('customers.dashboard')
      <li>
        <a class="{{ $active($isRoute('customers.dashboard')) }}" href="{{ route('customers.dashboard') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Customers Dashboard')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany($customerManagePatterns)
      <li>
        <a class="{{ $active($isRoute($customerManagePatterns)) }}" href="{{ route('customers.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Manage Customers')</span>
        </a>
      </li>
      @endroutecanany
    </ul>
  </li>
  @endroutecanany

  {{-- Guarantors --}}
  @routecanany(array_merge(['guarantors.dashboard'], $guarantorManagePatterns))
  <li class="nav-item">
    <a class="nav-link {{ $coll($guarantorsOpen) }}"
       data-bs-target="#guarantors-nav" data-bs-toggle="collapse" href="#"
       aria-expanded="{{ $guarantorsOpen ? 'true' : 'false' }}">
      <i class="bi bi-person-bounding-box"></i><span>@lang('sidebar.Guarantors')</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="guarantors-nav" class="nav-content collapse {{ $open($guarantorsOpen) }}" data-bs-parent="#sidebar-nav">
      @routecanany('guarantors.dashboard')
      <li>
        <a class="{{ $active($isRoute('guarantors.dashboard')) }}" href="{{ route('guarantors.dashboard') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Guarantors Dashboard')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany($guarantorManagePatterns)
      <li>
        <a class="{{ $active($isRoute($guarantorManagePatterns)) }}" href="{{ route('guarantors.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Manage Guarantors')</span>
        </a>
      </li>
      @endroutecanany
    </ul>
  </li>
  @endroutecanany

  {{-- المستثمرين --}}
  @routecanany(array_merge(['investors.dashboard'], $investorManagePatterns))
  <li class="nav-item">
    <a class="nav-link {{ $coll($investorsOpen) }}"
       data-bs-target="#investors-nav" data-bs-toggle="collapse" href="#"
       aria-expanded="{{ $investorsOpen ? 'true' : 'false' }}">
      <i class="bi bi-briefcase"></i><span>@lang('sidebar.Investors')</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="investors-nav" class="nav-content collapse {{ $open($investorsOpen) }}" data-bs-parent="#sidebar-nav">
      @routecanany('investors.dashboard')
      <li>
        <a class="{{ $active($isRoute('investors.dashboard')) }}" href="{{ route('investors.dashboard') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Investors Dashboard')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany($investorManagePatterns)
      <li>
        <a class="{{ $active($isRoute($investorManagePatterns)) }}" href="{{ route('investors.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Manage Investors')</span>
        </a>
      </li>
      @endroutecanany
    </ul>
  </li>
  @endroutecanany

  {{-- العقود --}}
  @routecanany(array_merge(['contracts.dashboard'], $contractsManagePatterns))
  <li class="nav-item">
    <a class="nav-link {{ $coll($contractsOpen) }}"
       data-bs-target="#contracts-nav" data-bs-toggle="collapse" href="#"
       aria-expanded="{{ $contractsOpen ? 'true' : 'false' }}">
      <i class="bi bi-file-earmark-text"></i><span>@lang('sidebar.Contracts')</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="contracts-nav" class="nav-content collapse {{ $open($contractsOpen) }}" data-bs-parent="#sidebar-nav">
      @routecanany('contracts.dashboard')
      <li>
        <a class="{{ $active($isRoute('contracts.dashboard')) }}" href="{{ route('contracts.dashboard') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Contracts Dashboard')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany($contractsManagePatterns)
      <li>
        <a class="{{ $active($isRoute($contractsManagePatterns)) }}" href="{{ route('contracts.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Manage Contracts')</span>
        </a>
      </li>
      @endroutecanany

    </ul>
  </li>
  @endroutecanany

  {{-- المطالبات --}}
  @routecanany($contractClaimsPatterns)
  <li class="nav-item">
    <a class="nav-link {{ $coll($isRoute('contract-claims.*')) }} {{ $active($isRoute('contract-claims.*')) }}"
       href="{{ route('contract-claims.index') }}">
      <i class="bi bi-exclamation-octagon"></i><span>@lang('sidebar.Claims')</span>
    </a>
  </li>
  @endroutecanany

  {{-- الحسابات --}}
  @routecanany($accountsNavPatterns)
  <li class="nav-item">
    <a class="nav-link {{ $coll($accountsOpen) }}"
       data-bs-target="#accounts-nav" data-bs-toggle="collapse" href="#"
       aria-expanded="{{ $accountsOpen ? 'true' : 'false' }}">
      <i class="bi bi-wallet2"></i><span>@lang('sidebar.Accounts')</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="accounts-nav" class="nav-content collapse {{ $open($accountsOpen) }}" data-bs-parent="#sidebar-nav">
      @routecanany('ledger.dashboard')
      <li>
        <a class="{{ $active($isRoute(['ledger.dashboard'])) }}" href="{{ route('ledger.dashboard') }}">
          <i class="bi bi-circle"></i><span>داش بورد الحسابات</span>
        </a>
      </li>
      @endroutecanany

      @routecanany(['ledger.index', 'ledger.create', 'ledger.store', 'ledger.transfer.*', 'ledger.split.*'])
      <li>
        <a class="{{ $active($isRoute(['ledger.index'])) }}" href="{{ route('ledger.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Ledger')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany($accountsOfficeShortcutPatterns)
      <li class="nav-heading">@lang('sidebar.Office Ledger Entries')</li>
      @endroutecanany

      @routecanany('ledger.office.shortcuts.mukataba')
      <li>
        <a class="{{ $active($isRoute('ledger.office.shortcuts.mukataba')) }}" href="{{ route('ledger.office.shortcuts.mukataba') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Office Mukataba Entry')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('ledger.office.shortcuts.sales_diff')
      <li>
        <a class="{{ $active($isRoute('ledger.office.shortcuts.sales_diff')) }}" href="{{ route('ledger.office.shortcuts.sales_diff') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Office Sales Difference Entry')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('ledger.office.shortcuts.account_deposit')
      <li>
        <a class="{{ $active($isRoute('ledger.office.shortcuts.account_deposit')) }}" href="{{ route('ledger.office.shortcuts.account_deposit') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Office Accounts Deposit')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('ledger.office.shortcuts.account_withdrawal')
      <li>
        <a class="{{ $active($isRoute('ledger.office.shortcuts.account_withdrawal')) }}" href="{{ route('ledger.office.shortcuts.account_withdrawal') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Office Accounts Withdrawal')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('ledger.office.shortcuts.opening_balance')
      <li>
        <a class="{{ $active($isRoute('ledger.office.shortcuts.opening_balance')) }}" href="{{ route('ledger.office.shortcuts.opening_balance') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Office Opening Balance')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany($investorLedgerPatterns)
      <li class="nav-heading">@lang('sidebar.Investor Ledger Entries')</li>
      @endroutecanany

      @routecanany(['investors.ledger.shortcuts', 'investors.ledger.shortcuts.capital'])
      <li>
        <a class="{{ $active($isRoute(['investors.ledger.shortcuts', 'investors.ledger.shortcuts.capital'])) }}" href="{{ route('investors.ledger.shortcuts.capital') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Investor Capital Entry')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('investors.ledger.shortcuts.liquidity_in')
      <li>
        <a class="{{ $active($isRoute('investors.ledger.shortcuts.liquidity_in')) }}" href="{{ route('investors.ledger.shortcuts.liquidity_in') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Investor Liquidity Deposit')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('investors.ledger.shortcuts.liquidity_out')
      <li>
        <a class="{{ $active($isRoute('investors.ledger.shortcuts.liquidity_out')) }}" href="{{ route('investors.ledger.shortcuts.liquidity_out') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Investor Liquidity Withdrawal')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('investors.ledger.shortcuts.zakat')
      <li>
        <a class="{{ $active($isRoute('investors.ledger.shortcuts.zakat')) }}" href="{{ route('investors.ledger.shortcuts.zakat') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Investor Zakat Withdrawal')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany($goodsEntryPatterns)
      <li class="nav-heading">@lang('sidebar.Goods Entries')</li>
      @endroutecanany

      @routecanany('accounts.entries.goods.*')
      <li>
        <a class="{{ $active($isRoute('accounts.entries.goods.*')) }}" href="{{ route('accounts.entries.goods.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Goods Purchase')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('accounts.entries.goods.sales.*')
      <li>
        <a class="{{ $active($isRoute('accounts.entries.goods.sales.*')) }}" href="{{ route('accounts.entries.goods.sales.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Goods Sale')</span>
        </a>
      </li>
      @endroutecanany
    </ul>
  </li>
  @endroutecanany


  {{-- استيرادات البيانات --}}
  @routecanany($dataImportPatterns)
  <li class="nav-item">
    <a class="nav-link {{ $coll($dataImportsOpen) }}"
       data-bs-target="#data-imports-nav" data-bs-toggle="collapse" href="#"
       aria-expanded="{{ $dataImportsOpen ? 'true' : 'false' }}">
      <i class="bi bi-cloud-arrow-down"></i><span>@lang('sidebar.Data Imports')</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="data-imports-nav" class="nav-content collapse {{ $open($dataImportsOpen) }}" data-bs-parent="#sidebar-nav">
      @routecanany('customers.import.*')
      <li>
        <a class="{{ $active($isRoute('customers.import.*')) }}" href="{{ route('customers.import.form') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Import Customers')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('guarantors.import.*')
      <li>
        <a class="{{ $active($isRoute('guarantors.import.*')) }}" href="{{ route('guarantors.import.form') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Import Guarantors')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('investors.import.*')
      <li>
        <a class="{{ $active($isRoute('investors.import.*')) }}" href="{{ route('investors.import.form') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Import Investors')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('investors.ledger.import.*')
      <li>
        <a class="{{ $active($isRoute('investors.ledger.import.*')) }}" href="{{ route('investors.ledger.import.form') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Import Investor Ledger')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('contracts.import.form')
      <li>
        <a class="{{ $active($isRoute('contracts.import.form')) }}" href="{{ route('contracts.import.form') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Import Contracts')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('contracts.import.basic.*')
      <li>
        <a class="{{ $active($isRoute('contracts.import.basic.*')) }}" href="{{ route('contracts.import.basic.form') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Import Basic Contracts')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('contracts.import.investors.*')
      <li>
        <a class="{{ $active($isRoute('contracts.import.investors.*')) }}" href="{{ route('contracts.import.investors.form') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Import Contract Investors')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('contracts.import.payments.*')
      <li>
        <a class="{{ $active($isRoute('contracts.import.payments.*')) }}" href="{{ route('contracts.import.payments.form') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Import Contract Payments')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('ledger.import.*')
      <li>
        <a class="{{ $active($isRoute('ledger.import.*')) }}" href="{{ route('ledger.import.form') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Import Ledger Entries')</span>
        </a>
      </li>
      @endroutecanany
    </ul>
  </li>
  @endroutecanany



  {{-- تصدير البيانات --}}
  @routecanany($dataExportPatterns)
  <li class="nav-item">
    <a class="nav-link {{ $coll($dataExportsOpen) }}"
       data-bs-target="#data-exports-nav" data-bs-toggle="collapse" href="#"
       aria-expanded="{{ $dataExportsOpen ? 'true' : 'false' }}">
      <i class="bi bi-cloud-arrow-up"></i><span>@lang('sidebar.Data Exports')</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="data-exports-nav" class="nav-content collapse {{ $open($dataExportsOpen) }}" data-bs-parent="#sidebar-nav">
      @routecanany('customers.export')
      <li>
        <a class="{{ $active($isRoute('customers.export')) }}" href="{{ route('customers.export') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Export Customers')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('guarantors.export')
      <li>
        <a class="{{ $active($isRoute('guarantors.export')) }}" href="{{ route('guarantors.export') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Export Guarantors')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('investors.export')
      <li>
        <a class="{{ $active($isRoute('investors.export')) }}" href="{{ route('investors.export') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Export Investors')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('investors.ledger.export')
      <li>
        <a class="{{ $active($isRoute('investors.ledger.export')) }}" href="{{ route('investors.ledger.export') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Export Investor Ledger')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('ledger.export')
      <li>
        <a class="{{ $active($isRoute('ledger.export')) }}" href="{{ route('ledger.export') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Export Ledger Entries')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('contracts.export.*')
      <li>
        <a class="{{ $active($isRoute('contracts.export.*')) }}" href="{{ route('contracts.export.data') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Export Contracts')</span>
        </a>
      </li>
      @endroutecanany
    </ul>
  </li>
  @endroutecanany


  {{-- الإعدادات (قابلة للطي) --}}
  @routecanany($settingsPermissionPatterns)
  <li class="nav-item">
    <a class="nav-link {{ $coll($settingsOpen) }}"
       data-bs-target="#settings-nav" data-bs-toggle="collapse" href="#"
       aria-expanded="{{ $settingsOpen ? 'true' : 'false' }}">
      <i class="bi bi-gear"></i><span>@lang('sidebar.Settings')</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="settings-nav" class="nav-content collapse {{ $open($settingsOpen) }}" data-bs-parent="#sidebar-nav">
      @routecanany($settingsGeneralPatterns)
      <li class="nav-heading">@lang('sidebar.General Settings')</li>
      @endroutecanany

      @routecanany('settings.index')
      <li>
        <a class="{{ $active($isRoute('settings.index')) }}" href="{{ route('settings.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.General Setting')</span>
        </a>
      </li>
      @endroutecanany

      @auth
      <li>
        <a class="{{ $active($isRoute('settings.account.edit')) }}" href="{{ route('settings.account.edit') }}">
          <i class="bi bi-circle"></i><span>@lang('setting.Account Settings')</span>
        </a>
      </li>
      @endauth

      @routecanany($settingsPeoplePatterns)
      <li class="nav-heading">@lang('sidebar.People & Customers')</li>
      @endroutecanany

      @routecanany('nationalities.*')
      <li>
        <a class="{{ $active($isRoute('nationalities.*')) }}" href="{{ route('nationalities.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Nationalities')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('titles.*')
      <li>
        <a class="{{ $active($isRoute('titles.*')) }}" href="{{ route('titles.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Titles')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('customer_statuses.*')
      <li>
        <a class="{{ $active($isRoute('customer_statuses.*')) }}" href="{{ route('customer_statuses.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Customer Statuses')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('guarantor_statuses.*')
      <li>
        <a class="{{ $active($isRoute('guarantor_statuses.*')) }}" href="{{ route('guarantor_statuses.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Guarantor Statuses')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany($settingsContractPatterns)
      <li class="nav-heading">@lang('sidebar.Contracts')</li>
      @endroutecanany

      @routecanany('contract_statuses.*')
      <li>
        <a class="{{ $active($isRoute('contract_statuses.*')) }}" href="{{ route('contract_statuses.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Contract Statuses')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany($settingsClaimsPatterns)
      <li class="nav-heading">@lang('sidebar.Claims')</li>
      @endroutecanany

      @routecanany('claim_statuses.*')
      <li>
        <a class="{{ $active($isRoute('claim_statuses.*')) }}" href="{{ route('claim_statuses.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Claim Statuses')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('claimants.*')
      <li>
        <a class="{{ $active($isRoute('claimants.*')) }}" href="{{ route('claimants.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Claimants')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('claim_payers.*')
      <li>
        <a class="{{ $active($isRoute('claim_payers.*')) }}" href="{{ route('claim_payers.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Claim Payers')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany($settingsInstallmentPatterns)
      <li class="nav-heading">@lang('sidebar.Installments')</li>
      @endroutecanany

      @routecanany('installment_types.*')
      <li>
        <a class="{{ $active($isRoute('installment_types.*')) }}" href="{{ route('installment_types.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Installment Types')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('installment_statuses.*')
      <li>
        <a class="{{ $active($isRoute('installment_statuses.*')) }}" href="{{ route('installment_statuses.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Installment Statuses')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany($settingsTransactionPatterns)
      <li class="nav-heading">@lang('sidebar.Transactions & Finance')</li>
      @endroutecanany

      @routecanany('transaction_types.*')
      <li>
        <a class="{{ $active($isRoute('transaction_types.*')) }}" href="{{ route('transaction_types.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Transaction Types')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('transaction_statuses.*')
      <li>
        <a class="{{ $active($isRoute('transaction_statuses.*')) }}" href="{{ route('transaction_statuses.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Transaction Statuses')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('categories.*')
      <li>
        <a class="{{ $active($isRoute('categories.*')) }}" href="{{ route('categories.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Categories')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany($settingsAccountsPatterns)
      <li class="nav-heading">@lang('sidebar.Accounts Group')</li>
      @endroutecanany

      @routecanany('accounts.bank-accounts.*')
      <li>
        <a class="{{ $active($isRoute('accounts.bank-accounts.*')) }}" href="{{ route('accounts.bank-accounts.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Bank Accounts')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('accounts.safes.*')
      <li>
        <a class="{{ $active($isRoute('accounts.safes.*')) }}" href="{{ route('accounts.safes.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Treasury Accounts')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany($settingsProductsPatterns)
      <li class="nav-heading">@lang('sidebar.Products')</li>
      @endroutecanany

      @routecanany('product_types.*')
      <li>
        <a class="{{ $active($isRoute('product_types.*')) }}" href="{{ route('product_types.index') }}">
          <i class="bi bi-circle"></i><span>@lang('lookups::sidebar.Product Types')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany($settingsUsersPatterns)
      <li class="nav-heading">@lang('sidebar.Users and Permissions')</li>
      @endroutecanany

      @routecanany('settings.roles.index')
      <li>
        <a class="{{ $active($isRoute('settings.roles.index')) }}" href="{{ route('settings.roles.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Manage Roles')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('settings.roles.permissions*')
      <li>
        <a class="{{ $active($isRoute('settings.roles.permissions*')) }}" href="{{ route('settings.roles.permissions') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Manage Role Permissions')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('settings.permissions.*')
      <li>
        <a class="{{ $active($isRoute('settings.permissions.*')) }}" href="{{ route('settings.permissions.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Manage Permissions')</span>
        </a>
      </li>
      @endroutecanany

      @routecanany('users.*')
      <li>
        <a class="{{ $active($isRoute('users.*')) }}" href="{{ route('users.index') }}">
          <i class="bi bi-circle"></i><span>@lang('sidebar.Assign Roles to Users')</span>
        </a>
      </li>
      @endroutecanany
    </ul>
  </li>
  @endroutecanany

  
  {{-- سجل النشاط --}}
  @routecanany(['audit.logs', 'audit.logs.*', 'view-audit-logs'])
  <li class="nav-item">
    <a class="nav-link {{ $coll($isRoute('audit.logs')) }} {{ $active($isRoute('audit.logs')) }}"
       href="{{ route('audit.logs') }}">
      <i class="bi bi-clipboard-data"></i><span>@lang('sidebar.Audit Logs')</span>
    </a>
  </li>
  @endroutecanany

</ul>
