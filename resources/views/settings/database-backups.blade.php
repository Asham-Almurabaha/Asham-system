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
      <div class="card border-0 shadow-sm bg-primary bg-gradient text-white mb-3 overflow-hidden">
        <div class="card-body p-4">
          <div class="row align-items-center g-3">
            <div class="col-md">
              <h4 class="mb-2 text-white">@lang('setting.Database Backup Heading')</h4>
              <p class="mb-0 text-white-50">@lang('setting.Database Backup Description')</p>
            </div>
            <div class="col-auto">
              <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-white bg-opacity-25 p-3">
                <i class="bi bi-hdd-stack fs-1"></i>
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-7">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 d-flex flex-column gap-4">
              <div>
                <h5 class="mb-2">@lang('setting.Database Backup Tools')</h5>
                <p class="text-muted mb-0">@lang('setting.Database Backup Tool Description')</p>
              </div>

              <div class="d-flex flex-column flex-lg-row gap-3 align-items-start align-items-lg-center">
                @can('settings.database.export')
                  <form method="POST" action="{{ route('settings.database.export') }}" class="d-flex gap-2 align-items-center flex-wrap">
                    @csrf
                    <x-button.action type="submit" variant="secondary" class="shadow-sm">
                      <i class="bi bi-download me-1"></i>@lang('setting.Download Database Backup')
                    </x-button.action>
                  </form>
                @else
                  <div class="alert alert-warning mb-0" role="alert">
                    @lang('setting.Database Backup Permission Warning')
                  </div>
                @endcan
              </div>

              <div class="alert alert-info d-flex gap-3 align-items-start mb-0" role="alert">
                <span class="text-primary-emphasis pt-1"><i class="bi bi-shield-lock"></i></span>
                <div class="small mb-0 text-muted">
                  @lang('setting.Database Backup Download Hint')
                </div>
              </div>

              <div>
                <div class="fw-semibold text-muted text-uppercase small mb-2">@lang('setting.Database Backup Next Steps Title')</div>
                @php($nextSteps = (array) __('setting.Database Backup Next Steps Items'))
                <ul class="list-unstyled mb-0 d-grid gap-2">
                  @foreach ($nextSteps as $item)
                    <li class="d-flex gap-2 align-items-start">
                      <span class="text-primary pt-1"><i class="bi bi-check-circle-fill"></i></span>
                      <span class="text-muted">{{ $item }}</span>
                    </li>
                  @endforeach
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 d-flex flex-column gap-3">
              <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-primary fs-3" style="width: 3.25rem; height: 3.25rem;">
                <i class="bi bi-cloud-arrow-down"></i>
              </div>
              <div>
                <h5 class="mb-2">@lang('setting.Database Backup Empty Title')</h5>
                <p class="text-muted mb-0">@lang('setting.Database Backup Empty Description')</p>
              </div>
              <div class="bg-light rounded-3 p-3 mt-auto">
                <div class="small text-muted mb-2">@lang('setting.Database Backup Restore Link Label')</div>
                <x-button.action href="{{ route('settings.database.restore') }}" variant="link" class="px-0">
                  <i class="bi bi-arrow-counterclockwise me-1"></i>@lang('setting.Go To Database Restore')
                </x-button.action>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
