# Testing Rules

**Globs:** `tests/**`, `phpunit*.xml`, `.github/workflows/**`

## Before editing

1. Load `testing-best-practices`.
2. Load `test-driven-development` when implementing new behavior or fixing a bug.
3. Load `systematic-debugging` first when a test is failing for an unexplained reason.
4. Use Boost `search-docs` for PHPUnit, Inertia testing, or framework testing APIs.

## Standards

- Most coverage is feature coverage. Unit tests are for pure logic.
- Use factories and their states; do not hand-build models the factory already covers.
- Test behavior and the important failure mode, not every line.
- Copy, styling, and layout-only changes do not require tests.
- Database tests must pass on PostgreSQL, not only SQLite.
- CI is part of the test surface: a change to `.github/workflows/**` is verified by watching the run.

## Evidence

Run the narrowest test file first, then the full suite. Report test count and assertions. For PostgreSQL, report `composer test:pgsql`. For CI, report the run ID and job status. Do not claim green without the output.
