{{-- resources/views/contracts/import_basic.blade.php --}}
@extends('layouts.master')

@section('title', 'استيراد عقود أساسية من Excel')

@section('content')
<div class="container-xxl py-4" dir="rtl">

  <div class="rounded-3 p-4 mb-4 position-relative overflow-hidden bg-light border">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-4 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:54px;height:54px;">
        <i class="bi bi-cloud-arrow-up fs-3"></i>
      </div>
      <div>
        <h1 class="h4 mb-1">استيراد عقود أساسية</h1>
        <p class="text-muted mb-0">
          ارفع ملف Excel أو CSV يحتوي على الأعمدة الأساسية فقط مثل:
          <code>contract_number, customer_id, guarantor_id, product_type_id, sale_price, contract_value, total_value, installment_type_id, installment_value, installments_count, start_date, first_installment_date</code>
          — الصف الأول عناوين.
        </p>
      </div>
      <div class="ms-auto d-none d-md-flex align-items-center gap-2">
        @if (Route::has('contracts.import.basic.template'))
          <x-button.action href="{{ route('contracts.import.basic.template') }}" variant="secondary" :outline="true" size="sm">
            <i class="bi bi-filetype-xlsx me-1"></i> @lang('contracts::contracts_import.Download template')
          </x-button.action>
        @endif
        <x-button.action href="{{ route('contracts.export.basic') }}" variant="secondary" :outline="true" size="sm">
          <i class="bi bi-download me-1"></i> تصدير البيانات الأساسية كمثال
        </x-button.action>
      </div>
    </div>
  </div>

  @php
    $failuresBag = session('contracts_basic_import.failures_simple') ?? [];
    if ($failuresBag instanceof \Illuminate\Support\Collection) {
        $failuresBag = $failuresBag->all();
    }
    $summary      = session('summary') ?: session('contracts_basic_import.summary') ?: [];
    $errorsSimple = session('errors_simple') ?? session('contracts_basic_import.errors_simple') ?? [];
    $rows      = (int)($summary['rows']      ?? 0);
    $inserted  = (int)($summary['inserted']  ?? 0);
    $skipped   = (int)($summary['skipped']   ?? 0);
    $changed   = (int)($summary['changed']   ?? $inserted);
    $failuresCount = is_array($failuresBag) ? count($failuresBag) : (is_object($failuresBag) && method_exists($failuresBag,'count') ? $failuresBag->count() : 0);
    $hasFailures = $failuresCount > 0;
    $hasIssues   = $hasFailures || $skipped > 0;
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
              <div class="text-muted small">تم حفظها</div>
              <div class="fs-4 fw-bold">{{ number_format($changed) }}</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-3">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="flex-grow-1">
              <div class="text-muted small">متخطى</div>
              <div class="fs-4 fw-bold">{{ number_format($skipped) }}</div>
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
          <div class="fw-semibold mb-1">أخطاء أثناء الحفظ:</div>
          <ul class="mb-0">@foreach ($errorsSimple as $msg) <li>{{ $msg }}</li> @endforeach</ul>
        </div>
      </div>
    </div>
  @endif

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <x-import.form
          :action="route('contracts.import.basic')"
          drag-text="اسحب الملف هنا أو اضغط للاختيار"
          help-text="Excel/CSV فقط"
          submit-text="استيراد الآن"
          selected-label="الملف المختار:"
          id-prefix="contracts-import-basic"
          invalid-format-message="صيغة الملف غير مدعومة."
          too-large-message="حجم الملف يتجاوز 10MB."
      >
        @if ($hasIssues && Route::has('contracts.import.basic.failures.fix'))
          <x-button.action href="{{ route('contracts.import.basic.failures.fix') }}" variant="warning">
            <i class="bi bi-wrench-adjustable me-1"></i>
            تنزيل ملف لتصحيح الصفوف
            @if($hasFailures)
              <span class="badge text-bg-danger ms-1">{{ $failuresCount }}</span>
            @endif
            @if($skipped > 0)
              <span class="badge text-bg-warning ms-1">{{ $skipped }}</span>
            @endif
          </x-button.action>
        @endif
      </x-import.form>
    </div>
  </div>

  @if ($hasFailures)
    <div class="card border-0 shadow-sm">
      <div class="card-header d-flex align-items-center bg-white">
        <i class="bi bi-list-check me-2"></i>
        <span>أخطاء التحقق</span>
        <span class="badge rounded-pill text-bg-danger ms-2">{{ $failuresCount }}</span>
        <x-button.action type="submit" variant="secondary" :outline="true" size="sm" class="ms-auto" data-bs-toggle="collapse" data-bs-target="#failuresTable" aria-expanded="true">
          إظهار/إخفاء
        </x-button.action>
      </div>

      <div id="failuresTable" class="collapse show">
        <div class="card-body p-0">
          <x-table head-class="table-light sticky-top" striped small>
              <x-slot name="head">
                  <tr>
                    <th style="width:110px">رقم الصف</th>
                    <th style="width:220px">الحقل</th>
                    <th>الرسائل</th>
                    <th style="min-width:260px">القيم</th>
                  </tr>
              </x-slot>
              @foreach ($failuresBag as $failure)
                @php
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
          <div class="p-3 text-muted small">صحّح الصفوف ثم أعد الرفع.</div>
        </div>
      </div>
    </div>
  @endif

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
@endpush

