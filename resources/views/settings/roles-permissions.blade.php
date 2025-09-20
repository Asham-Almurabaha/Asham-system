@extends('layouts.master')

@php
  use Illuminate\Support\Str;
@endphp

@section('title', __('permissions.Manage Roles & Permissions'))

@push('styles')
  <style>
    .settings-roles-permissions .hero-metric {
      background-color: rgba(255, 255, 255, 0.14);
      border: 1px solid rgba(255, 255, 255, 0.28);
      border-radius: 0.9rem;
      padding: 1rem 1.25rem;
      backdrop-filter: blur(8px);
      height: 100%;
    }

    .settings-roles-permissions .hero-metric .metric-label {
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: rgba(255, 255, 255, 0.75);
      margin-bottom: 0.35rem;
      display: block;
    }

    .settings-roles-permissions .hero-metric .metric-value {
      font-weight: 700;
      font-size: 1.35rem;
      color: #fff;
      margin: 0;
    }

    .settings-roles-permissions .permission-card {
      border: 1px solid var(--bs-border-color);
      border-radius: 1rem;
      padding: 1rem;
      transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
      background-color: var(--bs-body-bg);
      height: 100%;
    }

    .settings-roles-permissions .permission-card:hover,
    .settings-roles-permissions .permission-card:focus-within {
      border-color: rgba(var(--bs-primary-rgb), 0.55);
      box-shadow: 0 0 0 0.15rem rgba(var(--bs-primary-rgb), 0.15);
    }

    .settings-roles-permissions .permission-card .form-check {
      gap: 0.75rem;
    }

    .settings-roles-permissions .permission-card .form-check-input {
      margin-top: 0.25rem;
      cursor: pointer;
    }

    .settings-roles-permissions .permission-card .form-check-input:focus {
      box-shadow: none;
    }

    .settings-roles-permissions .permission-card .form-check-input:checked {
      background-color: var(--bs-primary);
      border-color: var(--bs-primary);
    }

    .settings-roles-permissions .permission-card .form-check-label {
      cursor: pointer;
      width: 100%;
    }

    .settings-roles-permissions .permission-card .form-check-label .permission-name {
      font-weight: 600;
      display: block;
      margin-bottom: 0.25rem;
      transition: color 0.2s ease;
    }

    .settings-roles-permissions .permission-card .form-check-input:checked + .form-check-label .permission-name {
      color: var(--bs-primary);
    }

    .settings-roles-permissions .permission-card .form-check-label .permission-meta {
      font-size: 0.75rem;
      color: var(--bs-secondary-color, var(--bs-gray-600));
      word-break: break-word;
    }

    .settings-roles-permissions .role-summary {
      border-radius: 0.9rem;
      background-color: var(--bs-body-secondary-bg, var(--bs-light));
      border: 1px solid var(--bs-border-color);
      padding: 1.25rem;
    }

    .settings-roles-permissions .role-summary ul {
      margin: 0;
      padding-inline-start: 1.15rem;
    }

    .settings-roles-permissions .role-summary li + li {
      margin-top: 0.5rem;
    }

    .settings-roles-permissions .toolbar-divider {
      width: 1px;
      height: 1.75rem;
      background: rgba(var(--bs-primary-rgb), 0.25);
    }

    .settings-roles-permissions .search-input-group .form-control {
      min-height: 44px;
    }

    .settings-roles-permissions .search-input-group .input-group-text {
      border-inline-end: 0;
    }

    .settings-roles-permissions .search-input-group .form-control {
      border-inline-start: 0;
    }

    .settings-roles-permissions .search-input-group .btn {
      border-inline-start: 0;
    }
  </style>
@endpush

@section('content')
  <div class="container-xxl py-4 settings-roles-permissions" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    {{-- Breadcrumbs --}}
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">@lang('sidebar.Settings')</a></li>
        <li class="breadcrumb-item active" aria-current="page">@lang('permissions.Manage Roles & Permissions')</li>
      </ol>
    </nav>

    {{-- Hero --}}
    <div class="card border-0 shadow-sm overflow-hidden mb-4">
      <div class="p-4 position-relative text-white" style="background: linear-gradient(135deg, var(--bs-primary) 0%, #6f42c1 100%);">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-3">
            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 64px; height: 64px;">
              <i class="bi bi-shield-check fs-3"></i>
            </span>
            <div>
              <p class="text-uppercase fw-semibold small mb-1" style="letter-spacing: 0.08em; color: rgba(255, 255, 255, 0.75);">
                @lang('permissions.Access Overview')
              </p>
              <h1 class="h3 mb-2">@lang('permissions.Manage Roles & Permissions')</h1>
              <p class="mb-0" style="color: rgba(255, 255, 255, 0.8);">@lang('permissions.Access Overview Description')</p>
            </div>
          </div>
          <div class="text-end">
            <span class="badge rounded-pill bg-white text-primary fw-semibold">
              @lang('sidebar.Manage Role Permissions')
            </span>
          </div>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mt-3">
          <div class="col">
            <div class="hero-metric">
              <span class="metric-label">@lang('permissions.Total Roles')</span>
              <p class="metric-value">{{ number_format($totalRoles) }}</p>
            </div>
          </div>
          <div class="col">
            <div class="hero-metric">
              <span class="metric-label">@lang('permissions.Total Permissions')</span>
              <p class="metric-value">{{ number_format($permissionCount) }}</p>
            </div>
          </div>
          <div class="col">
            <div class="hero-metric">
              <span class="metric-label">@lang('users.With Roles')</span>
              <p class="metric-value">{{ number_format($usersWithRoles) }}</p>
            </div>
          </div>
          <div class="col">
            <div class="hero-metric">
              <span class="metric-label">@lang('users.Without Roles')</span>
              <p class="metric-value">{{ number_format($usersWithoutRoles) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4 align-items-start">
      {{-- Sidebar summary --}}
      <div class="col-xl-4">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-4">
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 48px; height: 48px;">
                <i class="bi bi-diagram-3 fs-5"></i>
              </span>
              <div>
                <h5 class="mb-1">@lang('permissions.Role Selection Title')</h5>
                <p class="text-muted small mb-0">@lang('permissions.Role Selection Description')</p>
              </div>
            </div>

            <form method="GET" action="{{ route('settings.roles.permissions') }}" class="mb-4">
              <label for="role-selector" class="form-label fw-semibold">@lang('permissions.Select Role')</label>
              <select id="role-selector" name="role" class="form-select" onchange="this.form.submit()" @disabled($roles->isEmpty())>
                @foreach($roles as $role)
                  <option value="{{ $role->name }}" @selected(optional($selectedRole)->name === $role->name)>
                    {{ $roleLabels[$role->name] ?? $role->name }}
                  </option>
                @endforeach
              </select>
              <div class="form-text">@lang('permissions.Role Selection Help')</div>
            </form>

            @if($selectedRole)
              <div class="role-summary mb-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                  <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 36px; height: 36px;">
                    <i class="bi bi-shield-lock"></i>
                  </span>
                  <div>
                    <p class="text-uppercase small fw-semibold text-muted mb-1">@lang('permissions.Selected Role Summary', ['role' => $selectedRoleLabel])</p>
                    <p class="mb-0 text-muted small">@lang('permissions.Selected Role Description')</p>
                  </div>
                </div>
                <ul class="text-muted small">
                  <li>{{ trans_choice('permissions.Permission Count Summary', $selectedRolePermissionCount, ['count' => number_format($selectedRolePermissionCount)]) }}</li>
                  <li>{{ trans_choice('permissions.Users Assigned Count', $usersAssignedToSelectedRole, ['count' => number_format($usersAssignedToSelectedRole)]) }}</li>
                </ul>
              </div>

              <div class="border rounded-3 p-3 bg-light mb-0">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-semibold">@lang('permissions.Role Usage Title')</span>
                  <span class="badge rounded-pill bg-primary-subtle text-primary">
                    {{ number_format($usersAssignedToSelectedRole) }} / {{ number_format($totalUsers) }}
                  </span>
                </div>
                <p class="text-muted small mb-3">@lang('permissions.Role Usage Description')</p>
                @if($totalUsers > 0 && $roleUsagePercentage !== null)
                  <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $roleUsagePercentage }}%;" aria-valuenow="{{ $roleUsagePercentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                  </div>
                  <div class="d-flex justify-content-between text-muted small mt-2">
                    <span>@lang('permissions.Role Usage Percentage', ['percentage' => $roleUsagePercentage])</span>
                    <span>{{ trans_choice('permissions.Users Assigned Count', $usersAssignedToSelectedRole, ['count' => number_format($usersAssignedToSelectedRole)]) }}</span>
                  </div>
                @else
                  <div class="alert alert-light border mb-0">@lang('permissions.Role Usage Empty')</div>
                @endif
              </div>
            @else
              <div class="alert alert-light border mb-0">@lang('permissions.No roles available yet.')</div>
            @endif
          </div>
        </div>
      </div>

      {{-- Permissions management --}}
      <div class="col-xl-8">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-header bg-white border-0 pb-0">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
              <div>
                <h5 class="mb-1">@lang('permissions.Permissions Panel Title')</h5>
                <p class="text-muted small mb-0">@lang('permissions.Permissions Panel Description')</p>
              </div>
              <div class="d-flex flex-wrap align-items-center gap-2">
                <x-button type="button" variant="primary" :outline="true" size="sm" data-action="expand-all">
                  <i class="bi bi-arrows-fullscreen me-1"></i>@lang('permissions.Expand All')
                </x-button>
                <x-button type="button" variant="secondary" :outline="true" size="sm" data-action="collapse-all">
                  <i class="bi bi-arrows-collapse me-1"></i>@lang('permissions.Collapse All')
                </x-button>
              </div>
            </div>
          </div>
          <div class="card-body">
            @if(!$selectedRole)
              <div class="alert alert-info mb-0">@lang('permissions.No roles available yet.')</div>
            @elseif($permissionGroups->isEmpty())
              <div class="alert alert-info mb-0">@lang('permissions.No permissions defined.')</div>
            @else
              <form method="POST" action="{{ route('settings.roles.permissions.update', $selectedRole) }}">
                @csrf
                @method('PUT')

                <div class="row g-3 align-items-end mb-4">
                  <div class="col-lg-7">
                    <label for="permission-search" class="form-label small fw-semibold text-muted text-uppercase">@lang('permissions.Filter Permissions')</label>
                    <div class="input-group search-input-group">
                      <span class="input-group-text bg-body"><i class="bi bi-search"></i></span>
                      <input type="search" id="permission-search" class="form-control" placeholder="@lang('permissions.Permission Search Placeholder')" aria-label="@lang('permissions.Filter Permissions')">
                      <x-button type="button" variant="secondary" :outline="true" id="permission-search-clear">
                        <i class="bi bi-x-circle me-1"></i>@lang('permissions.Clear Search')
                      </x-button>
                    </div>
                    <div class="form-text">@lang('permissions.Permission Search Help')</div>
                  </div>
                </div>

                <div class="alert alert-light border d-none" id="permissions-empty-results" data-empty-template="@lang('permissions.No permissions match query', ['query' => ':query'])">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-search"></i>
                    <span data-permission-empty-text>@lang('permissions.No permissions match your search.')</span>
                  </div>
                </div>

                <div class="accordion" id="rolePermissionsAccordion">
                  @foreach($permissionGroups as $index => $group)
                    <div class="accordion-item border-0 mb-3 shadow-sm rounded-3 overflow-hidden">
                      <h2 class="accordion-header" id="permission-group-heading-{{ $index }}">
                        <x-button type="button" :unstyled="true" class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#permission-group-body-{{ $index }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="permission-group-body-{{ $index }}">
                          <span class="fw-semibold">{{ $group['label'] }}</span>
                          <span class="badge bg-primary-subtle text-primary ms-2">{{ $group['permissions']->count() }}</span>
                        </x-button>
                      </h2>
                      <div id="permission-group-body-{{ $index }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" data-bs-parent="#rolePermissionsAccordion">
                        <div class="accordion-body" data-permission-group-container="{{ $index }}">
                          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <p class="text-muted small mb-0">@lang('permissions.Permission Tools Description')</p>
                            <div class="btn-group btn-group-sm" role="group" aria-label="@lang('permissions.Group Actions Hint')">
                              <x-button type="button" variant="primary" :outline="true" size="sm" data-select-group="{{ $index }}">
                                <i class="bi bi-check2-all me-1"></i>@lang('permissions.Select Group')
                              </x-button>
                              <x-button type="button" variant="secondary" :outline="true" size="sm" data-deselect-group="{{ $index }}">
                                <i class="bi bi-eraser me-1"></i>@lang('permissions.Deselect Group')
                              </x-button>
                            </div>
                          </div>
                          <div class="row g-3">
                            @foreach($group['permissions'] as $permission)
                              @php
                                $permissionId = 'permission-'.$index.'-'.Str::slug($permission->name);
                              @endphp
                              <div class="col-12 col-md-6 col-xl-4" data-permission-card data-permission-group="{{ $index }}" data-permission="{{ Str::lower($permission->name) }}" data-permission-label="{{ Str::lower($permissionLabels[$permission->name] ?? $permission->name) }}">
                                <div class="permission-card">
                                  <div class="form-check d-flex align-items-start m-0">
                                    <input class="form-check-input flex-shrink-0" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="{{ $permissionId }}" @checked(in_array($permission->name, $selectedPermissions, true))>
                                    <label class="form-check-label" for="{{ $permissionId }}">
                                      <span class="permission-name">{{ $permissionLabels[$permission->name] ?? $permission->name }}</span>
                                      <span class="permission-meta">{{ $permission->name }}</span>
                                    </label>
                                  </div>
                                </div>
                              </div>
                            @endforeach
                          </div>
                          <div class="alert alert-light border d-none mt-3" data-permission-empty>
                            <div class="d-flex align-items-center gap-2">
                              <i class="bi bi-search"></i>
                              <span>@lang('permissions.Group Empty After Filter')</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>

                <div class="d-flex justify-content-end mt-4">
                  <x-button.save>
                    @lang('permissions.Save Permissions')
                  </x-button.save>
                </div>
              </form>
            @endif
          </div>
        </div>
      </div>
    </div>

  </div>
@endsection

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const root = document.querySelector('.settings-roles-permissions');
      if (!root) {
        return;
      }

      const permissionSearch = root.querySelector('#permission-search');
      const permissionClear = root.querySelector('#permission-search-clear');
      const permissionCards = root.querySelectorAll('[data-permission-card]');
      const groupContainers = root.querySelectorAll('[data-permission-group-container]');
      const emptyResults = root.querySelector('#permissions-empty-results');
      const emptyText = emptyResults ? emptyResults.querySelector('[data-permission-empty-text]') : null;
      const emptyTemplate = emptyResults ? emptyResults.getAttribute('data-empty-template') : null;

      if (emptyResults && emptyText) {
        emptyResults.setAttribute('data-default-text', emptyText.textContent);
      }

      const updatePermissionVisibility = () => {
        if (!permissionCards.length) {
          if (emptyResults) {
            emptyResults.classList.add('d-none');
          }
          return;
        }

        const query = permissionSearch ? permissionSearch.value.trim().toLowerCase() : '';
        let visibleCount = 0;

        permissionCards.forEach(card => {
          const datasetValue = (card.dataset.permission || '') + ' ' + (card.dataset.permissionLabel || '');
          const matches = query === '' || datasetValue.includes(query);
          card.classList.toggle('d-none', !matches);
          if (matches) {
            visibleCount += 1;
          }
        });

        groupContainers.forEach(container => {
          const visibleItems = container.querySelectorAll('[data-permission-card]:not(.d-none)');
          const groupEmpty = container.querySelector('[data-permission-empty]');
          if (groupEmpty) {
            groupEmpty.classList.toggle('d-none', visibleItems.length > 0);
          }
        });

        if (emptyResults) {
          const hasQuery = query !== '';
          emptyResults.classList.toggle('d-none', visibleCount > 0);
          if (hasQuery && emptyTemplate && emptyText) {
            emptyText.textContent = emptyTemplate.replace(':query', query);
          } else if (emptyText) {
            emptyText.textContent = emptyResults.getAttribute('data-default-text') || '';
          }
        }
      };

      permissionSearch?.addEventListener('input', updatePermissionVisibility);

      permissionClear?.addEventListener('click', () => {
        if (!permissionSearch) {
          return;
        }
        permissionSearch.value = '';
        permissionSearch.dispatchEvent(new Event('input'));
        permissionSearch.focus();
      });

      root.querySelectorAll('[data-select-group]').forEach(button => {
        button.addEventListener('click', () => {
          const target = button.getAttribute('data-select-group');
          root.querySelectorAll(`[data-permission-group="${target}"] input[type="checkbox"]`).forEach(input => {
            input.checked = true;
            input.dispatchEvent(new Event('change', { bubbles: true }));
          });
        });
      });

      root.querySelectorAll('[data-deselect-group]').forEach(button => {
        button.addEventListener('click', () => {
          const target = button.getAttribute('data-deselect-group');
          root.querySelectorAll(`[data-permission-group="${target}"] input[type="checkbox"]`).forEach(input => {
            input.checked = false;
            input.dispatchEvent(new Event('change', { bubbles: true }));
          });
        });
      });

      const accordion = root.querySelector('#rolePermissionsAccordion');
      const expandAllBtn = root.querySelector('[data-action="expand-all"]');
      const collapseAllBtn = root.querySelector('[data-action="collapse-all"]');

      const setAccordionState = (open) => {
        if (!accordion || typeof bootstrap === 'undefined' || !bootstrap.Collapse) {
          return;
        }

        accordion.querySelectorAll('.accordion-collapse').forEach(element => {
          const instance = bootstrap.Collapse.getOrCreateInstance(element, { toggle: false });
          if (open) {
            instance.show();
          } else {
            instance.hide();
          }
        });
      };

      expandAllBtn?.addEventListener('click', () => setAccordionState(true));
      collapseAllBtn?.addEventListener('click', () => setAccordionState(false));

      updatePermissionVisibility();

    });
  </script>
@endpush
