<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Folder;
use App\Models\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function create(Request $request): Response
    {
        $folderId = $request->integer('folder_id') ?: null;

        return Inertia::render('notes/create', [
            'folder_id' => $folderId,
            'folders' => Folder::orderBy('name')->get(['id', 'name', 'parent_id']),
        ]);
    }

    public function store(StoreNoteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $data['body'] ??= '';

        $note = Note::create($data);

        return redirect()->route('notes.edit', $note)->with('success', 'Note created.');
    }

    public function show(Note $note): Response
    {
        $this->authorize('view', $note);

        $note->load(['folder', 'author', 'lastEditor']);

        return Inertia::render('notes/show', [
            'note' => $this->serialize($note),
        ]);
    }

    public function edit(Note $note): Response
    {
        $this->authorize('view', $note);

        $note->load(['folder', 'author', 'lastEditor']);

        return Inertia::render('notes/edit', [
            'note' => $this->serialize($note),
            'folders' => Folder::orderBy('name')->get(['id', 'name', 'parent_id']),
        ]);
    }

    public function update(UpdateNoteRequest $request, Note $note): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;
        $note->update($data);

        return back()->with('success', 'Note saved.');
    }

    public function destroy(Request $request, Note $note): RedirectResponse
    {
        $this->authorize('delete', $note);
        $note->delete();

        return redirect()->route('dashboard')->with('success', 'Note deleted.');
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        if ($q === '') {
            return response()->json(['results' => []]);
        }

        $user = $request->user();
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';

        $results = Note::visibleTo($user)
            ->where(function ($query) use ($like) {
                $query->where('title', 'like', $like)->orWhere('body', 'like', $like);
            })
            ->with('folder:id,name')
            ->orderByDesc('updated_at')
            ->limit(25)
            ->get(['id', 'title', 'slug', 'folder_id', 'body', 'is_private', 'updated_at'])
            ->map(function (Note $n) use ($q) {
                $snippet = $this->snippet((string) $n->body, $q);

                return [
                    'id' => $n->id,
                    'title' => $n->title,
                    'slug' => $n->slug,
                    'folder' => $n->folder ? ['id' => $n->folder->id, 'name' => $n->folder->name] : null,
                    'snippet' => $snippet,
                    'is_private' => (bool) $n->is_private,
                    'updated_at' => $n->updated_at?->toIso8601String(),
                ];
            });

        return response()->json(['results' => $results]);
    }

    private function serialize(Note $note): array
    {
        return [
            'id' => $note->id,
            'title' => $note->title,
            'slug' => $note->slug,
            'body' => (string) $note->body,
            'is_private' => (bool) $note->is_private,
            'folder_id' => $note->folder_id,
            'folder' => $note->folder ? ['id' => $note->folder->id, 'name' => $note->folder->name] : null,
            'user_id' => $note->user_id,
            'author' => $note->author ? ['id' => $note->author->id, 'name' => $note->author->name] : null,
            'last_editor' => $note->lastEditor ? ['id' => $note->lastEditor->id, 'name' => $note->lastEditor->name] : null,
            'created_at' => $note->created_at?->toIso8601String(),
            'updated_at' => $note->updated_at?->toIso8601String(),
        ];
    }

    private function snippet(string $body, string $query): string
    {
        if ($body === '') {
            return '';
        }
        $pos = stripos($body, $query);
        if ($pos === false) {
            return mb_substr($body, 0, 140);
        }
        $start = max(0, $pos - 40);
        $excerpt = mb_substr($body, $start, 160);

        return ($start > 0 ? '…' : '').$excerpt.(mb_strlen($body) > $start + 160 ? '…' : '');
    }
}
