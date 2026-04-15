<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Note $note): bool
    {
        if (! $note->is_private) {
            return true;
        }

        return $note->user_id === $user->id || $user->role->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->role->canCreate();
    }

    public function update(User $user, Note $note): bool
    {
        if ($note->user_id === $user->id) {
            return $user->role->canCreate();
        }

        return $user->role->canModerate() && ! $note->is_private;
    }

    public function delete(User $user, Note $note): bool
    {
        if ($note->user_id === $user->id) {
            return $user->role->canCreate();
        }

        return $user->role->canModerate() && ! $note->is_private;
    }

    public function share(User $user, Note $note): bool
    {
        if ($note->user_id === $user->id) {
            return $user->role->canCreate();
        }

        return $user->role->canModerate() && ! $note->is_private;
    }

    public function restore(User $user, Note $note): bool
    {
        return $note->user_id === $user->id || $user->role->isAdmin();
    }

    public function forceDelete(User $user, Note $note): bool
    {
        return $note->user_id === $user->id || $user->role->isAdmin();
    }
}
