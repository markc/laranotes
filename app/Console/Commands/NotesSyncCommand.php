<?php

namespace App\Console\Commands;

use App\Models\Folder;
use App\Models\Note;
use App\Services\SettingService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class NotesSyncCommand extends Command
{
    protected $signature = 'notes:sync
        {direction : db-to-files or files-to-db}
        {--path= : Override flat-file path (default: from settings)}';

    protected $description = 'Sync notes between database and flat files';

    public function handle(SettingService $settings): int
    {
        $path = $this->option('path') ?? $settings->get('flatfile_path');
        $basePath = base_path($path);

        return match ($this->argument('direction')) {
            'db-to-files' => $this->dbToFiles($basePath),
            'files-to-db' => $this->filesToDb($basePath),
            default => $this->invalidDirection(),
        };
    }

    private function dbToFiles(string $basePath): int
    {
        if (! is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        $folders = Folder::all();
        $folderMap = [];

        foreach ($folders as $folder) {
            $dir = $basePath.'/'.$folder->slug;
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $meta = [
                'user_id' => $folder->user_id,
                'is_private' => $folder->is_private,
                'sort_order' => $folder->sort_order,
            ];
            file_put_contents($dir.'/.folder.yml', Yaml::dump($meta));

            $folderMap[$folder->id] = $folder->slug;
        }

        $notes = Note::with('folder')->get();
        $count = 0;

        foreach ($notes as $note) {
            $folderSlug = $note->folder ? ($folderMap[$note->folder_id] ?? $note->folder->slug) : '_unfiled';
            $dir = $basePath.'/'.$folderSlug;

            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $frontmatter = [
                'title' => $note->title,
                'slug' => $note->slug,
                'user_id' => $note->user_id,
                'updated_by' => $note->updated_by,
                'is_private' => $note->is_private,
                'folder' => $folderSlug,
                'created_at' => $note->created_at?->toIso8601String(),
                'updated_at' => $note->updated_at?->toIso8601String(),
            ];

            $yaml = Yaml::dump($frontmatter, 2, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
            $content = "---\n{$yaml}---\n\n{$note->body}";

            file_put_contents($dir.'/'.$note->slug.'.md', $content);
            $count++;
        }

        $this->info("Exported {$count} notes and ".count($folders)." folders to {$basePath}");

        return self::SUCCESS;
    }

    private function filesToDb(string $basePath): int
    {
        if (! is_dir($basePath)) {
            $this->error("Directory not found: {$basePath}");

            return self::FAILURE;
        }

        $dirs = array_filter(glob($basePath.'/*'), 'is_dir');
        $folderMap = [];
        $folderCount = 0;

        foreach ($dirs as $dir) {
            $name = basename($dir);
            if ($name === '_unfiled') {
                continue;
            }

            $meta = [];
            $metaFile = $dir.'/.folder.yml';
            if (file_exists($metaFile)) {
                $meta = Yaml::parseFile($metaFile) ?? [];
            }

            $folder = Folder::firstOrCreate(
                ['slug' => $name],
                [
                    'name' => $name,
                    'user_id' => $meta['user_id'] ?? 1,
                    'is_private' => (bool) ($meta['is_private'] ?? false),
                    'sort_order' => $meta['sort_order'] ?? 0,
                ],
            );

            $folderMap[$name] = $folder->id;
            $folderCount++;
        }

        $noteCount = 0;
        $skipped = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'md') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $frontmatter = [];
            $body = $content;

            if (str_starts_with($content, '---')) {
                $parts = preg_split('/^---\s*$/m', $content, 3);
                if (count($parts) >= 3) {
                    $frontmatter = Yaml::parse($parts[1]) ?? [];
                    $body = ltrim($parts[2]);
                }
            }

            $slug = $frontmatter['slug'] ?? pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $folderDir = basename(dirname($file->getPathname()));
            $folderId = $folderMap[$folderDir] ?? null;

            if (Note::where('slug', $slug)->exists()) {
                $base = $slug;
                $i = 2;
                while (Note::where('slug', $slug)->exists()) {
                    $slug = "{$base}-{$i}";
                    $i++;
                }
            }

            Note::create([
                'title' => $frontmatter['title'] ?? Str::title(str_replace('-', ' ', $slug)),
                'slug' => $slug,
                'body' => $body,
                'user_id' => $frontmatter['user_id'] ?? 1,
                'updated_by' => $frontmatter['updated_by'] ?? $frontmatter['user_id'] ?? 1,
                'is_private' => (bool) ($frontmatter['is_private'] ?? false),
                'folder_id' => $folderId,
            ]);

            $noteCount++;
        }

        $this->info("Imported {$noteCount} notes and {$folderCount} folders from {$basePath}");
        if ($skipped > 0) {
            $this->warn("Skipped {$skipped} notes (slug conflicts resolved with suffix)");
        }

        return self::SUCCESS;
    }

    private function invalidDirection(): int
    {
        $this->error('Direction must be "db-to-files" or "files-to-db"');

        return self::FAILURE;
    }
}
