# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Repository scaffold, toolchain configuration and the continuous integration gate.
- Offline coverage gate enforcing the floor on the `Domain` and `Application` layers.
- Colour foundation: sRGB, OKLab and OKLCH value objects, colour-space conversion with
  binary-search gamut mapping, the WCAG contrast engine, and an eleven-step neutral scale of
  designed inks.
- Accent palettes: an OKLCH ramp generator with chroma shaping, 24 curated accents, separate
  fill and accent-as-text choices per mode, and a per-mode review of custom hexes that
  carries a nearest-compliant suggestion.
- Pattern library: 28 tileable SVG patterns with `{{color}}` tinting and percent-encoded
  data-URI output, plus pattern settings with separate light and dark opacities.
- Typography: 22 self-hosted OFL families, 10 curated pairings, sparse per-role overrides
  stored as absence, and a fluid `clamp()` type scale with a damped mobile ratio.
- Motion: five presets with complete timing sets, six named easing curves plus a validated
  custom bezier, 17 individually toggleable effects, and module loading derived from the
  active effect list.
- Settings aggregate: `ThemeSettings` with a schema version, lossless `toArray()`/
  `fromArray()`, `Guard` coercion helpers, and a migration runner carrying a v1 blob forward
  without data loss.
- Per-section overrides: inheritance stored as absence, `divergedKeys()`, and a resolver that
  folds global settings with an override into a section where nothing is nullable.
- Token compiler and stylesheet pipeline: the complete custom-property set per mode, a
  dark block re-declaring only what differs, scoped section blocks, and a `prefers-reduced-motion`
  block emitted last, all named by a content hash.
- Style presets: ten complete looks, preview-first non-destructive application, byte-for-byte
  undo, and lossless export/import of user presets.
- Plugin bootstrap: plugin header, activation, deactivation and a gated uninstall, an explicit
  service container, named capabilities, option-backed settings storage that migrates on read,
  a hashed stylesheet written into uploads, and the first integration suite under `wp-env`.
- Content model: sixteen post types, eleven taxonomies and eight real post-to-post
  relationships stored as IDs, with filterable permalink bases and a per-item accent
  override, all registered by the plugin so content survives a theme switch.
- CSV import and export: chunked and resumable against an injected execution budget, per-row
  error reporting that never aborts a run, and an export that honours the applied filters and
  round-trips back through the importer.
- Lead domain and storage: the `Lead` entity with the pipeline's legal transitions enforced in
  the domain, an append-only note trail, assignment, and custom indexed tables created through
  an idempotent migration with every query prepared.
- Form builder and submission: twelve field types, multi-step forms with progress and
  conditional logic, six placements, honeypot and time-trap spam protection that works with no
  captcha configured, per-address rate limiting, GDPR consent, retention periods and erasure
  that also removes uploaded files.
- Lead inbox: composable filters with pagination, a counsellor scope applied after the request
  so it cannot be widened, page-by-page filtered CSV export, branch-aware notification
  recipients, and an autoresponder that inlines the site's own accent.
- Integrations and tracking: an HMAC-signed outbound webhook with capped exponential retries,
  CRM connectors that log failures instead of blocking the lead, and consent categories that
  actually gate every tracking script.
- REST API: the `edulume/v1` route catalogue as auditable data, with a JSON schema and a
  required capability on every route, and a registrar that derives the permission callback
  from the definition rather than trusting each controller to remember one.
- Admin shell foundations: an accent-derived admin palette proven AA across all 24 accents in
  both admin modes, with an admin light/dark choice independent of the public site, and the
  Cmd-K settings search index.
- Safety nets: settings export and import with a full before/after diff shown first, granular
  and full resets behind a typed confirmation, a bounded snapshot history, session undo/redo,
  and a safe mode that renders defaults without touching what is stored.
- Eligibility checker and cost calculator: profile clamping, course matching that reports near
  misses instead of hiding them, deterministic ranking, and a cost estimate converted once at
  the end through admin-editable static rates with no paid API.
- Roles and permissions: four roles stated as denials as much as grants, with the inbox scope
  applied to the query rather than to the rendered list.
- Theme skeleton: `theme.json` v3 registering the same tokens the front end uses, a base
  stylesheet built entirely on custom properties, a no-flash inline mode resolver, graceful
  degradation with an admin notice when the plugin is absent, and the shipped child theme.
- SEO: a Schema.org `@graph` that prunes empty properties, and deferral to Yoast/RankMath/
  SEOPress for meta tags while keeping the structured data.
- Security: an allowlist-based SVG sanitiser that strips scripts, event handlers and external
  references.
- A `composer audit:theme` gate proving no colour, font or spacing literal exists in theme CSS
  outside a `var()` fallback, and that every translation call uses the one text domain.
- Configurator panels: the nine panels described as data — controls with a settings path, inline
  help, an Advanced flag and a live-sample kind — so the search index, the modified dot, the
  revert button and the REST round-trip all work one way rather than nine.
- Live preview: token changes applied inside the iframe as CSS custom-property patches over
  `postMessage`, with a minimal-diff bridge, a device and page switcher, hold-to-compare, and an
  unsaved-changes guard that names the panels at risk.
- Setup wizard: eight optional steps that never block `wp-admin`, creating an _additional_ user
  with one of the product roles, with uniqueness validated inline rather than on submit.
- Global chrome: six header variants, four footer variants, the utility bar, the scheduled
  announcement bar, the mobile drawer with a focus trap, the floating action cluster, the 404,
  search and maintenance templates, and a favicon set that generates the full icon range and
  `site.webmanifest` from one square.
- Page templates: one shared archive body and one shared single body that all sixteen content
  types delegate to, plus the modular home page whose sections are reorderable and individually
  re-themeable.
- Block library: 38 blocks in five groups, each with two to four style variations, registered by
  the plugin so content survives a theme switch, and each declaring the script module it needs.
- Finders: course, institution and scholarship finders with declared facets, clamped page sizes,
  URL-shareable filter state in a stable order, compare sets of two to four, and `localStorage`
  shortlists with no login.
- Performance budget and accessibility, both enforced in CI: Lighthouse against explicit
  thresholds under 4× CPU throttling on Slow 4G, and axe against every template in both modes.
- Internationalisation: a build gate on physical CSS direction properties and on untranslated
  strings, a regenerable `.pot` checked for staleness in CI, and locale-aware numbers, currency
  and dates including South Asian digit grouping.
- Security hardening: a build gate on executable constructs, unprepared queries, REST routes
  with no permission callback and unsanitised request data; an allowlist upload policy checking
  extension and MIME type together; Administrator-only custom CSS and JS; and the WPCS security
  ruleset and Plugin Check as their own CI job.
- Starter demos: four complete demos and a chunked, resumable importer with full, content-only
  and settings-only modes, an explicit merge-or-replace choice, and an exact rollback.
- Licensing and updates: activation, deactivation, site-limit enforcement and a fourteen-day
  grace period — with no licence state ever disabling the product.
- Documentation and packaging: installation, setup, settings, content, CSV format with a sample,
  block reference, child-theme guide, FAQ, troubleshooting and support policy; a verified
  `CREDITS.md`; and an allowlist-based ZIP builder.
