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
- `archive-edulume_*.php` / `single-edulume_*.php` — one pair per CPT (16 pairs);
  `single-edulume_destination.php` is a bespoke country page
- `inc/` — `assets.php` (conditional module map + `edulume_require_feature`),
  `chrome.php` (header/footer/logo/mode switch), `consent.php`, `sections.php`
  (`edulume_section_posts`, `edulume_the_section_header`, `edulume_the_grid_section`)
- `template-parts/home/` — hero, trust-bar, statistics, services, video-rail, destinations,
  universities, highlights, process, courses, intakes, eligibility, scholarships,
  success-stories, team, events, testimonials, blog, faq, cta (+ `*-demo` fallbacks)
- `template-parts/header|footer|content|video/` — variants, card, finder, enquiry form,
  university card, founder card, video rail + card
- `page-templates/founders.php` — "Founders / About Us", selectable from Page Attributes
- `assets/css/` — `critical.css`, `base.css`, `chrome.css`, `video.css`
- `assets/js/` — chrome, consent, finder, compare, eligibility, calculator, forms, carousel,
  motion, interactions, video-rail, university-filter

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
- `assets/js/screens.js` — the enhancement layer for the server-rendered admin screens
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

- `Domain/Theming/SectionId` (backed enum), 23 cases: header, hero, highlights, destinations,
  courses, services, scholarships, testimonials, statistics, events, team, call-to-action,
  blog, footer, trust-bar, eligibility, success-stories, faq, cta, universities, process,
  intakes, video-rail. Helpers: `label()`, `canBeSwitchedOff()`, `isEnabledByDefault()`,
  `switchable()`, `homePageOrder()` (20 of them, in render order).
- `Domain/Theming/SectionOrder::reconcile()` — stored order first, unknown slugs dropped, new
  sections reinstated beside their default neighbour.
- `SectionResolver::resolveEnabled()` — header/footer always on, otherwise the stored
  `enabled` override, defaulting true.
- `Infrastructure/Theming/SectionVisibility` filters `edulume_section_is_enabled`,
  `edulume_enabled_home_sections` and `edulume_home_sections` (the stored order).
- Order is stored under the reserved `_order` key inside `edulume_section_overrides`.
- `front-page.php` builds the list via `edulume_home_sections`, filters it through
  `edulume_enabled_home_sections`, then `get_template_part('template-parts/home/<slug>')`.
- Each part can be replaced wholesale by the `edulume_home_section_content` filter.

## Admin settings UI

- `Infrastructure/Admin/AdminMenu` — one top-level `edulume` page + 11 sub-pages (Dashboard,
  Design, Home sections, Videos, Leads, Forms, Content tools, Starter demos, Safety, Licence,
  System), each capability-gated, each rendering `#edulume-admin-root` with a
  `data-edulume-route`.
- `Domain/Admin/ScreenHelp` supplies inline help + a WP contextual Help tab per route.
- Four screens render server-side and post to `admin-post.php`, so they work before the bundle
  loads and if it never does: `DemoImportScreen`, `SectionsScreen`, `VideoScreen`,
  `ShieldScreen`. `DemoImportAjax` drives the import ten items per request.
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

- Markup: `edulume_the_mode_toggle()` in `inc/chrome.php` — one 36px button carrying both
  icons; CSS cross-fades on the root `[data-theme]`. Labelled with the mode it switches _to_.
- Behaviour: `assets/js/chrome.js` `setUpModeToggle()` — writes `data-theme` on
  `documentElement`, `localStorage['edulume-theme']`, and an `edulume-theme` cookie.
- Anti-flash: inline script in `functions.php:105`, already present.

## Test setup

- `composer test:unit` (PHPUnit 10) — 100% line coverage gate via `bin/coverage-gate.php`.
- `composer test:integration` (PHPUnit 9 in `wp-env`).
- `npm test` (Vitest), `npm run test:e2e` (Playwright), `npm run lint` (ESLint + Prettier).
- `composer ci` / `ci:quick` runs every gate: PHPCS, PHPStan (2 configs), and the custom
  audits `theme`, `tokens`, `wiring`, `queries`, `i18n`, `security`, plus Lighthouse and axe.

## What this feature round added

- `Domain/Support/NestedArray` — recursive merge (maps merge, lists replace) + dot-path read.
- `Domain/Content/DemoContent` — the copy a fresh install renders; `all()` keyed by `SectionId`,
  `pages()` for the founders page. Served to the theme through `edulume_content_value`.
- Theme `inc/content.php` — `edulume_opt`, `edulume_opt_list`, `edulume_row`,
  `edulume_demo_img`, `edulume_the_demo_image`, `edulume_initials`.
- `SectionId` gained `universities`, `process`, `intakes`, `video-rail`; `homePageOrder()` is
  now 20 and `SectionOrder` reconciles a stored order against it.
- New home partials: statistics, universities, highlights, process, intakes, scholarships,
  team, blog, video-rail, plus demo fallbacks for testimonials and FAQ.
- Admin: `SectionsScreen` (toggle + drag order), `VideoScreen` (repeater), `ShieldScreen`,
  `DemoImportAjax` (batches of 10). All server-rendered, posting to `admin-post.php`.
- `Domain/Security/*` + `Infrastructure/Security/LoginShield` — custom slug, lockout,
  honeypot, `EDULUME_SHIELD_DISABLE` escape hatch, multisite bail.
- `Domain/Video/*` + `Infrastructure/Content/VideoRailProvider` — facade rail, ≤2 live
  iframes, three-tier intake-video fallback.
- `Infrastructure/Content/DestinationPage`, `FoundersProvider`; theme
  `single-edulume_destination.php`, `page-templates/founders.php`.
- `tests/e2e/` now has four Playwright specs, run inside the `site-audits` CI job.

## Gaps / risks found

- Demo packs `gulf-premium`, `test-prep`, `guide-site` are declared but carry no payloads.
- Settings are three option rows (`edulume_theme_settings`, `edulume_section_overrides`,
  `edulume_content`) plus `edulume_shield`, not one. The brief's single-row `PREFIX_settings`
  would have duplicated the existing store; the security row is deliberately separate so a
  settings reset or import cannot unlock the site.
- Demo media is SVG, not WebP: smaller, resolution-independent, and already sanitised through
  `SvgSanitiser` on import. Provenance in `demos/LICENSES.md`.
- The universities relation is post meta. Fine to a few hundred per country; past that it wants
  a taxonomy for index performance. Ceiling recorded in `DestinationPage`.
- The intake video's last tier is the rail's first video rather than a bundled sample — a video
  file in a theme costs megabytes every install pays for. Filterable via
  `edulume_demo_intake_video`.
- The video rail renders nothing until an owner pastes a URL. Editors see a hint saying where.
