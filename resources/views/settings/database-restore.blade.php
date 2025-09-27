@extends('layouts.master')
@section('title', __('setting.Database Restore Title'))

@section('content')
  @php
    $maxKilobytes = (int) config('backup.import.max_upload_kilobytes', 0);
    $maxMegabytes = $maxKilobytes > 0 ? (int) ceil($maxKilobytes / 1024) : null;
    $maxBytes = $maxKilobytes > 0 ? $maxKilobytes * 1024 : 0;

    $dropzoneHelp = $maxMegabytes
        ? __('setting.Database Restore Dropzone Help Text With Size', ['size' => number_format($maxMegabytes)])
        : __('setting.Database Restore Dropzone Help Text');

    $tooLargeMessage = $maxMegabytes
        ? __('setting.Database Restore Too Large', ['size' => number_format($maxMegabytes)])
        : __('setting.Database Restore Too Large Unlimited');

    $checklist = trans('setting.Database Restore Checklist Items');
    if (! is_array($checklist)) {
        $checklist = [];
    }

    $reminders = trans('setting.Database Restore Reminder Items');
    if (! is_array($reminders)) {
        $reminders = [];
    }
  @endphp

  <div class="container-xxl py-4">
    {{-- Breadcrumbs --}}
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">@lang('sidebar.Settings')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('settings.database.index') }}">@lang('setting.Database Backup Title')</a></li>
        <li class="breadcrumb-item active" aria-current="page">@lang('setting.Database Restore Title')</li>
      </ol>
    </nav>

    <div class="rounded-3 p-4 mb-4 position-relative overflow-hidden bg-light border">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-4 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:54px;height:54px;">
            <i class="bi bi-arrow-counterclockwise fs-3"></i>
          </div>
          <div>
            <h1 class="h4 mb-1">@lang('setting.Database Restore Heading')</h1>
            <p class="text-muted mb-0">@lang('setting.Database Restore Description')</p>
          </div>
        </div>
        <div class="ms-lg-auto d-flex flex-wrap gap-2">
          <x-button.action href="{{ route('settings.database.index') }}" variant="secondary" :outline="true">
            <i class="bi bi-archive me-1"></i>@lang('setting.Back To Database Backup')
          </x-button.action>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-12 col-xxl-9">
        @can('settings.database.import')
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
              <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-4">
                <div class="d-flex align-items-center gap-3">
                  <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                    <i class="bi bi-upload fs-5"></i>
                  </div>
                  <div>
                    <h2 class="h5 mb-1">@lang('setting.Database Restore Upload Title')</h2>
                    <p class="text-muted mb-0">@lang('setting.Database Restore Upload Description')</p>
                  </div>
                </div>
              </div>

              <x-import.form
                  :action="route('settings.database.import')"
                  :drag-text="__('setting.Database Restore Dropzone Drag Text')"
                  :help-text="$dropzoneHelp"
                  :submit-text="__('setting.Database Restore Submit Button')"
                  :selected-label="__('setting.Database Restore Selected Label')"
                  accept=".zip,.sql,.enc"
                  button-icon="bi-arrow-counterclockwise"
                  input-name="backup_file"
                  id-prefix="database-restore"
                  :max-size="$maxBytes"
                  :invalid-format-message="__('setting.Database Restore Invalid Format')"
                  :too-large-message="$tooLargeMessage"
              >
                <x-button.action href="{{ route('settings.database.index') }}" variant="secondary" :outline="true">
                  <i class="bi bi-archive me-1"></i>@lang('setting.Database Restore Backups Link')
                </x-button.action>
              </x-import.form>

              @error('backup_file')
                <div class="text-danger small mt-2">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
              <div class="d-flex align-items-start gap-3 mb-3">
                <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                  <i class="bi bi-journal-check"></i>
                </div>
                <div>
                  <h2 class="h6 mb-1">@lang('setting.Database Restore Checklist Title')</h2>
                  <p class="text-muted small mb-0">@lang('setting.Database Restore Checklist Description')</p>
                </div>
              </div>

              @if (count($checklist))
                <ul class="ps-3 mb-0 text-muted small">
                  @foreach ($checklist as $item)
                    <li class="mb-2">{{ $item }}</li>
                  @endforeach
                </ul>
              @endif
            </div>
          </div>
        @else
          <div class="alert alert-warning border-0 shadow-sm" role="alert">
            <div class="d-flex align-items-start gap-3">
              <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                <i class="bi bi-lock-fill"></i>
              </div>
              <div>
                <h2 class="h6 mb-1">@lang('setting.Database Restore Title')</h2>
                <p class="mb-0">@lang('setting.Database Restore Permission Warning')</p>
              </div>
            </div>
          </div>
        @endcan
      </div>

      <div class="col-12 col-xxl-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body p-4 d-flex flex-column">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                <i class="bi bi-shield-exclamation"></i>
              </div>
              <h2 class="h6 mb-0">@lang('setting.Database Restore Safety Title')</h2>
            </div>
            <p class="text-muted small mb-3">@lang('setting.Database Restore Safety Description')</p>

            @if (count($reminders))
              <ul class="ps-3 text-muted small mb-4">
                @foreach ($reminders as $item)
                  <li class="mb-2">{{ $item }}</li>
                @endforeach
              </ul>
            @endif

            <div class="mt-auto">
              <x-button.action href="{{ route('settings.database.index') }}" variant="link" class="px-0">
                <i class="bi bi-archive me-1"></i>@lang('setting.Database Restore Backups Link')
              </x-button.action>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
