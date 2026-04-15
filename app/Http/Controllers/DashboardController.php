<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $recent = Note::visibleTo($user)
            ->with(['folder:id,name', 'lastEditor:id,name'])
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get(['id', 'title', 'slug', 'folder_id', 'is_private', 'user_id', 'updated_by', 'updated_at'])
            ->map(fn (Note $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'slug' => $n->slug,
                'is_private' => (bool) $n->is_private,
                'folder' => $n->folder ? ['id' => $n->folder->id, 'name' => $n->folder->name] : null,
                'last_editor' => $n->lastEditor ? ['id' => $n->lastEditor->id, 'name' => $n->lastEditor->name] : null,
                'updated_at' => $n->updated_at?->toIso8601String(),
            ]);

        return Inertia::render('dashboard', [
            'recent_notes' => $recent,
        ]);
    }
}
