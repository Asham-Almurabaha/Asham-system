@extends('layouts.master')
@section('title', __('sidebar.General Settings'))

@section('content')
<div class="container py-3">

  {{-- Breadcrumbs --}}
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
      <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">@lang('sidebar.General Settings')</a></li>
      <li class="breadcrumb-item active" aria-current="page">@lang('sidebar.View')</li>
    </ol>
  </nav>

  {{-- Header / Toolbar --}}
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-3">
      @if(!empty($setting->logo_url))
        <img src="{{ $setting->logo_url }}" alt="@lang('setting.Logo')" class="rounded border bg-white p-1" style="height:48px">
      @else
        <div class="rounded border bg-light d-flex align-items-center justify-content-center" style="height:48px;width:48px;">
          <i class="bi bi-image text-muted fs-5"></i>
        </div>
      @endif
      <div>
        <h4 class="mb-0">{{ app()->getLocale()==='ar' ? ($setting->name_ar ?? $setting->name) : ($setting->name ?? $setting->name_ar) }}</h4>
        <small class="text-muted">@lang('sidebar.Details of the general system setting')</small>
      </div>
    </div>

    <div class="d-flex gap-2">
      <a href="{{ route('settings.edit', $setting) }}" class="btn btn-primary">
        <i class="bi bi-pencil-square me-1"></i>@lang('sidebar.Edit')
      </a>
      <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-right-circle me-1"></i>@lang('sidebar.Back')
      </a>
    </div>
  </div>

  <div class="row g-3">
    {{-- Details --}}
    <div class="col-lg-7">
      <div class="card shadow-sm h-100">
        <div class="card-body mt-2">
          <h6 class="mb-3">@lang('sidebar.Basic Data')</h6>
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

          {{-- Danger Zone --}}
          <div class="mt-4">
            <h6 class="text-danger mb-2"><i class="bi bi-exclamation-triangle me-1"></i>@lang('sidebar.Danger Zone')</h6>
            <div class="border border-danger-subtle rounded p-3">
              <p class="mb-3 text-danger small">@lang('sidebar.The record and associated images will be permanently deleted.')</p>
              <form action="{{ route('settings.destroy', $setting) }}" method="POST"
                    onsubmit="return confirm('@lang('sidebar.Are you sure you want to delete this setting? The operation cannot be undone.')')">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger">
                  <i class="bi bi-trash me-1"></i>@lang('sidebar.Delete Setting')
                </button>
              </form>
            </div>
          </div>

        </div>
      </div>
    </div>

    {{-- Media Preview --}}
    <div class="col-lg-5">
      <div class="card shadow-sm h-100">
        <div class="card-body mt-2">
          <h6 class="mb-3">@lang('sidebar.Previews')</h6>

          {{-- Logo Preview --}}
          <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted">@lang('sidebar.Logo')</span>
              @if(!empty($setting->logo_url))
                <a href="{{ $setting->logo_url }}" target="_blank" class="small">@lang('sidebar.Open Original')</a>
              @endif
            </div>
            @if(!empty($setting->logo_url))
              <div class="border rounded p-3 bg-light d-flex align-items-center" style="min-height:90px;">
                <img src="{{ $setting->logo_url }}" alt="Logo" class="img-fluid" style="max-height:64px">
              </div>
            @else
              <div class="border rounded p-3 bg-light d-flex align-items-center justify-content-center" style="min-height:90px;">
                <span class="text-muted fst-italic">@lang('sidebar.No logo uploaded')</span>
              </div>
            @endif
          </div>

          {{-- Favicon Preview --}}
          <div>
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted">@lang('setting.Icon')</span>
              @if(!empty($setting->favicon_url))
                <a href="{{ $setting->favicon_url }}" target="_blank" class="small">@lang('sidebar.Open Original')</a>
              @endif
            </div>
            @if(!empty($setting->favicon_url))
              <div class="d-flex align-items-center gap-3">
                <div class="border rounded bg-light d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                  <img src="{{ $setting->favicon_url }}" alt="@lang('setting.Icon')" class="img-fluid p-1" style="max-height:32px">
                </div>
                <div class="text-muted small">@lang('sidebar.Default preview for icon size')</div>
              </div>
            @else
              <div class="border rounded p-3 bg-light d-flex align-items-center justify-content-center" style="min-height:64px;">
                <span class="text-muted fst-italic">@lang('sidebar.No icon uploaded')</span>
              </div>
            @endif
          </div>

          {{-- Branding Inline Preview --}}
          <hr class="my-4">
          <div>
            <div class="text-muted small mb-2">@lang('sidebar.Inline Preview')</div>
            <div class="border rounded p-3 d-flex align-items-center gap-3">
              @if(!empty($setting->logo_url))
                <img src="{{ $setting->logo_url }}" alt="@lang('setting.Logo')" style="height:32px" class="rounded border bg-white p-1">
              @else
                <div class="rounded border bg-light d-flex align-items-center justify-content-center" style="height:32px;width:32px;">
                  <i class="bi bi-image text-muted"></i>
                </div>
              @endif
              <div class="fw-semibold">
                {{ app()->getLocale()==='ar' ? ($setting->name_ar ?? $setting->name) : ($setting->name ?? $setting->name_ar) }}
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div>
</div>
@endsection
