---
description: Full DealFlow ship pass — Boost, Impeccable, Corey Haines, tests, build, browser and CI evidence
---

Run the mandatory routing pass for: $ARGUMENTS

This command is the definition of done. Do not skip phases. If a phase does not apply to the scope, say why in one line and continue.

## Phase 0 — Route and scope

- Print: `Route: <skills + tools> — <why>`.
- Read `.ai/rules/index.md` and every rule file whose globs match the files in scope.
- List the files and flows in scope, and the files explicitly out of scope.

## Phase 1 — Laravel Boost discovery

Load no edits yet. Make the Boost calls the scope needs and record the results:

- `database-schema` for every table touched.
- `search-docs` for every framework or package API involved.
- `database-query` for current state.
- `last-error` and `read-log-entries` for any failure.
- `get-absolute-url` before using a URL.

## Phase 2 — Backend

- Load `laravel-best-practices` and the relevant `laravel-*` skill.
- Load `test-driven-development` for new behavior; load `systematic-debugging` for a defect.
- Implement, then run the narrowest tests and then the full suite.
- Run `vendor/bin/pint --dirty --format agent`.

## Phase 3 — Impeccable UI pass

- Load `impeccable`.
- Run `context` on each surface in scope.
- Run `critique` or `audit` per page.
- Run the detector.
- Fix hierarchy, truncation, spacing, contrast, empty/error/loading states, and responsive behavior.
- Check 360 px, 768 px, and 1440 px.
- Produce or update `DESIGN.md` only if the user asked for documentation.

## Phase 4 — Corey Haines pass

- Load `copywriting` or `copy-editing` for copy.
- Load `cro` for conversion surfaces: landing, marketplace listing, buyer request, checkout.
- Load `pricing` for credits or plans.
- Load `analytics` when the funnel needs measurement.
- Apply the recommendations, or record why a recommendation was rejected.

## Phase 5 — Verification

Run and paste the real output:

- `php artisan test --compact` (SQLite)
- `composer test:pgsql` (PostgreSQL)
- `vendor/bin/pint --dirty --format agent`
- `npm run check`
- `npm run types:check`
- `npm run build`
- `php artisan migrate:fresh --seed` on PostgreSQL
- Browser check of every changed flow, with console errors captured
- Push and watch the GitHub Actions run

## Phase 6 — Report

Use this exact structure:

```
Route:
Skills loaded:
Boost calls:
Changed:
Evidence (commands + results):
UI checks (widths + result):
Skipped or deferred (with reason):
Risks / not done:
```

Any claim without pasted evidence is a failure of this command.
