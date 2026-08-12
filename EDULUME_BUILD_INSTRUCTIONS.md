# Edulume — Build Instructions for an AI Coding Agent

**Product:** `edulume` — a premium WordPress theme + companion plugin for study-abroad / overseas-education consultancies.
**Source of truth for *what* to build:** the Product Requirements Document (PRD v1.0). This document defines *how* to build it.
**Audience:** an autonomous AI coding agent (Claude Code, Cursor, Codex, or equivalent) with repository write access and GitHub permissions.

---

## 0. How to use this document

Read sections **1–7 completely before writing a single line of code.** They are binding rules, not suggestions.

Then execute **Section 8** phase by phase, in order. Do not skip ahead. Do not batch phases together.

If any instruction in this document conflicts with the PRD, **this document wins on process and engineering standards**; the **PRD wins on product scope and requirements**.

If a requirement is genuinely ambiguous and the two possible readings would produce materially different work, stop and ask. Otherwise, make the call, write the assumption into the PR description, and keep building.

---

## 1. The five non-negotiable rules

These override everything else. A phase that violates any of them is not complete, regardless of whether the feature works.

### Rule 1 — Standard industrial coding style. No mess.

The code will be maintained by one person who did not write it. Every decision below serves that person.

- **One class, interface, enum, or trait per file.** The filename matches the type name exactly.
- **The directory tree is the architecture.** A developer looking for the lead pipeline logic must find it at a path that says "lead pipeline". Never scatter one concern across three directories.
- **No god files.** A PHP class over ~300 lines or a function over ~40 lines needs a stated reason in the PR description or it must be split.
- **Names are full words.** `AccentPalette`, not `AccPal`. `$institutionCount`, not `$ic`. The only permitted abbreviations are ones a domain expert would say out loud (`css`, `url`, `id`, `seo`).
- **No magic values.** Every literal number or string that carries meaning becomes a named constant or an enum case.
- **Comments explain *why*, never *what*.** If code needs a comment to explain what it does, rename things until it doesn't. Delete commented-out code before committing.
- **Consistent formatting, enforced by a tool, not by discipline.** PHP: PSR-12 for framework-agnostic code, WordPress Coding Standards for WordPress-facing code. TS/JS/CSS: Prettier + ESLint. Formatting is never a review topic because a linter already settled it.
- **Public API surfaces are documented.** Every public method has a docblock stating its contract when the signature alone is not self-evident. Private helpers usually do not need one.
- **Errors are explicit.** No silent `catch (Exception $e) {}`. Either handle it, or let it propagate, or log it with context. Rendering paths degrade gracefully; admin paths surface the problem.
- **No dead code, no speculative abstraction.** Do not build an interface with one implementation "in case". Build it when the second implementation arrives.

> **Standing preference (applies to all of this owner's projects, not just this one):**
> Industrial, boring, readable code. Clean separation of layers. A newcomer must be able to open the repo and find the thing they are looking for within two minutes without asking anyone. Prefer obvious over clever. Prefer explicit over magic. Never leave a mess for the next person.

### Rule 2 — Full test coverage

- **Coverage floor: 100% line and branch coverage on the `Domain` and `Application` layers.** These layers have no WordPress dependency and no excuse. CI fails below the floor.
- **Coverage floor: 80% on `Infrastructure` and `Admin` layers,** measured by integration tests running against a real WordPress instance.
- **Every bug fix begins with a failing test** that reproduces the bug. The commit contains the test and the fix together.
- **Every public behaviour has a test.** Not every private method — test through the public surface.
- Tests must be **deterministic**: no reliance on wall-clock time, network access, random seeds, or test execution order. Inject a clock, inject a random source, stub the network.
- Tests must be **fast**: the unit suite completes in under 30 seconds. If it does not, the domain layer has leaked a dependency.
- **A phase is not done until its tests pass locally *and* in CI.**

### Rule 3 — CLI test runner, wired into GitHub pull request checks

There is a single command that runs everything:

```bash
composer check
```

It runs, in order: lint → static analysis → unit tests → integration tests → coverage gate. It exits non-zero if any step fails.

The same command runs in GitHub Actions on **every pull request**. A PR with a red check is not mergeable. See Section 5 for the exact workflow definition.

### Rule 4 — Feature branch → pull request → green checks → only then done

For every phase:

1. Create a feature branch off `main`: `git checkout -b feat/phase-NN-short-slug`
2. Do the work for **that phase only**.
3. Run `composer check` locally until green.
4. Commit and push.
5. Open a pull request targeting `main`.
6. **Wait for all CI checks to report green.**
7. If any check is red: fix it, push again, wait again. Repeat until green.
8. Only when every check is green is the phase complete. Merge, then move to the next phase.

**The work is not done when the code is written. The work is done when the PR is green.** Never report a phase complete on the basis of local results alone. Never merge a red PR. Never disable, skip, or `continue-on-error` a failing check to make it pass.

### Rule 5 — Small phases, commit and push after each one

Section 8 defines the phases. Each is deliberately small — roughly a day of focused work, one coherent unit of review.

- **One phase, one branch, one PR.** Never combine two phases into one PR.
- **Never push everything at once at the end.** Push after every phase.
- Commit messages follow **Conventional Commits**: `feat(theming): generate accent palettes from a base hex`.
- If a phase turns out larger than expected mid-flight, **split it** and open two PRs. Splitting is always allowed. Batching never is.

---

## 2. Architecture you must follow

Two packages. The split is mandatory (PRD `AR-01`).

```
edulume-core/     ← PLUGIN. Owns all data, logic, settings, leads, REST. Survives a theme switch.
edulume-theme/    ← THEME. Presentation only. Zero database writes. Renders; never persists.
```

Inside the plugin, four layers with a strict dependency direction:

```
Domain          →  entities, value objects, pure business rules. ZERO wp_* calls. ZERO I/O.
Application     →  use cases orchestrating the domain. Depends on Domain + port interfaces only.
Infrastructure  →  WordPress hooks, repositories, database, REST controllers, file writing.
Admin           →  settings app bootstrap, REST schema, admin screens.
```

**Dependencies point inward only.** `Domain` knows about nothing. `Application` knows about `Domain`. `Infrastructure` knows about both. Nothing points back outward. This is what makes the domain 100%-testable without WordPress, which is what makes Rule 2 achievable.

Data access happens through **repository interfaces** defined in `Application/Port/`, with WordPress implementations in `Infrastructure/`.

The theme talks to the plugin **only** through documented PHP functions, hooks, and REST (`AR-04`). It never reads a plugin option key directly.

---

## 3. Repository layout

Create exactly this. A developer must be able to predict where anything lives.

```
/
├── README.md
├── CHANGELOG.md                        Keep a Changelog format, semantic versioning
├── CREDITS.md                          every bundled asset: source, licence, licence URL
├── composer.json                       root: autoload, scripts, dev dependencies
├── package.json                        root: build tooling for admin app and theme assets
├── phpunit.xml.dist
├── phpstan.neon.dist
├── phpcs.xml.dist
├── .editorconfig
├── .eslintrc.json
├── .prettierrc
├── .gitignore
├── .github/
│   ├── workflows/ci.yml                the PR gate (Section 5)
│   └── pull_request_template.md
├── docs/
│   ├── architecture.md
│   ├── theming.md
│   ├── configuration.md
│   ├── testing.md
│   ├── csv-import.md
│   ├── child-theme.md
│   ├── deployment.md
│   └── handover.md
├── plugins/edulume-core/
│   ├── edulume-core.php                plugin header + bootstrap ONLY, no logic
│   ├── uninstall.php
│   ├── src/
│   │   ├── Domain/
│   │   │   ├── Color/                  colour space maths, contrast engine
│   │   │   ├── Theming/                palettes, patterns, typography, motion, settings
│   │   │   ├── Content/                CPT definitions as value objects
│   │   │   ├── Lead/                   lead entity, pipeline states, form schema
│   │   │   └── Support/                shared guards and small helpers
│   │   ├── Application/
│   │   │   ├── Theming/                use cases: CompileStylesheet, ApplyPreset, …
│   │   │   ├── Lead/                   use cases: RegisterLead, AssignLead, ExportLeads
│   │   │   ├── Content/                use cases: ImportCoursesCsv, …
│   │   │   └── Port/                   repository + service interfaces
│   │   ├── Infrastructure/
│   │   │   ├── Wp/                     hook registration, activation, capabilities
│   │   │   ├── Settings/               option storage, schema migrations
│   │   │   ├── Theming/                compiled-CSS writer, asset enqueueing
│   │   │   ├── Content/                CPT/taxonomy registration, meta boxes
│   │   │   ├── Lead/                   custom tables, repositories, notifications
│   │   │   └── Rest/                   REST controllers, namespace edulume/v1
│   │   └── Admin/
│   ├── assets/admin/                   built admin SPA output (committed build artefact)
│   ├── blocks/                         custom Gutenberg block registrations
│   ├── languages/                      edulume.pot
│   └── tests/
│       ├── Unit/                       mirrors src/Domain and src/Application exactly
│       └── Integration/                mirrors src/Infrastructure, runs under wp-env
├── themes/edulume-theme/
│   ├── style.css
│   ├── theme.json
│   ├── functions.php                   bootstrap only; degrades if plugin absent
│   ├── templates/
│   ├── parts/
│   ├── blocks/
│   ├── assets/{css,js,fonts,icons}/
│   └── screenshot.png
├── themes/edulume-child/               shipped child theme (CO-12)
└── apps/admin/                         React + TypeScript configurator source
    ├── src/
    └── tests/
```

**The test tree mirrors the source tree, one to one.** `src/Domain/Theming/PaletteGenerator.php` is tested by `tests/Unit/Theming/PaletteGeneratorTest.php`. No exceptions — this is how the maintainer finds the test for a class without searching.

---

## 4. Toolchain

| Layer | Choice | Notes |
|---|---|---|
| PHP | 8.1 minimum, test through 8.3 | shared-hosting reality |
| WordPress | 6.5+ | `theme.json` v3, stable block APIs |
| Autoloading | Composer PSR-4 | `Edulume\Core\` → `plugins/edulume-core/src/` |
| Unit tests | PHPUnit 10 | no WordPress bootstrap on the unit suite |
| Integration tests | PHPUnit + `wp-env` (Docker) | real WordPress, real database |
| E2E | Playwright | public templates + admin flows |
| Visual regression | Playwright snapshots | the accent × mode × template matrix |
| Static analysis | PHPStan level 6 minimum, level 8 on `Domain` | |
| PHP linting | PHPCS: PSR-12 on `Domain`/`Application`, WPCS on `Infrastructure`/`Admin`/theme | |
| Admin UI | React + TypeScript on `@wordpress/components` | |
| Front-end JS | Vanilla TypeScript modules, **no framework, no jQuery** | 120 KB gzipped budget |
| Motion | Motion One (MIT, ~5 KB) + CSS + IntersectionObserver | |
| Styling | CSS custom properties + PostCSS | no utility framework |
| Build | Vite | |
| Accessibility | axe-core in Playwright | zero critical/serious violations |

**Explicitly rejected** (do not introduce, do not "temporarily" add): Elementor/WPBakery/Divi dependency, Redux Framework, Google Fonts CDN as the default, jQuery on the front end, Font Awesome, storing leads in the posts table, raster pattern tiles.

**Every bundled asset must carry a redistribution-safe licence** (SIL OFL, MIT, ISC, Apache-2.0, CC0, or a purchased extended licence) and must be recorded in `CREDITS.md` in the same commit that introduces it. An asset with no `CREDITS.md` entry is a failing PR.

---

## 5. Continuous integration — the pull request gate

### 5.1 The single CLI entry point

Define these in root `composer.json`:

```json
{
  "scripts": {
    "lint": "phpcs",
    "lint:fix": "phpcbf",
    "analyse": "phpstan analyse",
    "test:unit": "phpunit --testsuite unit",
    "test:integration": "phpunit --testsuite integration",
    "test:coverage": "XDEBUG_MODE=coverage phpunit --testsuite unit --coverage-clover build/coverage.xml",
    "check": ["@lint", "@analyse", "@test:unit", "@test:integration"]
  }
}
```

And in `package.json`:

```json
{
  "scripts": {
    "lint": "eslint . && prettier --check .",
    "test": "vitest run",
    "test:e2e": "playwright test",
    "build": "vite build"
  }
}
```

`composer check` is the command a human runs before pushing. CI runs the same steps.

### 5.2 The GitHub Actions workflow

Create `.github/workflows/ci.yml` in **Phase 0**, before any product code exists, so that every later PR is gated from its first commit.

```yaml
name: CI

on:
  pull_request:
    branches: [main]
  push:
    branches: [main]

concurrency:
  group: ${{ github.workflow }}-${{ github.ref }}
  cancel-in-progress: true

jobs:
  php-quality:
    name: PHP lint & static analysis
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          coverage: none
          tools: composer:v2
      - run: composer install --prefer-dist --no-progress
      - run: composer lint
      - run: composer analyse

  php-unit:
    name: PHP unit tests (${{ matrix.php }})
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        php: ['8.1', '8.2', '8.3']
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          coverage: xdebug
          tools: composer:v2
      - run: composer install --prefer-dist --no-progress
      - run: composer test:coverage
      - name: Enforce coverage floor on Domain and Application
        run: php bin/coverage-gate.php build/coverage.xml 100

  php-integration:
    name: WordPress integration tests
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: npm
      - run: npm ci
      - run: npx wp-env start
      - run: npx wp-env run tests-cli --env-cwd=wp-content/plugins/edulume-core composer install
      - run: npx wp-env run tests-cli --env-cwd=wp-content/plugins/edulume-core vendor/bin/phpunit --testsuite integration

  js-quality:
    name: JS lint & unit tests
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: npm
      - run: npm ci
      - run: npm run lint
      - run: npm test
      - run: npm run build

  e2e:
    name: Playwright E2E & accessibility
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: npm
      - run: npm ci
      - run: npx playwright install --with-deps chromium
      - run: npx wp-env start
      - run: npm run test:e2e

  plugin-check:
    name: WordPress Plugin Check
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
      - run: npx wp-env start
      - run: npx wp-env run cli wp plugin install plugin-check --activate
      - run: npx wp-env run cli wp plugin check edulume-core --format=table
```

Jobs are added to this file as the phases that need them land — `e2e` and `plugin-check` become meaningful once there is a theme to render. **Do not add a job that always passes as a placeholder.** Add it in the phase where it has something real to check.

### 5.3 Branch protection

Configure on `main` in Phase 0:

- Require a pull request before merging.
- Require **all** status checks above to pass.
- Require branches to be up to date before merging.
- Disallow force pushes to `main`.

### 5.4 The coverage gate

Write `bin/coverage-gate.php` in Phase 0. It parses the Clover XML, computes line and branch coverage **restricted to `src/Domain` and `src/Application`**, and exits non-zero below the threshold passed as an argument. Do not rely on a hosted coverage service — the gate must work offline and must be part of the same red/green signal as the tests.

---

## 6. Git and pull request workflow

### 6.1 Branch naming

```
feat/phase-03-pattern-library
fix/palette-gamut-clipping
chore/phase-00-repo-scaffold
docs/csv-import-guide
```

### 6.2 The loop the agent runs for every phase

```bash
git checkout main
git pull origin main
git checkout -b feat/phase-NN-slug

# ... implement the phase, tests first where practical ...

composer check          # must be green locally
npm run lint && npm test

git add -A
git commit -m "feat(scope): what this phase delivers"
git push -u origin feat/phase-NN-slug

# open the PR against main, then poll until every check reports green
# if red: diagnose, fix, commit, push, poll again
# merge only when green
```

### 6.3 Pull request description

Every PR states:

- **Phase number and name.**
- **What changed**, in three sentences or fewer.
- **PRD requirement IDs covered** (`TH-04`, `LD-09`, …).
- **How it was tested** — which suites, which new tests.
- **Assumptions made**, if any ambiguity was resolved unilaterally.
- **Anything deliberately deferred**, and to which phase.

### 6.4 Rules the agent must not break

- Never push directly to `main`.
- Never merge a pull request with a red or pending check.
- Never mark a check as skipped, allowed-to-fail, or `continue-on-error` to get a green.
- Never delete or weaken a test to make a build pass. If a test is genuinely wrong, fix the test in its own commit with the reason in the message.
- Never combine phases. One phase, one PR.
- If a PR sits red after three genuine attempts to fix it, stop and report the blocker rather than working around it.

---

## 7. Testing policy in practice

| Layer | Test type | Location | What it proves |
|---|---|---|---|
| `Domain` | Pure unit, no WordPress | `tests/Unit/` | The business rules are correct. 100% line + branch. |
| `Application` | Unit with in-memory fake repositories | `tests/Unit/` | Use cases orchestrate correctly. 100% line + branch. |
| `Infrastructure` | Integration under `wp-env` | `tests/Integration/` | Options persist, tables migrate, REST routes authorise. |
| Admin SPA | Vitest + Testing Library | `apps/admin/tests/` | Controls render, state updates, inherit/reset behave. |
| Public site | Playwright | `tests/e2e/` | Templates render, forms submit, filters filter. |
| Accessibility | axe-core inside Playwright | `tests/e2e/` | Zero critical/serious violations, light and dark. |
| Visual | Playwright snapshots | `tests/visual/` | The accent × mode × template matrix does not break. |

**Test naming:** `it_generates_a_full_palette_from_a_custom_hex()`. A failing test name must tell the maintainer what broke without opening the file.

**Fakes over mocks.** Write a small in-memory implementation of a repository port and reuse it. A test suite built on mock expectations breaks on every refactor and teaches the maintainer nothing.

**Property-based tests where the maths demands it.** The contrast engine and the palette generator must be tested across the whole input space, not on three hand-picked hexes. Assert invariants: *every* generated palette clears its contrast requirement at *every* step in *both* modes.

---

## 8. The phases

Each phase is one branch, one PR, one green CI run. The **Done when** list is the acceptance test for the phase — every line must be demonstrably true before the PR is merged.

Requirement IDs in brackets refer to the PRD.

---

### Phase 0 — Repository scaffold and the CI gate

**Scope:** No product code. Only the machinery that will police every later phase.

**Deliver:**
- Directory tree exactly as Section 3, with `.gitkeep` where empty.
- `composer.json` with PSR-4 autoloading and the scripts from §5.1.
- `package.json`, Vite config, ESLint, Prettier, `.editorconfig`.
- `phpunit.xml.dist` with `unit` and `integration` suites separated.
- `phpcs.xml.dist`: PSR-12 for `Domain`/`Application`, WPCS for `Infrastructure`/`Admin`/theme.
- `phpstan.neon.dist`: level 6 baseline, level 8 for `Domain`.
- `.github/workflows/ci.yml` with the `php-quality`, `php-unit`, and `js-quality` jobs.
- `bin/coverage-gate.php`.
- `README.md`, `CHANGELOG.md`, `CREDITS.md` (empty but present), `docs/` stubs.
- One trivial passing test so the pipeline proves itself end to end.
- Branch protection configured on `main`.

**Done when:** a pull request runs the full workflow and every check is green; a deliberately broken commit pushed to the branch turns a check red (verify this, then revert it).

---

### Phase 1 — Colour foundation

**Scope:** `Domain/Color/`. Pure maths, zero dependencies. [`TH-01`, `TH-06`]

**Deliver:** `Srgb` (hex parsing, relative luminance, linear transfer functions), `Oklch`, `ColorSpace` (sRGB ↔ OKLab ↔ OKLCH with binary-search gamut mapping), `ContrastRequirement` enum (WCAG 2.x thresholds), `ContrastEngine` (ratio, pass/fail, pick-foreground-from-candidates, nearest-compliant adjustment), `ContrastPair`, `NeutralScale` (designed inks — never pure black or white first).

**Done when:** hex → OKLCH → hex round-trips exactly for at least 100 sampled colours; `#000`/`#fff` yields exactly 21.00; every colour converted out of gamut lands back inside it; 100% coverage.

---

### Phase 2 — Accent palettes

**Scope:** `Domain/Theming/`. [`TH-04`, `TH-05`, `TH-06`, `TH-08`, `TH-14`]

**Deliver:** `AccentPalette` (11 steps, contrast pairs, separate light/dark primaries), `PaletteGenerator` (OKLCH lightness ramp with chroma shaping — **never naive HSL**), `AccentLibrary` (all 24 curated accents).

Carry **two** step choices per mode: the fill step and the accent-as-text step. A colour that works as a button background is frequently unreadable as link text on the same surface, and collapsing them is the usual reason accent-driven themes fail contrast audits.

**Done when:** all 24 palettes generate; every step's computed foreground meets AA; a property test proves this holds for several hundred random input hexes; a non-compliant custom hex is reported with a nearest-compliant suggestion rather than silently accepted; 100% coverage.

---

### Phase 3 — Pattern library

**Scope:** [`TH-17` – `TH-26`]

**Deliver:** `Pattern` (tileable SVG, `{{color}}` tint placeholder, percent-encoded data-URI output), `PatternGroup`, `PatternLibrary` with **28 patterns** including the four niche ones (Passport Stamps, Globe Meridians, Graduation Cap Scatter, Compass Rose), `PatternSettings` (per-mode opacity, scale 0.5×–3×, colour source, rotation, blend mode, attachment).

Separate light and dark opacity defaults — the same value that whispers on white is invisible on near-black.

**Done when:** every pattern emits well-formed SVG that parses; tint substitution is verified; data URIs contain no unescaped characters that break CSS `url()`; 100% coverage.

---

### Phase 4 — Typography

**Scope:** [`TH-27` – `TH-34`, `TH-30`, `TH-32`]

**Deliver:** `FontFamily`, `FontCategory`, `FontLibrary` (22 self-hosted families), `FontRole`, `FontPairing`, `FontPairingLibrary` (10 pairings), `TypeScale` (modular ratio, fluid `clamp()` output in rem, damped mobile ratio), `TypographySettings` (pairing + sparse per-role overrides + subsets).

Fonts are self-hosted WOFF2 by default. The Google CDN is an opt-in toggle, off by default, for GDPR and performance reasons.

**Done when:** every generated `clamp()` is valid CSS and monotonic across steps; only families actually in use are reported as needing enqueueing; every bundled family has a `CREDITS.md` entry; 100% coverage.

---

### Phase 5 — Motion

**Scope:** [`TH-35` – `TH-41`]

**Deliver:** `MotionPreset` (None / Subtle / Refined / Dynamic / Cinematic, each a complete timing set), `MotionEasing` (six named curves + validated custom bezier), `MotionEffect` (17 individually toggleable effects, each mapped to one conditionally-loaded module), `MotionSettings` (preset + granular overrides).

Smooth scroll and custom cursor ship **off**. Cinematic is the only preset that pulls the heavy timeline module, loaded on demand.

**Done when:** an invalid custom bezier is rejected rather than emitted; disabling motion globally zeroes every derived value; the active-effects list drives conditional loading; 100% coverage.

---

### Phase 6 — Settings aggregate, schema versioning, migrations

**Scope:** [`AR-07`, `AD-20`, `AD-21`, `AD-22`]

**Deliver:** `Guard` coercion helpers (one place where untrusted input is normalised), `LayoutSettings`, `ThemeSettings` aggregate with a schema version, a migration runner, and lossless `toArray()`/`fromArray()`.

**Every path coerces rather than throws.** Settings arrive from the REST API, from an imported JSON file, and from rows written by an older schema. One bad key must never white-screen a live site.

**Done when:** a round-trip through `toArray()`/`fromArray()` is lossless; deliberately corrupt input at every key produces valid defaults; a v1 settings blob migrates to v2 without data loss; 100% coverage.

---

### Phase 7 — Per-section overrides and inheritance

**Scope:** [`TH-42`, `TH-43`, `TH-09`, `AD-09`]

**Deliver:** `SectionId` enum, `SectionOverride` (every field nullable, null means inherit), a `SectionResolver` that folds global settings with an override into a concrete resolved section.

Inheritance is stored as **absence**, never as a copy of the global value. That is what makes a later global change propagate into untouched sections, and what lets the admin show an accurate modified-indicator dot.

**Done when:** an empty override resolves identically to global for every field; each field overrides independently; `divergedKeys()` reports exactly what the user changed; resetting one key restores inheritance for that key alone; 100% coverage.

---

### Phase 8 — Token compiler and the compiled-CSS pipeline

**Scope:** [`TH-01`, `TH-02`, `TH-03`, `AR-09`, `PF-02`, `PF-03`, `TH-38`]

**Deliver:** `TokenCompiler` in `Domain` producing the complete custom-property set for a settings object, and `CompileStylesheet` in `Application` producing the full stylesheet text: `:root` tokens, `[data-theme="dark"]` re-declarations, per-section scoped blocks, pattern backgrounds, and a `prefers-reduced-motion` block emitted **last** so no stored setting can out-specify it.

Then `Infrastructure/Theming/`: write to `wp-content/uploads/edulume/` with a content hash in the filename, enqueue as a static file, regenerate only on save. **Never inline a 40 KB style block on every request.**

**Done when:** compiled output parses as valid CSS; no hard-coded colour, font, or spacing literal appears anywhere in theme CSS; the reduced-motion block is provably last; the hash changes when and only when settings change; 100% coverage on compiler and use case.

---

### Phase 9 — Style presets

**Scope:** [`TH-44`, `TH-45`, `TH-46`]

**Deliver:** `StylePreset` and a library of 10+ complete looks (Oxford Classic, Gulf Premium, Nordic Minimal, Warm Campus, Midnight Pro, Editorial Journal, Vibrant Youth, Corporate Trust, Soft Neutral, Bold Brutalist). `ApplyStylePreset` use case: preview-first, non-destructive, snapshot taken before applying, undoable within the session.

**Done when:** each preset applies to a full valid settings object; applying then undoing restores the prior state byte-for-byte; user presets export and re-import losslessly; 100% coverage.

---

### Phase 10 — Plugin bootstrap and WordPress infrastructure

**Scope:** [`AR-01`, `AR-02`, `AR-03`, `SC-02`]

**Deliver:** plugin header file (bootstrap only, no logic), activation/deactivation/uninstall hooks, a service container, hook registration, capability definitions, the settings repository implementing its `Application/Port` interface, and the first integration tests under `wp-env`.

Add the `php-integration` job to the CI workflow in this phase.

**Done when:** the plugin activates and deactivates cleanly on a fresh WordPress install; settings persist and reload; deactivating loses nothing; integration suite green in CI.

---

### Phase 11 — Content model: post types and taxonomies

**Scope:** [`CM-01`, `CM-02`, `SE-02`]

**Deliver:** all 16 post types and their taxonomies from PRD §6, registered **by the plugin**, with real post-to-post relationships (not free text), editable permalink bases, and a per-item accent override.

**Done when:** every type registers with correct labels, capabilities, and rewrite rules; relationships resolve in both directions; switching themes loses no content; integration tests cover every registration.

---

### Phase 12 — CSV import and export

**Scope:** [`CM-01`, `CM-03`, `CO-08`]

**Deliver:** a chunked, resumable CSV importer with a field-mapping UI, validation with a per-row error report, and export honouring the current filters. Build for a **30-second `max_execution_time`** — this is the classic shared-hosting failure point.

**Done when:** 1,000 course rows import successfully under a simulated 30-second execution limit; malformed rows are reported, not silently dropped; a partial import resumes correctly; round-trip export/import is lossless.

---

### Phase 13 — Lead domain and storage

**Scope:** [`AR-08`, `LD-09`, `SC-03`, `SC-09`]

**Deliver:** `Lead` entity, `LeadStatus` pipeline (`New → Contacted → Counselling Booked → Application Started → Closed Won / Closed Lost`) with legal transitions enforced in the domain, note trail, assignment. Custom tables `{prefix}edulume_leads` and `_lead_meta` with a migration runner and correct indexes. **Never the posts table.**

Every query uses `$wpdb->prepare()`. No string-concatenated SQL anywhere, ever.

**Done when:** illegal status transitions are rejected by the domain; tables create and migrate idempotently; 10,000 leads paginate and filter without a slow query; 100% domain coverage.

---

### Phase 14 — Form builder and submission

**Scope:** [`LD-01` – `LD-07`, `SC-06`]

**Deliver:** field types including tel-with-country-flag and consent checkbox; multi-step forms with progress; conditional logic; all six placements; spam protection (honeypot + time-trap + optional reCAPTCHA v3 / hCaptcha / Turnstile) that **works with none of them configured**; GDPR consent, retention period, and erasure tooling; rate limiting per IP.

**Done when:** a multi-step form with conditional logic submits, stores, notifies, and autoresponds; spam protection blocks bots with and without a captcha configured; rate limiting triggers; erasure removes every trace of a lead including uploads.

---

### Phase 15 — Lead inbox

**Scope:** [`LD-08` – `LD-12`, `LD-15`]

**Deliver:** admin Leads screen with search and filters, detail drawer showing submission + UTM + page + device, pipeline management, branch-aware assignment, filtered CSV export, per-form and per-branch notification recipients, and an accent-inheriting HTML autoresponder template.

**Done when:** filters compose correctly; export matches the applied filters exactly; notifications and autoresponders send; E2E test covers the full flow from public submission to admin status change.

---

### Phase 16 — Integrations and tracking

**Scope:** [`LD-13`, `LD-14`, `LD-17`, `LD-18`]

**Deliver:** outbound webhook on new lead; Mailchimp, Brevo, Google Sheets, HubSpot connectors; GA4, Meta Pixel, GTM fields with a submission event carrying form ID and destination interest; a cookie-consent banner whose category toggles **actually gate** the tracking scripts.

**Done when:** the webhook fires with a correct signed payload and retries on failure; no tracking script loads before consent; integration failures are logged without blocking the lead from being stored.

---

### Phase 17 — REST API

**Scope:** [`AR-04`, `SC-01`, `SC-02`]

**Deliver:** the `edulume/v1` namespace: settings read/write, palette generation, preview compile, leads, content queries. Full JSON schema on every route. Capability checks on every route — **a nonce alone is never authorisation.**

**Done when:** every route has a schema and rejects malformed input with a useful error; an unauthenticated request to a privileged route returns 401/403; an authenticated request lacking the capability returns 403; integration tests cover both.

---

### Phase 18 — Admin application shell

**Scope:** [`AD-04`, `AD-05`, `AD-08`, `AD-14` – `AD-16`]

**Deliver:** React + TypeScript SPA on `@wordpress/components`; the menu IA from PRD §9.2; **admin themed by the site accent** and provably AA on all 24 accents; admin dark mode independent of the public site; `⌘K` / `Ctrl+K` settings search that jumps to and highlights a control; the Lucide icon sprite; the icon picker.

**Done when:** the shell renders, routes, and persists; an automated check proves AA compliance across all 24 accents in both admin modes; keyboard search reaches every registered control.

---

### Phase 19 — Configurator panels

**Scope:** [`TH-10`, `TH-26`, `TH-29`, `TH-41`, `AD-06`, `AD-07`, `AD-09`, `AD-10`]

**Deliver:** Presets, Colors, Typography, Patterns, Layout, Motion, Header, Footer, and Sections panels.

Every picker shows a **live sample**, not a swatch: the accent picker renders a miniature button + heading + link + card in that palette; the pattern picker renders a tile at the current accent and opacity; the font picker renders a real sentence at the real size and weight; the motion panel animates a replayable sample card.

Every panel opens with 3–6 controls and hides the rest behind Advanced. Every panel leads with presets. Every overridable control shows a modified dot with one-click revert and inline help.

**Done when:** every control round-trips through the REST API; every override shows an explicit Inherit state; component tests cover render, change, and reset for each panel.

---

### Phase 20 — Live preview

**Scope:** [`TH-47` – `TH-50`, `AD-11`]

**Deliver:** split view with an iframe preview; token changes pushed over `postMessage` and applied as CSS variable patches **in under 300 ms with no page reload**; device switcher; page switcher; unsaved-changes guard; hold-to-compare against the pre-change state.

**Done when:** a Playwright test measures the update latency and asserts it is under 300 ms; navigating away with unsaved changes prompts; nothing reaches the public site until Save.

---

### Phase 21 — Setup wizard

**Scope:** [`AD-01`, `AD-02`, `AD-03`, PRD §9.1 Option A]

**Deliver:** the eight-step wizard: welcome + licence, site identity, contact + branch, style with live preview, content/demo choice, **optional additional user creation**, essentials, done + checklist.

The PRD's original "create the admin account at first install" requirement is not technically possible — WordPress creates the administrator during core installation, before any theme or plugin exists. Implement Option A: the wizard creates an *additional* user with a role (Site Manager / Content Editor / Counsellor). The password field gets a show/hide toggle, a strength meter, and a generate-strong-password button. Username and email uniqueness validate **inline, before submit**.

**Done when:** the wizard completes end to end and creates a working user; it is skippable, resumable, and re-runnable; it never blocks access to `wp-admin`; E2E test covers the full flow.

---

### Phase 22 — Safety nets

**Scope:** [`AD-12`, `AD-19` – `AD-23`]

**Deliver:** settings export to JSON; import with a diff preview before applying; granular and full reset behind typed confirmation; automatic snapshots before any preset application, demo import, or plugin update (keep the last 10, restore from a list); safe mode via URL parameter rendering default tokens with no custom CSS; the dashboard health panel; undo/redo within a configuration session.

**Done when:** an intentionally broken configuration is recoverable through safe mode without database access; snapshots restore exactly; the diff preview shows every changed key before import.

---

### Phase 23 — Roles and permissions

**Scope:** [`AD-17`, `AD-18`]

**Deliver:** Site Manager, Content Editor, Counsellor, and Branch Manager roles with the capability matrix from PRD §9.5.

**Done when:** a Content Editor provably cannot change the colour scheme; a Counsellor sees only leads assigned to them; a Branch Manager is scoped to their branch; integration tests assert each denial, not just each permission.

---

### Phase 24 — Theme skeleton and design tokens

**Scope:** [`AR-02`, `AR-06`, `TH-02`, `CO-12`]

**Deliver:** theme bootstrap, `theme.json` registering the same tokens the front end uses, the base stylesheet built entirely on custom properties, graceful degradation with an admin notice when the plugin is absent, and the shipped child theme.

**Done when:** the theme renders a plain but functional site with the plugin deactivated; the block editor shows the same palette as the front end; no hard-coded colour, font, or spacing value exists in theme CSS.

---

### Phase 25 — Global chrome

**Scope:** [`PU-01` – `PU-11`, `TH-11` – `TH-16`]

**Deliver:** 6+ header variants, mega-menu, 4+ footer variants, utility bar, floating action cluster (WhatsApp / call / back-to-top / book counselling), scheduled announcement bar, mobile drawer, 404 / search / maintenance templates, favicon upload generating the **full** icon set and `site.webmanifest`, all logo slots including dark-mode alternates, preloader.

Light/dark/auto with a visitor toggle and **zero flash of wrong theme** — an inline blocking script in `<head>` resolves the mode before first paint.

**Done when:** a Playwright test on a throttled connection proves no FOUC; every header and footer variant renders correctly in both modes and in RTL; the favicon set generates completely.

---

### Phase 26 — Page templates

**Scope:** [PRD §7.2]

**Deliver:** the modular home page with reorderable, individually re-themeable sections, plus every archive and single template listed in PRD §7.2, plus About, Contact, FAQ, search results, privacy/terms, and thank-you.

Split this phase if it exceeds one day. Suggested split: 26a home + destinations + institutions + courses; 26b services + scholarships + test-prep + events; 26c team + testimonials + gallery + branches + careers + blog; 26d utility pages.

**Done when:** every template renders with real demo content in both modes and in RTL; each is covered by a visual snapshot.

---

### Phase 27 — Block library

**Scope:** [`PU-12`, `PU-13`, `AR-05`]

**Deliver:** 35+ reusable blocks, each accent-aware, motion-aware, and shipping **2–4 style variations**. Registered by the plugin so content survives a theme switch.

Split by group if needed: layout blocks, content blocks, data blocks, media blocks.

**Done when:** every block renders in the editor and on the front end; every variation renders; block content survives a theme switch; E2E covers insertion and rendering.

---

### Phase 28 — Finder tools

**Scope:** [`PU-14` – `PU-16`, `PU-19`, `PU-20`, `PF-09`]

**Deliver:** Course Finder and University Finder with faceted AJAX filtering, URL-shareable filter state, and pagination; Scholarship Finder with deadline sorting; compare 2–4 items; `localStorage` shortlists with no login.

**No unbounded `posts_per_page => -1` anywhere in the codebase.** This is a hard review gate. Every finder query is indexed and paginated.

**Done when:** filtering 3,000+ courses returns in under 500 ms on the demo dataset; filter state survives a page reload from the URL alone; a static analysis rule fails the build on any unbounded query.

---

### Phase 29 — Eligibility checker and cost calculator

**Scope:** [`PU-17`, `PU-18`]

**Deliver:** the multi-step eligibility quiz (level, grades, English score, budget, preferred countries) producing matched courses **and capturing the lead** — the highest-converting element on sites in this niche. Plus the cost calculator (tuition + living + visa + flights + insurance) with admin-editable static exchange rates and **no paid API dependency**.

**Done when:** the quiz produces sensible matches against the demo dataset, captures a lead with full context, and is fully keyboard-operable; the calculator's arithmetic is unit-tested at 100% coverage.

---

### Phase 30 — Performance budget

**Scope:** [`PF-01` – `PF-08`]

**Deliver:** critical CSS inlining, conditional JS module loading, lazy loading with `fetchpriority="high"` on the LCP image, responsive `srcset`, WebP with fallback, explicit dimensions on every image, caching-plugin compatibility notes.

Add a **Lighthouse CI job** to the workflow that fails the build when a budget is exceeded.

**Done when:** the demo homepage scores ≥90 mobile Lighthouse under 4× CPU throttle and Slow 4G, with LCP ≤2.0 s, CLS ≤0.05, INP ≤200 ms, JS ≤120 KB, CSS ≤60 KB, fonts ≤100 KB, requests ≤45 — all enforced in CI, not measured by hand.

---

### Phase 31 — Accessibility

**Scope:** [`AX-01` – `AX-09`]

**Deliver:** full keyboard operability with a visible accent-coloured focus ring at ≥3:1, skip link, focus trapping, `Esc` to close; semantic landmarks and heading hierarchy; enforced alt-text fields with a decorative-vs-meaningful distinction; ARIA on every interactive component; `aria-live` error announcement; ≥44×44 px touch targets.

Add the axe-core job to CI, running against **every** template in **both** modes.

**Done when:** zero critical or serious axe violations across all templates in both modes; a documented NVDA and VoiceOver pass; `prefers-reduced-motion` verified to override every stored motion setting.

---

### Phase 32 — SEO and structured data

**Scope:** [`SE-01` – `SE-08`]

**Deliver:** JSON-LD per template (`EducationalOrganization`, `Course`, `CollegeOrUniversity`, `FAQPage`, `Event`, `Article`/`BlogPosting`, `BreadcrumbList`, `Review`/`AggregateRating`, `LocalBusiness`); Open Graph and Twitter cards with per-post override and generated fallback; breadcrumbs; deferral to Yoast / RankMath / SEOPress when active rather than duplicating tags; XML sitemap coverage.

**Done when:** emitted JSON-LD validates against the Schema.org validator for every template; no duplicate meta tags when an SEO plugin is active.

---

### Phase 33 — Internationalisation and RTL

**Scope:** [`I8-01` – `I8-06`, `TH-32`]

**Deliver:** 100% of strings wrapped with a single text domain; a generated `.pot`; **logical CSS properties throughout** (`margin-inline-start`, never `margin-left`); mirrored icons and carousels; WPML and Polylang compatibility; locale-aware dates, numbers, and currency; Arabic and Bengali font subsets; a type scale that survives taller scripts.

Ship English, Arabic, and Bengali translated; the rest translation-ready.

**Done when:** an automated check finds zero unwrapped user-facing strings; RTL verified at four breakpoints with real Arabic content; Bengali and Devanagari render without line-height collisions.

---

### Phase 34 — Security hardening

**Scope:** [`SC-01` – `SC-09`]

**Deliver:** an audit pass proving every output is escaped and every input sanitised against a schema; nonces and capability checks on every admin action and REST route; `$wpdb->prepare()` everywhere; upload allowlists with SVG sanitisation stripping scripts, event handlers, and external references; custom CSS/JS restricted to Administrator; rate limiting; **no `eval`, no `base64_decode` of executable payloads, no runtime remote code fetch**.

Add the `plugin-check` job and a WPCS security ruleset to CI.

**Done when:** PHPCS with the WordPress security ruleset is clean; Plugin Check is clean; a malicious SVG upload is neutralised in test; a penetration checklist is documented in `docs/`.

---

### Phase 35 — Demo importer and starter demos

**Scope:** [`CO-06` – `CO-10`]

**Deliver:** four complete starter demos — Gulf/premium consultancy, consultancy + test-prep centre, content/SEO-led guide site, boutique/minimal — and a one-click importer bringing content, media, menus, widgets, homepage assignment, and theme settings. Chunked and resumable for a 30-second execution limit, with full / content-only / settings-only modes, clean rollback, and a warning-plus-merge-or-fresh choice rather than silent overwrite.

All demo photography must be CC0 or licensed for redistribution, or replaced with placeholders on import.

**Done when:** all four demos import cleanly on a fresh install in under three minutes each; rollback removes imported content completely; importing over existing content never destroys it silently.

---

### Phase 36 — Licensing and updates

**Scope:** [`CO-01` – `CO-04`]

**Deliver:** licence key activation, deactivation, site-limit enforcement, expiry with a grace period; auto-update delivery through the WordPress dashboard; a small licence/update server.

**The product stays fully functional when a licence lapses.** Only updates and support stop. Crippling a paid site is a refund and reputation disaster.

**Done when:** activation, deactivation, and site-limit enforcement all work against a live endpoint; an expired licence blocks updates and nothing else; the product functions with no licence key at all.

---

### Phase 37 — Documentation and packaging

**Scope:** [`CO-11` – `CO-17`]

**Deliver:** installation, setup wizard, every settings panel, CPT usage, CSV import format with a sample file, block reference, child-theme guide, FAQ, troubleshooting; a complete `CHANGELOG.md`; a verified `CREDITS.md`; the stated support policy; the packaged ZIP.

**Done when:** a person who has never seen the product installs it, rebrands it, and imports a demo using only the documentation; every bundled asset's licence is verified for resale.

---

### Phase 38 — Release candidate

**Scope:** the full acceptance criteria in PRD §18.

**Deliver:** the visual regression matrix (24 accents × 2 modes × 6 templates) reviewed; cross-browser testing on Chrome, Firefox, Safari (macOS + iOS), Edge, Samsung Internet; a test deployment on a **real shared-hosting account**, not only in Docker; beta feedback incorporated; version tagged.

**Done when:** every box in PRD §18 is checked with evidence — a link, a screenshot, or a CI run — not an assertion.

---

## 9. Final acceptance gate

The build is complete only when **all** of the following are true simultaneously:

- [ ] Every phase merged to `main` through its own green pull request.
- [ ] `composer check` green on `main`.
- [ ] 100% line and branch coverage on `Domain` and `Application`; ≥80% on `Infrastructure` and `Admin`.
- [ ] PHPCS clean, PHPStan clean at the configured levels, Plugin Check clean.
- [ ] Playwright E2E and visual suites green.
- [ ] Zero critical or serious axe-core violations on every template, both modes.
- [ ] Every §10 performance budget met and enforced in CI.
- [ ] RTL verified at four breakpoints with Arabic content.
- [ ] All four demos import cleanly on a fresh install in under three minutes.
- [ ] `CREDITS.md` complete and every bundled asset licence-verified for resale.
- [ ] `CHANGELOG.md` current; version tagged; release ZIP built and installed successfully on a real shared host.

**Nothing is "done" before its pull request is green. No exceptions.**

---

## 10. Decisions to confirm before Phase 1

These come from PRD §19 and block or reshape later phases. Confirm with the owner; if no answer arrives, proceed on the recommendation shown and record the assumption in the Phase 1 PR description.

| # | Question | Proceed on |
|---|---|---|
| Q1 | First-install admin creation: setup wizard, or bundled WordPress distribution? | Setup wizard (Option A) |
| Q2 | Final product name and slug — it prefixes every function, CSS variable, option key, and text domain | `edulume` until told otherwise |
| Q3 | Sales channel: Envato, self-hosted, or both? | Self-hosted first, Envato later |
| Q4 | Price and licence tiers | $89 single, $199 five-site, annual with a lifetime option |
| Q5 | Student portal in v1? | No — v2 |
| Q6 | Payment collection in v1? | No — v2 |
| Q9 | Redistribution licensing confirmed for every premium library, font, and stock image | Verify before any asset enters the bundle |
| Q10 | Which languages ship translated in v1? | English, Arabic, Bengali; the rest translation-ready |
| Q11 | White-label / removable branding for resellers? | Higher tier, not v1 |

---

## 11. Quick reference for the agent

```
Before every phase:   git checkout main && git pull && git checkout -b feat/phase-NN-slug
Before every push:    composer check && npm run lint && npm test
After every phase:    push → open PR to main → poll until ALL checks green → merge
Never:                push to main · merge red · skip a check · delete a test to pass
                      combine phases · push everything at once
Always:               one class per file · full words · no magic values · why-not-what comments
                      test tree mirrors source tree · commit and push after every phase
```
