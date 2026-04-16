<?php

namespace App\Repositories;

use App\Enums\Role;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Yaml\Yaml;

class FlatFileNoteRepository implements NoteRepositoryInterface
{
    private string $basePath;

    public function __construct(private SettingService $settings)
    {
        $this->basePath = base_path($this->settings->get('flatfile_path'));
    }

    public function find(int|string $id): ?array
    {
        return $this->findBySlug((string) $id);
    }

    public function findBySlug(string $slug): ?array
    {
        $file = $this->findFile($slug);

        return $file ? $this->parseFile($file) : null;
    }

    public function create(array $data): array
    {
        $this->ensureBasePath();

        $title = $data['title'] ?? 'Untitled';
        $slug = $this->generateUniqueSlug($title);
        $folderSlug = $this->resolveFolderSlug($data['folder_id'] ?? null);
        $dir = $folderSlug ? $this->basePath.'/'.$folderSlug : $this->basePath.'/_unfiled';

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $now = now()->toIso8601String();
        $frontmatter = [
            'title' => $title,
            'slug' => $slug,
            'user_id' => $data['user_id'] ?? 0,
            'updated_by' => $data['updated_by'] ?? $data['user_id'] ?? 0,
            'is_private' => (bool) ($data['is_private'] ?? false),
            'folder' => $folderSlug ?: '_unfiled',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $body = $data['body'] ?? '';
        $path = $dir.'/'.$slug.'.md';

        $this->writeFile($path, $frontmatter, $body);
        $this->autoCommit('create', $slug);

        return $this->parseFile($path);
    }

    public function update(int|string $id, array $data): array
    {
        $file = $this->findFile((string) $id);
        if (! $file) {
            throw new \RuntimeException("Note not found: {$id}");
        }

        $current = $this->parseFile($file);
        $frontmatter = $this->parseFrontmatter($file);

        if (isset($data['title'])) {
            $frontmatter['title'] = $data['title'];
        }
        if (isset($data['updated_by'])) {
            $frontmatter['updated_by'] = $data['updated_by'];
        }
        if (array_key_exists('is_private', $data)) {
            $frontmatter['is_private'] = (bool) $data['is_private'];
        }
        $frontmatter['updated_at'] = now()->toIso8601String();

        $body = $data['body'] ?? $current['body'];

        $newFolderSlug = null;
        if (array_key_exists('folder_id', $data)) {
            $newFolderSlug = $this->resolveFolderSlug($data['folder_id']);
            $frontmatter['folder'] = $newFolderSlug ?: '_unfiled';
        }

        $this->writeFile($file, $frontmatter, $body);

        if ($newFolderSlug !== null && $newFolderSlug !== ($current['folder']['name'] ?? null)) {
            $newDir = $newFolderSlug
                ? $this->basePath.'/'.$newFolderSlug
                : $this->basePath.'/_unfiled';

            if (! is_dir($newDir)) {
                mkdir($newDir, 0755, true);
            }

            $newPath = $newDir.'/'.basename($file);
            if ($newPath !== $file) {
                rename($file, $newPath);
                $file = $newPath;
            }
        }

        $this->autoCommit('update', (string) $id);

        return $this->parseFile($file);
    }

    public function delete(int|string $id): void
    {
        $file = $this->findFile((string) $id);
        if ($file && file_exists($file)) {
            unlink($file);
            $this->autoCommit('delete', (string) $id);
        }
    }

    public function search(User $user, string $query, int $limit): array
    {
        $results = [];

        foreach ($this->allFiles() as $file) {
            $note = $this->parseFile($file);
            if (! $note || ! $this->isVisible($note, $user)) {
                continue;
            }

            $titleMatch = stripos($note['title'], $query) !== false;
            $bodyMatch = stripos($note['body'], $query) !== false;

            if (! $titleMatch && ! $bodyMatch) {
                continue;
            }

            $results[] = [
                'id' => $note['slug'],
                'title' => $note['title'],
                'slug' => $note['slug'],
                'folder' => $note['folder'],
                'snippet' => $this->snippet($note['body'], $query),
                'is_private' => $note['is_private'],
                'updated_at' => $note['updated_at'],
            ];

            if (count($results) >= $limit) {
                break;
            }
        }

        usort($results, fn ($a, $b) => strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? ''));

        return $results;
    }

    public function recentForUser(User $user, int $limit): array
    {
        $notes = [];

        foreach ($this->allFiles() as $file) {
            $note = $this->parseFile($file);
            if (! $note || ! $this->isVisible($note, $user)) {
                continue;
            }

            $notes[] = [
                'id' => $note['slug'],
                'title' => $note['title'],
                'slug' => $note['slug'],
                'is_private' => $note['is_private'],
                'folder' => $note['folder'],
                'last_editor' => $note['last_editor'],
                'updated_at' => $note['updated_at'],
            ];
        }

        usort($notes, fn ($a, $b) => strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? ''));

        return array_slice($notes, 0, $limit);
    }

    private function findFile(string $slug): ?string
    {
        $filename = $slug.'.md';

        foreach ($this->allFiles() as $file) {
            if (basename($file) === $filename) {
                return $file;
            }
        }

        return null;
    }

    /** @return string[] */
    private function allFiles(): array
    {
        if (! is_dir($this->basePath)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->basePath, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function parseFile(string $path): ?array
    {
        if (! file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        $frontmatter = [];
        $body = $content;

        if (str_starts_with($content, '---')) {
            $parts = preg_split('/^---\s*$/m', $content, 3);
            if (count($parts) >= 3) {
                $frontmatter = Yaml::parse($parts[1]) ?? [];
                $body = ltrim($parts[2]);
            }
        }

        $slug = $frontmatter['slug'] ?? pathinfo($path, PATHINFO_FILENAME);
        $folderDir = basename(dirname($path));
        $folderName = ($folderDir === basename($this->basePath) || $folderDir === '_unfiled')
            ? null
            : $folderDir;

        return [
            'id' => $slug,
            'title' => $frontmatter['title'] ?? $slug,
            'slug' => $slug,
            'body' => $body,
            'is_private' => (bool) ($frontmatter['is_private'] ?? false),
            'folder_id' => $folderName,
            'folder' => $folderName ? ['id' => $folderName, 'name' => $folderName] : null,
            'user_id' => $frontmatter['user_id'] ?? 0,
            'author' => null,
            'last_editor' => null,
            'created_at' => $frontmatter['created_at'] ?? null,
            'updated_at' => $frontmatter['updated_at'] ?? null,
            'can_edit' => true,
            'can_delete' => true,
        ];
    }

    private function parseFrontmatter(string $path): array
    {
        $content = file_get_contents($path);

        if (str_starts_with($content, '---')) {
            $parts = preg_split('/^---\s*$/m', $content, 3);
            if (count($parts) >= 3) {
                return Yaml::parse($parts[1]) ?? [];
            }
        }

        return [];
    }

    private function writeFile(string $path, array $frontmatter, string $body): void
    {
        $yaml = Yaml::dump($frontmatter, 2, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        $content = "---\n{$yaml}---\n\n{$body}";

        file_put_contents($path, $content);
    }

    private function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'note';
        $slug = $base;
        $i = 2;

        while ($this->findFile($slug) !== null) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    private function resolveFolderSlug(?int $folderId): ?string
    {
        if (! $folderId) {
            return null;
        }

        $folder = \App\Models\Folder::find($folderId);

        return $folder?->slug;
    }

    private function isVisible(array $note, User $user): bool
    {
        if (! $note['is_private']) {
            return true;
        }

        if ($user->role === Role::Viewer) {
            return false;
        }

        return $note['user_id'] === $user->id;
    }

    private function autoCommit(string $action, string $slug): void
    {
        if (! $this->settings->get('flatfile_git_auto_commit')) {
            return;
        }

        $path = $this->basePath;

        \Illuminate\Support\Facades\Process::path($path)->run(
            "git add -A && git commit -m 'Auto-save: {$action} {$slug}'"
        );
    }

    private function ensureBasePath(): void
    {
        if (! is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
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
