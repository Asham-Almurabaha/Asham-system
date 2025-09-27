@extends('layouts.master')

@section('title', __('notes.title'))

@section('content')
    <div class="container-xxl py-4">
        <div class="d-flex flex-wrap align-items-start gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('sidebar.Dashboard')</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ __('notes.title') }}</li>
                    </ol>
                </nav>
                <h1 class="h4 mb-1">{{ __('notes.title') }}</h1>
                <p class="text-muted mb-0">{{ __('notes.subtitle') }}</p>
            </div>

            <div class="ms-auto d-flex flex-wrap align-items-center gap-2">
                <x-button.action href="{{ route('notes.create') }}" variant="success">
                    <i class="bi bi-plus-lg me-1"></i>{{ __('notes.actions.new') }}
                </x-button.action>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <x-table head-class="table-light position-sticky top-0" class="text-center">
                <x-slot name="head">
                    <tr>
                        <th style="width:60px">#</th>
                        <th class="text-start">{{ __('notes.fields.title') }}</th>
                        <th class="text-start">{{ __('notes.fields.content') }}</th>
                        <th>{{ __('notes.fields.reminder_at') }}</th>
                        <th>{{ __('notes.fields.status') }}</th>
                        <th class="text-center">{{ __('notes.fields.actions') }}</th>
                    </tr>
                </x-slot>

                @forelse($notes as $note)
                    @php
                        $reminder = $note->reminder_at;
                        $completed = $note->completed_at !== null;
                        $now = now();
                        $statusKey = $completed
                            ? 'completed'
                            : ($reminder && $reminder->lte($now) ? 'due' : ($reminder ? 'upcoming' : 'no_reminder'));

                        $statusClasses = [
                            'completed' => 'bg-success-subtle text-success',
                            'due' => 'bg-danger-subtle text-danger',
                            'upcoming' => 'bg-info-subtle text-info',
                            'no_reminder' => 'bg-secondary-subtle text-secondary',
                        ];
                        $reminderClasses = [
                            'due' => 'bg-danger-subtle text-danger',
                            'upcoming' => 'bg-info-subtle text-info',
                            'completed' => 'bg-success-subtle text-success',
                        ];
                        $rowNumber = $loop->iteration + ($notes->currentPage() - 1) * $notes->perPage();
                    @endphp
                    <tr class="{{ $completed ? 'table-light' : '' }}">
                        <td class="text-muted">{{ $rowNumber }}</td>
                        <td class="text-start fw-semibold">
                            <a href="{{ route('notes.edit', $note) }}" class="text-decoration-none text-dark hover-primary">
                                {{ $note->title }}
                            </a>
                        </td>
                        <td class="text-start">{{ \Illuminate\Support\Str::limit($note->content, 140) }}</td>
                        <td>
                            @if($reminder)
                                <span class="badge {{ $reminderClasses[$statusKey] ?? 'bg-secondary-subtle text-secondary' }}">
                                    {{ $reminder->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                </span>
                            @else
                                <span class="text-muted">{{ __('notes.status.no_reminder') }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $statusClasses[$statusKey] ?? 'bg-secondary-subtle text-secondary' }}">{{ __('notes.status.' . $statusKey) }}</span>
                        </td>
                        <td class="text-center">
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
                        <td colspan="6" class="py-5 text-center">
                            <div class="text-muted">
                                {{ __('notes.empty') }}
                                <a href="{{ route('notes.index') }}" class="ms-1">{{ __('View All') }}</a>
                            </div>
                            <div class="mt-3">
                                <x-button.action href="{{ route('notes.create') }}" variant="success" size="sm">
                                    + {{ __('notes.actions.new') }}
                                </x-button.action>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>

        @if($notes->hasPages())
            <div class="card border-0 shadow-sm">
                <div class="card-footer bg-white border-0">
                    {{ $notes->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>
@endsection
