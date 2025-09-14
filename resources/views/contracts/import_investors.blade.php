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
      <div class="ms-auto d-none d-md-block">
        <a href="{{ route('contracts.export.investors') }}" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-download me-1"></i> تصدير العقود غير المتطابقة
        </a>
      </div>
    </div>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm">
      <div class="d-flex align-items-start">
        <i class="bi bi-x-octagon me-2 fs-5"></i>
        <div>
          <div class="fw-semibold mb-1">فشلت العملية:</div>
          <ul class="mb-0">@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
        </div>
      </div>
    </div>
  @endif

  @if (session('success'))
    <div class="alert alert-success border-0 shadow-sm">
      <i class="bi bi-check2-circle me-2 fs-5"></i>{{ session('success') }}
    </div>
  @endif

  @php
    $failuresBag = session('contracts_investors_import.failures_simple') ?? [];
    if ($failuresBag instanceof \Illuminate\Support\Collection) {
        $failuresBag = $failuresBag->all();
    }
    $summary      = session('summary') ?: session('contracts_investors_import.summary') ?: [];
    $errorsSimple = session('errors_simple') ?? session('contracts_investors_import.errors_simple') ?? [];
    $rows    = (int)($summary['rows']    ?? 0);
    $updated = (int)($summary['updated'] ?? 0);
    $skipped = (int)($summary['skipped'] ?? 0);
    $changed = (int)($summary['changed'] ?? $updated);
    $failuresCount = is_array($failuresBag) ? count($failuresBag) : (is_object($failuresBag) && method_exists($failuresBag,'count') ? $failuresBag->count() : 0);
    $hasFailures = $failuresCount > 0;
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
      <form action="{{ route('contracts.import.investors') }}" method="POST" enctype="multipart/form-data" class="row g-3">
        @csrf
        <div class="col-12">
          <div id="dropzone" class="dz border border-2 border-dashed rounded-3 p-4 text-center">
            <i class="bi bi-file-earmark-arrow-up fs-1 d-block mb-2 text-primary"></i>
            <div class="mb-2 fw-semibold">اسحب الملف هنا أو اضغط للاختيار</div>
            <div class="text-muted small mb-3">Excel/CSV فقط</div>
            <input id="fileInput" type="file" name="file" class="position-absolute w-100 h-100 top-0 start-0 opacity-0" accept=".xlsx,.xls,.csv" required>
            <div class="small">
              <span class="text-secondary">الملف المختار:</span>
              <span id="fileName" class="fw-semibold">—</span>
              <span id="fileMeta" class="text-muted"></span>
            </div>
            <div id="fileError" class="text-danger small mt-1 d-none"></div>
          </div>
        </div>
        <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
          <button id="submitBtn" class="btn btn-primary" disabled>
            <i class="bi bi-upload me-1"></i> استيراد الآن
          </button>
        </div>
      </form>
    </div>
  </div>

  @if ($hasFailures)
    <div class="card border-0 shadow-sm">
      <div class="card-header d-flex align-items-center bg-white">
        <i class="bi bi-list-check me-2"></i>
        <span>أخطاء التحقق</span>
        <span class="badge rounded-pill text-bg-danger ms-2">{{ $failuresCount }}</span>
        <button class="btn btn-sm btn-outline-secondary ms-auto" data-bs-toggle="collapse" data-bs-target="#failuresTable" aria-expanded="true">
          إظهار/إخفاء
        </button>
      </div>
      <div id="failuresTable" class="collapse show">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-striped table-hover align-middle mb-0">
              <thead class="table-light sticky-top">
                <tr>
                  <th style="width:110px">رقم الصف</th>
                  <th style="width:220px">الحقل</th>
                  <th>الرسائل</th>
                  <th style="min-width:260px">القيم</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($failuresBag as $f)
                  <tr>
                    <td>{{ $f['row'] }}</td>
                    <td>{{ $f['attribute'] }}</td>
                    <td>{{ $f['messages'] }}</td>
                    <td><code>{{ implode(', ', array_map(fn($k,$v)=>"$k=$v", array_keys($f['values']), $f['values'])) }}</code></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  @endif

</div>
@endsection
