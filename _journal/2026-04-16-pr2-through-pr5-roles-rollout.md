# 2026-04-16 — PR2–5: roles rollout continued

Second big push of the multi-user roles work for the legacy Laravel
customer. Four PRs landed in one day on top of PR1's policy
foundation, taking the feature from "backend-only bug fixes" to a
real user-visible workflow.

## Commits

- `2d53fba` PR2 — private folders + visibility-scoped tree
- `dafee4f` PR3 — role middleware, admin backend, canHints
- `a0dcaf8` CI lint fixes (eslint react-hooks/set-state-in-effect
  surfaced three pre-existing issues in app-sidebar + search-panel +
  search-bar that PR3 was blocked on)
- `3d18f1d` PR4 — invite flow, kill public registration
- `d9074e2` PR5 — useCanHints hook, UI gating, read-only edit mode

## PR2 — folders become owned + visibility-scoped

`Folder::tree` rewrite with the three hard rules from the plan:

1. Direct visibility — fetch where owner OR `!is_private`.
2. Ancestor chain visibility — a public child of a private parent is
   now hidden rather than leaking as an orphan at the root. This
   preserves the parent's privacy expectation.
3. Empty non-owned public folders are pruned. Owners always see their
   own empty folders (they need somewhere to work). This is what
   prevents the graffiti problem where any user could spray top-level
   folders into everyone's sidebar.

FormRequest cross-ownership guards: notes cannot be created or moved
into another user's private folder, and a folder cannot be flipped
to private while it still contains notes owned by other users
(would silently hide cross-user content). Both enforced in
`withValidator` after-hooks.

UI: lock icon on private folders, per-folder privacy toggle in the
hover actions, is_private checkbox on new-folder form (with
Escape-to-cancel replacing the onBlur-dismiss that conflicted with
the checkbox). Tree output gains `is_private` and `user_id` so the
frontend can render these affordances.

## PR3 — role middleware and admin backend

`RequireRole` middleware aliased as `role`, variadic allowlist
(`role:admin,moderator`), 401 anonymous vs 403 wrong-role.

`Admin\UserController`: paginated index, role change, destroy. The
**last-admin guard** uses `DB::transaction` + `lockForUpdate` — see
Admin/UserController.php. It was tempting to do a simple count check,
but with concurrent requests that's racy: two admins could each see
count=2 and both demote. The transaction with row-lock serialises
them. Self-demote and self-delete are blocked independently so the
user's own actions never reach the transaction.

**canHints shape** lives in `HandleInertiaRequests::share`:
createNotes, createFolders, moderate, manageUsers. The code comment
explicitly flags it as advisory — authoritative checks are in
policies. Client types picked up the naming: `auth.canHints`, not
`auth.can`.

**UserFactory gotcha**: adding `role` to the DB default didn't
propagate to the in-memory model post-`create()` because factory
doesn't refresh. When HandleInertiaRequests started calling
`$user->role->canCreate()`, 20 tests broke on null deref. Fix: add
`'role' => Role::User->value` to UserFactory's definition so every
factory-built user has a role in memory.

**CI lint blockage**: three pre-existing issues surfaced after PR3
pushed and broke the linter workflow. Two were
`react-hooks/set-state-in-effect` violations in search-bar and
search-panel that cleared state from inside the effect when the
query went empty — redundant because the render-path already masked
stale results via the `{open && query.trim() && ...}` guard (search-
bar) and a new `hidden={query.trim() === ''}` on the `<ul>` (search-
panel). Third was an unused Button import in app-sidebar. Fixed in
`a0dcaf8`.

## PR4 — invites + kill public registration

`invites` table with 64-char tokens, 14-day default expiry, and three
distinct non-claimable states (expires_at, accepted_at, revoked_at)
so auditing can distinguish "expired naturally" from "revoked by
admin" from "already accepted."

`Role::canInvite(Role $target)` — admin invites any role, moderator
invites user/viewer only, user/viewer cannot invite. The controller
double-checks against this even after the allowlist validation, in
case the two ever drift.

Public accept routes sit **outside** the auth middleware under
`throttle:10,1` to discourage token enumeration. `InviteController::
accept` creates the user with the pre-assigned role, fires the
`Registered` event for Fortify hooks, logs them straight into the
dashboard.

Registration kill: `Features::registration()` removed from
`config/fortify.php`, `registerView` dropped from
`FortifyServiceProvider`, `canRegister` prop removed from the login
view, `resources/js/pages/auth/register.tsx` deleted. Wayfinder
regenerated the TS route files automatically — `register/` vanished
and `invites/` appeared. No manual cleanup needed.

Revoke authorization split: admin revokes any, moderator revokes own
only. Accepted invites cannot be revoked (you'd just want to delete
the user instead).

17 tests cover the role matrix for creation, all non-claimable token
states, the accept flow (role, hashed password, session, invite
state), weak password rejection, and the revoke authorization.

## PR5 — presentation layer

`useCanHints` hook wraps the shared prop with a safe all-false
fallback so components don't need to guard for the unauthenticated
case. Siblings: `useCurrentRole`, `useCurrentUser`.

The account panel gained a role badge under the email and
conditional "Manage users" (admin) and "Invites" (moderator+) links.
Before PR5 those surfaces existed but were URL-only — nobody would
find them without reading the routes file.

Folder tree hover actions now mirror FolderPolicy:

```ts
canModifyFolder = isOwner
    ? canHints.createFolders
    : canHints.moderate && !node.is_private;
canCreateNoteHere =
    canHints.createNotes && (isOwner || !node.is_private);
```

The toggle-privacy button is further gated to owner-only — even
moderators shouldn't flip someone else's folder visibility, because
that's a conceptual privacy boundary rather than a content-curation
act.

**Per-note authority**: NoteController::serialize now emits
`can_edit` and `can_delete`. These are NOT hints — they're
authoritative per-record from the policy. Comment in the code calls
out the distinction because it would otherwise look inconsistent
next to `canHints`. The edit page uses them for the read-only
experience: banner at the top, disabled title/folder inputs, hidden
privacy toggle, hidden delete button, `readOnly={true}` passed to
CodeMirror (new prop on MarkdownEditor), auto-save and Ctrl+S
short-circuited when read-only.

A viewer opening any public note and a user opening someone else's
public note now both get a coherent read-only workspace instead of
an editable UI that would 403 on save.

## Decisions reaffirmed

- **Admins and private notes**: still blind by default. PR5 did not
  add any path for admin to see other users' private notes. That's
  still impersonation's job (PR6).
- **canHints naming**: no client regression on the "advisory"
  framing. The hook doc and the HandleInertiaRequests comment both
  call it out.
- **Read-only mode uses per-note can_edit, not role**: matches the
  policy semantics exactly (owner OR moderator-on-public OR admin-
  on-public). Trying to derive this from `auth.role + note.is_private
  + note.user_id` on the client would duplicate the policy and drift.

## Gaps deliberately left

- The folder tree's rename handler uses `window.prompt`; it's ugly
  but out of scope for the roles work. Future cleanup.
- `notes/show.tsx` still exists but is barely wired. The edit page's
  read-only mode effectively replaces it — might delete `show` in a
  future chore PR.
- Folder tree "new note in folder" button: could also gate on
  specific folder ownership/visibility, but doesn't today because
  the note's folder_id FormRequest validation already rejects the
  bad case.
- Admin users page styling is minimal. Raw table + select. Good
  enough for an admin-only surface; polish if the customer asks.

## Test counts

- End of PR1: 88 tests (49 new)
- End of PR2: 100 tests (+12)
- End of PR3: 116 tests (+16)
- End of PR4: 131 tests (+17 invites — plus 2 Fortify registration
  tests now auto-skipped since the feature is disabled)
- End of PR5: 131 tests (+0 — pure presentation layer)

## Next up

**PR6** — admin impersonation. Session-based `impersonator_id`,
banner in shared data, `BlockDestructiveDuringImpersonation`
middleware on admin routes, audit table for start/stop events.
Dependencies: PR3's admin panel (done).

**PR7** — anonymous share links. New `note_shares` table, public
`/s/{token}` route outside auth, minimal bare layout, share
management UI on the note edit page. Independent of PR6 — can land
in parallel or swap order. Before merging, audit `markdown-preview.
tsx` for HTML injection surface since the anonymous path is
unauthenticated and untrusted.

## Unfinished working-tree state

`resources/js/components/markdown-preview.tsx` has unstaged edits
from before this session. Each PR commit since PR1 has explicitly
excluded it to leave the user's in-progress work alone. That file
will need attention at the start of PR7's security audit regardless.
