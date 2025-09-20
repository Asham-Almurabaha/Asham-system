@extends('layouts.master')

@section('title', __('users.Edit User Roles'))

@push('styles')
  <style>
    .user-roles-manager .hero-metric {
      background-color: rgba(255, 255, 255, 0.14);
      border: 1px solid rgba(255, 255, 255, 0.28);
      border-radius: 0.9rem;
      padding: 1rem 1.25rem;
      backdrop-filter: blur(8px);
      height: 100%;
    }

    .user-roles-manager .hero-metric .metric-label {
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: rgba(255, 255, 255, 0.75);
      margin-bottom: 0.35rem;
      display: block;
    }

    .user-roles-manager .hero-metric .metric-value {
      font-weight: 700;
      font-size: 1.35rem;
      color: #fff;
      margin: 0;
    }

    .user-roles-manager .summary-section-title {
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--bs-secondary-color, var(--bs-gray-600));
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .user-roles-manager .summary-card .badge {
      font-weight: 500;
    }

    .user-roles-manager .option-card {
      border: 1px solid var(--bs-border-color);
      border-radius: 1rem;
      padding: 1rem;
      background-color: var(--bs-body-bg);
      transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .user-roles-manager .option-card:hover,
    .user-roles-manager .option-card:focus-within {
      border-color: rgba(var(--bs-primary-rgb), 0.55);
      box-shadow: 0 0 0 0.15rem rgba(var(--bs-primary-rgb), 0.15);
    }

    .user-roles-manager .option-card.option-card-inherited {
      background-color: var(--bs-body-secondary-bg, rgba(108, 117, 125, 0.08));
      border-style: dashed;
    }

    .user-roles-manager .option-card .form-check {
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
      margin: 0;
    }

    .user-roles-manager .option-card .form-check-input {
      margin: 0;
      margin-top: 0.3rem;
      float: none;
    }

    .user-roles-manager .option-card .form-check-label {
      margin: 0;
      width: 100%;
    }

    .user-roles-manager .search-input-group .form-control {
      min-height: 44px;
    }

    .user-roles-manager .search-input-group .input-group-text {
      border-inline-end: 0;
    }

    .user-roles-manager .search-input-group .form-control {
      border-inline-start: 0;
    }

    .user-roles-manager .search-input-group .btn {
      border-inline-start: 0;
    }

    .user-roles-manager .summary-card-description {
      color: var(--bs-secondary-color, var(--bs-gray-600));
      font-size: 0.875rem;
    }

    .user-roles-manager .badge-rounded-pill {
      border-radius: 999px;
      padding: 0.35rem 0.75rem;
      font-size: 0.75rem;
    }
  </style>
@endpush

@section('content')
  @php
    $locale = app()->getLocale();
    $localeRoot = strtolower(strtok($locale, '_'));
    $rtlLocales = ['ar', 'he'];
    $isRtl = in_array($localeRoot, $rtlLocales, true);

    $selectedRoles = old('roles', $current);
    if (!is_array($selectedRoles)) {
      $selectedRoles = $current;
    }

    $selectedPermissions = old('permissions', $directPermissions);
    if (!is_array($selectedPermissions)) {
      $selectedPermissions = $directPermissions;
    }

    $totalRoleCount = $roles->count();
    $assignedRoleCount = count($selectedRoles);
    $directPermissionCount = count($selectedPermissions);
    $inheritedPermissionCount = count($inheritedPermissions);
  @endphp

  <div class="container-xxl py-4 user-roles-manager" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    <div class="mb-3">
      <x-button.secondary href="{{ route('users.index') }}" size="sm">
        @lang('users.Back to List')
      </x-button.secondary>
    </div>

    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('users.index') }}">@lang('users.Users')</a></li>
        <li class="breadcrumb-item active" aria-current="page">@lang('users.Edit User Roles')</li>
      </ol>
    </nav>

    <div class="card border-0 shadow-sm overflow-hidden mb-4">
      <div class="p-4 position-relative text-white" style="background: linear-gradient(135deg, var(--bs-primary) 0%, #6f42c1 100%);">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-3">
            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 64px; height: 64px;">
              <i class="bi bi-person-check fs-3"></i>
            </span>
            <div>
              <p class="text-uppercase fw-semibold small mb-1" style="letter-spacing: 0.08em; color: rgba(255, 255, 255, 0.75);">
                @lang('users.Manage Access For')
              </p>
              <h1 class="h3 mb-1">{{ $user->name }}</h1>
              <p class="mb-0" style="color: rgba(255, 255, 255, 0.8);">{{ $user->email }}</p>
            </div>
          </div>
          <div class="text-end">
            <span class="badge badge-rounded-pill bg-white text-primary fw-semibold">
              @lang('users.Exceptional Permissions Hint')
            </span>
          </div>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mt-3">
          <div class="col">
            <div class="hero-metric">
              <span class="metric-label">@lang('users.Available Roles')</span>
              <p class="metric-value">{{ number_format($totalRoleCount) }}</p>
            </div>
          </div>
          <div class="col">
            <div class="hero-metric">
              <span class="metric-label">@lang('users.Current Roles')</span>
              <p class="metric-value">{{ number_format($assignedRoleCount) }}</p>
            </div>
          </div>
          <div class="col">
            <div class="hero-metric">
              <span class="metric-label">@lang('users.Exceptional Permissions Label')</span>
              <p class="metric-value">{{ number_format($directPermissionCount) }}</p>
            </div>
          </div>
          <div class="col">
            <div class="hero-metric">
              <span class="metric-label">@lang('users.Role Permissions')</span>
              <p class="metric-value">{{ number_format($inheritedPermissionCount) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <form method="POST" action="{{ route('users.roles.update', $user) }}">
      @csrf
      @method('PUT')

      <div class="row g-4 align-items-start">
        <div class="col-xl-4">
          <div class="card shadow-sm h-100 border-0 summary-card">
            <div class="card-body">
              <div class="d-flex align-items-center gap-3 mb-4">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 48px; height: 48px;">
                  <i class="bi bi-person-fill-gear fs-5"></i>
                </span>
                <div>
                  <h5 class="mb-1">@lang('users.Access Overview')</h5>
                  <p class="summary-card-description mb-0">@lang('users.Access Overview Help')</p>
                </div>
              </div>

              <div class="mb-4">
                <div class="summary-section-title">@lang('users.Current Roles')</div>
                <div class="d-flex flex-wrap gap-1">
                  @forelse ($current as $role)
                    <span class="badge bg-primary-subtle text-primary border">{{ \Illuminate\Support\Str::headline(str_replace(['-', '_'], ' ', $role)) }}</span>
                  @empty
                    <span class="badge bg-light text-muted border">@lang('users.No Roles Assigned')</span>
                  @endforelse
                </div>
              </div>

              <div class="mb-4">
                <div class="summary-section-title">@lang('users.Role Permissions')</div>
                <div class="d-flex flex-wrap gap-1">
                  @forelse ($inheritedPermissions as $permission)
                    <span class="badge bg-secondary-subtle text-secondary border">{{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $permission)) }}</span>
                  @empty
                    <span class="badge bg-light text-muted border">@lang('users.No Role Permissions')</span>
                  @endforelse
                </div>
              </div>

              <div>
                <div class="summary-section-title">@lang('users.Exceptional Permissions Label')</div>
                <div class="d-flex flex-wrap gap-1">
                  @forelse ($selectedPermissions as $permission)
                    <span class="badge bg-success-subtle text-success border">{{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $permission)) }}</span>
                  @empty
                    <span class="badge bg-light text-muted border">@lang('users.No Exceptional Permissions')</span>
                  @endforelse
                </div>
              </div>
            </div>
          </div>

          <div class="alert alert-info border-0 shadow-sm small mt-4" role="alert">
            <div class="fw-semibold mb-1">@lang('users.Tip Title')</div>
            <div>@lang('users.Tip Description')</div>
          </div>
        </div>

        <div class="col-xl-8">
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pb-0">
              <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                  <h5 class="mb-1">@lang('users.Available Roles')</h5>
                  <p class="text-muted small mb-0">@lang('users.Assign Roles Help')</p>
                </div>
                <div class="text-end">
                  <span class="badge bg-primary-subtle text-primary fw-semibold">
                    {{ number_format($assignedRoleCount) }} / {{ number_format($totalRoleCount) }}
                  </span>
                </div>
              </div>
            </div>
            <div class="card-body">
              @if ($roles->isEmpty())
                <div class="alert alert-light border mb-0" role="alert">
                  @lang('roles.No roles defined.')
                </div>
              @else
                <div class="mb-4">
                  <label for="role-search" class="form-label small fw-semibold text-muted text-uppercase">@lang('roles.Filter Roles')</label>
                  <div class="input-group search-input-group">
                    <span class="input-group-text bg-body"><i class="bi bi-search"></i></span>
                    <input type="search" id="role-search" class="form-control" placeholder="@lang('roles.Role Search Placeholder')" aria-label="@lang('roles.Filter Roles')">
                    <x-button.action type="button" variant="secondary" :outline="true" id="role-search-clear">
                      <i class="bi bi-x-circle me-1"></i>@lang('roles.Clear Search')
                    </x-button.action>
                  </div>
                  <div class="form-text">@lang('roles.Role Search Help')</div>
                </div>

                <div class="row g-3" data-role-list>
                  @foreach ($roles as $roleName => $label)
                    @php
                      $roleId = 'role_' . \Illuminate\Support\Str::slug($roleName);
                      $roleLabel = \Illuminate\Support\Str::headline(str_replace(['.', '-', '_'], ' ', $label));
                      $isSelected = in_array($roleName, $selectedRoles, true);
                    @endphp
                    <div class="col-12 col-md-6" data-role-option data-name="{{ \Illuminate\Support\Str::lower($roleName) }}" data-label="{{ \Illuminate\Support\Str::lower($roleLabel) }}">
                      <div class="option-card">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $roleName }}" id="{{ $roleId }}" @checked($isSelected)>
                          <label class="form-check-label" for="{{ $roleId }}">
                            <span class="fw-semibold d-block">{{ $roleLabel }}</span>
                            <small class="d-block text-muted">@lang('users.Role Checkbox Helper')</small>
                          </label>
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>

                <div class="alert alert-light border d-none mt-3" data-role-empty data-empty-template="@lang('roles.No roles match query', ['query' => ':query'])">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-search"></i>
                    <span data-empty-text>@lang('roles.No roles match your search.')</span>
                  </div>
                </div>
              @endif
            </div>
          </div>

          <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pb-0">
              <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                  <h5 class="mb-1">@lang('users.Exceptional Permissions')</h5>
                  <p class="text-muted small mb-0">@lang('users.Exceptional Permissions Help')</p>
                </div>
                <span class="badge bg-info-subtle text-info fw-semibold">
                  {{ number_format($directPermissionCount) }}
                </span>
              </div>
            </div>
            <div class="card-body">
              @if ($permissions->isEmpty())
                <div class="alert alert-light border mb-0" role="alert">
                  @lang('users.No Permissions Found')
                </div>
              @else
                <div class="mb-4">
                  <label for="permission-search" class="form-label small fw-semibold text-muted text-uppercase">@lang('permissions.Filter Permissions')</label>
                  <div class="input-group search-input-group">
                    <span class="input-group-text bg-body"><i class="bi bi-search"></i></span>
                    <input type="search" id="permission-search" class="form-control" placeholder="@lang('permissions.Permission Search Placeholder')" aria-label="@lang('permissions.Filter Permissions')">
                    <x-button.action type="button" variant="secondary" :outline="true" id="permission-search-clear">
                      <i class="bi bi-x-circle me-1"></i>@lang('permissions.Clear Search')
                    </x-button.action>
                  </div>
                  <div class="form-text">@lang('permissions.Permission Search Help')</div>
                </div>

                <div class="row g-3" data-permission-list>
                  @foreach ($permissions as $permission)
                    @php
                      $inputId = 'permission_' . \Illuminate\Support\Str::slug($permission->name);
                      $label = \Illuminate\Support\Str::headline(str_replace('.', ' ', $permission->name));
                      $isInherited = in_array($permission->name, $inheritedPermissions, true);
                      $isDirect = in_array($permission->name, $selectedPermissions, true);
                      $optionClasses = 'option-card';
                      if ($isInherited) {
                        $optionClasses .= ' option-card-inherited';
                      }
                    @endphp
                    <div class="col-12 col-md-6 col-xl-4" data-permission-option data-name="{{ \Illuminate\Support\Str::lower($permission->name) }}" data-label="{{ \Illuminate\Support\Str::lower($label) }}">
                      <div class="{{ $optionClasses }}">
                        <div class="form-check">
                          <input
                            class="form-check-input"
                            type="checkbox"
                            name="permissions[]"
                            value="{{ $permission->name }}"
                            id="{{ $inputId }}"
                            @checked($isInherited || $isDirect)
                            @disabled($isInherited)
                          >
                          <label class="form-check-label" for="{{ $inputId }}">
                            <span class="fw-semibold d-block">{{ $label }}</span>
                            @if ($isInherited)
                              <small class="d-block text-muted">@lang('users.Inherited From Role')</small>
                            @elseif ($isDirect)
                              <small class="d-block text-success">@lang('users.Exceptional Permission Applied')</small>
                            @else
                              <small class="d-block text-muted">@lang('users.Exceptional Permission Available')</small>
                            @endif
                          </label>
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>

                <div class="alert alert-light border d-none mt-3" data-permission-empty data-empty-template="@lang('permissions.No permissions match query', ['query' => ':query'])">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-search"></i>
                    <span data-empty-text>@lang('permissions.No permissions match your search.')</span>
                  </div>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
        <x-button.save>
          @lang('users.Save')
        </x-button.save>
        <x-button.secondary href="{{ route('users.index') }}">
          @lang('users.Cancel')
        </x-button.secondary>
      </div>
    </form>
  </div>
@endsection

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const root = document.querySelector('.user-roles-manager');
      if (!root) {
        return;
      }

      const setupFilter = ({ inputSelector, clearSelector, itemSelector, emptySelector }) => {
        const input = root.querySelector(inputSelector);
        if (!input) {
          return;
        }

        const clearButton = clearSelector ? root.querySelector(clearSelector) : null;
        const items = root.querySelectorAll(itemSelector);
        const emptyState = emptySelector ? root.querySelector(emptySelector) : null;
        const emptyText = emptyState ? emptyState.querySelector('[data-empty-text]') : null;
        const emptyTemplate = emptyState ? emptyState.getAttribute('data-empty-template') : null;
        const defaultEmptyText = emptyText ? emptyText.textContent : '';

        if (!items.length) {
          if (emptyState) {
            emptyState.classList.add('d-none');
          }
          return;
        }

        const applyFilter = () => {
          const query = input.value.trim().toLowerCase();
          let visibleCount = 0;

          items.forEach(item => {
            const datasetValue = [item.dataset.name, item.dataset.label]
              .filter(Boolean)
              .join(' ')
              .toLowerCase();

            const matches = query === '' || datasetValue.includes(query);
            item.classList.toggle('d-none', !matches);
            if (matches) {
              visibleCount += 1;
            }
          });

          if (emptyState && emptyText) {
            emptyState.classList.toggle('d-none', visibleCount > 0);
            if (query !== '' && emptyTemplate) {
              emptyText.textContent = emptyTemplate.replace(':query', query);
            } else {
              emptyText.textContent = defaultEmptyText;
            }
          }
        };

        input.addEventListener('input', applyFilter);

        clearButton?.addEventListener('click', () => {
          input.value = '';
          applyFilter();
          input.focus();
        });

        applyFilter();
      };

      setupFilter({
        inputSelector: '#role-search',
        clearSelector: '#role-search-clear',
        itemSelector: '[data-role-option]',
        emptySelector: '[data-role-empty]',
      });

      setupFilter({
        inputSelector: '#permission-search',
        clearSelector: '#permission-search-clear',
        itemSelector: '[data-permission-option]',
        emptySelector: '[data-permission-empty]',
      });
    });
  </script>
@endpush
