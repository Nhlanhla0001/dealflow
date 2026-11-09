# Messaging, Attachments & Agent Directives — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Give DealFlow a professional conversation layer (human↔human per transaction), real file uploads (message attachments, listing media, avatars), and human→agent directives that feed the next negotiation round.

**Architecture:** Four new tables (`messages`, `message_reads`, `attachments`, `negotiation_directives`). Attachments are polymorphic: `attachable` = Message (private, auth-streamed), Service (public listing images), or User (public avatar). Public files use the `public` disk (`/storage/...`); private files use `local` + an authorized streaming route, matching the existing dispute-evidence pattern.

**Tech Stack:** Laravel 13, Inertia v3 + React 19, PostgreSQL, Laravel AI 1.3.

**Spec:** design agreed in conversation 2026-11-08 (local storage; per-transaction threads; reuse of existing notification preferences).

## Global Constraints

- Schema lives in `create_*` migrations; no `add_*` migrations.
- Authorization through policies; every write path has a test.
- `vendor/bin/pint --dirty --format agent` after PHP edits.
- UI verified at 360 / 768 / 1440 px; `npm run check`, `npm run types:check`, `npm run build`.
- Uploads: max 10 MB; mimes `jpg,jpeg,png,webp,pdf,zip`; images max 4096×4096.

## Review Focus

1. Outsider (non-participant) must never read or post in a transaction thread → 403 test.
2. Private message attachments must not be fetchable by URL guess → authorization test.
3. Uploaded filename traversal (`../../`) must be neutralised → stored path is server-generated.
4. Unread counts must not count the reader's own messages → read-marker test.
5. Directives must apply once (idempotent) and never leak the seller floor → coordinator test.

---

### Task 1: Schema + models

**Files:** `database/migrations/*_create_messages_table.php`, `*_create_message_reads_table.php`, `*_create_attachments_table.php`, `*_create_negotiation_directives_table.php`; `app/Models/{Message,Attachment,NegotiationDirective}.php`; `app/Models/MessageRead.php`; factories.

- [ ] Create migrations via `php artisan make:migration create_* --no-interaction`.
- [ ] Models with `$fillable`, casts, relations (`Transaction hasMany Message`, `Message belongsTo Transaction/User hasMany Attachment via morphMany`, `Attachment morphTo attachable`).
- [ ] Factories for Message, Attachment, NegotiationDirective.

### Task 2: Policies + message endpoints

**Files:** `app/Policies/MessagePolicy.php`, `app/Http/Controllers/MessageController.php`, `app/Http/Requests/StoreMessageRequest.php`, `routes/web.php`, `app/Notifications/MessageReceived.php`.

- [ ] RED: `tests/Feature/MessageTest.php` — participant sends, outsider 403, validation, unread marker.
- [ ] GREEN: controller `index` (JSON thread + unread) and `store`; policy keyed to buyer/seller; notification to counterpart.
- [ ] Show props in `TransactionController@show`: `messages`, `messagesReadAt`.

### Task 3: Attachments

**Files:** `app/Http/Controllers/AttachmentController.php`, `app/Policies/AttachmentPolicy.php`, `routes/web.php`, `tests/Feature/AttachmentTest.php`.

- [ ] RED: upload returns id/url; oversize/mime rejected; outsider cannot download a message attachment; public service image is downloadable.
- [ ] GREEN: `store` (disk chosen by purpose), `show` streaming.

### Task 4: Agent directives

**Files:** `app/Http/Controllers/NegotiationDirectiveController.php`, `app/Models/NegotiationDirective.php`, `NegotiationCoordinator` prompt injection, `tests/Feature/NegotiationDirectiveTest.php`.

- [ ] RED: participant stores directive; outsider 403; directive appears once and is marked applied.
- [ ] GREEN: store + coordinator reads unapplied directives, appends to prompt, marks applied atomically.

### Task 5: Listing media upload (UI)

**Files:** `resources/js/pages/seller/services/steps/media-step.tsx`, `app/Http/Controllers/WizardDraftController.php` (validation accepts URLs from uploads).

- [ ] Upload control → `POST /attachments` (purpose `service_image`) → store returned URL in `image_urls`.
- [ ] Previews, remove, reorder-safe, 360/768/1440.

### Task 6: Avatar upload (UI + settings)

**Files:** `app/Http/Controllers/Settings/ProfileController.php`, `resources/js/pages/settings/profile.tsx` (or existing profile page), `Attachment` attachable=User.

- [ ] Upload avatar → public URL; show in header/user menu with initials fallback.

### Task 7: Transaction conversation UI

**Files:** `resources/js/pages/transactions/show.tsx`, `resources/js/components/…` (only if a needed primitive is missing).

- [ ] Thread with own/counterpart bubbles, timestamps, empty state, loading/error states.
- [ ] Composer: textarea, attach button, send; optimistic disabled-state; unread divider.
- [ ] Agent directive composer (separate card, clearly labelled).

### Task 8: Final gates

- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --compact` (SQLite) and `composer test:pgsql`
- [ ] LSP diagnostics on every edited PHP file
- [ ] `npm run check`, `npm run types:check`, `npm run build`
- [ ] Browser verification at 360/768/1440 with real send + upload
