@extends('layouts.master')
@section('title', __('setting.Database Backup Title'))

@section('content')
<div class="container py-3">
  {{-- Breadcrumbs --}}
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
      <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">@lang('sidebar.Settings')</a></li>
      <li class="breadcrumb-item active" aria-current="page">@lang('setting.Database Backup Title')</li>
    </ol>
  </nav>

  <div class="row g-3">
    <div class="col-12 col-xxl-10 mx-auto">
      <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-3">
        <div>
          <h4 class="mb-1">@lang('setting.Database Backup Heading')</h4>
          <p class="text-muted mb-0">@lang('setting.Database Backup Description')</p>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex flex-column flex-lg-row gap-3 align-items-start align-items-lg-center">
            @can('settings.database.export')
              <form method="POST" action="{{ route('settings.database.export') }}" class="d-flex gap-2 align-items-center flex-wrap">
                @csrf
                <x-button.action type="submit" variant="secondary">
                  <i class="bi bi-download me-1"></i>@lang('setting.Download Database Backup')
                </x-button.action>
              </form>
            @endcan

            @can('settings.database.import')
              <form method="POST" action="{{ route('settings.database.import') }}" enctype="multipart/form-data" class="d-flex gap-2 align-items-center flex-wrap">
                @csrf
                <div>
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
                <x-button.action type="submit" variant="primary">
                  <i class="bi bi-upload me-1"></i>@lang('setting.Import Database Backup')
                </x-button.action>
              </form>
            @endcan
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
