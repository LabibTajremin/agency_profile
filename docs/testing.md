# Testing

## The single command

```bash
npx wp-env start   # once, for the integration suite
composer check
```

Runs lint → static analysis → unit tests → integration tests. Exits non-zero if any step fails.
The same steps run on every pull request.

The unit suite needs nothing but PHP. The integration suite needs a real WordPress, a real
database and real option storage — that is precisely what it proves — so it runs under
`wp-env` (Docker) and has its own config, `phpunit-integration.xml.dist`, with its own
bootstrap. Without WordPress present it fails with instructions rather than skipping: a green
run must never mean "did not run".

## Suites

| Layer            | Type                       | Location                                  | Proves                                               |
| ---------------- | -------------------------- | ----------------------------------------- | ---------------------------------------------------- |
| `Domain`         | Pure unit, no WordPress    | `plugins/edulume-core/tests/Unit/`        | Business rules are correct. 100% line + branch.      |
| `Application`    | Unit with in-memory fakes  | `plugins/edulume-core/tests/Unit/`        | Use cases orchestrate correctly. 100% line + branch. |
| `Infrastructure` | Integration under `wp-env` | `plugins/edulume-core/tests/Integration/` | Options persist, tables migrate, REST authorises.    |
| Admin SPA        | Vitest                     | `apps/admin/tests/`                       | Controls render, state updates, reset behaves.       |
| Public site      | Playwright                 | `tests/e2e/`                              | Templates render, forms submit, filters filter.      |
| Visual           | Playwright snapshots       | `tests/visual/`                           | The accent × mode × template matrix holds.           |

### Running the end-to-end suite

`tests/e2e/` was a declared row in the table above with nothing in the directory. It now holds
four specs, and they need a running site:

```bash
npm run env:start
npx wp-env run cli wp plugin activate edulume-core
npx wp-env run cli wp theme activate edulume-theme
npx wp-env run cli wp edulume demo import boutique --mode=full
npx wp-env run cli wp rewrite structure '/%postname%/' && npx wp-env run cli wp rewrite flush --hard
npm run test:e2e
```

Point it somewhere else with `EDULUME_BASE_URL`. The admin specs sign in with `admin` /
`password`, which is what `wp-env` creates; override with `WP_ADMIN_USER` and
`WP_ADMIN_PASSWORD`.

In CI these run inside the `site-audits` job rather than their own, because that job has already
booted WordPress, activated both, imported the pack and flushed the rewrites. A second job would
pay for all of that again to assert against the same site.

What they cover, and why each one exists:

| Spec                   | Proves                                                                         |
| ---------------------- | ------------------------------------------------------------------------------ |
| `front-page.spec.ts`   | Sections render content, not empty scopes. The toggle switches and persists.   |
| `destinations.spec.ts` | A country page uses its own template and is complete in the server's response. |
| `video-rail.spec.ts`   | No iframe before activation; never more than two players alive.                |
| `admin.spec.ts`        | Every screen explains itself; the import button exists; the shield is off.     |

Every one of those is a wiring bug that shipped: a section that rendered nothing, a screen with
no route behind it, a module bound to markup nobody emitted. None was catchable by a unit test
of either side, because both sides were correct on their own.

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

## Why coverage runs in its own job

Coverage runs once on PHP 8.1 rather than three times across the version matrix. The matrix job
runs the tests without coverage and answers the question it exists to answer — does the suite
pass on 8.1, 8.2 and 8.3 — in about two minutes.

### The gate that never ran

The per-pull-request gate used to be Xdebug **path** coverage. That was a mistake worth
recording, because it failed in the way that is hardest to notice: it did not report a wrong
answer, it reported no answer at all.

Path coverage enumerates every distinct execution path through each function, so its cost is
combinatorial in branch count where line coverage is linear. Over this suite it reached 56% in
sixty minutes and needed roughly 107, against a 90-minute timeout. It had never once finished.
Every push cancelled the attempt, and a timeout looks much like a cancellation in a check list.

A gate that never finishes is not a strict gate. It is no gate. While it was failing to run,
the suite was leaving 51 lines of `Domain` and `Application` untested — three of the five
conditional-logic operators that decide which questions a person is shown, every post type's
label set, both validation guards on `Pattern::of()`, and the `slugs()` accessor behind every
picker in the configurator. Line coverage found all of them in twenty seconds.

The per-pull-request gate is now line coverage, and it enforces the 100% floor exactly.

### What the two modes actually disagreed about

The old note claimed path coverage had to stay because line coverage "reports a different
figure for the same code" — 99.01% against 100%. That was true, and the difference was not
noise: three lines are unreachable by construction, and Xdebug's dead-code analysis was
excluding them silently under path coverage.

- `SvgSanitiser` narrows a `DOMNode` to `DOMAttr`. A `DOMNamedNodeMap` taken from `$attributes`
  yields nothing else, so the arm exists for static analysis and no test can reach it.
- `SettingsMigrator`'s `default` match arm. The version is clamped to `[first, current]` and
  the loop only runs below current, so version 1 is the only value it sees.
- `AdminTheme::meetsContrastRequirements` returning false. `compile()` resolves every
  foreground through `nearestCompliantForeground`, so no accent seed can fail it — a sweep of
  the RGB cube in both modes confirms none does.

Those three now carry `@codeCoverageIgnore` and state their reasoning in the source. Both modes
agree at 100%, and the exclusions are visible to a reader instead of implied by a coverage
driver's heuristics — which is the more honest arrangement, since an exclusion nobody can see
is indistinguishable from a line nobody tested.

### Branch coverage

Branch data needs path coverage, so it runs on its own schedule — weekly, plus manual dispatch,
in `.github/workflows/branch-coverage.yml` — rather than in front of every push. If that
cadence proves too loose, the next step is to shard the suite four ways and merge the resulting
`.cov` files with `phpcov`, which turns one 107-minute job into four 27-minute ones.

## Why the two suites run on different PHPUnit versions

The unit suite runs on PHPUnit 10 with attributes. The integration suite runs on PHPUnit 9 with
`@test` annotations, from a phar fetched in CI.

WordPress's core test suite calls `PHPUnit\Util\Test::parseTestMethodAnnotations()`, which
PHPUnit 10 removed. Every integration test therefore errors before reaching an assertion — all
42 of them, same message, zero assertions. That is true on WordPress 6.5 and still true on 6.8;
bumping the WordPress version does not fix it, which was worth finding out rather than assuming.

Running WordPress integration tests on PHPUnit 9 is what the ecosystem does. The cost is two
test dialects in one repository, and it buys a suite that actually runs.
