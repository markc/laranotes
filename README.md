# Laranotes

A simple, shared markdown note-taking app built with **Laravel 13**, **Inertia.js**, and **React 19 + TypeScript**. Generic and self-contained — adopt it for any team or personal use.

## Features

- **Folders** — nested folder tree for organising notes
- **Markdown editor** — CodeMirror 6 with syntax highlighting
- **Live preview** — GitHub-Flavoured Markdown via `react-markdown` + `remark-gfm`
- **Split view** — editor and preview side-by-side, or either full-width
- **Auto-save** — edits persist 700 ms after you stop typing
- **Full-text search** — `Ctrl/Cmd + K` to jump to any note
- **Private notes** — toggle to hide notes from other users
- **Dark / light theme** — with system preference detection
- **SQLite** — zero-config, single-file database

## Stack

- Laravel 13 (PHP 8.3+) with Fortify auth
- Inertia v3 + React 19 + TypeScript + Vite 8
- Tailwind CSS 4 with shadcn/ui primitives
- CodeMirror 6 for editing
- SQLite for storage
- bun / npm / pnpm for frontend tooling

## Getting started

```bash
# install deps
composer install
bun install  # or npm install

# configure (uses SQLite by default)
cp .env.example .env
php artisan key:generate

# database
php artisan migrate --seed

# build
bun run build  # or: bun run dev (for HMR)

# serve
php artisan serve
```

Default seeded user:

- **Email:** `admin@example.com`
- **Password:** `password`

## Data model

- `users` — Fortify auth, factory-seeded admin
- `folders` — hierarchical (`parent_id` self-reference), per-user
- `notes` — `folder_id`, `user_id`, `updated_by`, `is_private`, markdown body

`is_private = true` hides a note from all users except its author; `false` (default) is visible to everyone.

## Routes

| Method | URI                      | Action                   |
|--------|--------------------------|--------------------------|
| GET    | `/dashboard`             | Recent notes landing     |
| GET    | `/notes/create`          | New note form            |
| POST   | `/notes`                 | Create note              |
| GET    | `/notes/{id}`            | View note (read-only)    |
| GET    | `/notes/{id}/edit`       | Edit note (split view)   |
| PUT    | `/notes/{id}`            | Update note              |
| DELETE | `/notes/{id}`            | Delete note              |
| GET    | `/notes/search?q=…`      | JSON full-text search    |
| POST   | `/folders`               | Create folder            |
| PUT    | `/folders/{id}`          | Rename folder            |
| DELETE | `/folders/{id}`          | Delete (must be empty)   |
| GET    | `/api/folder-tree`       | JSON nested tree         |

## License

MIT
