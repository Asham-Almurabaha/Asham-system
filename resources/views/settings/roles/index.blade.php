@extends('layouts.master')

@php
  use Illuminate\Support\Str;
@endphp

@section('title', __('roles.Manage Roles'))

@push('styles')
  <style>
    .settings-roles .hero-metric {
      background-color: rgba(255, 255, 255, 0.14);
      border: 1px solid rgba(255, 255, 255, 0.28);
      border-radius: 0.9rem;
      padding: 1rem 1.25rem;
      backdrop-filter: blur(8px);
      height: 100%;
    }

    .settings-roles .hero-metric .metric-label {
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: rgba(255, 255, 255, 0.75);
      margin-bottom: 0.35rem;
      display: block;
    }

    .settings-roles .hero-metric .metric-value {
      font-weight: 700;
      font-size: 1.35rem;
      color: #fff;
      margin: 0;
    }

    .settings-roles .role-card {
      border: 1px solid var(--bs-border-color);
      border-radius: 1rem;
      padding: 1rem;
      transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
      background-color: var(--bs-body-bg);
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    .settings-roles .role-card:hover,
    .settings-roles .role-card:focus-within {
      border-color: rgba(var(--bs-primary-rgb), 0.55);
      box-shadow: 0 0 0 0.15rem rgba(var(--bs-primary-rgb), 0.15);
    }

    .settings-roles .role-card .role-name {
      font-weight: 600;
      display: block;
      margin-bottom: 0.25rem;
      transition: color 0.2s ease;
    }

    .settings-roles .role-card .role-meta {
      font-size: 0.75rem;
      color: var(--bs-secondary-color, var(--bs-gray-600));
      word-break: break-word;
    }

    .settings-roles .role-card .role-section-title {
      color: var(--bs-secondary-color, var(--bs-gray-600));
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .settings-roles .role-card .role-actions {
      margin-top: auto;
      padding-top: 1rem;
      border-top: 1px solid var(--bs-border-color);
    }

    .settings-roles .search-input-group .form-control {
      min-height: 44px;
    }

    .settings-roles .search-input-group .input-group-text {
      border-inline-end: 0;
    }

    .settings-roles .search-input-group .form-control {
      border-inline-start: 0;
    }

    .settings-roles .search-input-group .btn {
      border-inline-start: 0;
    }

    .settings-roles .badge {
      font-weight: 500;
    }
  </style>
@endpush

@section('content')
  @php
    $locale = app()->getLocale();
    $localeRoot = strtolower(strtok($locale, '_'));
    $rtlLocales = ['ar', 'he'];
    $isRtl = in_array($localeRoot, $rtlLocales, true);
  @endphp

  <div class="container-xxl py-4 settings-roles" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    {{-- Breadcrumbs --}}
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">@lang('sidebar.Settings')</a></li>
        <li class="breadcrumb-item active" aria-current="page">@lang('roles.Manage Roles')</li>
      </ol>
    </nav>

    {{-- Hero --}}
    <div class="card border-0 shadow-sm overflow-hidden mb-4">
      <div class="p-4 position-relative text-white" style="background: linear-gradient(135deg, var(--bs-primary) 0%, #0d6efd 60%, #6f42c1 100%);">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-3">
            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 64px; height: 64px;">
              <i class="bi bi-person-badge fs-3"></i>
            </span>
            <div>
              <p class="text-uppercase fw-semibold small mb-1" style="letter-spacing: 0.08em; color: rgba(255, 255, 255, 0.75);">
                @lang('roles.Roles Overview')
              </p>
              <h1 class="h3 mb-2">@lang('roles.Manage Roles')</h1>
              <p class="mb-0" style="color: rgba(255, 255, 255, 0.8);">
                @lang('roles.Manage Roles Description', ['guard' => $guardLabel])
              </p>
            </div>
          </div>
          <div class="text-end">
            <span class="badge rounded-pill bg-white text-primary fw-semibold">
              <i class="bi bi-people-fill me-1"></i>
              @lang('roles.Role Library Title')
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
              <span class="metric-label">@lang('roles.Roles In Use')</span>
              <p class="metric-value">{{ number_format($rolesWithUsersCount) }}</p>
            </div>
          </div>
          <div class="col">
            <div class="hero-metric">
              <span class="metric-label">@lang('roles.Roles Without Users')</span>
              <p class="metric-value">{{ number_format($rolesWithoutUsersCount) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4 align-items-start">
      {{-- Sidebar actions --}}
      <div class="col-xl-4">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-4">
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 48px; height: 48px;">
                <i class="bi bi-diagram-2 fs-5"></i>
              </span>
              <div>
                <h5 class="mb-1">@lang('roles.Role Creation Title')</h5>
                <p class="text-muted small mb-0">@lang('roles.Role Creation Description')</p>
              </div>
            </div>

            <form method="GET" action="{{ route('settings.roles.index') }}" class="mb-4">
              <label for="guard-selector" class="form-label fw-semibold">@lang('roles.Guard Filter Label')</label>
              <select id="guard-selector" name="guard" class="form-select" onchange="this.form.submit()">
                <option value="all" @selected($showAllGuards)>@lang('roles.Guard Filter All Option')</option>
                @foreach($guardOptions as $guardOption)
                  <option value="{{ $guardOption }}" @selected(!$showAllGuards && $selectedGuard === $guardOption)>
                    {{ Str::headline($guardOption) }}
                  </option>
                @endforeach
              </select>
              <div class="form-text">@lang('roles.Guard Filter Help')</div>
            </form>

            <form method="POST" action="{{ route('settings.roles.store') }}">
              @csrf
              <input type="hidden" name="redirect_guard" value="{{ $showAllGuards ? 'all' : $selectedGuard }}">
              <div class="mb-3">
                <label for="role-name" class="form-label fw-semibold">@lang('roles.Role Name Label')</label>
                <input
                  type="text"
                  name="name"
                  id="role-name"
                  class="form-control @error('name') is-invalid @enderror"
                  placeholder="@lang('roles.Role Name Placeholder')"
                  value="{{ old('name') }}"
                  autocomplete="off"
                  required
                >
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @else
                  <div class="form-text">@lang('roles.Role Name Help')</div>
                @enderror
              </div>

              <div class="mb-3">
                <label for="role-guard" class="form-label fw-semibold">@lang('roles.Role Guard Label')</label>
                <input
                  type="text"
                  name="guard_name"
                  id="role-guard"
                  class="form-control @error('guard_name') is-invalid @enderror"
                  placeholder="@lang('roles.Role Guard Placeholder')"
                  value="{{ old('guard_name', $showAllGuards ? $defaultGuard : $selectedGuard) }}"
                  list="role-guard-options"
                  autocomplete="off"
                >
                <datalist id="role-guard-options">
                  @foreach($guardOptions as $guardOption)
                    <option value="{{ $guardOption }}"></option>
                  @endforeach
                </datalist>
                @error('guard_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @else
                  <div class="form-text">@lang('roles.Role Guard Help', ['guard' => $defaultGuard])</div>
                @enderror
              </div>

              <div class="d-grid">
                <x-button.save type="submit">
                  @lang('roles.Create Role Button')
                </x-button.save>
              </div>
            </form>

            <div class="border rounded-3 p-3 bg-light mt-4">
              <p class="fw-semibold text-muted text-uppercase small mb-2">@lang('roles.Role Library Title')</p>
              <ul class="list-unstyled text-muted small mb-0">
                <li>{{ trans_choice('roles.Role Count Summary', $totalRoles, ['count' => number_format($totalRoles)]) }}</li>
                <li>{{ trans_choice('roles.Roles In Use Summary', $rolesWithUsersCount, ['count' => number_format($rolesWithUsersCount)]) }}</li>
                <li>{{ trans_choice('roles.Guard Count Summary', $guardOptions->count(), ['count' => number_format($guardOptions->count())]) }}</li>
              </ul>
              <p class="text-muted small mt-3 mb-0">@lang('roles.Role Creation Help')</p>
            </div>
          </div>
        </div>
      </div>

      {{-- Roles library --}}
      <div class="col-xl-8">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-header bg-white border-0 pb-0">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
              <div>
                <h5 class="mb-1">@lang('roles.Role Library Title')</h5>
                <p class="text-muted small mb-0">@lang('roles.Role Library Description')</p>
              </div>
            </div>
          </div>
          <div class="card-body">
            @if($totalRoles === 0)
              <div class="alert alert-info mb-0">@lang('roles.No roles defined.')</div>
            @else
              <div class="row g-3 align-items-end mb-4">
                <div class="col-lg-7">
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
              </div>

              <div class="alert alert-light border d-none" id="roles-empty-results" data-empty-template="@lang('roles.No roles match query', ['query' => ':query'])">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-search"></i>
                  <span data-role-empty-text>@lang('roles.No roles match your search.')</span>
                </div>
              </div>

              <div class="row g-3" data-role-list>
                @foreach($roles as $role)
                  @php
                    $assignedUsers = $roleUsers->get($role->name, collect());
                    $permissionDataset = Str::lower($role->permissions->pluck('name')->implode(' '));
                    $permissionLabelsForRole = $role->permissions->sortBy('name');
                    $userDataset = Str::lower(
                      $assignedUsers
                        ->flatMap(fn ($user) => [$user['display'] ?? '', $user['email'] ?? ''])
                        ->filter()
                        ->implode(' ')
                    );
                  @endphp
                  <div
                    class="col-12 col-md-6"
                    data-role-card
                    data-role="{{ Str::lower($role->name) }}"
                    data-role-label="{{ Str::lower($roleLabels[$role->name] ?? $role->name) }}"
                    data-role-guard="{{ Str::lower($role->guard_name ?? '') }}"
                    data-role-permissions="{{ $permissionDataset }}"
                    data-role-users="{{ $userDataset }}"
                  >
                    <div class="role-card">
                      <div class="d-flex align-items-start justify-content-between gap-2">
                        <div>
                          <span class="role-name">{{ $roleLabels[$role->name] ?? $role->name }}</span>
                          <span class="role-meta">{{ $role->name }}</span>
                        </div>
                        <span class="badge bg-primary-subtle text-primary">@lang('roles.Role Guard Display', ['guard' => $role->guard_name])</span>
                      </div>

                      <div class="mt-3">
                        <div class="role-section-title">@lang('roles.Assigned Permissions Label')</div>
                        @if($permissionLabelsForRole->isNotEmpty())
                          <div class="d-flex flex-wrap gap-1">
                            @foreach($permissionLabelsForRole as $permission)
                              <span class="badge bg-secondary-subtle text-secondary border">{{ $permissionLabels[$permission->name] ?? $permission->name }}</span>
                            @endforeach
                          </div>
                        @else
                          <p class="text-muted small mb-0">@lang('roles.No Permissions Assigned')</p>
                        @endif
                      </div>

                      <div class="mt-3">
                        <div class="role-section-title">@lang('roles.Assigned Users Label')</div>
                        @if($assignedUsers->isNotEmpty())
                          <div class="d-flex flex-wrap gap-1">
                            @foreach($assignedUsers as $user)
                              <span class="badge bg-info-subtle text-info border">{{ $user['display'] }}</span>
                            @endforeach
                          </div>
                        @else
                          <p class="text-muted small mb-0">@lang('roles.No Users Assigned')</p>
                        @endif
                      </div>

                      <div class="role-actions mt-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                          <x-button.action href="{{ route('settings.roles.permissions', ['role' => $role->name]) }}" variant="primary" :outline="true" size="sm">
                            <i class="bi bi-sliders me-1"></i>@lang('roles.Manage Role Permissions Button')
                          </x-button.action>
                          <form method="POST" action="{{ route('settings.roles.destroy', $role) }}" onsubmit="return confirm('@lang('roles.Role Delete Confirmation')');">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="redirect_guard" value="{{ $showAllGuards ? 'all' : $selectedGuard }}">
                            <x-button.delete type="submit" size="sm">
                              @lang('roles.Delete Role')
                            </x-button.delete>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
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
      const root = document.querySelector('.settings-roles');
      if (!root) {
        return;
      }

      const roleSearch = root.querySelector('#role-search');
      const roleClear = root.querySelector('#role-search-clear');
      const roleCards = root.querySelectorAll('[data-role-card]');
      const emptyResults = root.querySelector('#roles-empty-results');
      const emptyText = emptyResults ? emptyResults.querySelector('[data-role-empty-text]') : null;
      const emptyTemplate = emptyResults ? emptyResults.getAttribute('data-empty-template') : null;

      if (emptyResults && emptyText) {
        emptyResults.setAttribute('data-default-text', emptyText.textContent);
      }

      const updateRoleVisibility = () => {
        if (!roleCards.length) {
          if (emptyResults) {
            emptyResults.classList.add('d-none');
          }
          return;
        }

        const query = roleSearch ? roleSearch.value.trim().toLowerCase() : '';
        let visibleCount = 0;

        roleCards.forEach(card => {
          const datasetValue = [
            card.dataset.role,
            card.dataset.roleLabel,
            card.dataset.roleGuard,
            card.dataset.rolePermissions,
            card.dataset.roleUsers,
          ].filter(Boolean).join(' ').toLowerCase();

          const matches = query === '' || datasetValue.includes(query);
          card.classList.toggle('d-none', !matches);
          if (matches) {
            visibleCount += 1;
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

      roleSearch?.addEventListener('input', updateRoleVisibility);

      roleClear?.addEventListener('click', () => {
        if (!roleSearch) {
          return;
        }

        roleSearch.value = '';
        roleSearch.dispatchEvent(new Event('input'));
        roleSearch.focus();
      });

      updateRoleVisibility();
    });
  </script>
@endpush
