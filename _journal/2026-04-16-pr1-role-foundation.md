# 2026-04-16 — PR1: role enum + authorization fixes

First phase of the multi-user roles rollout for the legacy Laravel
customer. This PR is backend-only and user-invisible; it establishes
the role primitive and closes two pre-existing authorization bugs.

## Shipped

Commit: `da4d29c` on `main`.

- **`App\Enums\Role`** — PHP native enum `Admin|Moderator|User|Viewer`
  with `canCreate`, `canModerate`, `isAdmin` helpers. Naming note:
  "viewer" is the logged-in read-only role. "Guest" is reserved for
  the anonymous share-link visitor coming in PR7.
- **Migration `2026_04_16_090000_add_roles_and_folder_privacy`** —
  `users.role` string default `'user'` indexed, `folders.is_private`
  bool default `false` indexed. Backfills `admin@example.com` → admin.
  Used `string` not `enum()` on SQLite since the PHP enum cast is the
  real safety belt and SQLite's enum support is cosmetic.
- **`NotePolicy` rewrite** — fixed two bugs. `update()` previously
  delegated to `view()`, letting any authed user edit any public note.
  `delete()` had the equivalent hole. New contract: owner-or-
  moderator-if-public for update/delete; private notes stay owner-only
  regardless of moderator role; admin gets a bypass on `view` for
  private-other but NOT on write (write access to others' private
  content comes via impersonation in PR6).
- **`FolderPolicy` rewrite** — was returning `true` from every method
  except `forceDelete`. Now mirrors NotePolicy. `folders.is_private`
  defaults to `false`, so every existing folder behaves exactly as
  before (visible to all) until PR2 introduces the private-folder UI.
- **FormRequest hardening** — `StoreNoteRequest` / `StoreFolderRequest`
  now authorize via `can('create', ...)` instead of checking
  `user !== null`, which is how viewers get blocked at the request
  layer. `UpdateFolderRequest` also moved from "is logged in" to
  policy-based, since its controller already called `$this->authorize`
  but it's cleaner to enforce at the request edge.
- **`Note::scopeVisibleTo`** — added viewer branch (public notes
  only, no owner exception — viewers shouldn't own notes, but if
  somehow one does, the scope still suppresses it).
- **Seeder** — now seeds one user per role
  (`moderator@`, `user@`, `viewer@example.com`, all `password`) so
  the role matrix is testable out of the box.
- **Test matrix** — 49 new tests across three files:
  - `tests/Feature/Notes/NotePolicyTest.php` — 16-case data provider
    (4 roles × 4 note shapes × {view, update, delete}) + 4 create
    cases + 2 scope tests.
  - `tests/Feature/Notes/NoteAccessTest.php` — 6 HTTP regression
    tests exercising the actual routes (store/update/destroy/show/
    dashboard) so both the policy path and the scope path are covered.
  - `tests/Feature/Folders/FolderPolicyTest.php` — mirror of the
    note matrix for folders.

Full suite: **88 passing**, 0 failing. Deleted the stale
`tests/Feature/ExampleTest.php` — the `/` route redirects by design,
so the test could never pass; this was pre-existing noise on main.

## Decisions logged during the session

Confirmed before PR1 started:

1. **Share links** — yes, anonymous single-doc share URLs in addition
   to the logged-in viewer role. Deferred to PR7.
2. **Moderator scope** — full curator: edit + delete on public content.
3. **Admin privacy** — admins do **not** casually see others' private
   notes. The route in is explicit impersonation (PR6), audited via a
   dedicated event table.
4. **Registration** — closing public registration, replacing with an
   invite flow in PR4. Admin can invite any role; moderator can invite
   user or viewer only.
5. **Folder ownership** — becoming owned + visibility-scoped like
   notes. PR2 rewrites `Folder::tree` to prune empty non-owned public
   folders (prevents graffiti).
6. **Last-admin demote** — atomic check via
   `lockForUpdate + transaction`, coming in PR3's AdminUserController.
7. **Client `can` naming** — will surface as `auth.canHints` in
   `HandleInertiaRequests` (PR3/PR5) so the advisory-not-authoritative
   nature is obvious at the call site.
8. **Share link UX** — never-expires + explicit revoke; show author
   name in the footer.
9. **Stack sanity check** — Laranotes is a legacy customer project.
   Laravel/React/Inertia work is justified; this is not new investment
   in a stack I'm leaving.

## What's left in the working tree

`resources/js/components/markdown-preview.tsx` has pre-session edits
from the user — untouched, not part of PR1, left alone for their own
follow-up.

## Next up

**PR2** — `Folder::tree` rewrite + private-folder UI toggle.
Dependencies: none (all PR1 groundwork is in place).

Subsequent phases queued: PR3 role middleware + admin backend, PR4
invite flow (kills public registration in the same PR), PR5 Inertia
`canHints` + React role gating, PR6 impersonation, PR7 anonymous
share links.
