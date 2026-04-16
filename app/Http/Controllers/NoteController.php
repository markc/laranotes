<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Folder;
use App\Models\Note;
use App\Repositories\NoteRepositoryInterface;
use App\Services\CollabTokenService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public function __construct(
        private NoteRepositoryInterface $notes,
        private CollabTokenService $collabTokenService,
    ) {}

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

        $note = $this->notes->create($data);

        return redirect()->route('notes.edit', $note['slug'])->with('success', 'Note created.');
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

        $user = request()->user();
        $canEdit = $user->can('update', $note);
        $collab = null;

        if (! $note->is_private && config('collab.secret')) {
            $collab = [
                'token' => $this->collabTokenService->mint($user->id, $note->id, $canEdit),
                'ws_url' => config('collab.ws_url', 'ws://localhost:4444')."/ws/note/{$note->id}",
                'user' => ['id' => $user->id, 'name' => $user->name],
                'can_edit' => $canEdit,
            ];
        }

        return Inertia::render('notes/edit', [
            'note' => $this->serialize($note),
            'folders' => Folder::orderBy('name')->get(['id', 'name', 'parent_id']),
            'collab' => $collab,
        ]);
    }

    public function update(UpdateNoteRequest $request, Note $note): RedirectResponse
    {
        if ($this->noteHasActiveCollabRoom($note)) {
            abort(409, 'Note is being collaboratively edited. Changes are synced automatically.');
        }

        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;
        $note->update($data);

        return back()->with('success', 'Note saved.');
    }

    private function noteHasActiveCollabRoom(Note $note): bool
    {
        $secret = config('collab.secret');
        if (! $secret) {
            return false;
        }

        try {
            $httpUrl = config('collab.http_url', 'http://localhost:4444');

            $response = Http::timeout(2)->withHeaders([
                'X-Collab-Secret' => $secret,
            ])->get("{$httpUrl}/rooms");

            if ($response->ok()) {
                $rooms = $response->json('rooms', []);

                return in_array($note->id, $rooms);
            }
        } catch (\Throwable) {
            // Collab server unreachable — allow the update
        }

        return false;
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

        $limit = app(SettingService::class)->get('search_max_results');
        $results = $this->notes->search($request->user(), $q, $limit);

        return response()->json(['results' => $results]);
    }

    private function serialize(Note $note): array
    {
        $user = request()->user();

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
            'can_edit' => $user ? $user->can('update', $note) : false,
            'can_delete' => $user ? $user->can('delete', $note) : false,
        ];
    }
}
