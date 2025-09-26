@extends('layouts.master')

@php
  use Illuminate\Support\Str;
@endphp

@section('title', __('permissions.Manage Permissions'))

@push('styles')
  <style>
    .settings-permissions .hero-metric {
      background-color: rgba(255, 255, 255, 0.14);
      border: 1px solid rgba(255, 255, 255, 0.28);
      border-radius: 0.9rem;
      padding: 1rem 1.25rem;
      backdrop-filter: blur(8px);
      height: 100%;
    }

    .settings-permissions .hero-metric .metric-label {
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: rgba(255, 255, 255, 0.75);
      margin-bottom: 0.35rem;
      display: block;
    }

    .settings-permissions .hero-metric .metric-value {
      font-weight: 700;
      font-size: 1.35rem;
      color: #fff;
      margin: 0;
    }

    .settings-permissions .permission-card {
      border: 1px solid var(--bs-border-color);
      border-radius: 1rem;
      padding: 1rem;
      transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
      background-color: var(--bs-body-bg);
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    .settings-permissions .permission-toolbar {
      border: 1px solid var(--bs-border-color);
      border-radius: 0.75rem;
      padding: 0.75rem 1rem;
      background: var(--bs-body-secondary-bg, rgba(var(--bs-secondary-rgb, 108, 117, 125), 0.08));
    }

    .settings-permissions .permission-toolbar .btn {
      border-radius: 999px;
    }

    .settings-permissions .permission-toolbar .btn.active {
      background-color: rgba(var(--bs-primary-rgb), 0.12);
      border-color: rgba(var(--bs-primary-rgb), 0.32);
      color: var(--bs-primary);
    }

    .settings-permissions .permission-toolbar .summary-pill {
      border-radius: 999px;
      background-color: rgba(var(--bs-primary-rgb), 0.08);
      color: var(--bs-primary);
      font-weight: 600;
      padding: 0.35rem 0.85rem;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .settings-permissions .permission-toolbar .result-counter {
      font-size: 0.75rem;
      color: var(--bs-secondary-color, var(--bs-gray-600));
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
    }

    .settings-permissions .permission-card:hover,
    .settings-permissions .permission-card:focus-within {
      border-color: rgba(var(--bs-primary-rgb), 0.55);
      box-shadow: 0 0 0 0.15rem rgba(var(--bs-primary-rgb), 0.15);
    }

    .settings-permissions .permission-card .permission-name {
      font-weight: 600;
      display: block;
      margin-bottom: 0.25rem;
      transition: color 0.2s ease;
    }

    .settings-permissions .permission-card .permission-meta {
      font-size: 0.75rem;
      color: var(--bs-secondary-color, var(--bs-gray-600));
      word-break: break-word;
    }

    .settings-permissions .permission-card .permission-actions {
      margin-top: auto;
      padding-top: 1rem;
      border-top: 1px solid var(--bs-border-color);
    }

    .settings-permissions .search-input-group .form-control {
      min-height: 44px;
    }

    .settings-permissions .search-input-group .input-group-text {
      border-inline-end: 0;
    }

    .settings-permissions .search-input-group .form-control {
      border-inline-start: 0;
    }

    .settings-permissions .search-input-group .btn {
      border-inline-start: 0;
    }

    @media (max-width: 991.98px) {
      .settings-permissions .hero-metric {
        padding: 0.85rem 1rem;
      }
    }

    @media (max-width: 767.98px) {
      .settings-permissions .card .p-4 > .d-flex {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
        text-align: start;
      }

      .settings-permissions .card .p-4 > .d-flex .text-end {
        width: 100%;
        text-align: inherit !important;
      }

      .settings-permissions .card-header .d-flex {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
      }

      .settings-permissions .card-header .text-end,
      .settings-permissions .card-header .badge,
      .settings-permissions .card-header .btn-group {
        width: 100%;
        text-align: inherit !important;
      }

      .settings-permissions .search-input-group {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
      }

      .settings-permissions .permission-toolbar {
        padding: 1rem;
      }

      .settings-permissions .permission-toolbar .d-flex {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
      }

      .settings-permissions .permission-toolbar .btn-group,
      .settings-permissions .permission-toolbar .btn {
        width: 100%;
      }

      .settings-permissions .search-input-group > .input-group-text,
      .settings-permissions .search-input-group > .form-control,
      .settings-permissions .search-input-group > .btn {
        width: 100%;
        border-radius: 0.75rem !important;
      }

      .settings-permissions .search-input-group > .input-group-text {
        justify-content: flex-start;
      }
    }

    @media (max-width: 575.98px) {
      .settings-permissions .permission-card {
        padding: 0.85rem;
      }
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
  <div class="container-xxl py-4 settings-permissions" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    {{-- Breadcrumbs --}}
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">@lang('sidebar.Settings')</a></li>
        <li class="breadcrumb-item active" aria-current="page">@lang('permissions.Manage Permissions')</li>
      </ol>
    </nav>

    {{-- Hero --}}
    <div class="card border-0 shadow-sm overflow-hidden mb-4">
      <div class="p-4 position-relative text-white" style="background: linear-gradient(135deg, var(--bs-primary) 0%, #6f42c1 100%);">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-3">
            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 64px; height: 64px;">
              <i class="bi bi-key fs-3"></i>
            </span>
            <div>
              <p class="text-uppercase fw-semibold small mb-1" style="letter-spacing: 0.08em; color: rgba(255, 255, 255, 0.75);">
                @lang('permissions.Access Overview')
              </p>
              <h1 class="h3 mb-2">@lang('permissions.Manage Permissions')</h1>
              <p class="mb-0" style="color: rgba(255, 255, 255, 0.8);">
                @lang('permissions.Manage Permissions Description', ['guard' => $guardLabel])
              </p>
            </div>
          </div>
          <div class="text-end">
            <span class="badge rounded-pill bg-white text-primary fw-semibold">
              @lang('permissions.Permission Library Title')
            </span>
          </div>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mt-3">
          <div class="col">
            <div class="hero-metric">
              <span class="metric-label">@lang('permissions.Total Permissions')</span>
              <p class="metric-value">{{ number_format($totalPermissions) }}</p>
            </div>
          </div>
          <div class="col">
            <div class="hero-metric">
              <span class="metric-label">@lang('permissions.Total Roles')</span>
              <p class="metric-value">{{ number_format($totalRoles) }}</p>
            </div>
          </div>
          <div class="col">
            <div class="hero-metric">
              <span class="metric-label">@lang('permissions.Assigned Permissions')</span>
              <p class="metric-value">{{ number_format($assignedPermissionCount) }}</p>
            </div>
          </div>
          <div class="col">
            <div class="hero-metric">
              <span class="metric-label">@lang('permissions.Unassigned Permissions')</span>
              <p class="metric-value">{{ number_format($unassignedPermissionCount) }}</p>
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
                <i class="bi bi-sliders2 fs-5"></i>
              </span>
              <div>
                <h5 class="mb-1">@lang('permissions.Permission Creation Title')</h5>
                <p class="text-muted small mb-0">@lang('permissions.Permission Creation Description')</p>
              </div>
            </div>

            <form method="GET" action="{{ route('settings.permissions.index') }}" class="mb-4">
              <label for="guard-selector" class="form-label fw-semibold">@lang('permissions.Guard Filter Label')</label>
              <select id="guard-selector" name="guard" class="form-select" onchange="this.form.submit()">
                <option value="all" @selected($showAllGuards)>@lang('permissions.Guard Filter All Option')</option>
                @foreach($guardOptions as $guardOption)
                  <option value="{{ $guardOption }}" @selected(!$showAllGuards && $selectedGuard === $guardOption)>
                    {{ Str::headline($guardOption) }}
                  </option>
                @endforeach
              </select>
              <div class="form-text">@lang('permissions.Guard Filter Help')</div>
            </form>

            <form method="POST" action="{{ route('settings.permissions.store') }}">
              @csrf
              <input type="hidden" name="redirect_guard" value="{{ $showAllGuards ? 'all' : $selectedGuard }}">
              <div class="mb-3">
                <label for="permission-name" class="form-label fw-semibold">@lang('permissions.Permission Name Label')</label>
                <input
                  type="text"
                  name="name"
                  id="permission-name"
                  class="form-control @error('name') is-invalid @enderror"
                  placeholder="@lang('permissions.Permission Name Placeholder')"
                  value="{{ old('name') }}"
                  autocomplete="off"
                  required
                >
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @else
                  <div class="form-text">@lang('permissions.Permission Name Help')</div>
                @enderror
              </div>

              <div class="mb-3">
                <label for="permission-guard" class="form-label fw-semibold">@lang('permissions.Permission Guard Label')</label>
                <input
                  type="text"
                  name="guard_name"
                  id="permission-guard"
                  class="form-control @error('guard_name') is-invalid @enderror"
                  placeholder="@lang('permissions.Permission Guard Placeholder')"
                  value="{{ old('guard_name', $showAllGuards ? $defaultGuard : $selectedGuard) }}"
                  list="permission-guard-options"
                  autocomplete="off"
                >
                <datalist id="permission-guard-options">
                  @foreach($guardOptions as $guardOption)
                    <option value="{{ $guardOption }}"></option>
                  @endforeach
                </datalist>
                @error('guard_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @else
                  <div class="form-text">@lang('permissions.Permission Guard Help', ['guard' => $defaultGuard])</div>
                @enderror
              </div>

              <div class="d-grid">
                <x-button.save type="submit">
                  @lang('permissions.Create Permission Button')
                </x-button.save>
              </div>
            </form>

            <div class="border rounded-3 p-3 bg-light mt-4">
              <p class="fw-semibold text-muted text-uppercase small mb-2">@lang('permissions.Permission Library Title')</p>
              <ul class="list-unstyled text-muted small mb-0">
                <li>{{ trans_choice('permissions.Permission Groups Count Summary', $groupCount, ['count' => number_format($groupCount)]) }}</li>
                <li>{{ trans_choice('permissions.Guard Count Summary', $guardOptions->count(), ['count' => number_format($guardOptions->count())]) }}</li>
              </ul>
              <p class="text-muted small mt-3 mb-0">@lang('permissions.Permission Creation Help')</p>
            </div>
          </div>
        </div>
      </div>

      {{-- Permission library --}}
      <div class="col-xl-8">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-header bg-white border-0 pb-0">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
              <div>
                <h5 class="mb-1">@lang('permissions.Permission Library Title')</h5>
                <p class="text-muted small mb-0">@lang('permissions.Permission Library Description')</p>
              </div>
              <div class="d-flex flex-wrap align-items-center gap-2">
                <x-button.action type="button" variant="primary" :outline="true" size="sm" data-action="expand-all">
                  <i class="bi bi-arrows-fullscreen me-1"></i>@lang('permissions.Expand All')
                </x-button.action>
                <x-button.action type="button" variant="secondary" :outline="true" size="sm" data-action="collapse-all">
                  <i class="bi bi-arrows-collapse me-1"></i>@lang('permissions.Collapse All')
                </x-button.action>
              </div>
            </div>
          </div>
          <div class="card-body">
            @if($totalPermissions === 0)
              <div class="alert alert-info mb-0">@lang('permissions.No permissions defined.')</div>
            @else
              <div class="row g-3 align-items-end mb-4">
                <div class="col-12 col-lg-7">
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
                <div class="col-12 col-lg-5">
                  <div class="permission-toolbar h-100 d-flex flex-column justify-content-center gap-2">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                      <span class="summary-pill">
                        <i class="bi bi-diagram-3"></i>
                        <span data-permission-library-summary data-template="@lang('permissions.Permission Library Summary Template')"></span>
                      </span>
                      <span class="result-counter" data-permission-result-count data-template="@lang('permissions.Permission Result Count')">
                        <i class="bi bi-list-check"></i>
                        <span></span>
                      </span>
                    </div>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                      <div class="btn-group btn-group-sm" role="group" aria-label="@lang('permissions.Permission Assignment Filter Label')">
                        <button type="button" class="btn btn-outline-primary active" data-permission-filter="all">@lang('permissions.Permission Assignment Filter All')</button>
                        <button type="button" class="btn btn-outline-primary" data-permission-filter="assigned">@lang('permissions.Permission Assignment Filter Assigned')</button>
                        <button type="button" class="btn btn-outline-primary" data-permission-filter="unassigned">@lang('permissions.Permission Assignment Filter Unassigned')</button>
                      </div>
                      <div class="btn-group btn-group-sm" role="group" aria-label="@lang('permissions.Permission Bulk Actions Label')">
                        <button type="button" class="btn btn-outline-secondary" data-permission-bulk="expand">@lang('permissions.Expand All')</button>
                        <button type="button" class="btn btn-outline-secondary" data-permission-bulk="collapse">@lang('permissions.Collapse All')</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="alert alert-light border d-none" id="permissions-empty-results" data-empty-template="@lang('permissions.No permissions match query', ['query' => ':query'])">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-search"></i>
                  <span data-permission-empty-text>@lang('permissions.No permissions match your search.')</span>
                </div>
              </div>

              <div class="accordion" id="permissionLibraryAccordion">
                @foreach($permissionGroups as $index => $group)
                  <div class="accordion-item border-0 mb-3 shadow-sm rounded-3 overflow-hidden">
                    <h2 class="accordion-header" id="permission-library-heading-{{ $index }}">
                      <x-button.action type="button" :unstyled="true" class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#permission-library-body-{{ $index }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="permission-library-body-{{ $index }}">
                        <span class="fw-semibold">{{ $group['label'] }}</span>
                        <span class="badge bg-primary-subtle text-primary ms-2">{{ $group['permissions']->count() }}</span>
                      </x-button.action>
                    </h2>
                    <div id="permission-library-body-{{ $index }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" data-bs-parent="#permissionLibraryAccordion">
                      <div class="accordion-body" data-permission-group-container="{{ $index }}">
                        <div class="row g-3">
                          @foreach($group['permissions'] as $permission)
                            @php
                              $rolesForPermission = $permission->roles->sortBy('name')->values();
                            @endphp
                            <div class="col-12 col-md-6" data-permission-card data-permission="{{ Str::lower($permission->name) }}" data-permission-label="{{ Str::lower($permissionLabels[$permission->name] ?? $permission->name) }}" data-permission-roles="{{ Str::lower($rolesForPermission->pluck('name')->implode(' ')) }}" data-permission-guard="{{ Str::lower($permission->guard_name ?? '') }}" data-permission-assigned="{{ $rolesForPermission->isNotEmpty() ? '1' : '0' }}">
                              <div class="permission-card">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                  <div>
                                    <span class="permission-name">{{ $permissionLabels[$permission->name] ?? $permission->name }}</span>
                                    <span class="permission-meta">{{ $permission->name }}</span>
                                  </div>
                                  <span class="badge bg-primary-subtle text-primary">@lang('permissions.Permission Guard Display', ['guard' => $permission->guard_name])</span>
                                </div>

                                <div class="mt-3">
                                  <div class="text-muted text-uppercase small fw-semibold mb-2">@lang('permissions.Assigned Roles Label')</div>
                                  @if($rolesForPermission->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-1">
                                      @foreach($rolesForPermission as $role)
                                        <span class="badge bg-secondary-subtle text-secondary border">{{ $roleLabels[$role->name] ?? $role->name }}</span>
                                      @endforeach
                                    </div>
                                  @else
                                    <p class="text-muted small mb-0">@lang('permissions.No Roles Assigned')</p>
                                  @endif
                                </div>

                                <div class="permission-actions mt-3">
                                  <form method="POST" action="{{ route('settings.permissions.destroy', $permission) }}" onsubmit="return confirm('@lang('permissions.Permission Delete Confirmation')');">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="redirect_guard" value="{{ $showAllGuards ? 'all' : $selectedGuard }}">
                                    <x-button.delete type="submit" size="sm">
                                      @lang('permissions.Delete Permission')
                                    </x-button.delete>
                                  </form>
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
      const root = document.querySelector('.settings-permissions');
      if (!root) {
        return;
      }

      const permissionSearch = root.querySelector('#permission-search');
      const permissionClear = root.querySelector('#permission-search-clear');
      const permissionCards = root.querySelectorAll('[data-permission-card]');
      const groupContainers = root.querySelectorAll('[data-permission-group-container]');
      const resultCounter = root.querySelector('[data-permission-result-count] span');
      const summaryBadge = root.querySelector('[data-permission-library-summary]');
      const filterButtons = root.querySelectorAll('[data-permission-filter]');
      const toolbarBulkButtons = root.querySelectorAll('[data-permission-bulk]');
      const emptyResults = root.querySelector('#permissions-empty-results');
      const emptyText = emptyResults ? emptyResults.querySelector('[data-permission-empty-text]') : null;
      const emptyTemplate = emptyResults ? emptyResults.getAttribute('data-empty-template') : null;
      let currentFilter = 'all';

      if (emptyResults && emptyText) {
        emptyResults.setAttribute('data-default-text', emptyText.textContent);
      }

      const updateSummary = () => {
        if (!summaryBadge) {
          return;
        }

        const template = summaryBadge.getAttribute('data-template') || '';
        const assignedCount = root.querySelectorAll('[data-permission-card][data-permission-assigned="1"]').length;
        const total = permissionCards.length;
        summaryBadge.textContent = template
          .replace(':assigned', new Intl.NumberFormat().format(assignedCount))
          .replace(':total', new Intl.NumberFormat().format(total));
      };

      const updatePermissionVisibility = () => {
        if (!permissionCards.length) {
          if (emptyResults) {
            emptyResults.classList.add('d-none');
          }
          if (resultCounter) {
            resultCounter.textContent = '';
          }
          return;
        }

        const query = permissionSearch ? permissionSearch.value.trim().toLowerCase() : '';
        let visibleCount = 0;

        permissionCards.forEach(card => {
          const datasetValue = [
            card.dataset.permission,
            card.dataset.permissionLabel,
            card.dataset.permissionRoles,
            card.dataset.permissionGuard,
          ].filter(Boolean).join(' ').toLowerCase();
          const assigned = card.getAttribute('data-permission-assigned') === '1';
          const matchesFilter = currentFilter === 'all'
            || (currentFilter === 'assigned' && assigned)
            || (currentFilter === 'unassigned' && !assigned);
          const matchesQuery = query === '' || datasetValue.includes(query);
          const matches = matchesFilter && matchesQuery;
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

        if (resultCounter) {
          const template = resultCounter.parentElement?.getAttribute('data-template') || '';
          resultCounter.textContent = template
            .replace(':count', new Intl.NumberFormat().format(visibleCount))
            .replace(':total', new Intl.NumberFormat().format(permissionCards.length));
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

      const accordion = root.querySelector('#permissionLibraryAccordion');
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

      filterButtons.forEach(button => {
        button.addEventListener('click', () => {
          currentFilter = button.getAttribute('data-permission-filter') || 'all';
          filterButtons.forEach(btn => btn.classList.toggle('active', btn === button));
          updatePermissionVisibility();
        });
      });

      toolbarBulkButtons.forEach(button => {
        button.addEventListener('click', () => {
          const action = button.getAttribute('data-permission-bulk');
          if (action === 'expand') {
            setAccordionState(true);
          }
          if (action === 'collapse') {
            setAccordionState(false);
          }
        });
      });

      updateSummary();
      updatePermissionVisibility();
    });
  </script>
@endpush
