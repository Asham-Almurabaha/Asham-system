@extends('layouts.master')
@section('title', __('sidebar.Settings'))

@section('content')
<div class="container py-3">

  {{-- Breadcrumbs --}}
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
      <li class="breadcrumb-item active" aria-current="page">@lang('sidebar.General Settings')</li>
    </ol>
  </nav>

  @php
    // تجهيز روابط الصور (تعمل سواء عندك accessors أو لأ)
    $logoUrl    = $setting?->logo_url    ?? ($setting?->logo    ? asset('storage/'.$setting->logo)    : null);
    $faviconUrl = $setting?->favicon_url ?? ($setting?->favicon ? asset('storage/'.$setting->favicon) : null);
  @endphp

  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-0">@lang('sidebar.General Settings')</h4>
      <small class="text-muted">@lang('sidebar.Control the name, owner name, logo, and site icon')</small>
    </div>

    <div class="d-flex gap-2">
      @if($setting)
        <a href="{{ route('settings.show', $setting) }}" class="btn btn-outline-secondary">
          <i class="bi bi-eye me-1"></i>@lang('sidebar.View')
        </a>
        <a href="{{ route('settings.edit', $setting) }}" class="btn btn-primary">
          <i class="bi bi-pencil-square me-1"></i>@lang('sidebar.Edit')
        </a>
        <form action="{{ route('settings.destroy', $setting) }}" method="POST"
              onsubmit="return confirm('@lang('sidebar.Delete setting and images. Are you sure?')')">
          @csrf @method('DELETE')
          <button class="btn btn-danger">
            <i class="bi bi-trash me-1"></i>@lang('sidebar.Delete')
          </button>
        </form>
      @else
        <a href="{{ route('settings.create') }}" class="btn btn-success">
          <i class="bi bi-plus-circle me-1"></i>@lang('sidebar.Create Setting')
        </a>
      @endif
    </div>
  </div>

  @if(!$setting)
    <div class="card shadow-sm mt">
      <div class="card-body text-center py-5">
        <div class="mb-3"><i class="bi bi-gear-wide-connected fs-1"></i></div>
        <h5 class="mb-2">@lang('sidebar.No saved setting yet')</h5>
        <p class="text-muted mb-4">@lang('sidebar.Create a setting to show the name, owner name, logo, and site icon across the system.')</p>
        <a href="{{ route('settings.create') }}" class="btn btn-success">@lang('sidebar.Create Now')</a>
      </div>
    </div>
  @else
    {{-- Summary + Media --}}
    <div class="row g-3">
      <div class="col-lg-8">
        <div class="card shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3 mt-2">
              <h5 class="mb-0 mt">@lang('sidebar.Basic Data')</h5>
              <span class="badge bg-success-subtle text-success border">@lang('sidebar.Active')</span>
            </div>

            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <tbody>
                  <tr>
                    <th style="width:220px">@lang('sidebar.Owner Name')</th>
                    <td class="fw-medium">{{ $setting->owner_name }}</td>
                  </tr>
                  <tr>
                    <th>@lang('sidebar.Name (EN)')</th>
                    <td class="fw-medium">{{ $setting->name }}</td>
                  </tr>
                  <tr>
                    <th>@lang('sidebar.Name (AR)')</th>
                    <td class="fw-medium">{{ $setting->name_ar }}</td>
                  </tr>
                  <tr>
                    <th>@lang('sidebar.Created At')</th>
                    <td>
                      {{ $setting->created_at?->format('Y-m-d H:i') }}
                      <span class="text-muted">— {{ $setting->created_at?->diffForHumans() }}</span>
                    </td>
                  </tr>
                  <tr>
                    <th>@lang('sidebar.Last Updated')</th>
                    <td>
                      {{ $setting->updated_at?->format('Y-m-d H:i') }}
                      <span class="text-muted">— {{ $setting->updated_at?->diffForHumans() }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card shadow-sm h-100">
          <div class="card-body mt-2">
            <h6 class="mb-3">@lang('sidebar.Media')</h6>

            <div class="d-flex gap-4 flex-wrap">
              {{-- Logo --}}
              <div class="text-center">
                <div class="text-muted small mb-2">@lang('sidebar.Logo')</div>
                @if(!empty($logoUrl))
                  <div class="ratio ratio-1x1 border rounded bg-light d-flex align-items-center justify-content-center" style="width:96px;">
                    <img src="{{ $logoUrl }}" alt="@lang('setting.Logo')" class="img-fluid p-1">
                  </div>
                @else
                  <div class="text-muted fst-italic">@lang('sidebar.Not uploaded')</div>
                @endif
              </div>

              {{-- Favicon --}}
              <div class="text-center">
                <div class="text-muted small mb-2">@lang('setting.Icon')</div>
                @if(!empty($faviconUrl))
                  <div class="ratio ratio-1x1 border rounded bg-light d-flex align-items-center justify-content-center" style="width:64px;">
                    <img src="{{ $faviconUrl }}" alt="@lang('setting.Icon')" class="img-fluid p-1">
                  </div>
                @else
                  <div class="text-muted fst-italic">@lang('sidebar.Not uploaded')</div>
                @endif
              </div>
            </div>

            <div class="mt-3 d-flex gap-2">
              <a href="{{ route('settings.show', $setting) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-eye me-1"></i>@lang('sidebar.View Details')
              </a>
              <a href="{{ route('settings.edit', $setting) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil me-1"></i>@lang('sidebar.Edit')
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Branding Preview --}}
    <div class="card shadow-sm mt-3">
      <div class="card-body mt-2">
        <h6 class="mb-3">@lang('sidebar.Brand Preview')</h6>
        <div class="border rounded p-3 d-flex align-items-center gap-3">
          @if(!empty($logoUrl))
            <img src="{{ $logoUrl }}" alt="@lang('setting.Logo')" class="logo-img-40" class="rounded border bg-white p-1">
          @else
            <div class="rounded border bg-light d-flex align-items-center justify-content-center" style="height:40px;width:40px;">
              <i class="bi bi-image text-muted"></i>
            </div>
          @endif
          <div class="fs-5 fw-semibold">
            {{ app()->getLocale()==='ar'
                ? ($setting->name_ar ?? $setting->name)
                : ($setting->name ?? $setting->name_ar) }}
          </div>
        </div>
      </div>
    </div>
  @endif
</div>
@endsection

