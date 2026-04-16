<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Services\CollabTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CollabController extends Controller
{
    public function __construct(
        private CollabTokenService $tokenService,
    ) {}

    public function token(Request $request, Note $note): JsonResponse
    {
        $this->authorize('view', $note);

        $user = $request->user();

        if ($note->is_private && $note->user_id !== $user->id) {
            abort(403, 'Private notes cannot be collaborated on.');
        }

        $canEdit = $user->can('update', $note);
        $token = $this->tokenService->mint($user->id, $note->id, $canEdit);

        return response()->json([
            'token' => $token,
            'ws_url' => config('collab.ws_url')."/ws/note/{$note->id}",
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'can_edit' => $canEdit,
        ]);
    }

    public function body(Request $request, Note $note): JsonResponse
    {
        return response()->json([
            'body' => (string) $note->body,
        ]);
    }

    public function saveBody(Request $request, Note $note): \Illuminate\Http\Response
    {
        $data = $request->validate([
            'body' => 'required|string',
            'updated_by' => 'required|integer|exists:users,id',
        ]);

        $note->update([
            'body' => $data['body'],
            'updated_by' => $data['updated_by'],
        ]);

        return response()->noContent();
    }

    public function rooms(): JsonResponse
    {
        try {
            $httpUrl = config('collab.http_url', 'http://localhost:4444');

            $response = Http::timeout(2)->withHeaders([
                'X-Collab-Secret' => config('collab.secret'),
            ])->get("{$httpUrl}/rooms");

            if ($response->ok()) {
                return response()->json($response->json());
            }
        } catch (\Throwable) {
            // Collab server unreachable
        }

        return response()->json(['rooms' => []]);
    }
}
