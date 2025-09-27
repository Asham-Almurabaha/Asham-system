@extends('layouts.master')
@section('title', __('setting.Database Restore Title'))

@section('content')
<div class="container py-3">
  {{-- Breadcrumbs --}}
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
      <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">@lang('sidebar.Settings')</a></li>
      <li class="breadcrumb-item"><a href="{{ route('settings.database.index') }}">@lang('setting.Database Backup Title')</a></li>
      <li class="breadcrumb-item active" aria-current="page">@lang('setting.Database Restore Title')</li>
    </ol>
  </nav>

  <div class="row g-3">
    <div class="col-12 col-xxl-10 mx-auto">
      <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-3">
        <div>
          <h4 class="mb-1">@lang('setting.Database Restore Heading')</h4>
          <p class="text-muted mb-0">@lang('setting.Database Restore Description')</p>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-body">
          @can('settings.database.import')
            <form method="POST" action="{{ route('settings.database.import') }}" enctype="multipart/form-data" class="d-flex flex-column flex-lg-row gap-3 align-items-start">
              @csrf
              <div class="w-100" style="max-width: 420px;">
                <label for="backup_file" class="form-label mb-1 small text-muted">@lang('setting.Select Backup File')</label>
                <input type="file" name="backup_file" id="backup_file" class="form-control" required accept=".zip,.sql">
                @php
                  $maxKilobytes = (int) config('backup.import.max_upload_kilobytes', 0);
                  $maxMegabytes = $maxKilobytes > 0 ? (int) ceil($maxKilobytes / 1024) : null;
                @endphp
                @if ($maxMegabytes)
                  <div class="form-text small text-muted">
                    @lang('setting.Database Import Size Help', ['size' => number_format($maxMegabytes)])
                  </div>
                @endif
                @error('backup_file')
                  <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
              </div>

              <div class="d-flex gap-2">
                <x-button.action type="submit" variant="primary">
                  <i class="bi bi-upload me-1"></i>@lang('setting.Import Database Backup')
                </x-button.action>
                <x-button.action href="{{ route('settings.database.index') }}" variant="secondary" :outline="true">
                  <i class="bi bi-arrow-left me-1"></i>@lang('setting.Back To Database Backup')
                </x-button.action>
              </div>
            </form>
          @else
            <div class="alert alert-warning mb-0" role="alert">
              @lang('setting.Database Restore Permission Warning')
            </div>
          @endcan
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
