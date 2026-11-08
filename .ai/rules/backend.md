# Backend Rules

**Globs:** `app/**`, `database/**`, `routes/**`, `config/**`, `bootstrap/**`

## Before editing

1. Load the routed Laravel skill plus `laravel-best-practices`.
2. Make the required Laravel Boost call:
    - `database-schema` for any table, column, model, relationship, or query-shape change.
    - `search-docs` for framework, Inertia, Fortify, queue, or package API behavior.
    - `database-query` for read-only verification of data or state.
    - `last-error` / `read-log-entries` for any failure.
3. Confirm installed versions with `composer show --direct` before assuming an API.

## Standards

- Use `php artisan make:*` with `--no-interaction` for controllers, models, migrations, requests, policies, jobs, and tests.
- Schema changes belong in `create_*` migrations for this project; do not reintroduce `add_*` migrations.
- Wrap multi-write operations in `DB::transaction`, lock rows for state transitions, and use idempotency keys for money and usage records.
- Authorization goes through policies, not controller conditionals.
- Every write path that can fail has a test.
- Run `vendor/bin/pint --dirty --format agent` after PHP edits.

## Diagnostics

Laravel LSP runs through the project plugin:

- Every `write`, `edit`, and `patch` on a `.php` or `.blade.php` file returns Laravel diagnostics for routes, config, views, env, middleware, policies, and Inertia.
- Reading a PHP file warms the server so the next edit is fast.
- Use the `laravel_lsp` tool for on-demand diagnostics or hover on a route, config key, or view.
- Fix every reported WARN or ERROR before claiming the change is complete. The first LSP call after a server restart pays a cold-start cost; later calls are fast.

## Evidence

Name the Boost calls used in the final report. Name any LSP findings and their fixes. Run the narrowest test file first, then the full suite. Show the pass count.
