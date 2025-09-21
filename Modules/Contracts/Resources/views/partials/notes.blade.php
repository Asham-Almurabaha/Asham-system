@php
    $notesCollection = $contract->notes ?? collect();
    $notesCollection = $notesCollection->values();

    $defaultNoteDate = old('note_date', now()->toDateString());
    $defaultNote = old('note');
@endphp

<div class="card shadow-sm border-warning mb-4">
    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
        <strong class="mb-0">{{ __('contracts::notes.notes') }}</strong>
        <x-button.action
            type="submit"
            form="contract-add-note-form-{{ $contract->id }}"
            variant="dark"
            size="sm"
        >
            <i class="bi bi-plus-lg me-1"></i>
            {{ __('contracts::notes.add_note') }}
        </x-button.action>
    </div>
    <div class="card-body p-0">
        <div class="p-3 border-bottom">
            <form
                action="{{ route('contracts.notes.store', $contract) }}"
                method="POST"
                id="contract-add-note-form-{{ $contract->id }}"
            >
                @csrf
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="contract-note-date-{{ $contract->id }}" class="form-label">{{ __('contracts::notes.note_date') }}</label>
                        <input
                            type="date"
                            name="note_date"
                            id="contract-note-date-{{ $contract->id }}"
                            value="{{ $defaultNoteDate }}"
                            class="form-control js-date @error('note_date') is-invalid @enderror"
                            required
                        >
                        @error('note_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-9">
                        <label for="contract-note-content-{{ $contract->id }}" class="form-label">{{ __('contracts::notes.note') }}</label>
                        <textarea
                            name="note"
                            id="contract-note-content-{{ $contract->id }}"
                            class="form-control @error('note') is-invalid @enderror"
                            rows="1"
                            required>{{ $defaultNote }}</textarea>
                        @error('note')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </form>
        </div>

        @if ($notesCollection->isNotEmpty())
            <x-table head-class="table-light" striped bordered class="text-center" :hover="false">
                <x-slot name="head">
                    <tr>
                        <th style="width: 60px;" class="text-center">#</th>
                        <th>{{ __('contracts::notes.contract_number') }}</th>
                        <th>{{ __('contracts::notes.note_date') }}</th>
                        <th class="text-start">{{ __('contracts::notes.note') }}</th>
                        <th>{{ __('contracts::notes.created_at') }}</th>
                        <th class="text-center">{{ __('contracts::notes.actions') }}</th>
                    </tr>
                </x-slot>

                @foreach ($notesCollection as $index => $note)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $contract->contract_number }}</td>
                        <td>{{ optional($note->note_date)->format('Y-m-d') ?? '—' }}</td>
                        <td class="text-start">{{ $note->note }}</td>
                        <td>{{ optional($note->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>
                            <form action="{{ route('contracts.notes.destroy', [$contract, $note]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('contracts::notes.confirm_delete') }}');">
                                @csrf
                                @method('DELETE')
                                <x-button.action type="submit" variant="danger" :outline="true" size="sm">
                                    <i class="bi bi-trash"></i>
                                </x-button.action>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <div class="p-3 text-muted">{{ __('contracts::notes.no_results') }}</div>
        @endif
    </div>
</div>
