{{-- resources/views/contracts/import.blade.php --}}
@extends('layouts.master')

@section('title', __('contracts::contracts_import.Import Contracts from Excel'))

@section('content')
<div class="container-xxl py-4" dir="rtl">

  {{-- Header --}}
  <div class="rounded-3 p-4 mb-4 position-relative overflow-hidden bg-light border">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-4 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:54px;height:54px;">
        <i class="bi bi-cloud-arrow-up fs-3"></i>
      </div>
      <div>
        <h1 class="h4 mb-1">@lang('contracts::contracts_import.Import Contracts')</h1>
        <p class="text-muted mb-0">
          @lang('contracts::contracts_import.Upload an Excel/CSV file with specs:') @lang('contracts::contracts_import.Fields accept ID or name.')
          <code>customer_id/customer_name, guarantor_id/guarantor_name, product_type_id/product_type_name, products_count, purchase_price, sale_price, contract_value, investor_profit, total_value, discount_amount, installment_type_id/installment_type_name, installment_value, installments_count, start_date, first_installment_date, contract_number</code>
          — الصف الأول عناوين.
          <br>
          المستثمرون:
          <ol class="mb-1">
            <li>@lang('contracts::contracts_import.Investors field as id:pct list')</li>
            <li>أعمدة منفصلة حتى 6: <code>investorN_id / investorN_name + investorN_pct</code> (N=1..6).</li>
          </ol>
          @lang('contracts::contracts_import.Installments sequence up to 18')
        </p>
      </div>
      <div class="ms-auto d-none d-md-block">
        @if (Route::has('contracts.import.template'))
          <a href="{{ route('contracts.import.template') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-filetype-xlsx me-1"></i> @lang('contracts::contracts_import.Download template')
          </a>
        @endif
      </div>
    </div>
  </div>

  @php
    // نقرأ أولاً من المفتاح الدائم الذي نحفّظه في الكنترولر
    $failuresBag = session('contracts_import.failures_simple')
                ?? session('failures_simple')
                ?? session('failures')
                ?? [];

    // لو Collection حوّلها Array
    if ($failuresBag instanceof \Illuminate\Support\Collection) {
        $failuresBag = $failuresBag->all();
    }

    // الملخّص (من الفلاش أو التخزين الدائم)
    $summary      = session('summary') ?: session('contracts_import.summary') ?: [];
    $errorsSimple = session('errors_simple') ?? session('contracts_import.errors_simple') ?? [];

    $rows      = (int)($summary['rows']      ?? 0);
    $inserted  = (int)($summary['inserted']  ?? 0);
    $updated   = (int)($summary['updated']   ?? 0);
    $unchanged = (int)($summary['unchanged'] ?? 0);
    $skipped   = (int)($summary['skipped']   ?? 0);
    $changed   = (int)($summary['changed']   ?? ($inserted + $updated));

    // عدّ مرن (Array-of-arrays أو Array-of-objects)
    $failuresCount = 0;
    if (is_array($failuresBag)) {
        $failuresCount = count($failuresBag);
    } elseif (is_object($failuresBag) && method_exists($failuresBag, 'count')) {
        $failuresCount = (int) $failuresBag->count();
    }
    $hasFailures = $failuresCount > 0;
    $hasIssues   = $hasFailures || $skipped > 0;

    $successPct = $rows > 0 ? round(($changed / $rows) * 100, 1) : 0;
    $skipPct    = $rows > 0 ? round(($skipped / $rows) * 100, 1) : 0;
  @endphp

  {{-- KPIs --}}
  @if ($rows || $changed || $skipped)
    <div class="row g-3 mb-4">
      <div class="col-12 col-md-3">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-primary-subtle text-primary"><i class="bi bi-table"></i></div>
            <div class="flex-grow-1">
              <div class="text-muted small">@lang('contracts::contracts_import.Total Rows')</div>
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
              <div class="text-muted small">@lang('contracts::contracts_import.Saved')</div>
              <div class="fs-4 fw-bold">{{ number_format($changed) }}</div>
              <div class="text-success small">@lang('contracts::contracts_import.Success Rate'): {{ $successPct }}%</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-3">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-secondary-subtle text-secondary"><i class="bi bi-arrow-repeat"></i></div>
            <div class="flex-grow-1">
              <div class="text-muted small">@lang('contracts::contracts_import.Unchanged')</div>
              <div class="fs-4 fw-bold">{{ number_format($unchanged) }}</div>
              <div class="text-muted small">@lang('contracts::contracts_import.Records matched 1:1')</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-3">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="flex-grow-1">
              <div class="text-muted small">@lang('contracts::contracts_import.Skipped')</div>
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

  {{-- read/save errors (عام) --}}
  @if (!empty($errorsSimple))
    <div class="alert alert-warning border-0 shadow-sm mb-4">
      <div class="d-flex align-items-start">
        <i class="bi bi-exclamation-circle me-2 fs-5"></i>
        <div>
          <div class="fw-semibold mb-1">@lang('contracts::contracts_import.Errors during read/save:')</div>
          <ul class="mb-0">@foreach ($errorsSimple as $msg) <li>{{ $msg }}</li> @endforeach</ul>
        </div>
      </div>
    </div>
  @endif

  {{-- Upload --}}
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <x-import.form
          :action="route('contracts.import')"
          :drag-text="__('contracts::contracts_import.Drag file here or click to choose')"
          :help-text="__('contracts::contracts_import.Excel/CSV only — validation before save')"
          :submit-text="__('contracts::contracts_import.Import Now')"
          :selected-label="__('contracts::contracts_import.Selected file:')"
          id-prefix="contracts-import"
          invalid-format-message="صيغة الملف غير مدعومة. الصيغ المسموحة: xlsx, xls, csv"
          too-large-message="حجم الملف يتجاوز 10MB."
      >
        @if ($hasIssues && Route::has('contracts.import.failures.fix'))
          <a class="btn btn-warning" href="{{ route('contracts.import.failures.fix') }}">
            <i class="bi bi-wrench-adjustable me-1"></i>
            تنزيل ملف لتصحيح الصفوف
            @if($hasFailures)
              <span class="badge text-bg-danger ms-1">{{ $failuresCount }}</span>
            @endif
            @if($skipped > 0)
              <span class="badge text-bg-warning ms-1">{{ $skipped }}</span>
            @endif
          </a>
        @endif
      </x-import.form>
    </div>
  </div>

  {{-- Failures Table --}}
  @if ($hasFailures)
    <div class="card border-0 shadow-sm">
      <div class="card-header d-flex align-items-center bg-white">
        <i class="bi bi-list-check me-2"></i>
        <span>@lang('contracts::contracts_import.Validation Errors')</span>
        <span class="badge rounded-pill text-bg-danger ms-2">{{ $failuresCount }}</span>
        <button class="btn btn-sm btn-outline-secondary ms-auto" data-bs-toggle="collapse" data-bs-target="#failuresTable" aria-expanded="true">
          @lang('contracts::contracts_import.Show/Hide')
        </button>
      </div>

      <div id="failuresTable" class="collapse show">
        <div class="card-body p-0">
          <x-table head-class="table-light sticky-top" striped small>
              <x-slot name="head">
                  <tr>
                    <th style="width:110px">@lang('contracts::contracts_import.Row Number')</th>
                    <th style="width:220px">@lang('contracts::contracts_import.Field')</th>
                    <th>@lang('contracts::contracts_import.Messages')</th>
                    <th style="min-width:260px">@lang('contracts::contracts_import.Values')</th>
                  </tr>
              </x-slot>
              @foreach ($failuresBag as $failure)
                @php
                  // يدعم array أو objects (كائن Failure أو صف مبسّط)
                  $isObj = is_object($failure);
                  $rowNum = $isObj && method_exists($failure, 'row') ? (int)$failure->row() : (int)($failure['row'] ?? 0);
                  $attr   = $isObj && method_exists($failure, 'attribute') ? $failure->attribute() : ($failure['attribute'] ?? '');
                  $msgs   = $isObj && method_exists($failure, 'errors') ? (array)$failure->errors() : (array)($failure['messages'] ?? $failure['errors'] ?? []);
                  $vals   = $isObj && method_exists($failure, 'values') ? (array)$failure->values() : (array)($failure['values'] ?? []);
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
                    <code class="small code-wrap">{{ json_encode($vals, JSON_UNESCAPED_UNICODE) }}</code>
                  </td>
                </tr>
              @endforeach
          </x-table>
          <div class="p-3 text-muted small">
            صحّح الصفوف ثم أعد الرفع. يُفضّل استخدام زر “تنزيل ملف لتصحيح الصفوف”.
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

