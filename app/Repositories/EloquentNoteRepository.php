<?php

namespace App\Repositories;

use App\Models\Note;
use App\Models\User;

class EloquentNoteRepository implements NoteRepositoryInterface
{
    public function find(int|string $id): ?array
    {
        $note = Note::with(['folder', 'author', 'lastEditor'])->find($id);

        return $note ? $this->serialize($note) : null;
    }

    public function findBySlug(string $slug): ?array
    {
        $note = Note::with(['folder', 'author', 'lastEditor'])->where('slug', $slug)->first();

        return $note ? $this->serialize($note) : null;
    }

    public function create(array $data): array
    {
        $note = Note::create($data);
        $note->load(['folder', 'author', 'lastEditor']);

        return $this->serialize($note);
    }

    public function update(int|string $id, array $data): array
    {
        $note = Note::findOrFail($id);
        $note->update($data);
        $note->load(['folder', 'author', 'lastEditor']);

        return $this->serialize($note);
    }

    public function delete(int|string $id): void
    {
        Note::findOrFail($id)->delete();
    }

    public function search(User $user, string $query, int $limit): array
    {
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $query).'%';

        return Note::visibleTo($user)
            ->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)->orWhere('body', 'like', $like);
            })
            ->with('folder:id,name')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['id', 'title', 'slug', 'folder_id', 'body', 'is_private', 'updated_at'])
            ->map(function (Note $n) use ($query) {
                return [
                    'id' => $n->id,
                    'title' => $n->title,
                    'slug' => $n->slug,
                    'folder' => $n->folder ? ['id' => $n->folder->id, 'name' => $n->folder->name] : null,
                    'snippet' => $this->snippet((string) $n->body, $query),
                    'is_private' => (bool) $n->is_private,
                    'updated_at' => $n->updated_at?->toIso8601String(),
                ];
            })
            ->all();
    }

    public function recentForUser(User $user, int $limit): array
    {
        return Note::visibleTo($user)
            ->with(['folder:id,name', 'lastEditor:id,name'])
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['id', 'title', 'slug', 'folder_id', 'is_private', 'user_id', 'updated_by', 'updated_at'])
            ->map(fn (Note $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'slug' => $n->slug,
                'is_private' => (bool) $n->is_private,
                'folder' => $n->folder ? ['id' => $n->folder->id, 'name' => $n->folder->name] : null,
                'last_editor' => $n->lastEditor ? ['id' => $n->lastEditor->id, 'name' => $n->lastEditor->name] : null,
                'updated_at' => $n->updated_at?->toIso8601String(),
            ])
            ->all();
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

        return ($start > 0 ? '...' : '').$excerpt.(mb_strlen($body) > $start + 160 ? '...' : '');
    }
}
