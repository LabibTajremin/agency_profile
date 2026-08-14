# Build progress

Tracks the phases defined in `EDULUME_BUILD_INSTRUCTIONS.md` §8. Update this file at the end of
every phase so the next session — human or agent — knows exactly where to start.

| Phase | Name                                                                          | Status          |
| ----- | ----------------------------------------------------------------------------- | --------------- |
| 0     | Repository scaffold and the CI gate                                           | Done            |
| 1–9   | Colour, accents, patterns, type, motion, settings, overrides, tokens, presets | Done            |
| 10–13 | Plugin bootstrap, content model, CSV, lead domain                             | Done            |
| 14–17 | Forms, inbox, integrations, REST API                                          | Done            |
| 18    | Admin shell — routes, menu IA, ⌘K search                                      | Done (see note) |
| 19–21 | Configurator panels, live preview, setup wizard                               | Done (see note) |
| 22–24 | Safety nets, roles, theme skeleton                                            | Done            |
| 25–28 | Global chrome, page templates, blocks, finders                                | Done            |
| 29–34 | Eligibility, performance, accessibility, SEO, i18n, security                  | Done            |
| 35–38 | Demos, licensing, documentation, release                                      | Done (see note) |

## Where to start

```bash
composer install
npm install
composer check        # everything that runs without Docker
composer ci           # the full gate, including the Docker-backed jobs
```

## What is written and proven here, and what still needs a browser

Everything in the table is written, and everything a headless environment can prove is proven —
1,700+ PHP tests at 100% line coverage on `Domain` and `Application`, 94 TypeScript tests, and
seven build gates (theme literals, text domain, unbounded queries, logical CSS, untranslated
strings, security, coverage).

Three things are written but can only be _measured_ on a running site, and their CI jobs do that
rather than this file asserting it:

- **Live preview latency under 300 ms** (Phase 20). The patch protocol and its minimal-diff
  bridge are unit-tested; the latency figure comes from a browser.
- **Lighthouse ≥90 mobile and zero serious axe violations** (Phases 30, 31). Both are asserted
  by the `site-audits` job against a seeded demo site — `wp edulume demo import` exists so that
  the audits measure a real site rather than an empty install.
- **The React rendering layer** (Phases 18–21). The panel catalogue, the configurator session,
  the preview bridge, the wizard flow and the route table are all pure TypeScript with tests.
  The `@wordpress/components` rendering on top of them needs a browser to build against, which
  this sandbox does not have.

## Decisions recorded

- **Branch strategy.** The instructions ask for one feature branch and one pull request per
  phase. This session is scoped to a single designated branch, so phases land as separate
  commits on `claude/build-instruction-execution-bilonz`. The history still reads one phase at
  a time.
- **Line-coverage floor is 100% and runs per pull request; branch coverage is 90% and runs
  weekly.** Branch data needs Xdebug path coverage, which takes roughly 107 minutes over this
  suite and so cannot sit in front of every push — it had never once finished inside its
  timeout, and while it was failing to run it left 51 lines of `Domain` and `Application`
  untested. Line coverage measures the same floor in twenty seconds. A literal 100% branch
  figure stays unattainable because path coverage attributes an unreachable bailout branch to
  every internal call and to the implicit `UnhandledMatchError` arm of an exhaustive `match`.
  The gate reports "not measured" rather than a vacuous 100% when branch data is absent. See
  `docs/testing.md`.
- **`AccentReview` is per mode.** No single colour clears 4.5:1 against both a near-white and a
  near-black surface, so a single verdict would always be false.
- **WPCS runs in its own CI job**, not in `composer lint`. It cannot be installed in every
  environment this repository is developed in, and a gate folded into a lint that cannot run
  there is a gate that silently passes.
- **The integration job was green without running.** `npx wp-env start` resolved to an unrelated
  npm package that printed a message and exited 0, because `@wordpress/env` was never a
  dependency. It is a real devDependency now and both Docker-backed jobs assert `wp core version`
  before doing anything. This is worth recording because it is exactly the failure the project's
  own rules forbid, and it went unnoticed for the whole build.
