<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NoteController extends Controller
{
    public function index(Request $request): View
    {
        $notes = Note::query()
            ->where('user_id', Auth::id())
            ->orderByRaw('completed_at IS NULL DESC')
            ->orderByDesc('reminder_at')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('notes.index', compact('notes'));
    }

    public function create(): View
    {
        return view('notes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        Auth::user()->notes()->create($data);

        return redirect()
            ->route('notes.index')
            ->with('success', __('notes.messages.created'));
    }

    public function edit(Note $note): View
    {
        $this->authorizeNote($note);

        return view('notes.edit', compact('note'));
    }

    public function update(Request $request, Note $note): RedirectResponse
    {
        $this->authorizeNote($note);

        $data = $this->validatedData($request);

        $note->update($data);

        return redirect()
            ->route('notes.index')
            ->with('success', __('notes.messages.updated'));
    }

    public function destroy(Note $note): RedirectResponse
    {
        $this->authorizeNote($note);

        $note->delete();

        return redirect()
            ->route('notes.index')
            ->with('success', __('notes.messages.deleted'));
    }

    public function complete(Note $note): RedirectResponse
    {
        $this->authorizeNote($note);

        $note->update(['completed_at' => Carbon::now()]);

        return redirect()
            ->route('notes.index')
            ->with('success', __('notes.messages.completed'));
    }

    public function reopen(Note $note): RedirectResponse
    {
        $this->authorizeNote($note);

        $note->update(['completed_at' => null]);

        return redirect()
            ->route('notes.index')
            ->with('success', __('notes.messages.reopened'));
    }

    protected function validatedData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'reminder_at' => ['nullable', 'date'],
        ]);

        if (!empty($data['reminder_at'])) {
            $data['reminder_at'] = Carbon::parse($data['reminder_at']);
        }

        return $data;
    }

    protected function authorizeNote(Note $note): void
    {
        if ($note->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
