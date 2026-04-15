<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Folder;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'role' => Role::Admin->value,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'moderator@example.com'],
            [
                'name' => 'Moderator',
                'role' => Role::Moderator->value,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'User',
                'role' => Role::User->value,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'viewer@example.com'],
            [
                'name' => 'Viewer',
                'role' => Role::Viewer->value,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $general = Folder::firstOrCreate(
            ['slug' => 'general', 'parent_id' => null],
            ['name' => 'General', 'user_id' => $admin->id, 'sort_order' => 0],
        );

        Folder::firstOrCreate(
            ['slug' => 'ideas', 'parent_id' => null],
            ['name' => 'Ideas', 'user_id' => $admin->id, 'sort_order' => 1],
        );

        Folder::firstOrCreate(
            ['slug' => 'inbox', 'parent_id' => null],
            ['name' => 'Inbox', 'user_id' => $admin->id, 'sort_order' => 2],
        );

        Note::firstOrCreate(
            ['slug' => 'welcome'],
            [
                'folder_id' => $general->id,
                'user_id' => $admin->id,
                'updated_by' => $admin->id,
                'title' => 'Welcome to Laranotes',
                'body' => <<<'MD'
# Welcome to Laranotes

A simple, shared markdown note-taking app built with Laravel, Inertia, and React.

## Features

- **Folders** — organise notes in a nested tree
- **Markdown editor** with live preview (CodeMirror 6 + GFM)
- **Auto-save** — edits persist after a 2-second pause
- **Full-text search** across titles and bodies
- **Private notes** — toggle `is_private` to hide from other users
- **Dark/light theme**

## Getting started

1. Click **New note** in the sidebar to create your first note.
2. Notes auto-save as you type.
3. Use the search bar to find any note instantly.
4. Right-click folders and notes for more actions.

Happy note-taking!
MD,
            ],
        );
    }
}
