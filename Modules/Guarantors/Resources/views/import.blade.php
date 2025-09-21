{{-- resources/views/guarantors/import.blade.php --}}
@extends('layouts.master')

@section('title', __('guarantors::messages.Import Guarantors from Excel'))

@section('content')
<div class="container-xxl py-4" dir="rtl">

  {{-- Header --}}
  <div class="rounded-3 p-4 mb-4 position-relative overflow-hidden bg-light border">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-4 bg-primary-subtle text-primary d-flex align-items-center justify-content-center"
           style="width:54px;height:54px;">
        <i class="bi bi-cloud-arrow-up fs-3"></i>
      </div>
      <div>
        <h1 class="h4 mb-1">{{ __('guarantors::messages.Import Guarantors') }}</h1>
        <p class="text-muted mb-0">
          {{ __('guarantors::messages.Upload an Excel/CSV file with specifications:') }}
          <code>name, national_id, phone, email, address, nationality, title, notes, id_card_image, contract_image</code>
          — @lang('accounts::ledger_import.First row is headers.')
        </p>
      </div>
      <div class="ms-auto d-none d-md-block">
        <x-button.action href="{{ route('guarantors.import.template') }}" variant="secondary" :outline="true" size="sm">
          <i class="bi bi-filetype-xlsx me-1"></i> {{ __('guarantors::messages.Download Template') }}
        </x-button.action>
      </div>
    </div>
  </div>

  {{-- KPI --}}
  @php
    $summary      = session('summary') ?: session('guarantors_import.summary') ?: [];
    $failuresBag  = session('failures') ?? session('failures_simple') ?? [];
    $errorsSimple = session('errors_simple') ?? [];

    $rows      = (int)($summary['rows']      ?? 0);
    $inserted  = (int)($summary['inserted']  ?? 0);
    $updated   = (int)($summary['updated']   ?? 0);
    $unchanged = (int)($summary['unchanged'] ?? 0);
    $skipped   = (int)($summary['skipped']   ?? 0);
    $changed   = (int)($summary['changed']   ?? ($inserted + $updated));

    $pendingUpdates = session('guarantors_import.pending_updates') ?? [];
    if ($pendingUpdates instanceof \Illuminate\Support\Collection) {
      $pendingUpdates = $pendingUpdates->toArray();
    } elseif (is_object($pendingUpdates) && method_exists($pendingUpdates, 'toArray')) {
      $pendingUpdates = $pendingUpdates->toArray();
    }

    $pendingCount   = is_countable($pendingUpdates) ? count($pendingUpdates)
                      : (method_exists($pendingUpdates, 'count') ? (int)$pendingUpdates->count() : 0);
    $pendingSummary = max((int)($summary['pending'] ?? 0), $pendingCount);
    $hasPending     = $pendingCount > 0;

    $fieldLabels = trans('guarantors::messages.fields');
    if (!is_array($fieldLabels)) $fieldLabels = [];

    $formatPendingValue = function ($value) {
      if ($value === null) return '—';
      if (is_string($value)) {
        $trimmed = trim($value);
        return $trimmed === '' ? '—' : $trimmed;
      }
      if (is_numeric($value)) {
        return (string)$value;
      }
      if (is_array($value)) {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
      }
      if (is_object($value)) {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
      }
      return (string)$value;
    };

    $failuresCount = is_countable($failuresBag) ? count($failuresBag) : (method_exists($failuresBag, 'count') ? (int)$failuresBag->count() : 0);
    $hasFailures   = $failuresCount > 0;

    $successPct = $rows > 0 ? round(($changed / $rows) * 100, 1) : 0;
    $skipPct    = $rows > 0 ? round(($skipped / $rows) * 100, 1) : 0;

    $skippedBag   = session('guarantors_import.skipped_simple') ?? [];
    $skippedCount = is_countable($skippedBag) ? count($skippedBag)
                    : (method_exists($skippedBag, 'count') ? (int)$skippedBag->count() : 0);

    $hasIssues = $hasFailures || $skippedCount > 0;
  @endphp

  @if ($rows || $changed || $unchanged || $skipped)
    <div class="row g-3 mb-4">
      <div class="col-12 col-md-3">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-primary-subtle text-primary"><i class="bi bi-table"></i></div>
            <div class="flex-grow-1">
              <div class="text-muted small">{{ __('guarantors::messages.Total Rows') }}</div>
              <div class="fs-4 fw-bold">{{ number_format($rows) }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-3">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-success-subtle text-success"><i class="bi bi-check2"></i></div>
            <div class="flex-grow-1">
              <div class="text-muted small">{{ __('guarantors::messages.Actually Saved') }}</div>
              <div class="fs-4 fw-bold">{{ number_format($changed) }}</div>
              <div class="text-success small">{{ __('guarantors::messages.Success Rate:') }} {{ $successPct }}%</div>
            </div>
          </div>
        </div>
      </div>

      @if ($pendingSummary > 0)
        <div class="col-12 col-md-3">
          <div class="card shadow-sm h-100 border-0">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="kpi-icon bg-info-subtle text-info"><i class="bi bi-hourglass-split"></i></div>
              <div class="flex-grow-1">
                <div class="text-muted small">{{ __('guarantors::messages.Pending Review') }}</div>
                <div class="fs-4 fw-bold">{{ number_format($pendingSummary) }}</div>
                <div class="text-info small">{{ __('guarantors::messages.Pending updates require confirmation') }}</div>
              </div>
            </div>
          </div>
        </div>
      @endif

      <div class="col-12 col-md-3">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-secondary-subtle text-secondary"><i class="bi bi-arrow-repeat"></i></div>
            <div class="flex-grow-1">
              <div class="text-muted small">{{ __('guarantors::messages.Without Change') }}</div>
              <div class="fs-4 fw-bold">{{ number_format($unchanged) }}</div>
              <div class="text-muted small">{{ __('guarantors::messages.Matching records 1:1') }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-3">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="flex-grow-1">
              <div class="text-muted small">{{ __('guarantors::messages.Skipped') }}</div>
              <div class="fs-4 fw-bold">{{ number_format($skipped) }}</div>
              <div class="text-warning small">{{ __('guarantors::messages.Rate:') }} {{ $skipPct }}%</div>
            </div>
            @if ($hasFailures)
              <span class="badge rounded-pill bg-warning-subtle text-warning border">{{ $failuresCount }} {{ __('guarantors::messages.Validation Errors') }}</span>
            @endif
          </div>
        </div>
      </div>
    </div>
  @endif

  @if ($pendingCount > 0)
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header d-flex align-items-center bg-white">
        <i class="bi bi-hourglass-split me-2"></i>
        <span>{{ __('guarantors::messages.Pending Updates') }}</span>
        <span class="badge rounded-pill text-bg-warning ms-2">{{ $pendingCount }}</span>
      </div>
      <div class="card-body p-0">
        <x-table head-class="table-light" striped small>
            <x-slot name="head">
                <tr>
                  <th style="width:110px">{{ __('guarantors::messages.Row Number') }}</th>
                  <th style="width:220px">{{ __('guarantors::messages.Guarantor') }}</th>
                  <th>{{ __('guarantors::messages.Changes') }}</th>
                  <th style="width:220px">{{ __('guarantors::messages.Actions') }}</th>
                </tr>
            </x-slot>
            @foreach ($pendingUpdates as $tokenKey => $pendingItem)
              @php
                $token = is_string($tokenKey) ? $tokenKey : ($pendingItem['token'] ?? $tokenKey);
              @endphp
              @if (empty($token))
                @continue
              @endif
              @php
                $diff           = $pendingItem['diff'] ?? [];
                $identifiers    = $pendingItem['identifiers'] ?? [];
                $guarantorRow   = $pendingItem['row'] ?? '—';
                $guarantorName  = $pendingItem['guarantor_name'] ?? '—';
              @endphp
              <tr>
                <td class="fw-semibold">{{ $guarantorRow }}</td>
                <td>
                  <div class="fw-semibold">{{ $guarantorName }}</div>
                  <div class="text-muted small">
                    @if (!empty($identifiers['national_id']))
                      <span class="d-inline-flex align-items-center me-2">
                        <i class="bi bi-person-vcard me-1"></i>
                        {{ $formatPendingValue($identifiers['national_id']) }}
                      </span>
                    @endif
                    @if (!empty($identifiers['phone']))
                      <span class="d-inline-flex align-items-center">
                        <i class="bi bi-telephone me-1"></i>
                        {{ $formatPendingValue($identifiers['phone']) }}
                      </span>
                    @endif
                  </div>
                </td>
                <td>
                  @if (!empty($diff))
                    <div class="d-flex flex-column gap-2">
                      @foreach ($diff as $field => $change)
                        <div class="border rounded-3 p-2">
                          <div class="fw-semibold small text-secondary">{{ $fieldLabels[$field] ?? $field }}</div>
                          <div class="d-flex align-items-center gap-2">
                            <span class="badge text-bg-light">{{ $formatPendingValue($change['old'] ?? null) }}</span>
                            <i class="bi bi-arrow-left-right text-muted"></i>
                            <span class="badge text-bg-primary">{{ $formatPendingValue($change['new'] ?? null) }}</span>
                          </div>
                        </div>
                      @endforeach
                    </div>
                  @else
                    <span class="text-muted small">{{ __('guarantors::messages.No differences found') }}</span>
                  @endif
                </td>
                <td>
                  <div class="d-flex flex-column flex-lg-row gap-2">
                    <form method="POST" action="{{ route('guarantors.import.pending.confirm', $token) }}">
                      @csrf
                      <x-button.action type="submit" variant="success" size="sm" class="w-100">
                        <i class="bi bi-check2-circle me-1"></i> {{ __('guarantors::messages.Confirm Update') }}
                      </x-button.action>
                    </form>
                    <form method="POST" action="{{ route('guarantors.import.pending.store-new', $token) }}">
                      @csrf
                      <x-button.action type="submit" variant="primary" :outline="true" size="sm" class="w-100">
                        <i class="bi bi-plus-circle me-1"></i> {{ __('guarantors::messages.Save as new record') }}
                      </x-button.action>
                    </form>
                    <form method="POST" action="{{ route('guarantors.import.pending.ignore', $token) }}">
                      @csrf
                      <x-button.action type="submit" variant="secondary" :outline="true" size="sm" class="w-100">
                        <i class="bi bi-x-circle me-1"></i> {{ __('guarantors::messages.Ignore') }}
                      </x-button.action>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
        </x-table>
      </div>
    </div>
  @endif

  {{-- read/save errors --}}
  @if (!empty($errorsSimple))
    <div class="alert alert-warning border-0 shadow-sm mb-4">
      <div class="d-flex align-items-start">
        <i class="bi bi-exclamation-circle me-2 fs-5"></i>
        <div>
          <div class="fw-semibold mb-1">{{ __('guarantors::messages.Errors during reading/saving:') }}</div>
          <ul class="mb-0">@foreach ($errorsSimple as $msg) <li>{{ $msg }}</li> @endforeach</ul>
        </div>
      </div>
    </div>
  @endif

  {{-- Upload --}}
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <x-import.form
          :action="route('guarantors.import')"
          :drag-text="__('guarantors::messages.Drag the file here or click to select')"
          :help-text="__('guarantors::messages.Excel/CSV file only — will be verified before saving')"
          :submit-text="__('guarantors::messages.Import Now')"
          :selected-label="__('guarantors::messages.Selected File:')"
          id-prefix="guarantors-import"
          :invalid-format-message="__('guarantors::messages.File format not supported. Allowed formats: xlsx, xls, csv')"
          :too-large-message="__('guarantors::messages.File size exceeds 10MB.')"
      >
        @if ($hasIssues && Route::has('guarantors.import.failures.fix'))
          <x-button.action href="{{ route('guarantors.import.failures.fix') }}" variant="warning">
            <i class="bi bi-wrench-adjustable me-1"></i>
            {{ __('guarantors::messages.Download Errors/Skipped File') }}
            @if($hasFailures)
              <span class="badge text-bg-danger ms-1">{{ $failuresCount }}</span>
            @endif
            @if($skippedCount > 0)
              <span class="badge text-bg-warning ms-1">{{ $skippedCount }}</span>
            @endif
          </x-button.action>
        @endif
      </x-import.form>
    </div>
  </div>

  {{-- Failures Table --}}
  @if ($hasFailures)
    <div class="card border-0 shadow-sm">
      <div class="card-header d-flex align-items-center bg-white">
        <i class="bi bi-list-check me-2"></i>
        <span>{{ __('guarantors::messages.Validation Errors') }}</span>
        <span class="badge rounded-pill text-bg-danger ms-2">{{ $failuresCount }}</span>
        <x-button.action type="submit" variant="secondary" :outline="true" size="sm" class="ms-auto" data-bs-toggle="collapse" data-bs-target="#failuresTable" aria-expanded="true">
          {{ __('guarantors::messages.Show/Hide') }}
        </x-button.action>
      </div>

      <div id="failuresTable" class="collapse show">
        <div class="card-body p-0">
          <x-table head-class="table-light sticky-top" striped small>
              <x-slot name="head">
                  <tr>
                    <th style="width:110px">{{ __('guarantors::messages.Row Number') }}</th>
                    <th style="width:220px">{{ __('guarantors::messages.Field') }}</th>
                    <th>{{ __('guarantors::messages.Messages') }}</th>
                    <th style="min-width:260px">{{ __('guarantors::messages.Values') }}</th>
                  </tr>
              </x-slot>
              @foreach ($failuresBag as $failure)
                @php
                  $rowNum = is_object($failure) && method_exists($failure, 'row') ? (int)$failure->row() : (int)($failure['row'] ?? 0);
                  $attr   = is_object($failure) && method_exists($failure, 'attribute') ? $failure->attribute() : ($failure['attribute'] ?? '');
                  $msgs   = is_object($failure) && method_exists($failure, 'errors') ? (array)$failure->errors() : (array)($failure['messages'] ?? $failure['errors'] ?? []);
                  $vals   = is_object($failure) && method_exists($failure, 'values') ? $failure->values() : ($failure['values'] ?? []);
                @endphp
                <tr>
                  <td class="text-muted">{{ $rowNum }}</td>
                  <td>{{ is_array($attr) ? implode(', ', $attr) : (string)$attr }}</td>
                  <td>
                    @if (count($msgs))
                      <ul class="mb-0 ps-3">@foreach ($msgs as $m) <li>{{ $m }}</li> @endforeach</ul>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td class="text-break">
                    <code class="small" style="white-space: pre-wrap; word-break: break-word;">
                      {{ json_encode($vals, JSON_UNESCAPED_UNICODE) }}
                    </code>
                  </td>
                </tr>
              @endforeach
          </x-table>
          <div class="p-3 text-muted small">
            {{ __('guarantors::messages.Correct the rows then re-upload. It is preferable to use the "Download Errors/Skipped File" button.') }}
          </div>
        </div>
      </div>
    </div>
  @endif

  {{-- Skipped Table --}}
  @php $hasSkipped = $skippedCount > 0; @endphp
  @if ($hasSkipped)
    <div class="card border-0 shadow-sm mt-4">
      <div class="card-header d-flex align-items-center bg-white">
        <i class="bi bi-skip-forward-fill me-2"></i>
        <span>{{ __('guarantors::messages.Skipped Rows') }}</span>
        <span class="badge rounded-pill text-bg-warning ms-2">{{ $skippedCount }}</span>
        <x-button.action type="submit" variant="secondary" :outline="true" size="sm" class="ms-auto" data-bs-toggle="collapse" data-bs-target="#skippedTable" aria-expanded="true">
          {{ __('guarantors::messages.Show/Hide') }}
        </x-button.action>
      </div>

      <div id="skippedTable" class="collapse show">
        <div class="card-body p-0">
          <x-table head-class="table-light sticky-top" striped small>
              <x-slot name="head">
                  <tr>
                    <th style="width:110px">{{ __('guarantors::messages.Row Number') }}</th>
                    <th style="width:260px">{{ __('guarantors::messages.Reason') }}</th>
                    <th style="min-width:260px">{{ __('guarantors::messages.Values') }}</th>
                  </tr>
              </x-slot>
              @foreach ($skippedBag as $r)
                @php
                  $rowNum = (int)($r['row'] ?? 0);
                  $reason = (string)($r['reason'] ?? ($r['messages'] ?? ''));
                  $vals   = $r['values'] ?? [];
                @endphp
                <tr>
                  <td class="text-muted">{{ $rowNum }}</td>
                  <td>{{ $reason !== '' ? $reason : '—' }}</td>
                  <td class="text-break">
                    <code class="small" style="white-space: pre-wrap; word-break: break-word;">
                      {{ json_encode($vals, JSON_UNESCAPED_UNICODE) }}
                    </code>
                  </td>
                </tr>
              @endforeach
          </x-table>
          <div class="p-3 text-muted small">
            {{ __('guarantors::messages.Review the values and reason then correct the rows and re-upload.') }}
          </div>
        </div>
      </div>
    </div>
  @endif

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

@endpush

