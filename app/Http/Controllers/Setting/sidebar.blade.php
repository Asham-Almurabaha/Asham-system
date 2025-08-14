<ul class="sidebar-nav" id="sidebar-nav">
    {{-- <li class="nav-item">
        <a class="nav-link collapsed" href="#">
            <i class="bi bi-grid"></i>
            <span>Dashboard</span>
        </a>
    </li><!-- End Dashboard Nav --> --}}

    <li class="nav-item">
        <a class="nav-link collapsed" href="{{ route('customers.index') }}">
            <i class="bi bi-grid"></i>
            <span>العملاء</span>
        </a>
    </li><!-- End customers Nav -->

    <li class="nav-item">
        <a class="nav-link collapsed" href="{{ route('guarantors.index') }}">
            <i class="bi bi-grid"></i>
            <span>الكفلاء</span>
        </a>
    </li><!-- End ر Nav -->

    @php
        $isSettingsActive = Request::is('setting*') || Request::is('nationalities*') || Request::is('titles*') || Request::is('contract_statuses*') || Request::is('contract_types*') || Request::is('installment_statuses*') || Request::is('installment_types*');
    @endphp

    <li class="nav-item">
        <a class="nav-link {{ $isSettingsActive ? '' : 'collapsed' }}" data-bs-target="#settings-nav"
            data-bs-toggle="collapse" href="#"
            aria-expanded="{{ $isSettingsActive ? 'true' : 'false' }}">
            <i class="bi bi-gear"></i><span>@lang('sidebar.Settings')</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="settings-nav" class="nav-content collapse {{ $isSettingsActive ? 'show' : '' }}"
            data-bs-parent="#sidebar-nav">
            <li>
                <a class="{{ Request::is('*/settings*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                    <i class="bi bi-circle"></i><span>@lang('sidebar.General Setting')</span>
                </a>
            </li>
            <li>
                <a class="{{ Request::is('*/nationalities*') ? 'active' : '' }}" href="{{ route('nationalities.index') }}">
                    <i class="bi bi-circle"></i><span>@lang('sidebar.Nationalities')</span>
                </a>
            </li>
            <li>
                <a class="{{ Request::is('*/titles*') ? 'active' : '' }}" href="{{ route('titles.index') }}">
                    <i class="bi bi-circle"></i><span>@lang('sidebar.titles')</span>
                </a>
            </li>
            <li>
                <a class="{{ Request::is('*/contract_statuses*') ? 'active' : '' }}" href="{{ route('contract_statuses.index') }}">
                    <i class="bi bi-circle"></i><span>@lang('sidebar.Contract Statuses')</span>
                </a>
            </li>
            <li>
                <a class="{{ Request::is('*/contract_types*') ? 'active' : '' }}" href="{{ route('contract_types.index') }}">
                    <i class="bi bi-circle"></i><span>@lang('sidebar.Contract Types')</span>
                </a>
            </li>
            <li>
                <a class="{{ Request::is('*/installment_statuses*') ? 'active' : '' }}" href="{{ route('installment_statuses.index') }}">
                    <i class="bi bi-circle"></i><span>@lang('sidebar.Installment Statuses')</span>
                </a>
            </li>
            <li>
                <a class="{{ Request::is('*/installment_types*') ? 'active' : '' }}" href="{{ route('installment_types.index') }}">
                    <i class="bi bi-circle"></i><span>@lang('sidebar.Installment Types')</span>
                </a>
            </li>
            <li>
                <a class="{{ Request::is('*/products*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                    <i class="bi bi-circle"></i><span>@lang('sidebar.Products')</span>
                </a>
            </li>
            <li>
                <a class="{{ Request::is('*/product_entries*') ? 'active' : '' }}" href="{{ route('product_entries.index') }}">
                    <i class="bi bi-circle"></i><span>@lang('sidebar.Product Entries')</span>
                </a>
            </li>
        </ul>
    </li><!-- End Settings Nav -->

    <li class="nav-heading">@lang('sidebarpages')</li>


{{-- 
    <li class="nav-item">
        <a class="nav-link {{ Request::is('*/hr/*') ? '' : 'collapsed' }}" data-bs-target="#hr-nav" data-bs-toggle="collapse"
            href="#" {{ Request::is('*/hr/*') ? 'aria-expanded="true"' : 'aria-expanded="false"' }}>
            <i class="bi bi-people"></i><span>@lang('sidebar.HR')</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="hr-nav" class="nav-content collapse {{ Request::is('*/hr/*') ? 'show' : '' }}"
            data-bs-parent="#sidebar-nav">
            <li>
                <a class="{{ Request::is('*/employees') ? 'active' : '' }}" href="{{ route('employees.index') }}">
                    <i class="bi bi-circle"></i>
                    <span>@lang('sidebar.Employees')</span>
                </a>
            </li>
            <li>
                <a class="{{ Request::is('*/employee status') ? 'active' : '' }}" href="{{ route('employee_status.index') }}">
                    <i class="bi bi-circle"></i>
                    <span>@lang('sidebar.Employee Status')</span>
                </a>
            </li>
            <li>
                <a class="{{ Request::is('*/payroll/statements') ? 'active' : '' }}" href="{{ route('payroll_statements.index') }}">
                    <i class="bi bi-circle"></i>
                    <span>@lang('sidebar.Payroll Statements')</span>
                </a>
            </li>
            <li>
                <a class="{{ Request::is('*/payroll/method targets') ? 'active' : '' }}" href="{{ route('payroll_method_target.index') }}">
                    <i class="bi bi-circle"></i>
                    <span>@lang('sidebar.Payroll Statements')</span>
                </a>
            </li>
            <li>
                <a class="{{ Request::is('*/payroll/method status') ? 'active' : '' }}" href="{{ route('payroll_method_status.index') }}">
                    <i class="bi bi-circle"></i>
                    <span>@lang('sidebar.Payroll Statements')</span>
                </a>
            </li>
        </ul>
    </li><!-- End HR Nav -->

    <li class="nav-item">
        <a class="nav-link {{ Request::is('*/account/*') ? '' : 'collapsed' }}" data-bs-target="#accounts-nav"
            data-bs-toggle="collapse" href="#"
            {{ Request::is('*/account/*') ? 'aria-expanded="true"' : 'aria-expanded="false"' }}>
            <i class="bi bi-people"></i><span>@lang('sidebar.Accounts')</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="accounts-nav" class="nav-content collapse {{ Request::is('*/account/*') ? 'show' : '' }}" data-bs-parent="#sidebar-nav">
            <li>
                <a class="{{ Request::is('*/treasury accounts') ? 'active' : '' }}" href="{{ route('treasuryaccounts.index') }}">
                    <i class="bi bi-circle"></i><span>@lang('sidebar.Treasury Accounts')</span>
                </a>
            </li>
            <li>
                <a class="{{ Request::is('*/bank accounts') ? 'active' : '' }}" href="{{ route('bankaccounts.index') }}">
                    <i class="bi bi-circle"></i><span>@lang('sidebar.Bank Accounts')</span>
                </a>
            </li>
            <li>
                <a class="{{ Request::is('*/financial statements') ? 'active' : '' }}" href="{{ route('financial_statements.index') }}">
                    <i class="bi bi-circle"></i><span>@lang('sidebar.Financial Statements')</span>
                </a>
            </li>
            <li>
                <a class="{{ Request::is('*/financial status') ? 'active' : '' }}" href="{{ route('financial_status.index') }}">
                    <i class="bi bi-circle"></i><span>@lang('sidebar.Financial Status')</span>
                </a>
            </li>
        </ul>
    </li><!-- End Accounts Nav -->


    <li class="nav-item">
        <a class="nav-link {{ Request::is('*/Operating/*') ? '' : 'collapsed' }}" data-bs-target="#operating-nav"
            data-bs-toggle="collapse" href="#"
            {{ Request::is('*/operating/*') ? 'aria-expanded="true"' : 'aria-expanded="false"' }}>
            <i class="bi bi-gear"></i><span>@lang('sidebar.Operating')</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="operating-nav" class="nav-content collapse {{ Request::is('*/operating/*') ? 'show' : '' }}"
            data-bs-parent="#sidebar-nav">
            <li>
                <a class="{{ Request::is('*/cars') ? 'active' : '' }}" href="{{ route('cars.index') }}">
                    <i class="bi bi-circle"></i><span>@lang('sidebar.Cars')</span>
                </a>
            </li>
            <li>
                <a class="{{ Request::is('*/motocycles') ? 'active' : '' }}"
                    href="{{ route('motocycles.index') }}">
                    <i class="bi bi-circle"></i><span>@lang('sidebar.Motocycles')</span>
                </a>
            </li>
        </ul>
    </li><!-- End Operations Nav -->

    

    <li class="nav-item">
        <a class="nav-link {{ Request::is('*/hr/*') ? '' : 'collapsed' }}" data-bs-target="#hr-nav" data-bs-toggle="collapse"
            href="#" {{ Request::is('*/hr/*') ? 'aria-expanded="true"' : 'aria-expanded="false"' }}>
            <i class="bi bi-people"></i><span>@lang('sidebar.HR')</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="hr-nav" class="nav-content collapse {{ Request::is('*/hr/*') ? 'show' : '' }}"
            data-bs-parent="#sidebar-nav">
            <li>
                <a class="{{ Request::is('*/employees') ? 'active' : '' }}" href="{{ route('employees.index') }}">
                    <i class="bi bi-circle"></i>
                    <span>@lang('sidebar.Employees')</span>
                </a>
            </li>
        </ul>
    </li><!-- End HR Nav -->
 --}}

</ul>
