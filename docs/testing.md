# Testing

## The single command

```bash
composer check
```

Runs lint → static analysis → unit tests → integration tests. Exits non-zero if any step fails.
The same steps run on every pull request.

## Suites

| Layer            | Type                       | Location                                  | Proves                                               |
| ---------------- | -------------------------- | ----------------------------------------- | ---------------------------------------------------- |
| `Domain`         | Pure unit, no WordPress    | `plugins/edulume-core/tests/Unit/`        | Business rules are correct. 100% line + branch.      |
| `Application`    | Unit with in-memory fakes  | `plugins/edulume-core/tests/Unit/`        | Use cases orchestrate correctly. 100% line + branch. |
| `Infrastructure` | Integration under `wp-env` | `plugins/edulume-core/tests/Integration/` | Options persist, tables migrate, REST authorises.    |
| Admin SPA        | Vitest                     | `apps/admin/tests/`                       | Controls render, state updates, reset behaves.       |
| Public site      | Playwright                 | `tests/e2e/`                              | Templates render, forms submit, filters filter.      |
| Visual           | Playwright snapshots       | `tests/visual/`                           | The accent × mode × template matrix holds.           |

## Rules

- The test tree mirrors the source tree one to one.
- Test names read as sentences: `it_generates_a_full_palette_from_a_custom_hex()`.
- Fakes over mocks. A small in-memory repository beats a wall of mock expectations.
- Deterministic: no wall-clock time, no network, no random seeds, no order dependence.
- Fast: the unit suite finishes in under 30 seconds.
- Every bug fix starts with a failing test, committed together with the fix.

## Coverage

`bin/coverage-gate.php` parses the Clover report, restricts the calculation to `src/Domain` and
`src/Application`, and exits non-zero below the threshold. It works offline and is part of the
same red/green signal as the tests.

```bash
composer test:coverage && composer coverage:gate
```

### Why the branch threshold is not 100

Line coverage is gated at **100%** and is enforced exactly.

Branch coverage is gated separately, at **90%**. Xdebug's path coverage attributes an
unreachable bailout branch to every internal function call — `max()`, `preg_match()`,
`hexdec()` — and to the implicit `UnhandledMatchError` arm of an exhaustive `match`. Those
branches cannot be executed from a test, so a literal 100% is unreachable and a gate set
there would only teach the team to disable it.

The gate never reports a coverage figure it did not measure: a Clover report produced without
`--path-coverage` carries no branch data at all, and the gate says so rather than printing a
vacuous 100%.

Every condition still gets a test. The floor is the automated backstop, not the standard.
