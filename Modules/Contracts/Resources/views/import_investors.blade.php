{{-- resources/views/contracts/import_investors.blade.php --}}
@extends('layouts.master')

@section('title', 'استيراد مستثمري العقود من Excel')

@section('content')
<div class="container-xxl py-4" dir="rtl">

  <div class="rounded-3 p-4 mb-4 position-relative overflow-hidden bg-light border">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-4 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:54px;height:54px;">
        <i class="bi bi-cloud-arrow-up fs-3"></i>
      </div>
      <div>
        <h1 class="h4 mb-1">استيراد مستثمري العقود</h1>
        <p class="text-muted mb-0">
          ارفع ملف Excel أو CSV يحتوي على العمود <code>contract_number</code>
          مع أعمدة المستثمرين <code>investor1_id + investor1_pct</code> حتى 6 مستثمرين أو عمود واحد
          <code>investors</code> بصيغة <code>id:pct|id:pct</code>.
          الصف الأول عناوين.
        </p>
      </div>
      <div class="ms-auto d-none d-md-flex align-items-center gap-2">
        @if (Route::has('contracts.import.investors.template'))
          <x-button.action href="{{ route('contracts.import.investors.template') }}" variant="secondary" :outline="true" size="sm">
            <i class="bi bi-filetype-xlsx me-1"></i> @lang('contracts::contracts_import.Download template')
          </x-button.action>
        @endif
        <x-button.action href="{{ route('contracts.export.investors') }}" variant="secondary" :outline="true" size="sm">
          <i class="bi bi-download me-1"></i> تصدير العقود غير المتطابقة
        </x-button.action>
      </div>
    </div>
  </div>

  @php
    $failuresBag = session('contracts_investors_import.failures_simple') ?? [];
    if ($failuresBag instanceof \Illuminate\Support\Collection) {
        $failuresBag = $failuresBag->all();
    }
    $skippedBag  = session('contracts_investors_import.skipped_simple') ?? [];
    if ($skippedBag instanceof \Illuminate\Support\Collection) {
        $skippedBag = $skippedBag->all();
    }
    $summary      = session('summary') ?: session('contracts_investors_import.summary') ?: [];
    $errorsSimple = session('errors_simple') ?? session('contracts_investors_import.errors_simple') ?? [];
    $rows    = (int)($summary['rows']    ?? 0);
    $updated = (int)($summary['updated'] ?? 0);
    $skipped = (int)($summary['skipped'] ?? 0);
    $changed = (int)($summary['changed'] ?? $updated);
    $failuresCount = is_array($failuresBag) ? count($failuresBag) : (is_object($failuresBag) && method_exists($failuresBag,'count') ? $failuresBag->count() : 0);
    $skippedCount  = is_array($skippedBag) ? count($skippedBag) : (is_object($skippedBag) && method_exists($skippedBag,'count') ? $skippedBag->count() : 0);
    $hasFailures = $failuresCount > 0;
    $hasIssues   = $hasFailures || $skippedCount > 0;
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
              <div class="text-muted small">تم تحديثها</div>
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
          :action="route('contracts.import.investors')"
          drag-text="اسحب الملف هنا أو اضغط للاختيار"
          help-text="Excel/CSV فقط"
          submit-text="استيراد الآن"
          selected-label="الملف المختار:"
          id-prefix="contracts-import-investors"
          invalid-format-message="صيغة الملف غير مدعومة. الصيغ المسموحة: xlsx, xls, csv"
          too-large-message="حجم الملف يتجاوز 10MB."
      >
        @if ($hasIssues && Route::has('contracts.import.investors.failures.fix'))
          <x-button.action href="{{ route('contracts.import.investors.failures.fix') }}" variant="warning">
            <i class="bi bi-download me-1"></i> تنزيل ملف لتصحيح الصفوف
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
              @foreach ($failuresBag as $f)
                <tr>
                  <td>{{ $f['row'] }}</td>
                  <td>{{ $f['attribute'] }}</td>
                  <td>{{ is_array($f['messages']) ? implode(', ', $f['messages']) : $f['messages'] }}</td>
                  <td><code>{{ implode(', ', array_map(fn($k,$v)=>"$k=$v", array_keys($f['values']), $f['values'])) }}</code></td>
                </tr>
              @endforeach
          </x-table>
        </div>
      </div>
    </div>
  @endif

  @if ($skippedCount > 0)
    <div class="card border-0 shadow-sm mt-4">
      <div class="card-header d-flex align-items-center bg-white">
        <i class="bi bi-skip-forward-fill me-2"></i>
        <span>الصفوف المتخطّاة</span>
        <span class="badge rounded-pill text-bg-warning ms-2">{{ $skippedCount }}</span>
        <x-button.action type="submit" variant="secondary" :outline="true" size="sm" class="ms-auto" data-bs-toggle="collapse" data-bs-target="#skippedTable" aria-expanded="true">
          إظهار/إخفاء
        </x-button.action>
      </div>
      <div id="skippedTable" class="collapse show">
        <div class="card-body p-0">
          <x-table head-class="table-light sticky-top" striped small>
              <x-slot name="head">
                  <tr>
                    <th style="width:110px">رقم الصف</th>
                    <th style="width:260px">السبب</th>
                    <th style="min-width:260px">القيم</th>
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
                  <td class="text-break"><code>{{ json_encode($vals, JSON_UNESCAPED_UNICODE) }}</code></td>
                </tr>
              @endforeach
          </x-table>
        </div>
      </div>
    </div>
  @endif

</div>
@endsection

