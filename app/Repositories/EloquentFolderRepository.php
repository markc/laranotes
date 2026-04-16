<?php

namespace App\Repositories;

use App\Models\Folder;
use App\Models\User;

class EloquentFolderRepository implements FolderRepositoryInterface
{
    public function tree(User $user): array
    {
        return Folder::tree($user);
    }

    public function find(int|string $id): ?array
    {
        $folder = Folder::find($id);

        return $folder ? $this->serialize($folder) : null;
    }

    public function create(array $data): array
    {
        return $this->serialize(Folder::create($data));
    }

    public function update(int|string $id, array $data): array
    {
        $folder = Folder::findOrFail($id);
        $folder->update($data);

        return $this->serialize($folder);
    }

    public function delete(int|string $id): bool
    {
        $folder = Folder::findOrFail($id);

        if ($folder->notes()->exists() || $folder->children()->exists()) {
            return false;
        }

        $folder->delete();

        return true;
    }

    public function flatList(): array
    {
        return Folder::orderBy('name')
            ->get(['id', 'name', 'parent_id'])
            ->toArray();
    }

    private function serialize(Folder $folder): array
    {
        return [
            'id' => $folder->id,
            'name' => $folder->name,
            'slug' => $folder->slug,
            'parent_id' => $folder->parent_id,
            'is_private' => (bool) $folder->is_private,
            'user_id' => $folder->user_id,
            'sort_order' => $folder->sort_order,
        ];
    }
}
