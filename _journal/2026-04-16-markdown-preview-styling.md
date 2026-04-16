# 2026-04-16 — Custom markdown preview component styling

## Changes

- Replaced Tailwind `prose` utility classes with explicit `react-markdown` component overrides in `markdown-preview.tsx`.
- Each markdown element (headings, lists, code blocks, tables, blockquotes, links, images) now has hand-tuned Tailwind classes using shadcn/ui design tokens (`border`, `muted`, `foreground`, `primary`).
- Inline code vs block code detection uses the `language-` class prefix to apply different styles.
- Links open in new tabs with `noreferrer noopener`.

## Notes

- This gives full control over markdown rendering without depending on `@tailwindcss/typography` prose styles, which were hard to override in dark mode and didn't match the shadcn/ui design system.
