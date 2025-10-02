@extends('layouts.master')

@php
  $locale = app()->getLocale();
  $localeRoot = strtolower(strtok($locale, '_'));
  $rtlLocales = ['ar', 'he'];
  $isRtl = in_array($localeRoot, $rtlLocales, true);
  $accordionId = 'sidebar-permission-groups';
@endphp

@section('title', __('sidebar-permissions.title'))

@push('styles')
  <style>
    .sidebar-permissions .summary-card {
      background: linear-gradient(135deg, var(--bs-primary) 0%, #6610f2 100%);
      color: #fff;
      border-radius: 1rem;
      padding: 1.5rem;
      position: relative;
      overflow: hidden;
    }

    .sidebar-permissions .summary-card::after {
      content: '';
      position: absolute;
      inset-inline-end: -40px;
      inset-block-start: -40px;
      width: 160px;
      height: 160px;
      background: rgba(255, 255, 255, 0.08);
      border-radius: 50%;
      pointer-events: none;
    }

    .sidebar-permissions .metric-value {
      font-size: 2rem;
      font-weight: 700;
    }

    .sidebar-permissions .metric-label {
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-size: 0.75rem;
      opacity: 0.85;
    }

    .sidebar-permissions .group-card {
      border: 1px solid var(--bs-border-color);
      border-radius: 1rem;
      overflow: hidden;
      background-color: var(--bs-body-bg);
    }

    .sidebar-permissions .group-header {
      background: var(--bs-body-secondary-bg, rgba(var(--bs-secondary-rgb, 108, 117, 125), 0.08));
    }

    .sidebar-permissions .permission-row + .permission-row {
      border-top: 1px solid var(--bs-border-color);
    }

    .sidebar-permissions .permission-meta {
      font-size: 0.8rem;
      color: var(--bs-secondary-color, var(--bs-gray-600));
    }
  </style>
@endpush

@section('content')
  <div class="container-xxl py-4 sidebar-permissions" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    {{-- Breadcrumbs --}}
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">@lang('sidebar.Settings')</a></li>
        <li class="breadcrumb-item active" aria-current="page">@lang('sidebar-permissions.title')</li>
      </ol>
    </nav>

    <div class="row g-4">
      <div class="col-xl-4">
        <div class="summary-card h-100">
          <div class="d-flex align-items-start justify-content-between gap-3">
            <div>
              <p class="metric-label mb-1">@lang('sidebar-permissions.overview_label')</p>
              <h1 class="h4 mb-2">@lang('sidebar-permissions.overview_title')</h1>
              <p class="mb-3" style="opacity: 0.85;">@lang('sidebar-permissions.overview_description')</p>
            </div>
            <span class="d-inline-flex align-items-center justify-content-center bg-white bg-opacity-25 rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-layout-sidebar-inset fs-4"></i>
            </span>
          </div>

          <div class="mt-3">
            <div class="mb-3">
              <span class="metric-label d-block">@lang('sidebar-permissions.total_links')</span>
              <span class="metric-value">{{ number_format($totalSidebarLinks) }}</span>
            </div>
            <div class="mb-3">
              <span class="metric-label d-block">@lang('sidebar-permissions.total_roles')</span>
              <span class="metric-value">{{ number_format($roles->count()) }}</span>
            </div>
            <div>
              <span class="metric-label d-block">@lang('sidebar-permissions.total_assignments')</span>
              <span class="metric-value">{{ number_format($assignedCount) }}</span>
            </div>
          </div>

          @if (!empty($missingPermissions))
            <div class="alert alert-warning mt-4 mb-0" role="alert">
              <div class="fw-semibold mb-1">@lang('sidebar-permissions.missing_permissions_title')</div>
              <p class="mb-1">@lang('sidebar-permissions.missing_permissions_description')</p>
              <ul class="mb-0 small">
                @foreach($missingPermissions as $missing)
                  <li><code>{{ $missing }}</code></li>
                @endforeach
              </ul>
            </div>
          @endif
        </div>
      </div>

      <div class="col-xl-8">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
              <div>
                <h2 class="h4 mb-1">@lang('sidebar-permissions.assignment_title')</h2>
                <p class="text-muted mb-0">@lang('sidebar-permissions.assignment_description')</p>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary fw-semibold">
                  @lang('sidebar-permissions.guard_label', ['guard' => \Illuminate\Support\Str::headline($guard)])
                </span>
              </div>
            </div>

            @if (session('success'))
              <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
              <div class="alert alert-danger">@lang('sidebar-permissions.validation_error')</div>
            @endif

            @if ($roles->isEmpty())
              <div class="alert alert-info mb-0">@lang('sidebar-permissions.no_roles')</div>
            @else
              <form method="POST" action="{{ route('settings.sidebar-permissions.update') }}" class="needs-validation">
                @csrf

                <div class="accordion" id="{{ $accordionId }}">
                  @foreach($groups as $index => $group)
                    @php
                      $groupId = $accordionId.'-'.($group['key'] ?? $index);
                      $collapseId = $groupId.'-collapse';
                    @endphp
                    <div class="accordion-item group-card mb-3">
                      <h2 class="accordion-header group-header" id="{{ $groupId }}">
                        <button class="accordion-button {{ $index !== 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
                          {{ __($group['label']) }}
                        </button>
                      </h2>
                      <div id="{{ $collapseId }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" aria-labelledby="{{ $groupId }}" data-bs-parent="#{{ $accordionId }}">
                        <div class="accordion-body p-0">
                          @foreach($group['items'] as $item)
                            <div class="permission-row p-3">
                              <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-start justify-content-between">
                                <div class="flex-grow-1">
                                  <div class="d-flex align-items-center gap-2">
                                    <h3 class="h6 mb-0">{{ __($item['label']) }}</h3>
                                    @if ($item['missing'])
                                      <span class="badge bg-warning text-dark">@lang('sidebar-permissions.permission_missing_badge')</span>
                                    @endif
                                  </div>
                                  @if (!empty($item['permission']))
                                    <div class="permission-meta mt-2">
                                      <span class="d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-shield-check"></i>
                                        <code>{{ $item['permission'] }}</code>
                                      </span>
                                    </div>
                                  @endif
                                  @if (!empty($item['additional_permissions']))
                                    <div class="permission-meta mt-2">
                                      <span>@lang('sidebar-permissions.additional_permissions')</span>
                                      <div class="mt-1">
                                        @foreach($item['additional_permissions'] as $extra)
                                          <code class="me-2">{{ $extra }}</code>
                                        @endforeach
                                      </div>
                                    </div>
                                  @endif
                                </div>
                                <div class="flex-grow-1">
                                  <div class="d-flex flex-wrap gap-2">
                                    @foreach($roles as $role)
                                      <div class="form-check form-switch">
                                        <input
                                          class="form-check-input"
                                          type="checkbox"
                                          value="{{ $role->name }}"
                                          id="permission-{{ \Illuminate\Support\Str::slug($item['permission'] ?? $item['key']) }}-role-{{ \Illuminate\Support\Str::slug($role->name) }}"
                                          name="permissions[{{ $item['permission'] }}][]"
                                          @checked(in_array($role->name, $item['assigned_roles'], true))
                                          @disabled($item['permission'] === null || $item['missing'])
                                        >
                                        <label class="form-check-label small" for="permission-{{ \Illuminate\Support\Str::slug($item['permission'] ?? $item['key']) }}-role-{{ \Illuminate\Support\Str::slug($role->name) }}">
                                          {{ $roleLabels[$role->name] ?? $role->name }}
                                        </label>
                                      </div>
                                    @endforeach
                                  </div>
                                </div>
                              </div>
                            </div>
                          @endforeach
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>

                <div class="d-flex justify-content-end mt-4">
                  <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>@lang('sidebar-permissions.save_changes')
                  </button>
                </div>
              </form>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
