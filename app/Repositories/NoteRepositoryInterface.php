<?php

namespace App\Repositories;

use App\Models\User;

interface NoteRepositoryInterface
{
    public function find(int|string $id): ?array;

    public function findBySlug(string $slug): ?array;

    public function create(array $data): array;

    public function update(int|string $id, array $data): array;

    public function delete(int|string $id): void;

    public function search(User $user, string $query, int $limit): array;

    public function recentForUser(User $user, int $limit): array;
}
