{{-- resources/views/guarantors/import.blade.php --}}
@extends('layouts.master')

@section('title', __('guarantors.Import Guarantors from Excel'))

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
        <h1 class="h4 mb-1">{{ __('guarantors.Import Guarantors') }}</h1>
        <p class="text-muted mb-0">
          {{ __('guarantors.Upload an Excel/CSV file with specifications: name, national_id, phone, email, address, nationality, title, notes, id_card_image, contract_image — first row is headers.') }}
        </p>
      </div>
      <div class="ms-auto d-none d-md-block">
        <a href="{{ route('guarantors.import.template') }}" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-filetype-xlsx me-1"></i> {{ __('guarantors.Download Template') }}
        </a>
      </div>
    </div>
  </div>

  {{-- Alerts --}}
  @if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm">
      <div class="d-flex align-items-start">
        <i class="bi bi-x-octagon me-2 fs-5"></i>
        <div>
          <div class="fw-semibold mb-1">{{ __('guarantors.Failed to execute the operation:') }}</div>
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

  @if (session('info'))
    <div class="alert alert-info border-0 shadow-sm">
      <i class="bi bi-info-circle me-2 fs-5"></i>{{ session('info') }}
    </div>
  @endif

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
              <div class="text-muted small">{{ __('guarantors.Total Rows') }}</div>
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
              <div class="text-muted small">{{ __('guarantors.Actually Saved') }}</div>
              <div class="fs-4 fw-bold">{{ number_format($changed) }}</div>
              <div class="text-success small">{{ __('guarantors.Success Rate:') }} {{ $successPct }}%</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-3">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-secondary-subtle text-secondary"><i class="bi bi-arrow-repeat"></i></div>
            <div class="flex-grow-1">
              <div class="text-muted small">{{ __('guarantors.Without Change') }}</div>
              <div class="fs-4 fw-bold">{{ number_format($unchanged) }}</div>
              <div class="text-muted small">{{ __('guarantors.Matching records 1:1') }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-3">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="flex-grow-1">
              <div class="text-muted small">{{ __('guarantors.Skipped') }}</div>
              <div class="fs-4 fw-bold">{{ number_format($skipped) }}</div>
              <div class="text-warning small">{{ __('guarantors.Rate:') }} {{ $skipPct }}%</div>
            </div>
            @if ($hasFailures)
              <span class="badge rounded-pill bg-warning-subtle text-warning border">{{ $failuresCount }} {{ __('guarantors.Validation Errors') }}</span>
            @endif
          </div>
        </div>
      </div>
    </div>
  @endif

  {{-- read/save errors --}}
  @if (!empty($errorsSimple))
    <div class="alert alert-warning border-0 shadow-sm mb-4">
      <div class="d-flex align-items-start">
        <i class="bi bi-exclamation-circle me-2 fs-5"></i>
        <div>
          <div class="fw-semibold mb-1">{{ __('guarantors.Errors during reading/saving:') }}</div>
          <ul class="mb-0">@foreach ($errorsSimple as $msg) <li>{{ $msg }}</li> @endforeach</ul>
        </div>
      </div>
    </div>
  @endif

  {{-- Upload --}}
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form action="{{ route('guarantors.import') }}" method="POST" enctype="multipart/form-data" class="row g-3">
        @csrf

        <div class="col-12">
          <div id="dropzone" class="dz border border-2 border-dashed rounded-3 p-4 text-center">
            <i class="bi bi-file-earmark-arrow-up fs-1 d-block mb-2 text-primary"></i>
            <div class="mb-2 fw-semibold">{{ __('guarantors.Drag the file here or click to select') }}</div>
            <div class="text-muted small mb-3">{{ __('guarantors.Excel/CSV file only — will be verified before saving') }}</div>
            <input id="fileInput" type="file" name="file"
                   class="position-absolute w-100 h-100 top-0 start-0 opacity-0"
                   accept=".xlsx,.xls,.csv" required>
            <div class="small">
              <span class="text-secondary">{{ __('guarantors.Selected File:') }}</span>
              <span id="fileName" class="fw-semibold">—</span>
              <span id="fileMeta" class="text-muted"></span>
            </div>
            <div id="fileError" class="text-danger small mt-1 d-none"></div>
          </div>
        </div>

        <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
          <button id="submitBtn" class="btn btn-primary" disabled>
            <i class="bi bi-upload me-1"></i> {{ __('guarantors.Import Now') }}
          </button>

          @if ($hasIssues && Route::has('guarantors.import.failures.fix'))
            <a class="btn btn-warning" href="{{ route('guarantors.import.failures.fix') }}">
              <i class="bi bi-wrench-adjustable me-1"></i>
              {{ __('guarantors.Download Errors/Skipped File') }}
              @if($hasFailures)
                <span class="badge text-bg-danger ms-1">{{ $failuresCount }}</span>
              @endif
              @if($skippedCount > 0)
                <span class="badge text-bg-warning ms-1">{{ $skippedCount }}</span>
              @endif
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  {{-- Failures Table --}}
  @if ($hasFailures)
    <div class="card border-0 shadow-sm">
      <div class="card-header d-flex align-items-center bg-white">
        <i class="bi bi-list-check me-2"></i>
        <span>{{ __('guarantors.Validation Errors') }}</span>
        <span class="badge rounded-pill text-bg-danger ms-2">{{ $failuresCount }}</span>
        <button class="btn btn-sm btn-outline-secondary ms-auto"
                data-bs-toggle="collapse" data-bs-target="#failuresTable" aria-expanded="true">
          {{ __('guarantors.Show/Hide') }}
        </button>
      </div>

      <div id="failuresTable" class="collapse show">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-striped table-hover align-middle mb-0">
              <thead class="table-light sticky-top">
                <tr>
                  <th style="width:110px">{{ __('guarantors.Row Number') }}</th>
                  <th style="width:220px">{{ __('guarantors.Field') }}</th>
                  <th>{{ __('guarantors.Messages') }}</th>
                  <th style="min-width:260px">{{ __('guarantors.Values') }}</th>
                </tr>
              </thead>
              <tbody>
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
              </tbody>
            </table>
          </div>
          <div class="p-3 text-muted small">
            {{ __('guarantors.Correct the rows then re-upload. It is preferable to use the "Download Errors/Skipped File" button.') }}
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
        <span>{{ __('guarantors.Skipped Rows') }}</span>
        <span class="badge rounded-pill text-bg-warning ms-2">{{ $skippedCount }}</span>
        <button class="btn btn-sm btn-outline-secondary ms-auto"
                data-bs-toggle="collapse" data-bs-target="#skippedTable" aria-expanded="true">
          {{ __('guarantors.Show/Hide') }}
        </button>
      </div>

      <div id="skippedTable" class="collapse show">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-striped table-hover align-middle mb-0">
              <thead class="table-light sticky-top">
                <tr>
                  <th style="width:110px">{{ __('guarantors.Row Number') }}</th>
                  <th style="width:260px">{{ __('guarantors.Reason') }}</th>
                  <th style="min-width:260px">{{ __('guarantors.Values') }}</th>
                </tr>
              </thead>
              <tbody>
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
              </tbody>
            </table>
          </div>
          <div class="p-3 text-muted small">
            {{ __('guarantors.Review the values and reason then correct the rows and re-upload.') }}
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

@push('scripts')
<script>
(function () {
  const dz   = document.getElementById('dropzone');
  const inp  = document.getElementById('fileInput');
  const name = document.getElementById('fileName');
  const meta = document.getElementById('fileMeta');
  const err  = document.getElementById('fileError');
  const btn  = document.getElementById('submitBtn');

  const MAX_SIZE = 10 * 1024 * 1024;
  const okExt = ['xlsx','xls','csv'];

  function fmtSize(bytes){
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024*1024) return (bytes/1024).toFixed(1) + ' KB';
    return (bytes/1024/1024).toFixed(1) + ' MB';
  }

  function validate(file){
    if (!err || !btn) return;
    err.classList.add('d-none'); err.textContent = ''; btn.disabled = true;
    if (!file) return;
    const ext = (file.name.split('.').pop() || '').toLowerCase();
    if (!okExt.includes(ext)) { err.textContent = '{{ __('guarantors.File format not supported. Allowed formats: xlsx, xls, csv') }}'; err.classList.remove('d-none'); return; }
    if (file.size > MAX_SIZE) { err.textContent = '{{ __('guarantors.File size exceeds 10MB.') }}'; err.classList.remove('d-none'); return; }
    btn.disabled = false;
  }

  if (dz && inp) {
    ['dragenter','dragover'].forEach(ev =>
      dz.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); dz.classList.add('dragover'); })
    );
    ['dragleave','drop'].forEach(ev =>
      dz.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); dz.classList.remove('dragover'); })
    );

    dz.addEventListener('drop', e => {
      if (e.dataTransfer?.files?.length) {
        inp.files = e.dataTransfer.files;
        const f = e.dataTransfer.files[0];
        if (name) name.textContent = f.name;
        if (meta) meta.textContent = ' (' + fmtSize(f.size) + ')';
        validate(f);
      }
    });

    inp.addEventListener('change', () => {
      const f = inp.files?.[0];
      name.textContent = f?.name || '—';
      meta.textContent = f ? ' (' + fmtSize(f.size) + ')' : '';
      validate(f || null);
    });
  }
})();
</script>
@endpush

