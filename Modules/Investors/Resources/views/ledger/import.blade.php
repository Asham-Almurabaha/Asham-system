{{-- resources/views/investors/ledger/import.blade.php --}}
@extends('layouts.master')

@section('title', __('investors::investor_ledger_import.Import Ledger Entries from Excel'))

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
        <h1 class="h4 mb-1">@lang('investors::investor_ledger_import.Import Ledger Entries')</h1>
        <p class="text-muted mb-1">
          @lang('investors::investor_ledger_import.Upload an Excel/CSV file with specs:')
          <code>investor_id, status_id, bank_account_id, safe_id, amount, transaction_date, contract_id, installment_id, ref, notes</code>
          — @lang('investors::investor_ledger_import.First row is headers.')
        </p>
        <p class="text-muted small mb-0">@lang('investors::investor_ledger_import.IDs or names note')</p>
      </div>
      <div class="ms-auto d-none d-md-block">
        <a href="{{ route('investors.ledger.import.template') }}" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-filetype-xlsx me-1"></i> @lang('investors::investors_import.Download template')
        </a>
      </div>
    </div>
  </div>

  @php
    $summary      = session('summary') ?: session('investors_ledger_import.summary') ?: [];
    $failuresBag  = session('failures') ?? session('failures_simple') ?? session('investors_ledger_import.failures_simple') ?? [];
    $errorsSimple = session('errors_simple') ?? [];

    $rows     = (int) ($summary['rows']     ?? 0);
    $inserted = (int) ($summary['inserted'] ?? 0);
    $skipped  = (int) ($summary['skipped']  ?? 0);
    $changed  = (int) ($summary['changed']  ?? $inserted);

    $failuresCount = is_countable($failuresBag)
      ? count($failuresBag)
      : (method_exists($failuresBag, 'count') ? (int) $failuresBag->count() : 0);

    $hasFailures = $failuresCount > 0;

    $successPct = $rows > 0 ? round(($changed / max($rows, 1)) * 100, 1) : 0;
    $skipPct    = $rows > 0 ? round(($skipped / max($rows, 1)) * 100, 1) : 0;

    $failuresSimple = session('failures_simple') ?? session('investors_ledger_import.failures_simple') ?? [];
    if ($failuresSimple instanceof \Illuminate\Support\Collection) {
      $failuresSimple = $failuresSimple->all();
    }
  @endphp

  @if ($rows || $changed || $skipped)
    <div class="row g-3 mb-4">
      <div class="col-12 col-md-4">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-primary-subtle text-primary"><i class="bi bi-table"></i></div>
            <div class="flex-grow-1">
              <div class="text-muted small">@lang('investors::investors_import.Total Rows')</div>
              <div class="fs-4 fw-bold">{{ number_format($rows) }}</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-success-subtle text-success"><i class="bi bi-check2"></i></div>
            <div class="flex-grow-1">
              <div class="text-muted small">@lang('investors::investors_import.Saved')</div>
              <div class="fs-4 fw-bold">{{ number_format($changed) }}</div>
              <div class="text-success small">@lang('investors::investors_import.Success Rate'): {{ $successPct }}%</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="flex-grow-1">
              <div class="text-muted small">@lang('investors::investors_import.Skipped')</div>
              <div class="fs-4 fw-bold">{{ number_format($skipped) }}</div>
              <div class="text-warning small">@lang('investors::investors_import.Rate'): {{ $skipPct }}%</div>
            </div>
            @if ($hasFailures)
              <span class="badge rounded-pill bg-warning-subtle text-warning border">{{ $failuresCount }} @lang('investors::investors_import.Validation Errors')</span>
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
          <div class="fw-semibold mb-1">@lang('investors::investors_import.Errors during read/save:')</div>
          <ul class="mb-0">
            @foreach ($errorsSimple as $msg) <li>{{ $msg }}</li> @endforeach
          </ul>
        </div>
      </div>
    </div>
  @endif

  {{-- Upload form --}}
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form action="{{ route('investors.ledger.import') }}" method="POST" enctype="multipart/form-data" class="row g-3">
        @csrf

        <div class="col-12">
          <div id="dropzone" class="dz border border-2 border-dashed rounded-3 p-4 text-center position-relative overflow-hidden">
            <i class="bi bi-file-earmark-arrow-up fs-1 d-block mb-2 text-primary"></i>
            <div class="mb-2 fw-semibold">@lang('investors::investors_import.Drag file here or click to choose')</div>
            <div class="text-muted small mb-3">@lang('investors::investors_import.Excel/CSV only — validation before save')</div>
            <input id="fileInput" type="file" name="file"
                   class="position-absolute w-100 h-100 top-0 start-0 opacity-0"
                   accept=".xlsx,.xls,.csv" required>
            <div class="small">
              <span class="text-secondary">@lang('investors::investors_import.Selected file:')</span>
              <span id="fileName" class="fw-semibold">—</span>
              <span id="fileMeta" class="text-muted"></span>
            </div>
            <div id="fileError" class="text-danger small mt-1 d-none"></div>
          </div>
        </div>

        <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
          <button id="submitBtn" class="btn btn-primary" disabled>
            <i class="bi bi-upload me-1"></i> @lang('investors::investors_import.Import Now')
          </button>

          @if ($hasFailures && Route::has('investors.ledger.import.failures.fix'))
            <a class="btn btn-warning" href="{{ route('investors.ledger.import.failures.fix') }}">
              <i class="bi bi-wrench-adjustable me-1"></i> @lang('investors::investors_import.Download file to fix rows')
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  {{-- Failures table --}}
  @if ($hasFailures)
    <div class="card border-0 shadow-sm">
      <div class="card-header d-flex align-items-center bg-white">
        <i class="bi bi-list-check me-2"></i>
        <span>@lang('investors::investors_import.Validation Errors')</span>
        <span class="badge rounded-pill text-bg-danger ms-2">{{ $failuresCount }}</span>
        <button class="btn btn-sm btn-outline-secondary ms-auto"
                data-bs-toggle="collapse" data-bs-target="#failuresTable" aria-expanded="true">
          @lang('investors::investors_import.Show/Hide')
        </button>
      </div>

      <div id="failuresTable" class="collapse show">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-striped table-hover align-middle mb-0">
              <thead class="table-light sticky-top">
                <tr>
                  <th style="width:110px">@lang('investors::investors_import.Row Number')</th>
                  <th style="width:220px">@lang('investors::investors_import.Field')</th>
                  <th>@lang('investors::investors_import.Messages')</th>
                  <th style="min-width:260px">@lang('investors::investors_import.Values')</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($failuresBag as $failure)
                  @php
                    $rowNum = is_object($failure) && method_exists($failure, 'row') ? (int) $failure->row() : (int) ($failure['row'] ?? 0);
                    $attrRaw = is_object($failure) && method_exists($failure, 'attribute') ? $failure->attribute() : ($failure['attribute'] ?? '');
                    $messages = is_object($failure) && method_exists($failure, 'errors') ? (array) $failure->errors() : (array) ($failure['messages'] ?? $failure['errors'] ?? []);
                    $values = is_object($failure) && method_exists($failure, 'values') ? $failure->values() : ($failure['values'] ?? []);

                    $attrLabel = function ($attr) {
                      if (is_array($attr)) {
                        return collect($attr)->map(fn($item) => \App\Support\ExcelHeadingLocalizer::translate($item))->implode(', ');
                      }

                      $attr = (string) $attr;
                      return $attr !== ''
                        ? \App\Support\ExcelHeadingLocalizer::translate($attr)
                        : '—';
                    };
                  @endphp
                  <tr>
                    <td class="text-muted">{{ $rowNum }}</td>
                    <td>{{ $attrLabel($attrRaw) }}</td>
                    <td>
                      @if (count($messages))
                        <ul class="mb-0 ps-3">
                          @foreach ($messages as $m) <li>{{ $m }}</li> @endforeach
                        </ul>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                    <td class="text-break">
                      <code class="small code-wrap">{{ json_encode($values, JSON_UNESCAPED_UNICODE) }}</code>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="p-3 text-muted small">
            @lang('investors::investors_import.Correct rows above then re-upload. Prefer using the “Download file to fix rows” button.')
          </div>
        </div>
      </div>
    </div>
  @endif

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
  .dz.dragover { background-color: rgba(13,110,253,0.08); border-color: rgba(13,110,253,0.5); }
  .code-wrap { white-space: normal; word-break: break-all; }
  .border-dashed { border-style: dashed !important; }
  .kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
  }
</style>
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

  const MAX_SIZE = 10 * 1024 * 1024; // 10MB
  const okExt = ['xlsx','xls','csv'];

  function fmtSize(bytes){
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024*1024) return (bytes/1024).toFixed(1) + ' KB';
    return (bytes/1024/1024).toFixed(1) + ' MB';
  }

  function validate(file){
    err.classList.add('d-none');
    err.textContent = '';
    btn.disabled = true;
    if (!file) return;
    const ext = (file.name.split('.').pop() || '').toLowerCase();
    if (!okExt.includes(ext)) {
      err.textContent = @js(__('investors::investor_ledger_import.Unsupported file format. Allowed: xlsx, xls, csv'));
      err.classList.remove('d-none');
      return;
    }
    if (file.size > MAX_SIZE) {
      err.textContent = @js(__('investors::investor_ledger_import.File size exceeds 10MB.'));
      err.classList.remove('d-none');
      return;
    }
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
        name.textContent = f.name;
        meta.textContent = ' (' + fmtSize(f.size) + ')';
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
