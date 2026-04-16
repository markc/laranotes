<?php

namespace App\Services;

use App\Models\Setting;

class SettingService
{
    private const REGISTRY = [
        'site_title' => ['group' => 'general', 'type' => 'string', 'default' => null, 'label' => 'Site title'],
        'site_description' => ['group' => 'general', 'type' => 'string', 'default' => '', 'label' => 'Site description'],
        'registration_mode' => ['group' => 'general', 'type' => 'enum', 'default' => 'invite_only', 'label' => 'Registration mode', 'options' => ['closed', 'invite_only', 'open']],
        'notes_storage' => ['group' => 'storage', 'type' => 'enum', 'default' => 'database', 'label' => 'Notes storage backend', 'options' => ['database', 'flatfile']],
        'flatfile_path' => ['group' => 'storage', 'type' => 'string', 'default' => '_notes', 'label' => 'Flat-file path'],
        'flatfile_git_auto_commit' => ['group' => 'storage', 'type' => 'bool', 'default' => false, 'label' => 'Git auto-commit on save'],
        'default_editor' => ['group' => 'editor', 'type' => 'enum', 'default' => 'source', 'label' => 'Default editor', 'options' => ['source', 'wysiwyg']],
        'search_max_results' => ['group' => 'search', 'type' => 'int', 'default' => 25, 'label' => 'Max search results'],
        'default_theme' => ['group' => 'display', 'type' => 'enum', 'default' => 'system', 'label' => 'Default theme', 'options' => ['light', 'dark', 'system']],
        'default_scheme' => ['group' => 'display', 'type' => 'enum', 'default' => 'crimson', 'label' => 'Default colour scheme', 'options' => ['crimson', 'stone', 'ocean', 'forest', 'sunset']],
        'notes_per_page' => ['group' => 'display', 'type' => 'int', 'default' => 10, 'label' => 'Notes per page'],
    ];

    private ?array $cache = null;

    public function get(string $key, mixed $fallback = null): mixed
    {
        if (! isset(self::REGISTRY[$key])) {
            return $fallback;
        }

        $this->load();

        $raw = $this->cache[$key] ?? null;

        if ($raw === null) {
            $default = self::REGISTRY[$key]['default'];

            if ($key === 'site_title' && $default === null) {
                return config('app.name');
            }

            return $default;
        }

        return $this->cast($key, $raw);
    }

    public function set(string $key, mixed $value): void
    {
        if (! isset(self::REGISTRY[$key])) {
            return;
        }

        $stored = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
        $meta = self::REGISTRY[$key];

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'group' => $meta['group'], 'type' => $meta['type']],
        );

        if ($this->cache !== null) {
            $this->cache[$key] = $stored;
        }
    }

    public function all(): array
    {
        $this->load();

        $result = [];
        foreach (self::REGISTRY as $key => $meta) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    public function grouped(): array
    {
        $all = $this->all();
        $groups = [];

        foreach (self::REGISTRY as $key => $meta) {
            $groups[$meta['group']][$key] = $all[$key];
        }

        return $groups;
    }

    public function registry(): array
    {
        return self::REGISTRY;
    }

    public function flush(): void
    {
        $this->cache = null;
    }

    private function load(): void
    {
        if ($this->cache !== null) {
            return;
        }

        $this->cache = Setting::pluck('value', 'key')->toArray();
    }

    private function cast(string $key, string $raw): mixed
    {
        return match (self::REGISTRY[$key]['type']) {
            'bool' => (bool) (int) $raw,
            'int' => (int) $raw,
            default => $raw,
        };
    }
}
