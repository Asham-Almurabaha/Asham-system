@php
    $isEdit = isset($note);
    $reminderValue = old('reminder_at');

    if ($reminderValue === null) {
        $reminderValue = $isEdit && $note->reminder_at
            ? $note->reminder_at->format('Y-m-d\TH:i')
            : '';
    }
@endphp

<div class="row g-3">
    <div class="col-12">
        <label for="title" class="form-label">{{ __('notes.fields.title') }}</label>
        <input type="text" name="title" id="title" value="{{ old('title', $note->title ?? '') }}" class="form-control" required>
    </div>

    <div class="col-12">
        <label for="content" class="form-label">{{ __('notes.fields.content') }}</label>
        <textarea name="content" id="content" rows="4" class="form-control">{{ old('content', $note->content ?? '') }}</textarea>
    </div>

    <div class="col-12 col-md-6">
        <label for="reminder_at" class="form-label">{{ __('notes.fields.reminder_at') }}</label>
        <input type="datetime-local" name="reminder_at" id="reminder_at" value="{{ $reminderValue }}" class="form-control js-date">
        <div class="form-text">{{ __('notes.helpers.reminder_at') }}</div>
    </div>
</div>

<div class="mt-4 d-flex justify-content-end gap-2">
    <a href="{{ route('notes.index') }}" class="btn btn-light">{{ __('notes.actions.cancel') }}</a>
    <button type="submit" class="btn btn-primary">{{ $isEdit ? __('notes.actions.update') : __('notes.actions.create') }}</button>
</div>
