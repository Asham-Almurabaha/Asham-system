@props([
    'action',
    'dragText',
    'helpText',
    'submitText',
    'selectedLabel' => 'Selected file:',
    'accept' => '.xlsx,.xls,.csv',
    'buttonIcon' => 'bi-upload',
    'idPrefix' => 'import',
    'method' => 'POST',
    'maxSize' => 10 * 1024 * 1024,
    'invalidFormatMessage' => 'Unsupported file format. Allowed: xlsx, xls, csv',
    'tooLargeMessage' => 'File size exceeds 10MB.',
])

@php
    $formMethod = strtoupper($method);
    $dropzoneId = $idPrefix . '-dropzone';
    $inputId = $idPrefix . '-file-input';
    $nameId = $idPrefix . '-file-name';
    $metaId = $idPrefix . '-file-meta';
    $errorId = $idPrefix . '-file-error';
    $submitId = $idPrefix . '-submit';

    $extensions = array_values(array_filter(array_map(
        static fn (string $ext) => ltrim(trim($ext), '.'),
        explode(',', (string) $accept)
    )));
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="row g-3">
    @csrf
    @if ($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <div class="col-12">
        <div id="{{ $dropzoneId }}" class="dz border border-2 border-dashed rounded-3 p-4 text-center position-relative overflow-hidden">
            <i class="bi bi-file-earmark-arrow-up fs-1 d-block mb-2 text-primary"></i>
            <div class="mb-2 fw-semibold">{!! $dragText !!}</div>
            <div class="text-muted small mb-3">{!! $helpText !!}</div>
            <input
                id="{{ $inputId }}"
                type="file"
                name="file"
                class="position-absolute w-100 h-100 top-0 start-0 opacity-0"
                accept="{{ $accept }}"
                required
            >
            <div class="small">
                <span class="text-secondary">{!! $selectedLabel !!}</span>
                <span id="{{ $nameId }}" class="fw-semibold">—</span>
                <span id="{{ $metaId }}" class="text-muted"></span>
            </div>
            <div id="{{ $errorId }}" class="text-danger small mt-1 d-none"></div>
        </div>
    </div>

    <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
        <x-button.action id="{{ $submitId }}" type="submit" variant="primary" :disabled="true">
            @if (!empty($buttonIcon))
                <i class="bi {{ $buttonIcon }} me-1"></i>
            @endif
            {{ $submitText }}
        </x-button.action>

        {{ $slot }}
    </div>
</form>

@push('scripts')
    <script>
        (function () {
            const dropzone = document.getElementById(@js($dropzoneId));
            const fileInput = document.getElementById(@js($inputId));
            const fileName = document.getElementById(@js($nameId));
            const fileMeta = document.getElementById(@js($metaId));
            const fileError = document.getElementById(@js($errorId));
            const submitBtn = document.getElementById(@js($submitId));

            if (!dropzone || !fileInput || !fileName || !fileMeta || !fileError || !submitBtn) {
                return;
            }

            const okExt = @json($extensions);
            const maxSize = {{ (int) $maxSize }};
            const invalidMessage = @js($invalidFormatMessage);
            const tooLargeMessage = @js($tooLargeMessage);

            function formatSize(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                return (bytes / 1024 / 1024).toFixed(1) + ' MB';
            }

            function validate(file) {
                fileError.classList.add('d-none');
                fileError.textContent = '';
                submitBtn.disabled = true;

                if (!file) {
                    fileName.textContent = '—';
                    fileMeta.textContent = '';
                    return;
                }

                const ext = (file.name.split('.').pop() || '').toLowerCase();
                if (okExt.length && !okExt.includes(ext)) {
                    fileError.textContent = invalidMessage;
                    fileError.classList.remove('d-none');
                    return;
                }

                if (maxSize > 0 && file.size > maxSize) {
                    fileError.textContent = tooLargeMessage;
                    fileError.classList.remove('d-none');
                    return;
                }

                submitBtn.disabled = false;
            }

            ['dragenter', 'dragover'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    dropzone.classList.add('dragover');
                });
            });

            ['dragleave', 'drop'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    dropzone.classList.remove('dragover');
                });
            });

            dropzone.addEventListener('drop', function (event) {
                if (event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files.length) {
                    fileInput.files = event.dataTransfer.files;
                    const file = event.dataTransfer.files[0];
                    fileName.textContent = file.name;
                    fileMeta.textContent = ' (' + formatSize(file.size) + ')';
                    validate(file);
                } else {
                    fileInput.value = '';
                    validate(null);
                }
            });

            fileInput.addEventListener('change', function () {
                const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                fileName.textContent = file ? file.name : '—';
                fileMeta.textContent = file ? ' (' + formatSize(file.size) + ')' : '';
                validate(file);
            });
        })();
    </script>
@endpush
