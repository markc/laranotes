<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;

class FolderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Folder $folder): bool
    {
        if (! $folder->is_private) {
            return true;
        }

        return $folder->user_id === $user->id || $user->role->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->role->canCreate();
    }

    public function update(User $user, Folder $folder): bool
    {
        if ($folder->user_id === $user->id) {
            return $user->role->canCreate();
        }

        return $user->role->canModerate() && ! $folder->is_private;
    }

    public function delete(User $user, Folder $folder): bool
    {
        if ($folder->user_id === $user->id) {
            return $user->role->canCreate();
        }

        return $user->role->canModerate() && ! $folder->is_private;
    }

    public function restore(User $user, Folder $folder): bool
    {
        return $folder->user_id === $user->id || $user->role->isAdmin();
    }

    public function forceDelete(User $user, Folder $folder): bool
    {
        return $folder->user_id === $user->id || $user->role->isAdmin();
    }
}
