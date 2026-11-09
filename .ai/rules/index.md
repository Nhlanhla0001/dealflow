# DealFlow Rule Index

Read this file before entering plan mode or editing any file. Then read every rule below whose globs match the path(s) in scope. Also run `grep -rin '<keyword>' .ai/rules` to catch conventions a path match alone misses.

| Rule                           | Applies to                                                                                                                   | Required before editing                                         |
| ------------------------------ | ---------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------- |
| [`backend.md`](backend.md)     | `app/**`, `database/**`, `routes/**`, `config/**`, `bootstrap/**`                                                            | Laravel skill, `laravel-best-practices`, Laravel Boost evidence |
| [`frontend.md`](frontend.md)   | `resources/js/**`, `resources/css/**`, `resources/views/**`                                                                  | `impeccable`, Impeccable context/critique, UI verification      |
| [`marketing.md`](marketing.md) | `resources/js/pages/welcome*`, `marketplace/**`, `pricing*`, `checkout/**`, `onboarding/**`, `auth/**`, marketing components | Corey Haines skill, conversion and copy checks                  |
| [`testing.md`](testing.md)     | `tests/**`, `phpunit*.xml`, `.github/workflows/**`                                                                           | `testing-best-practices`, TDD or regression evidence            |

## Global rules

- The route line from `AGENTS.md` comes first. No tool call before it.
- A skill that applies is loaded with the `skill` tool, not recalled from memory.
- A task that touches more than one area reads every matching rule file.
- A skipped step is named with a reason. Silent skipping is a failure.
- Evidence beats assertion. Paste the command result, screenshot, or CI run.
- Publishing (`git commit`, `git push`, `gh pr create`) requires `verification-before-completion` and passing checks.
- `OPENCODE_SKILL_ROUTER=off` is only for a stated, intentional exception.
