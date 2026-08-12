# Build progress

Tracks the phases defined in `EDULUME_BUILD_INSTRUCTIONS.md` §8. Update this file at the end
of every phase so the next session — human or agent — knows exactly where to start.

| Phase | Name                                     | Status      |
| ----- | ---------------------------------------- | ----------- |
| 0     | Repository scaffold and the CI gate      | Done        |
| 1     | Colour foundation                        | Done        |
| 2     | Accent palettes                          | Done        |
| 3     | Pattern library                          | Done        |
| 4     | Typography                               | Not started |
| 5     | Motion                                   | Not started |
| 6     | Settings aggregate and migrations        | Not started |
| 7     | Per-section overrides and inheritance    | Not started |
| 8     | Token compiler and compiled-CSS pipeline | Not started |
| 9     | Style presets                            | Not started |
| 10    | Plugin bootstrap and WordPress infra     | Not started |
| 11–38 | Content, leads, admin, theme, release    | Not started |

## Where to start

```bash
composer install
npm install
composer check
```

Phase 4 is next: `Domain/Theming/` gains `FontFamily`, `FontCategory`, `FontLibrary` (22
self-hosted families), `FontRole`, `FontPairing`, `FontPairingLibrary` (10 pairings),
`TypeScale` and `TypographySettings`. Every bundled family needs a `CREDITS.md` entry in the
same commit that introduces it.

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
