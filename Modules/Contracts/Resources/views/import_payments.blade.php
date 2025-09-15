{{-- resources/views/contracts/import_payments.blade.php --}}
@extends('layouts.master')

@section('title', 'استيراد سدادات العقود من Excel')

@section('content')
<div class="container-xxl py-4" dir="rtl">

  <div class="rounded-3 p-4 mb-4 position-relative overflow-hidden bg-light border">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-4 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:54px;height:54px;">
        <i class="bi bi-cloud-arrow-up fs-3"></i>
      </div>
      <div>
        <h1 class="h4 mb-1">استيراد سدادات العقود</h1>
        <p class="text-muted mb-0">
          ارفع ملف Excel أو CSV يحتوي على العمود <code>contract_number</code>
          مع أعمدة السدادات مثل عمود واحد <code>payments</code> بصيغة <code>date:amount|date:amount</code>
          أو الأعمدة المزدوجة <code>paymentN_amount + paymentN_date</code> حتى 18 قسطاً،
          ويقبل أيضاً <code>down_payment(+_date)</code>.
          الصف الأول عناوين.
        </p>
      </div>
      <div class="ms-auto d-none d-md-block">
        <a href="{{ route('contracts.export.payments') }}" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-download me-1"></i> تصدير حالة الأقساط
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
    $failuresBag = session('contracts_payments_import.failures_simple') ?? [];
    if ($failuresBag instanceof \Illuminate\Support\Collection) {
        $failuresBag = $failuresBag->all();
    }
    $summary      = session('summary') ?: session('contracts_payments_import.summary') ?: [];
    $errorsSimple = session('errors_simple') ?? session('contracts_payments_import.errors_simple') ?? [];
    $rows     = (int)($summary['rows']     ?? 0);
    $inserted = (int)($summary['inserted'] ?? 0);
    $skipped  = (int)($summary['skipped']  ?? 0);
    $changed  = (int)($summary['changed']  ?? $inserted);
    $failuresCount = is_array($failuresBag) ? count($failuresBag) : (is_object($failuresBag) && method_exists($failuresBag,'count') ? $failuresBag->count() : 0);
    $hasFailures = $failuresCount > 0;
  @endphp

  @if ($rows || $changed || $skipped)
    <div class="row g-3 mb-4">
      <div class="col-12 col-md-4">
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
      <div class="col-12 col-md-4">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-success-subtle text-success"><i class="bi bi-check2"></i></div>
            <div class="flex-grow-1">
              <div class="text-muted small">تم تسجيلها</div>
              <div class="fs-4 fw-bold">{{ number_format($changed) }}</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="card shadow-sm h-100 border-0">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="flex-grow-1">
              <div class="text-muted small">متخطٍّ</div>
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
          <div class="fw-semibold mb-1">أخطاء أثناء القراءة/الحفظ:</div>
          <ul class="mb-0">@foreach ($errorsSimple as $msg) <li>{{ $msg }}</li> @endforeach</ul>
        </div>
      </div>
    </div>
  @endif

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form action="{{ route('contracts.import.payments') }}" method="POST" enctype="multipart/form-data" class="row g-3">
        @csrf
        <div class="col-12">
          <div id="dropzone" class="dz border border-2 border-dashed rounded-3 p-4 text-center">
            <i class="bi bi-file-earmark-arrow-up fs-1 d-block mb-2 text-primary"></i>
            <div class="mb-2 fw-semibold">اسحب الملف هنا أو اضغط للاختيار</div>
            <div class="text-muted small mb-3">Excel/CSV فقط — تحقق قبل الحفظ</div>
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
            <i class="bi bi-upload me-1"></i> ابدأ الاستيراد
          </button>
        </div>
      </form>
    </div>
  </div>

  @if ($hasFailures)
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <h5 class="card-title mb-3">أخطاء التحقق ({{ $failuresCount }})</h5>
        <div class="table-responsive">
          @php
            $normalizeFailureValue = function ($value, string $separator = ' | ') {
                if ($value instanceof \Illuminate\Support\Collection) {
                    $value = $value->all();
                }

                if (is_array($value)) {
                    $stringified = array_map(function ($item) {
                        if ($item instanceof \Illuminate\Support\Collection) {
                            $item = $item->all();
                        }

                        if (is_array($item)) {
                            $encoded = json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            return $encoded === false ? '' : $encoded;
                        }

                        if ($item instanceof \Stringable || (is_object($item) && method_exists($item, '__toString'))) {
                            return (string) $item;
                        }

                        if (is_scalar($item)) {
                            return (string) $item;
                        }

                        if ($item === null) {
                            return '';
                        }

                        $encoded = json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                        return $encoded === false ? '' : $encoded;
                    }, $value);

                    return implode($separator, $stringified);
                }

                if ($value instanceof \Stringable || (is_object($value) && method_exists($value, '__toString'))) {
                    return (string) $value;
                }

                if (is_scalar($value)) {
                    return (string) $value;
                }

                if ($value === null) {
                    return '';
                }

                $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                return $encoded === false ? '' : $encoded;
            };
          @endphp
          <table class="table table-sm table-bordered mb-0">
            <thead class="table-light">
              <tr>
                <th>الصف</th>
                <th>الحقل</th>
                <th>الرسائل</th>
                <th>القيم</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($failuresBag as $f)
                @php
                  $rowRaw = is_array($f)
                      ? ($f['row'] ?? null)
                      : (method_exists($f, 'row')
                          ? $f->row()
                          : ($f->row ?? null));

                  $attributeRaw = is_array($f)
                      ? ($f['attribute'] ?? null)
                      : (method_exists($f, 'attribute')
                          ? $f->attribute()
                          : ($f->attribute ?? null));

                  $messagesRaw = null;
                  if (is_array($f)) {
                      $messagesRaw = $f['messages'] ?? ($f['errors'] ?? null);
                  } else {
                      if (method_exists($f, 'errors')) {
                          $messagesRaw = $f->errors();
                      } elseif (method_exists($f, 'messages')) {
                          $messagesRaw = $f->messages();
                      } else {
                          $messagesRaw = $f->messages ?? ($f->errors ?? null);
                      }
                  }

                  $valuesRaw = is_array($f)
                      ? ($f['values'] ?? null)
                      : (method_exists($f, 'values')
                          ? $f->values()
                          : ($f->values ?? null));

                  $rowValue = $normalizeFailureValue($rowRaw, ', ');
                  $attributeValue = $normalizeFailureValue($attributeRaw, ', ');
                  $messagesValue = $normalizeFailureValue($messagesRaw, ' | ');
                  $valuesValue = $normalizeFailureValue($valuesRaw, ', ');
                @endphp
                <tr>
                  <td>{{ $rowValue }}</td>
                  <td>{{ $attributeValue }}</td>
                  <td>{{ $messagesValue }}</td>
                  <td>{{ $valuesValue }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endif

</div>
@endsection

@push('scripts')
<script>
(function () {
    const input = document.getElementById('fileInput');
    const button = document.getElementById('submitBtn');
    input.addEventListener('change', () => {
        button.disabled = input.files.length === 0;
    });
})();
</script>
@endpush
