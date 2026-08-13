# Build progress

Tracks the phases defined in `EDULUME_BUILD_INSTRUCTIONS.md` §8. Update this file at the end
of every phase so the next session — human or agent — knows exactly where to start.

| Phase | Name                                     | Status                      |
| ----- | ---------------------------------------- | --------------------------- |
| 0     | Repository scaffold and the CI gate      | Done                        |
| 1     | Colour foundation                        | Done                        |
| 2     | Accent palettes                          | Done                        |
| 3     | Pattern library                          | Done                        |
| 4     | Typography                               | Done                        |
| 5     | Motion                                   | Done                        |
| 6     | Settings aggregate and migrations        | Done                        |
| 7     | Per-section overrides and inheritance    | Done                        |
| 8     | Token compiler and compiled-CSS pipeline | Done (domain + application) |
| 9     | Style presets                            | Not started                 |
| 10    | Plugin bootstrap and WordPress infra     | Not started                 |
| 11–38 | Content, leads, admin, theme, release    | Not started                 |

## Where to start

```bash
composer install
npm install
composer check
```

Phase 13 is next: the `Lead` entity, the `LeadStatus` pipeline with legal transitions enforced
in the domain, and the custom `{prefix}edulume_leads` tables with their migration runner.

Phase 12's WordPress-side adapters — a `ContentWriter` backed by `wp_insert_post`, a
`ContentReader` backed by `WP_Query`, a file-backed `CsvSource`, and a real
`ExecutionBudget` reading `max_execution_time` — still need writing. The ports, the use cases
and the fakes proving the contracts are all in place.

### Two things Phase 10 could not finish in this sandbox

- **The integration suite has not been run here.** It needs `wp-env`, which needs a Docker
  daemon, and this build environment has the Docker client but no daemon. The suite, its
  bootstrap, its own PHPUnit config and the `php-integration` CI job are all in place, and the
  job runs them on every pull request. Without WordPress present the suite fails with
  instructions rather than skipping — a green run must never mean "did not run".
- **WordPress Coding Standards are still not enforced.** `wp-coding-standards/wpcs` cannot be
  installed here (the sandbox cannot reach GitHub for Composer dist downloads), so adding the
  ruleset would mean shipping a gate whose findings nobody has seen — likely red on arrival.
  It needs adding, and its findings fixing, in an environment that can install it.

## Decisions recorded so far

- **Branch strategy.** The build instructions ask for one feature branch and one pull request
  per phase off `main`. This repository has no `main` branch and the session is scoped to a
  single designated branch, so phases land as separate commits on
  `claude/build-instruction-execution-bilonz` instead. The commit history still reads one
  phase at a time.
- **Branch-coverage floor.** Line coverage on `Domain` and `Application` is gated at 100% and
  holds. Branch coverage is gated separately at 90% because Xdebug path coverage attributes
  an unreachable bailout branch to every internal function call and to the implicit
  `UnhandledMatchError` arm of an exhaustive `match`. See `docs/testing.md`.
- **WordPress Coding Standards.** `phpcs.xml.dist` currently enforces PSR-12 only. WPCS lands
  with Phase 10, the first phase to contain WordPress-facing code for it to check.
- **`AccentReview` is per mode.** No single colour clears 4.5:1 against both a near-white and
  a near-black surface, so a single "is this hex compliant" verdict would always be false. The
  review reports per mode and suggests per mode.
