@extends('layouts.master')

@section('title', __('sidebar.Assign Roles to Users'))

@section('content')
<div class="container py-3" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
      <li class="breadcrumb-item">@lang('sidebar.Settings')</li>
      <li class="breadcrumb-item active" aria-current="page">@lang('sidebar.Assign Roles to Users')</li>
    </ol>
  </nav>

  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body p-2">
      <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div>
          <h4 class="mb-1">@lang('sidebar.Assign Roles to Users')</h4>
          <p class="mb-0 text-muted">@lang('users.Assign Roles Description')</p>
        </div>
        <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2">
          <i class="bi bi-shield-lock me-1"></i>
          @lang('users.Users Overview')
        </span>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body p-2">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
              <i class="bi bi-people-fill"></i>
            </div>
            <div>
              <div class="text-muted text-uppercase small fw-semibold">@lang('users.Total Users')</div>
              <div class="fs-4 fw-bold mb-0">{{ $totalUsers }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body p-2">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
              <i class="bi bi-check2-circle"></i>
            </div>
            <div>
              <div class="text-muted text-uppercase small fw-semibold">@lang('users.With Roles')</div>
              <div class="fs-4 fw-bold mb-0">{{ $usersWithRoles }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body p-2">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
              <i class="bi bi-exclamation-circle"></i>
            </div>
            <div>
              <div class="text-muted text-uppercase small fw-semibold">@lang('users.Without Roles')</div>
              <div class="fs-4 fw-bold mb-0">{{ $usersWithoutRoles }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    @if ($users->count())
      <div class="card-header bg-white border-0 pb-0">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-people text-primary"></i>
          <h5 class="mb-0">@lang('users.Users')</h5>
        </div>
      </div>
      <div class="card-body p-2">
        <x-table head-class="table-light">
          <x-slot name="head">
            <tr>
              <th style="width:70px">#</th>
              <th>@lang('users.Name')</th>
              <th>@lang('users.Email')</th>
              <th>@lang('users.Roles')</th>
              <th class="text-end">@lang('users.Actions')</th>
            </tr>
          </x-slot>

          @foreach ($users as $u)
            <tr>
              <td>{{ $u->id }}</td>
              <td>{{ $u->name }}</td>
              <td>{{ $u->email }}</td>
              <td>
                @forelse ($u->roles as $r)
                  <span class="badge text-bg-secondary me-1">{{ $r->name }}</span>
                @empty
                  <span class="text-muted">—</span>
                @endforelse
              </td>
              <td class="text-end">
                <a href="{{ route('users.roles.edit', $u) }}" class="btn btn-sm btn-outline-primary">
                  @lang('users.Manage Roles')
                </a>
              </td>
            </tr>
          @endforeach
        </x-table>
      </div>
      @if ($users->hasPages())
        <div class="card-footer bg-white border-0">
          {{ $users->links() }}
        </div>
      @endif
    @else
      <div class="card-body text-center py-5">
        <div class="mb-3">
          <span class="display-5 text-muted"><i class="bi bi-person-slash"></i></span>
        </div>
        <h5 class="mb-2">@lang('users.Empty State Title')</h5>
        <p class="text-muted mb-0">@lang('users.Empty State Description')</p>
      </div>
    @endif
  </div>
</div>
@endsection
