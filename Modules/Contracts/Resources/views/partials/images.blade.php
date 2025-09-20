{{-- Contract: Images --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <strong class="mb-0">{{ __('Images') }}</strong>
        <x-button type="button" variant="light" size="sm" data-bs-toggle="modal" data-bs-target="#contract-images-modal">
            <i class="bi bi-plus-circle me-1"></i>
            {{ __('Add Images') }}
        </x-button>
    </div>
    <div class="card-body p-0">
        <div class="row p-3 g-3 justify-content-center">
            @if($contract->contract_image)
                <div class="col-12 col-md-4 text-center d-flex flex-column align-items-center">
                    <div class="fw-semibold text-muted mb-2">{{ __('Contract Image') }}</div>
                    <img src="{{ asset('storage/'.$contract->contract_image) }}" class="img-fluid rounded shadow-sm mx-auto" style="max-height: 260px;" alt="{{ __('Contract Image') }}">
                </div>
            @endif

            @if($contract->contract_customer_image)
                <div class="col-12 col-md-4 text-center d-flex flex-column align-items-center">
                    <div class="fw-semibold text-muted mb-2">{{ __('Customer Contract Image') }}</div>
                    <img src="{{ asset('storage/'.$contract->contract_customer_image) }}" class="img-fluid rounded shadow-sm mx-auto" style="max-height: 260px;" alt="{{ __('Customer Contract Image') }}">
                </div>
            @endif

            @if($contract->contract_guarantor_image)
                <div class="col-12 col-md-4 text-center d-flex flex-column align-items-center">
                    <div class="fw-semibold text-muted mb-2">{{ __('Guarantor Contract Image') }}</div>
                    <img src="{{ asset('storage/'.$contract->contract_guarantor_image) }}" class="img-fluid rounded shadow-sm mx-auto" style="max-height: 260px;" alt="{{ __('Guarantor Contract Image') }}">
                </div>
            @endif

            @if(!$contract->contract_image && !$contract->contract_customer_image && !$contract->contract_guarantor_image)
                <div class="col-12 text-muted text-center">{{ __('No contract images uploaded.') }}</div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="contract-images-modal" tabindex="-1" aria-labelledby="contract-images-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('contracts.images.update', $contract) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="modal-header">
                    <h5 class="modal-title" id="contract-images-modal-label">{{ __('Update Contract Images') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-info d-flex align-items-center gap-2" role="alert">
                        <i class="bi bi-info-circle-fill fs-5"></i>
                        <div>{{ __('Review the selected images before saving.') }}</div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-4">
                            <label for="contract_image" class="form-label">{{ __('Contract Image') }}</label>
                            <input type="file" name="contract_image" id="contract_image" class="form-control @error('contract_image') is-invalid @enderror" accept="image/*">
                            @error('contract_image')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror

                            <div class="mt-3">
                                @if($contract->contract_image)
                                    <div class="text-muted small mb-1">{{ __('Current Image:') }}</div>
                                    <img src="{{ asset('storage/'.$contract->contract_image) }}" alt="{{ __('Contract Image') }}" class="img-fluid rounded border mb-2">
                                @endif
                                <div class="text-muted small mb-1">{{ __('Preview after upload:') }}</div>
                                <div class="border rounded p-2 text-center bg-light">
                                    <img data-preview-for="contract_image" src="#" alt="{{ __('Image Preview') }}" class="img-fluid rounded d-none">
                                    <div class="text-muted small" data-placeholder-for="contract_image">{{ __('Preview will appear here after selecting a file.') }}</div>
                                </div>
                                <x-button type="button" variant="danger" :outline="true" size="sm" class="mt-2 w-100 d-none" data-remove-for="contract_image">
                                    <i class="bi bi-x-circle me-1"></i>
                                    {{ __('Remove Selected Image') }}
                                </x-button>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="contract_customer_image" class="form-label">{{ __('Customer Contract Image') }}</label>
                            <input type="file" name="contract_customer_image" id="contract_customer_image" class="form-control @error('contract_customer_image') is-invalid @enderror" accept="image/*">
                            @error('contract_customer_image')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror

                            <div class="mt-3">
                                @if($contract->contract_customer_image)
                                    <div class="text-muted small mb-1">{{ __('Current Image:') }}</div>
                                    <img src="{{ asset('storage/'.$contract->contract_customer_image) }}" alt="{{ __('Customer Contract Image') }}" class="img-fluid rounded border mb-2">
                                @endif
                                <div class="text-muted small mb-1">{{ __('Preview after upload:') }}</div>
                                <div class="border rounded p-2 text-center bg-light">
                                    <img data-preview-for="contract_customer_image" src="#" alt="{{ __('Image Preview') }}" class="img-fluid rounded d-none">
                                    <div class="text-muted small" data-placeholder-for="contract_customer_image">{{ __('Preview will appear here after selecting a file.') }}</div>
                                </div>
                                <x-button type="button" variant="danger" :outline="true" size="sm" class="mt-2 w-100 d-none" data-remove-for="contract_customer_image">
                                    <i class="bi bi-x-circle me-1"></i>
                                    {{ __('Remove Selected Image') }}
                                </x-button>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="contract_guarantor_image" class="form-label">{{ __('Guarantor Contract Image') }}</label>
                            <input type="file" name="contract_guarantor_image" id="contract_guarantor_image" class="form-control @error('contract_guarantor_image') is-invalid @enderror" accept="image/*">
                            @error('contract_guarantor_image')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror

                            <div class="mt-3">
                                @if($contract->contract_guarantor_image)
                                    <div class="text-muted small mb-1">{{ __('Current Image:') }}</div>
                                    <img src="{{ asset('storage/'.$contract->contract_guarantor_image) }}" alt="{{ __('Guarantor Contract Image') }}" class="img-fluid rounded border mb-2">
                                @endif
                                <div class="text-muted small mb-1">{{ __('Preview after upload:') }}</div>
                                <div class="border rounded p-2 text-center bg-light">
                                    <img data-preview-for="contract_guarantor_image" src="#" alt="{{ __('Image Preview') }}" class="img-fluid rounded d-none">
                                    <div class="text-muted small" data-placeholder-for="contract_guarantor_image">{{ __('Preview will appear here after selecting a file.') }}</div>
                                </div>
                                <x-button type="button" variant="danger" :outline="true" size="sm" class="mt-2 w-100 d-none" data-remove-for="contract_guarantor_image">
                                    <i class="bi bi-x-circle me-1"></i>
                                    {{ __('Remove Selected Image') }}
                                </x-button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <x-button type="button" variant="secondary" :outline="true" data-bs-dismiss="modal">{{ __('Cancel') }}</x-button>
                    <x-button type="submit" variant="primary" :outline="true">{{ __('Save') }}</x-button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('contract-images-modal');
    if (!modalEl) { return; }

    const hasErrors = @json($errors->has('contract_image') || $errors->has('contract_customer_image') || $errors->has('contract_guarantor_image'));

    const getModalInstance = () => {
        if (typeof bootstrap === 'undefined' || !bootstrap.Modal) { return null; }
        return bootstrap.Modal.getOrCreateInstance(modalEl);
    };

    if (hasErrors) {
        const modalInstance = getModalInstance();
        if (modalInstance) { modalInstance.show(); }
    }

    const toggleRemoveButton = (input, shouldShow) => {
        const removeButton = modalEl.querySelector(`[data-remove-for="${input.id}"]`);
        if (!removeButton) { return; }
        removeButton.classList.toggle('d-none', !shouldShow);
    };

    const updatePreview = (input) => {
        const preview = modalEl.querySelector(`[data-preview-for="${input.id}"]`);
        const placeholder = modalEl.querySelector(`[data-placeholder-for="${input.id}"]`);
        if (!preview) { return; }

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = (event) => {
                preview.src = event.target.result;
                preview.classList.remove('d-none');
                if (placeholder) { placeholder.classList.add('d-none'); }
                toggleRemoveButton(input, true);
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.src = '#';
            preview.classList.add('d-none');
            if (placeholder) { placeholder.classList.remove('d-none'); }
            toggleRemoveButton(input, false);
        }
    };

    const resetPreview = (input) => {
        const preview = modalEl.querySelector(`[data-preview-for="${input.id}"]`);
        const placeholder = modalEl.querySelector(`[data-placeholder-for="${input.id}"]`);
        if (preview) {
            preview.src = '#';
            preview.classList.add('d-none');
        }
        if (placeholder) {
            placeholder.classList.remove('d-none');
        }
        toggleRemoveButton(input, false);
    };

    const fileInputs = modalEl.querySelectorAll('input[type="file"]');
    fileInputs.forEach((input) => {
        input.addEventListener('change', () => updatePreview(input));
    });

    const removeButtons = modalEl.querySelectorAll('[data-remove-for]');
    removeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-remove-for');
            if (!targetId) { return; }
            const input = modalEl.querySelector(`#${targetId}`);
            if (!input) { return; }
            input.value = '';
            resetPreview(input);
            input.dispatchEvent(new Event('change'));
        });
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        fileInputs.forEach((input) => {
            input.value = '';
            resetPreview(input);
        });
    });
});
</script>
@endpush
