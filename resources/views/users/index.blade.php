@extends('layouts.master')

@section('title', __('users.Users'))

@section('content')
<div class="container-xxl py-4" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="d-flex align-items-center mb-3">
    <h1 class="h4 mb-0">@lang('users.Users')</h1>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>@lang('users.Name')</th>
            <th>@lang('users.Email')</th>
            <th>@lang('users.Roles')</th>
            <th class="text-end">@lang('users.Actions')</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($users as $u)
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
          @empty
            <tr>
              <td colspan="5" class="text-center text-muted">@lang('users.No users found.')</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-body">
      {{ $users->links() }}
    </div>
  </div>
</div>
@endsection
