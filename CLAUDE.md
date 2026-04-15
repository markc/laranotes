# CLAUDE.md — Laranotes

A shared markdown note-taking app — Laravel 13 + Inertia v3 + React 19 + SQLite.

## Stack

- **Backend:** Laravel 13 (PHP 8.3+), Fortify auth
- **Frontend:** Inertia v3, React 19, TypeScript, Vite 8, Tailwind CSS 4
- **Editor:** CodeMirror 6 (`@uiw/react-codemirror`) + `react-markdown` preview
- **Database:** SQLite (`database/database.sqlite`) — no MySQL, no Postgres
- **UI primitives:** shadcn/ui sidebar shell from the Laravel React starter kit

## Key conventions

- Inertia pages live in `resources/js/pages/*.tsx`, components in `resources/js/components/`.
- The folder tree is passed on every request as `folderTree` via `HandleInertiaRequests`.
- Note policy (`NotePolicy`) enforces `is_private` visibility.
- Auto-save debounces PUT `/notes/{id}` by 700 ms on title/body/privacy/folder changes.
- Slugs are generated on model `creating` hook; don't pass them from the client.
- Search uses `LIKE` queries — OK for small collections. Swap to FTS5 later if needed.

## Do not

- Don't add a WYSIWYG editor. CodeMirror + markdown only.
- Don't add real-time collaboration. Single-editor-at-a-time is fine.
- Don't add attachments / file uploads. Text markdown only.
- Don't introduce MySQL or Postgres. SQLite is a hard constraint for the starter.
- Don't expose a REST API. Inertia handles all data flow (plus `/notes/search` and `/api/folder-tree` which return JSON for convenience).

## Local dev

```bash
php artisan serve --port=8765
bun run dev          # HMR (uses /usr/sbin/bun on this machine)
bun run build        # production build
php artisan migrate:fresh --seed
```

Default user: `admin@example.com` / `password`.
