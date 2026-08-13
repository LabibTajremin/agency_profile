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

Phase 16 is next: integrations and tracking — outbound webhook with a signed payload and
retries, Mailchimp/Brevo/Google Sheets/HubSpot connectors, GA4/Meta/GTM fields, and a
cookie-consent banner whose toggles actually gate the scripts.

Phase 15's WordPress-side screen still needs writing: the admin Leads list table, the detail
drawer and the export download handler. The listing, pipeline, export and autoresponder logic
they call is done and covered.

Phase 14's WordPress-side adapters still need writing: a REST/admin-post submission endpoint,
a transient-backed `RateLimiter`, reCAPTCHA/hCaptcha/Turnstile `CaptchaVerifier`
implementations, a `wp_mail` `LeadNotifier`, and an uploads-backed `UploadedFileStore`. The
ports, the use cases and the fakes proving the contracts are all in place.

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
