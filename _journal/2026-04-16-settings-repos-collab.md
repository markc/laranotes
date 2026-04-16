# 2026-04-16 — Settings system, repository pattern, and real-time collaboration

## What changed

### Settings system
- `Setting` model with key/value storage in SQLite
- `SettingService` with a registry pattern — each setting has a default, type, label, and group
- Admin settings page at `/admin/settings` with auto-generated form from registry metadata
- Settings migration for the `settings` table
- Shared Inertia props: `site_title`, `site_description`, `default_editor`, `default_theme`, `default_scheme`

### Repository pattern
- `NoteRepositoryInterface` / `FolderRepositoryInterface` with Eloquent and flat-file implementations
- `AppServiceProvider` selects backend based on `notes_storage` setting (database or flatfile)
- `NotesSyncCommand` for bidirectional database-to-flatfile migration
- Route model binding updated to resolve notes via repository when using flatfile storage

### Real-time collaborative editing (Yjs + WebSocket)
- Rust-based collab server in `collab-server/` — handles WebSocket connections, Yjs document sync, room management
- `CollabTokenService` mints JWT tokens for authenticated collab sessions
- `CollabController` provides token endpoint and room queries
- `VerifyCollabServer` middleware for internal collab API authentication
- Frontend hooks: `useYjsCollab` (WebSocket + awareness), `useCollabExtensions` (CodeMirror Yjs binding)
- `CollabPresenceBar` component showing live peers and connection status
- Edit page locks to CodeMirror during active collab (Tiptap incompatible with Yjs text binding)
- Auto-save suppresses body updates when collab connected (CRDT handles body sync)
- Ctrl+S shows "synced" toast during collab instead of PUT request

### Server-driven theme defaults
- `ThemeProvider` now accepts `serverDefaults` from Inertia shared props
- New users get admin-configured theme/scheme instead of hardcoded defaults
- Layout passes server defaults from shared props to ThemeProvider

## Dependencies added
- `yjs`, `y-websocket`, `y-codemirror.next`, `y-protocols`, `ws`
