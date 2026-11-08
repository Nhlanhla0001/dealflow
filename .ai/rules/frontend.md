# Frontend Rules

**Globs:** `resources/js/**`, `resources/css/**`, `resources/views/**`

## Before editing

1. Load `impeccable` with the `skill` tool and follow its workflow for the surface in scope.
2. Run Impeccable `context` on the surface before changing it. Use `critique` or `audit` for page-level work, not only the landing page.
3. Load `inertia-react-development` for Inertia pages, forms, navigation, deferred props, or optimistic updates.
4. Load `tailwindcss-development` when changing utilities, layout, spacing, or responsive behavior.
5. Use Boost `search-docs` for Inertia or React API behavior, and `browser-logs` after exercising the flow.

## Standards

- Reuse existing components in `resources/js/components` before writing new ones.
- Every async surface has loading, empty, error, and success states.
- No truncation, overflow, or hierarchy regressions at 360 px, 768 px, and 1440 px.
- Text and controls meet contrast and focus requirements; do not remove focus rings.
- Copy changes in a UI file are still copy changes: route through `marketing.md` when the surface matches.
- Run `npm run check`, `npm run types:check`, and `npm run build` after frontend edits.

## Evidence

Paste the Impeccable findings that were fixed. State the viewport sizes checked. Attach or describe the screenshots. Name any remaining Impeccable finding that was deliberately deferred.
