# DealFlow AI → DealFlow Rename Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rename the product brand from "DealFlow AI" to "DealFlow" everywhere — env/config, UI, email subjects, and docs — with zero remaining occurrences.

**Architecture:** Mechanical literal replacement of `DealFlow AI` → `DealFlow` across the repo, with two hand-fixes for split-markup brand headers (`DealFlow <span className="font-normal">AI</span>`). Verification is grep-zero plus the existing build/test gates; no behavior changes, so no new tests.

**Tech Stack:** Laravel 13 / PHP 8.4, Inertia + React (TypeScript, Tailwind), PHPUnit, Vite.

**Spec:** In-conversation design approved this session (no spec file — project rules bar unrequested docs). Design summary:

- **Goals:** zero `DealFlow AI` matches repo-wide; `APP_NAME`/`VITE_APP_NAME` = `DealFlow`; build, tests, Pint, LSP clean.
- **Non-goals:** no GitHub repo rename (`dealflow-ai` URLs stay), no `DealFlow123!` sandbox/demo password change, no `dealflow.*` config keys or `@dealflow.test` emails touched, no copy rewording beyond the name itself, no visual changes.
- **Approach chosen:** mechanical rename (approach #1) over a curated copy pass (#2, churn with no payoff) and UI-only (#3, ruled out by "All").

## Global Constraints

- Replace ONLY the exact literal `DealFlow AI` → `DealFlow`. Never replace bare `DealFlow`.
- Never touch: `dealflow-ai` (5 GitHub URLs), `DealFlow123!` (README:189, docs/devpost-submission.md:89), `dealflow.*` config prefix, `@dealflow.test` emails, package name (`package-lock.json` already `DealFlow`).
- Two split-markup headers become plain `DealFlow` with the `<span className="font-normal">AI</span>` wrapper removed: `resources/js/pages/welcome.tsx:115`, `resources/js/layouts/marketplace-layout.tsx:26`.
- Copy-only change: per `.ai/rules/testing.md`, no new tests required; the full existing suite must still pass.
- Commit steps are omitted from this plan; the repository history records the result.
- Skills to have loaded via `skill` before the first edit: `impeccable`, `inertia-react-development`, `tailwindcss-development`, `copy-editing`, `laravel-best-practices`; `verification-before-completion` before claiming done.
- Laravel Boost MCP tools are NOT connected in this session (catalog search for `boost`/`database-schema`/`search-docs` returns nothing). Name this as a skipped step with that reason in the final report. Substitutes: `php artisan config:show`, `laravel_lsp` diagnostics, full test suite.
- PHP gates: `vendor/bin/pint --dirty --format agent` after PHP edits; fix every `laravel_lsp` WARN/ERROR.
- Frontend gates (per `.ai/rules/frontend.md`): `npm run check`, `npm run types:check`, `npm run build`, then exercise the flow and read `browser-logs`.
- Viewport evidence at 360 px, 768 px, 1440 px (the new name is shorter than the old, so overflow risk is one-directional — confirm nothing re-wraps awkwardly).

## Review Focus

1. **`.env` is gitignore-hidden from ignore-respecting search** — a missed `APP_NAME` leaves the running app on the old name. Pinned to Task 1 by `php artisan config:show app.name` expecting `DealFlow`.
2. **Split markup escapes a literal text replace** — `DealFlow <span ...>AI</span>` would render "DealFlow AI" still. Pinned to Task 3 by grep for `AI</span>` expecting zero, plus the browser header check.
3. **Over-broad replacement corrupts exclusions** — `dealflow-ai` URLs, `DealFlow123!` passwords, `dealflow.*` config keys. Pinned to Task 5 by greps: `dealflow-ai` still ≥6 matches, `DealFlow123!` still 2 matches, and the suite (which exercises `config('dealflow.ai_negotiation')`) passes.
4. **`VITE_APP_NAME` feeds the document title** (`resources/js/app.tsx:9`) — a missed env edit shows a stale title. Pinned to Task 1 (env edit) and checked in Task 3's browser step (page title reads `DealFlow`).
5. **Email subjects are user-facing with zero test coverage** — no test asserts any brand string. Pinned to Task 2 by per-file grep after edit + `laravel_lsp` diagnostics, and by the full suite in Task 5.

---

### Task 1: Env & config rename

**Files:**

- Modify: `.env` (APP_NAME, VITE_APP_NAME — read first; ignore-respecting search hides this file)
- Modify: `.env.example:1,78`
- Modify: `.env.production.example:1,56`

**Interfaces:**

- Consumes: none.
- Produces: `APP_NAME=DealFlow` and `VITE_APP_NAME=DealFlow` — flows to `config/app.php:16`, `MAIL_FROM_NAME`, cache/session/redis prefixes, and `resources/js/app.tsx:9` (document title).

- [ ] **Step 1: Load `laravel-best-practices`** (skip if already loaded this session).

- [ ] **Step 2: Read `.env`** to locate the exact `APP_NAME` and `VITE_APP_NAME` lines (file is hidden from ignore-respecting search).

- [ ] **Step 3: Replace in all three env files** — `APP_NAME="DealFlow AI"` → `APP_NAME="DealFlow"` and `VITE_APP_NAME="DealFlow AI"` → `VITE_APP_NAME="DealFlow"`. Leave `MAIL_FROM_NAME="${APP_NAME}"` untouched.

- [ ] **Step 4: Verify**

```powershell
php artisan config:show app.name        # Expected: DealFlow
Select-String -Path .env,.env.example,.env.production.example -Pattern 'DealFlow AI'   # Expected: no output
```

---

### Task 2: Backend notification subjects

**Files:**

- Modify: `app/Notifications/TermsRejected.php:26`
- Modify: `app/Notifications/TermsReadyForApproval.php:22`
- Modify: `app/Notifications/CounterpartyApprovedTerms.php:26`
- Modify: `app/Notifications/BuyerRequestReceived.php:20`

**Interfaces:**

- Consumes: none.
- Produces: subject strings `'DealFlow — …'` — no signature changes; nothing downstream reads the subject.

- [ ] **Step 1: Confirm `laravel-best-practices` is loaded** (from Task 1).

- [ ] **Step 2: Replace in each of the four files** — `->subject('DealFlow AI — ` → `->subject('DealFlow — ` (the em-dash and spacing stay exactly as-is).

- [ ] **Step 3: Verify with Laravel LSP** — `laravel_lsp` diagnostics on each of the four files; Expected: 0 WARN, 0 ERROR. (First call may pay a cold-start cost.)

- [ ] **Step 4: Verify no literal remains**

Run grep for `DealFlow AI` scoped to `app/` → Expected: 0 matches.

- [ ] **Step 5: Pint**

```powershell
vendor/bin/pint --dirty --format agent
```

Expected: exits 0; report any file it reformatted.

---

### Task 3: Frontend pages & layouts (incl. split markup)

**Files:**

- Modify: `resources/js/pages/welcome.tsx:115` (split span), `:193`, `:581`
- Modify: `resources/js/layouts/marketplace-layout.tsx:26` (split span), `:98`
- Modify: `resources/js/pages/transactions/index.tsx:44`
- Modify: `resources/js/pages/transactions/create.tsx:44`
- Modify: `resources/js/pages/dashboard.tsx:108`
- Modify: `resources/js/pages/marketplace/seller.tsx:21`
- Modify: `resources/js/pages/onboarding/wizard.tsx:184`
- Modify: `resources/js/pages/notifications/index.tsx:27`

**Interfaces:**

- Consumes: `VITE_APP_NAME=DealFlow` from Task 1 (document title in `app.tsx`).
- Produces: rendered brand string `DealFlow` in all headers/footers/meta copy.

- [ ] **Step 1: Load skills** — `impeccable`, `inertia-react-development`, `tailwindcss-development`, `copy-editing` (skip any already loaded); run Impeccable `context` on the landing page and one app page before editing, per `.ai/rules/frontend.md`.

- [ ] **Step 2: Literal replace** `DealFlow AI` → `DealFlow` everywhere it appears as one contiguous string: welcome.tsx (193, 581), marketplace-layout.tsx (98), transactions/index.tsx (44), transactions/create.tsx (44), dashboard.tsx (108), marketplace/seller.tsx (21), onboarding/wizard.tsx (184), notifications/index.tsx (27). The split-span occurrences in the same two layout files are NOT caught here — Step 3 handles them.

- [ ] **Step 3: Hand-fix the two split headers** — in `welcome.tsx:115` and `marketplace-layout.tsx:26`, replace `DealFlow <span className="font-normal">AI</span>` with plain `DealFlow` (the `<span>` wrapper is removed entirely; surrounding JSX/whitespace preserved).

- [ ] **Step 4: Verify no literal remains in `resources/js`**

Grep `DealFlow AI` and `AI</span>` scoped to `resources/js` → Expected: 0 matches for both.

- [ ] **Step 5: Frontend gates**

```powershell
npm run check
npm run types:check
npm run build
```

Expected: all exit 0.

- [ ] **Step 6: Browser check** — start the dev server in the background (`composer run dev` or `npm run dev`), open the landing page and the marketplace page; confirm the header/brand reads `DealFlow` (no leftover `AI`, no orphaned spacing), page title reads `DealFlow`, and nothing re-wraps at 360 px, 768 px, 1440 px. Then read `browser-logs`; Expected: no new errors.

---

### Task 4: Docs

**Files:**

- Modify: `README.md:1,5,9,17` (NOT line 189 — `DealFlow123!` password stays)
- Modify: `PRODUCT.md:1,5` (line 40 already says plain "DealFlow")
- Modify: `AGENTS.md:1`
- Modify: `.ai/rules/index.md:1`
- Modify: `docs/devpost-submission.md:1,5` (NOT line 89 — password stays)

**Interfaces:**

- Consumes: none.
- Produces: consistent brand in all project documentation.

- [ ] **Step 1: Replace** `DealFlow AI` → `DealFlow` in the listed lines of the five files. Do not touch GitHub URLs (`dealflow-ai`) or password lines.

- [ ] **Step 2: Verify**

Grep `DealFlow AI` across the repo excluding `vendor`/`node_modules` → Expected: 0 matches (Task 5 re-runs this repo-wide as the final gate).

---

### Task 5: Final verification & report

**Files:** none modified.

**Interfaces:**

- Consumes: Tasks 1–4 outputs.
- Produces: the evidence block for the completion claim.

- [ ] **Step 1: Repo-wide zero-check** (including gitignored files):

```powershell
rg "DealFlow AI" --hidden -g '!vendor/**' -g '!node_modules/**' -g '!storage/**' -g '!bootstrap/cache/**'
```

Expected: no output. (If `rg` is unavailable, use `Get-ChildItem -Recurse -File` + `Select-String` with the same exclusions.)

- [ ] **Step 2: Exclusions intact** — `rg "dealflow-ai"` → ≥6 matches; `rg "DealFlow123!"` → 2 matches; `php artisan config:show database.default` still resolves (config untouched).

- [ ] **Step 3: Config** — `php artisan config:show app.name` → `DealFlow`.

- [ ] **Step 4: Pint** — `vendor/bin/pint --dirty --format agent` → exit 0, no changes.

- [ ] **Step 5: Full suite** — `php artisan test --compact` → all pass; paste the pass count.

- [ ] **Step 6: Frontend gates re-run if any edit followed Step 5 of Task 3** — otherwise cite Task 3 output.

- [ ] **Step 7: Write the report** — must state: Boost calls skipped and why (MCP not connected this session); LSP results per PHP file; viewport sizes checked; browser-logs result; that the directory is not a git repo so no commits were made; any deliberately deferred finding.
