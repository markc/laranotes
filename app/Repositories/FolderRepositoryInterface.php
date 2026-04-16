<?php

namespace App\Repositories;

use App\Models\User;

interface FolderRepositoryInterface
{
    public function tree(User $user): array;

    public function find(int|string $id): ?array;

    public function create(array $data): array;

    public function update(int|string $id, array $data): array;

    public function delete(int|string $id): bool;

    public function flatList(): array;
}
