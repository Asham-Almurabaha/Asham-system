@php
    $notesCollection = $contract->notes ?? collect();
    $notesCollection = $notesCollection->values();

    $defaultNoteDate = old('note_date', now()->toDateString());
    $defaultNote = old('note');
@endphp

<div class="card shadow-sm mb-4">
    <div class="card-header bg-info text-white">
        <strong>{{ __('contracts::notes.notes') }}</strong>
    </div>
    <div class="card-body">
        <form action="{{ route('contracts.notes.store', $contract) }}" method="POST" class="mb-4">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="contract-note-date-{{ $contract->id }}" class="form-label">{{ __('contracts::notes.note_date') }}</label>
                    <input
                        type="date"
                        name="note_date"
                        id="contract-note-date-{{ $contract->id }}"
                        value="{{ $defaultNoteDate }}"
                        class="form-control @error('note_date') is-invalid @enderror"
                        required
                    >
                    @error('note_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-7">
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
                <div class="col-md-2 d-flex align-items-end">
                    <x-button.action type="submit" variant="primary" class="w-100">
                        <i class="bi bi-plus-lg me-1"></i> {{ __('contracts::notes.add_note') }}
                    </x-button.action>
                </div>
            </div>
        </form>

        @if ($notesCollection->isNotEmpty())
            <x-table head-class="table-light" striped>
                <x-slot name="head">
                    <tr>
                        <th style="width: 60px;" class="text-center">#</th>
                        <th>{{ __('contracts::notes.contract_number') }}</th>
                        <th>{{ __('contracts::notes.note_date') }}</th>
                        <th>{{ __('contracts::notes.note') }}</th>
                        <th>{{ __('contracts::notes.created_at') }}</th>
                        <th class="text-center">{{ __('contracts::notes.actions') }}</th>
                    </tr>
                </x-slot>

                @foreach ($notesCollection as $index => $note)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $contract->contract_number }}</td>
                        <td>{{ optional($note->note_date)->format('Y-m-d') ?? '—' }}</td>
                        <td>{{ $note->note }}</td>
                        <td>{{ optional($note->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="text-center">
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
            <div class="text-muted">{{ __('contracts::notes.no_results') }}</div>
        @endif
    </div>
</div>
