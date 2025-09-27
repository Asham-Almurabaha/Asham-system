@extends('layouts.master')

@section('title', __('notes.title'))

@section('content')
    <div class="pagetitle d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-0">{{ __('notes.title') }}</h1>
            <p class="text-muted mb-0">{{ __('notes.subtitle') }}</p>
        </div>
        <a href="{{ route('notes.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>
            {{ __('notes.actions.new') }}
        </a>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('notes.fields.title') }}</th>
                            <th scope="col" class="w-50">{{ __('notes.fields.content') }}</th>
                            <th scope="col">{{ __('notes.fields.reminder_at') }}</th>
                            <th scope="col">{{ __('notes.fields.status') }}</th>
                            <th scope="col" class="text-end">{{ __('notes.fields.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notes as $note)
                            @php
                                $reminder = $note->reminder_at;
                                $completed = $note->completed_at !== null;
                                $now = now();
                                $statusKey = $completed
                                    ? 'completed'
                                    : ($reminder && $reminder->lte($now) ? 'due' : ($reminder ? 'upcoming' : 'no_reminder'));
                            @endphp
                            <tr class="{{ $completed ? 'table-light' : '' }}">
                                <td class="fw-semibold">{{ $note->title }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($note->content, 140) }}</td>
                                <td>
                                    @if($reminder)
                                        <span class="badge {{ $statusKey === 'due' ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary' }}">
                                            {{ $reminder->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                        </span>
                                    @else
                                        <span class="text-muted">{{ __('notes.status.no_reminder') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary">{{ __('notes.status.' . $statusKey) }}</span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="{{ route('notes.edit', $note) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @if(!$completed)
                                            <form action="{{ route('notes.complete', $note) }}" method="POST" onsubmit="return confirm('{{ __('notes.confirmations.complete') }}');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-check2-circle"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('notes.reopen', $note) }}" method="POST" onsubmit="return confirm('{{ __('notes.confirmations.reopen') }}');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-warning">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('notes.destroy', $note) }}" method="POST" onsubmit="return confirm('{{ __('notes.confirmations.delete') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-5 text-center">
                                    <div class="text-muted">{{ __('notes.empty') }}</div>
                                    <div class="mt-3">
                                        <x-button.action href="{{ route('notes.create') }}" variant="success" size="sm">
                                            + {{ __('notes.actions.new') }}
                                        </x-button.action>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $notes->links() }}
            </div>
        </div>
    </div>
@endsection
