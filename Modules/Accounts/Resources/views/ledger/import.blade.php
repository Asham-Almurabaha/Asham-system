{{-- resources/views/ledger/import.blade.php --}}
@extends('layouts.master')

@section('title', __('accounts::ledger_import.Import Ledger Entries from Excel'))

@section('content')
<div class="container-xxl py-4" dir="rtl">

  <div class="rounded-3 p-4 mb-4 position-relative overflow-hidden bg-light border">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-4 bg-primary-subtle text-primary d-flex align-items-center justify-content-center"
           style="width:54px;height:54px;">
        <i class="bi bi-cloud-arrow-up fs-3"></i>
      </div>
      <div>
        <h1 class="h4 mb-1">استيراد قيود الدفتر</h1>
        <p class="text-muted mb-0">
          ارفع ملف Excel/CSV بالمواصفات:
          <code>party_category, investor_id, status_id, bank_account_id, safe_id, amount, transaction_date, contract_id, installment_id, ref, notes</code>
          — الصف الأول عناوين الأعمدة.
        </p>
      </div>
      <div class="ms-auto d-none d-md-block">
        <x-button.action href="{{ route('ledger.import.template') }}" variant="secondary" :outline="true" size="sm">
          <i class="bi bi-filetype-xlsx me-1"></i> @lang('accounts::ledger_import.Download template')
        </x-button.action>
      </div>
    </div>
  </div>

  @php
    // نفس منطق customers: نقرأ من مفاتيح عامة فقط
    $summary      = session('summary') ?: [];
    $failuresBag  = session('failures') ?? session('failures_simple') ?? [];
    $errorsSimple = session('errors_simple') ?? [];

    $rows      = (int)($summary['rows']      ?? 0);
    $inserted  = (int)($summary['inserted']  ?? 0);
    $updated   = (int)($summary['updated']   ?? 0);
    $unchanged = (int)($summary['unchanged'] ?? 0);
    $skipped   = (int)($summary['skipped']   ?? 0);
    $changed   = (int)($summary['changed']   ?? ($inserted + $updated));

    $failuresCount = is_countable($failuresBag) ? count($failuresBag) : (method_exists($failuresBag, 'count') ? (int)$failuresBag->count() : 0);
    $hasFailures   = $failuresCount > 0;

    $successPct = $rows > 0 ? round(($changed / $rows) * 100, 1) : 0;
    $skipPct    = $rows > 0 ? round(($skipped / $rows) * 100, 1) : 0;
  @endphp

  @if ($rows || $changed || $skipped)
    <div class="row g-3 mb-4">
      <div class="col-12 col-md-3">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-primary-subtle text-primary"><i class="bi bi-table"></i></div>
            <div class="flex-grow-1">
              <div class="text-muted small">إجمالي الصفوف</div>
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
              <div class="text-muted small">المحفوظ فعليًا</div>
              <div class="fs-4 fw-bold">{{ number_format($changed) }}</div>
              <div class="text-success small">نسبة النجاح: {{ $successPct }}%</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-3">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-secondary-subtle text-secondary"><i class="bi bi-arrow-repeat"></i></div>
            <div class="flex-grow-1">
              <div class="text-muted small">بدون تغيير</div>
              <div class="fs-4 fw-bold">{{ number_format($unchanged) }}</div>
              <div class="text-muted small">سجلات مطابقة 1:1</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-3">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="flex-grow-1">
              <div class="text-muted small">متخطّى</div>
              <div class="fs-4 fw-bold">{{ number_format($skipped) }}</div>
              <div class="text-warning small">النسبة: {{ $skipPct }}%</div>
            </div>
            @if ($hasFailures)
              <span class="badge rounded-pill bg-warning-subtle text-warning border">{{ $failuresCount }} خطأ تحقق</span>
            @endif
          </div>
        </div>
      </div>
    </div>
  @endif

  @if (!empty($errorsSimple))
    <div class="alert alert-warning border-0 shadow-sm mb-4">
      <div class="d-flex align-items-start">
        <i class="bi bi-exclamation-circle me-2 fs-5"></i>
        <div>
          <div class="fw-semibold mb-1">@lang('accounts::ledger_import.Errors during read/save:')</div>
          <ul class="mb-0">
            @foreach ($errorsSimple as $msg) <li>{{ $msg }}</li> @endforeach
          </ul>
        </div>
      </div>
    </div>
  @endif

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <x-import.form
          :action="route('ledger.import')"
          :drag-text="__('accounts::ledger_import.Drag file here or click to choose')"
          :help-text="__('accounts::ledger_import.Excel/CSV only — validation before save')"
          :submit-text="__('accounts::ledger_import.Import Now')"
          :selected-label="__('accounts::ledger_import.Selected file:')"
          id-prefix="accounts-ledger-import"
          :invalid-format-message="__('accounts::ledger_import.Unsupported file format. Allowed: xlsx, xls, csv')"
          :too-large-message="__('accounts::ledger_import.File size exceeds 10MB.')"
      >
        @if ($hasFailures && Route::has('ledger.import.failures.fix'))
          <x-button.action href="{{ route('ledger.import.failures.fix') }}" variant="warning">
            <i class="bi bi-wrench-adjustable me-1"></i> @lang('accounts::ledger_import.Download file to fix rows')
          </x-button.action>
        @endif
      </x-import.form>
    </div>
  </div>

  @if ($hasFailures)
    <div class="card border-0 shadow-sm">
      <div class="card-header d-flex align-items-center bg-white">
        <i class="bi bi-list-check me-2"></i>
        <span>@lang('accounts::ledger_import.Validation Errors')</span>
        <span class="badge rounded-pill text-bg-danger ms-2">{{ $failuresCount }}</span>
        <x-button.action type="submit" variant="secondary" :outline="true" size="sm" class="ms-auto" data-bs-toggle="collapse" data-bs-target="#failuresTable" aria-expanded="true">
          @lang('accounts::ledger_import.Show/Hide')
        </x-button.action>
      </div>

      <div id="failuresTable" class="collapse show">
        <div class="card-body p-0">
          <x-table head-class="table-light sticky-top" striped small>
              <x-slot name="head">
                  <tr>
                    <th style="width:110px">@lang('accounts::ledger_import.Row Number')</th>
                    <th style="width:220px">@lang('accounts::ledger_import.Field')</th>
                    <th>@lang('accounts::ledger_import.Messages')</th>
                    <th style="min-width:260px">@lang('accounts::ledger_import.Values')</th>
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
                      <ul class="mb-0 ps-3">
                        @foreach ($msgs as $m) <li>{{ $m }}</li> @endforeach
                      </ul>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td class="text-break">
                    <code class="small code-wrap">{{ json_encode($vals, JSON_UNESCAPED_UNICODE) }}</code>
                  </td>
                </tr>
              @endforeach
          </x-table>
          <div class="p-3 text-muted small">
            @lang('accounts::ledger_import.Correct rows above then re-upload. Prefer using the “Download file to fix rows” button.')
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

