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
