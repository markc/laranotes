# 2026-04-22 — Development roadmap for next phases

## Summary

Assessed current state and designed a 6-phase roadmap for continued development.

## Phases planned

1. **Stabilize collab deployment** — systemd service, README, clean up test artifacts (1 session)
2. **Mobile/responsive polish** — full-width sidebar on mobile, backdrop overlay, disable split view, compact toolbar (2–3 sessions)
3. **Tags** — global flat tags with many-to-many pivot, tag picker in editor, filter in search (2 sessions)
4. **Note versioning** — revision history with diff view, soft deletes, throttled snapshots (3–4 sessions, depends on Phase 1)
5. **Markdown enhancements** — TOC, wiki-link backlinks, optional mermaid/KaTeX (2–3 sessions)
6. **Export** — HTML and PDF export via dompdf (1–2 sessions, depends on Phase 5)

## Notes

- Phases 1–3 can run in parallel
- Phase 4 waits on collab stability for save-to-version pipeline
- Phase 6 comes last so exports benefit from markdown enhancements
- Full plan at `.claude/plans/curious-skipping-porcupine.md`
