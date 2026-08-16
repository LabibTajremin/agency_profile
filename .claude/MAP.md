# Codebase Map

Written for the feature round. Placeholders from the brief resolve as:
`THEME_SLUG=edulume-theme`, `PLUGIN_SLUG=edulume-core`, `PREFIX=edulume_`,
`TEXT_DOMAIN=edulume`, `PROD_DOMAIN=sparkpath.com.bd`, `BRAND_NAME=SparkPath`.

## Prefix / text domain / option keys in use

- Function prefix `edulume_`, PHP namespace `Edulume\Core\`, text domain `edulume`.
- Options: `edulume_theme_settings` (ThemeSettings blob, autoload off),
  `edulume_section_overrides` (per-section overrides), `edulume_forms`,
  `edulume_integration_log`, `edulume_version`, `edulume_activated_at`,
  `edulume_lead_table_version`, `edulume_stylesheet_url` / `_version`.
- Post meta prefix `_edulume_`: `_edulume_demo`, `_edulume_accent`, `_edulume_rel_<type>`
  (relationships), `_edulume_minimum_grade`, `_edulume_minimum_english`, `_edulume_currency`,
  `_edulume_tuition_per_year`, `_edulume_living_per_year`, `_edulume_visa_cost`,
  `_edulume_flight_cost`.

## Theme file tree (2 levels)

- `style.css`, `functions.php` (bootstrap + no-flash inline script), `front-page.php`
  (filtered section loop), `header.php`, `footer.php`, `index.php`, `singular.php`,
  `page.php`, `search.php`, `404.php`, `maintenance.php`
- `archive-edulume_*.php` / `single-edulume_*.php` — one pair per CPT (16 pairs)
- `inc/` — `assets.php` (conditional module map + `edulume_require_feature`),
  `chrome.php` (header/footer/logo/mode switch), `consent.php`, `sections.php`
  (`edulume_section_posts`, `edulume_the_section_header`, `edulume_the_grid_section`)
- `template-parts/home/` — hero, trust-bar, services, destinations, courses, eligibility,
  success-stories, events, testimonials, faq, cta
- `template-parts/header|footer|content/` — variants + card/finder/enquiry-form/related
- `assets/css/` — `critical.css`, `base.css`, `chrome.css`
- `assets/js/` — chrome, consent, finder, compare, eligibility, calculator, forms, carousel,
  motion, interactions
- No `page-templates/` directory yet. No video assets or partials yet.

## Plugin file tree (2 levels)

- `edulume-core.php` (header + `Plugin::boot`), `uninstall.php`, `readme.txt`
- `src/Domain/` — Accessibility, Admin, Blocks, Chrome, Color, Content, Demo, Finder, I18n,
  Lead, Licence, Performance, Rest, Security, Seo, Support, Theming, Tracking
- `src/Application/` — Content, Demo, Lead, Licence, Port (interfaces), Theming
- `src/Infrastructure/` — Admin, Blocks, Content, Demo, Lead, Licence, Rest, Settings,
  Theming, Wp
- `demos/{boutique,gulf-premium,test-prep,guide-site}/` — boutique has 16 JSON payloads +
  `media/` (69 SVGs) + `media-credits.json`; the other three are empty
- `assets/admin/edulume-admin.iife.js` — built admin bundle
- `tests/Unit/**` (PHPUnit 10, attributes), `tests/Integration/**` (PHPUnit 9, `@test`)

## Existing CPTs + taxonomies

Registered in `Infrastructure/Content/ContentRegistrar.php` from
`Domain/Content/ContentModel.php`. Keys are `edulume_<slug>`:
`destination`, `institution`, `course`, `service`, `scholarship`, `test-prep`, `event`,
`team-member`, `testimonial`, `story`, `gallery-item`, `branch`, `job-opening`, `faq`,
`resource`, `partner`. All `show_in_rest`, `has_archive`, nested under the Edulume menu.
Relationships are registered post meta `_edulume_rel_<target>` (`RelationshipDefinition`).

**Consequences for this round:** `edulume_destination` already exists (do not add
`PREFIX_destination`); `edulume_institution` is the universities CPT (do not add
`PREFIX_university`); `edulume_team-member` is the people CPT (do not add `PREFIX_team`).

## Existing options schema

- `edulume_theme_settings` → `ThemeSettings::toArray()`: palette/accent, typography, layout,
  motion, patterns, chrome, tracking, SEO, safe mode.
- `edulume_section_overrides` → `array<sectionSlug, array<SectionOverrideKey, scalar>>`.
  Keys: `enabled`, `accentSlug`, `customAccent`, `pattern*`, `backgroundTone`, `motion*`,
  `typographyPairingSlug`, `sectionSpacingPixels`, `cornerRadiusPixels`.
- Reads migrate on load (`SettingsMigrator`), so an old DB dump still boots.

## Section registry

- `Domain/Theming/SectionId` (backed enum): header, hero, highlights, destinations, courses,
  services, scholarships, testimonials, statistics, events, team, call-to-action, blog,
  footer, trust-bar, eligibility, success-stories, faq, cta. Helpers: `label()`,
  `canBeSwitchedOff()`, `isEnabledByDefault()`, `switchable()`, `homePageOrder()`.
- `SectionResolver::resolveEnabled()` — header/footer always on, otherwise the stored
  `enabled` override, defaulting true.
- `Infrastructure/Theming/SectionVisibility` filters `edulume_section_is_enabled` and
  `edulume_enabled_home_sections`.
- `front-page.php` builds the list via `edulume_home_sections`, filters it through
  `edulume_enabled_home_sections`, then `get_template_part('template-parts/home/<slug>')`.
- Each part can be replaced wholesale by the `edulume_home_section_content` filter.

## Admin settings UI

- `Infrastructure/Admin/AdminMenu` — one top-level `edulume` page + 9 sub-pages (Dashboard,
  Design, Leads, Forms, Content tools, Starter demos, Safety, Licence, System), each
  capability-gated, each rendering `#edulume-admin-root` with a `data-edulume-route`.
- `Domain/Admin/ScreenHelp` supplies inline help + a WP contextual Help tab per route.
- `Infrastructure/Admin/DemoImportScreen` renders the Starter-demos screen server-side and
  posts to `admin-post.php` (`edulume_import_demo` / `edulume_remove_demo`).
- `Infrastructure/Admin/AdminAssets` paints wp-admin with the site accent.
- SPA lives in `apps/admin/src` (TypeScript, Vite → `plugins/edulume-core/assets/admin/`).
- REST surface declared in `Domain/Rest/RouteCatalogue`, bound in `Infrastructure/Rest/*`.
  Namespace `edulume/v1`.

## Asset pipeline

- `npm run build` → Vite → `plugins/edulume-core/assets/admin/edulume-admin.iife.js`.
- Theme CSS/JS ship unbuilt; `inc/assets.php` inlines `critical.css` and enqueues a module
  only when a template calls `edulume_require_feature('<name>')`.
- `composer make:pot` regenerates `languages/edulume.pot` (gated in CI).

## Theme toggle

- Markup: `edulume_the_mode_toggle()` in `inc/chrome.php:296` — a `role="radiogroup"` with two
  labelled radios (icon + visible text).
- Behaviour: `assets/js/chrome.js:112` `setUpModeToggle()` — writes `data-theme` on
  `documentElement`, `localStorage['edulume-theme']`, and an `edulume-theme` cookie.
- Anti-flash: inline script in `functions.php:105`, already present.

## Test setup

- `composer test:unit` (PHPUnit 10) — 100% line coverage gate via `bin/coverage-gate.php`.
- `composer test:integration` (PHPUnit 9 in `wp-env`).
- `npm test` (Vitest), `npm run test:e2e` (Playwright), `npm run lint` (ESLint + Prettier).
- `composer ci` / `ci:quick` runs every gate: PHPCS, PHPStan (2 configs), and the custom
  audits `theme`, `tokens`, `wiring`, `queries`, `i18n`, `security`, plus Lighthouse and axe.

## Gaps / risks found

- No login/security module at all (`wp_login_failed`, `xmlrpc`, `login_url` unreferenced).
- No video component anywhere; `interactions.js` has a facade for a single block only.
- No `page-templates/`, so no founders template and no Page Attributes entry.
- `template-parts/home/` has 11 parts against 19 section keys; highlights, statistics, team
  and blog have `SectionId` cases but no home partial.
- Destination meta covers cost/eligibility only — no flag, hero, stats or intake video.
- Institutions have no destination relationship rendered on a destination page.
- Demo packs `gulf-premium`, `test-prep`, `guide-site` are declared but carry no payloads.
- Settings are two option rows, not one; the brief's §4.1 single-row `PREFIX_settings` would
  duplicate the existing store, so this round extends `ThemeSettings`/`SectionOverride`
  instead and adds a dot-path reader over them.
