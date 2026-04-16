<?php

namespace App\Repositories;

use App\Enums\Role;
use App\Models\User;
use App\Services\SettingService;
use Symfony\Component\Yaml\Yaml;

class FlatFileFolderRepository implements FolderRepositoryInterface
{
    private string $basePath;

    public function __construct(private SettingService $settings)
    {
        $this->basePath = base_path($this->settings->get('flatfile_path'));
    }

    public function tree(User $user): array
    {
        if (! is_dir($this->basePath)) {
            return [];
        }

        $dirs = array_filter(glob($this->basePath.'/*'), 'is_dir');
        $tree = [];

        foreach ($dirs as $dir) {
            $name = basename($dir);
            $meta = $this->readMeta($dir);

            if (! $this->isFolderVisible($meta, $user)) {
                continue;
            }

            $notes = $this->notesInDir($dir, $user);

            if ($name === '_unfiled' && empty($notes)) {
                continue;
            }

            $tree[] = [
                'id' => $name,
                'name' => $name,
                'slug' => $name,
                'parent_id' => null,
                'is_private' => (bool) ($meta['is_private'] ?? false),
                'user_id' => $meta['user_id'] ?? 0,
                'notes' => $notes,
                'children' => [],
            ];
        }

        usort($tree, fn ($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0) ?: strcasecmp($a['name'], $b['name']));

        return $tree;
    }

    public function find(int|string $id): ?array
    {
        $dir = $this->basePath.'/'.$id;

        if (! is_dir($dir)) {
            return null;
        }

        $meta = $this->readMeta($dir);

        return [
            'id' => $id,
            'name' => (string) $id,
            'slug' => (string) $id,
            'parent_id' => null,
            'is_private' => (bool) ($meta['is_private'] ?? false),
            'user_id' => $meta['user_id'] ?? 0,
            'sort_order' => $meta['sort_order'] ?? 0,
        ];
    }

    public function create(array $data): array
    {
        $this->ensureBasePath();

        $name = $data['name'] ?? 'folder';
        $slug = \Illuminate\Support\Str::slug($name);
        $dir = $this->basePath.'/'.$slug;

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $meta = [
            'user_id' => $data['user_id'] ?? 0,
            'is_private' => (bool) ($data['is_private'] ?? false),
            'sort_order' => $data['sort_order'] ?? 0,
        ];

        $this->writeMeta($dir, $meta);

        return [
            'id' => $slug,
            'name' => $name,
            'slug' => $slug,
            'parent_id' => null,
            'is_private' => $meta['is_private'],
            'user_id' => $meta['user_id'],
            'sort_order' => $meta['sort_order'],
        ];
    }

    public function update(int|string $id, array $data): array
    {
        $dir = $this->basePath.'/'.$id;
        $meta = $this->readMeta($dir);

        if (isset($data['is_private'])) {
            $meta['is_private'] = (bool) $data['is_private'];
        }
        if (isset($data['sort_order'])) {
            $meta['sort_order'] = (int) $data['sort_order'];
        }

        $this->writeMeta($dir, $meta);

        if (isset($data['name']) && $data['name'] !== (string) $id) {
            $newSlug = \Illuminate\Support\Str::slug($data['name']);
            $newDir = $this->basePath.'/'.$newSlug;
            if ($newDir !== $dir && ! is_dir($newDir)) {
                rename($dir, $newDir);
                $id = $newSlug;
            }
        }

        return $this->find($id);
    }

    public function delete(int|string $id): bool
    {
        $dir = $this->basePath.'/'.$id;

        if (! is_dir($dir)) {
            return false;
        }

        $mdFiles = glob($dir.'/*.md');
        $subDirs = array_filter(glob($dir.'/*'), 'is_dir');

        if (! empty($mdFiles) || ! empty($subDirs)) {
            return false;
        }

        $metaFile = $dir.'/.folder.yml';
        if (file_exists($metaFile)) {
            unlink($metaFile);
        }

        rmdir($dir);

        return true;
    }

    public function flatList(): array
    {
        if (! is_dir($this->basePath)) {
            return [];
        }

        $dirs = array_filter(glob($this->basePath.'/*'), 'is_dir');
        $list = [];

        foreach ($dirs as $dir) {
            $name = basename($dir);
            if ($name === '_unfiled') {
                continue;
            }
            $list[] = ['id' => $name, 'name' => $name, 'parent_id' => null];
        }

        usort($list, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $list;
    }

    private function notesInDir(string $dir, User $user): array
    {
        $files = glob($dir.'/*.md');
        $notes = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $frontmatter = $this->extractFrontmatter($content);

            $isPrivate = (bool) ($frontmatter['is_private'] ?? false);
            $userId = $frontmatter['user_id'] ?? 0;

            if ($isPrivate) {
                if ($user->role === Role::Viewer) {
                    continue;
                }
                if ($userId !== $user->id) {
                    continue;
                }
            }

            $slug = $frontmatter['slug'] ?? pathinfo($file, PATHINFO_FILENAME);

            $notes[] = [
                'id' => $slug,
                'title' => $frontmatter['title'] ?? $slug,
                'slug' => $slug,
                'is_private' => $isPrivate,
                'user_id' => $userId,
                'updated_at' => $frontmatter['updated_at'] ?? null,
            ];
        }

        usort($notes, fn ($a, $b) => strcasecmp($a['title'], $b['title']));

        return $notes;
    }

    private function readMeta(string $dir): array
    {
        $file = $dir.'/.folder.yml';

        if (! file_exists($file)) {
            return [];
        }

        return Yaml::parseFile($file) ?? [];
    }

    private function writeMeta(string $dir, array $meta): void
    {
        $file = $dir.'/.folder.yml';
        file_put_contents($file, Yaml::dump($meta));
    }

    private function extractFrontmatter(string $content): array
    {
        if (str_starts_with($content, '---')) {
            $parts = preg_split('/^---\s*$/m', $content, 3);
            if (count($parts) >= 3) {
                return Yaml::parse($parts[1]) ?? [];
            }
        }

        return [];
    }

    private function isFolderVisible(array $meta, User $user): bool
    {
        $isPrivate = (bool) ($meta['is_private'] ?? false);
        if (! $isPrivate) {
            return true;
        }

        if ($user->role === Role::Viewer) {
            return false;
        }

        return ($meta['user_id'] ?? 0) === $user->id;
    }

    private function ensureBasePath(): void
    {
        if (! is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
    }
}
