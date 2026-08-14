# Theming

## Colour

Every colour decision is made in **OKLCH**, never in HSL. Ramping lightness in HSL swings hue
and perceived saturation between steps, which is why HSL-derived palettes look muddy at the
light end and neon at the dark end.

| Piece             | Where           | Job                                                                 |
| ----------------- | --------------- | ------------------------------------------------------------------- |
| `Srgb`            | `Domain/Color/` | Hex parsing, channels, WCAG relative luminance, transfer functions. |
| `Oklab` / `Oklch` | `Domain/Color/` | The perceptual space, cartesian and cylindrical.                    |
| `ColorSpace`      | `Domain/Color/` | sRGB ↔ OKLab ↔ OKLCH and binary-search gamut mapping.               |
| `ContrastEngine`  | `Domain/Color/` | Ratio, pass/fail, foreground choice, nearest-compliant suggestion.  |
| `NeutralScale`    | `Domain/Color/` | Eleven designed inks, tinted toward the accent hue.                 |

### Designed inks, not pure black and white

The neutral scale never starts at `#ffffff` or ends at `#000000`. Pure white glares, and pure
black smears text edges on OLED panels. The trade-off is that the inks alone cannot always
reach 4.5:1 — so where they cannot, the contrast engine pushes the better ink along its
lightness axis until they do.

## Accent palettes

`PaletteGenerator` turns one seed colour into eleven steps: an OKLCH lightness ramp with a
chroma curve that peaks in the middle and tapers at both ends, holding the seed hue.

`AccentLibrary` ships **24 curated accents**. A custom hex is accepted too, after review.

### Two step choices per mode

Each mode carries **two** choices, not one:

- **Fill step** — the accent as a solid control background. Fixed by design: a strong mid-dark
  step in light mode, several steps brighter in dark mode where the same colour would sink
  into the background.
- **Accent-as-text step** — resolved, not fixed: the most vivid step that still clears AA on
  that mode's page surface.

A colour that works as a button background is frequently unreadable as link text on the same
surface. Collapsing the two is the usual reason accent-driven themes fail a contrast audit.

### Reviewing a custom hex

`AccentReviewer` judges a custom hex **per mode**, because a light page and a dark page sit at
opposite ends of the luminance range and no single colour clears 4.5:1 against both. Where the
hex as typed fails, the review carries the nearest compliant colour, so the admin can offer a
one-click fix rather than silently accepting an illegible accent or refusing the input.

## Patterns

`PatternLibrary` ships **28 tileable patterns**, grouped for the picker: dots, lines,
geometric, waves, texture, editorial, and travel — the last group carrying the four this
niche actually asks for (passport stamps, globe meridians, graduation caps, compass rose).

Every tile is a single `<svg>` element carrying one `{{color}}` placeholder, so one tile
serves every accent. `Pattern::toDataUri()` percent-encodes the whole document: raster tiles
cost a request each and blur when scaled, and an unencoded SVG data URI breaks CSS `url()`
the moment the markup contains a `#`.

### Separate opacities per mode

`PatternSettings` carries **two** opacities, not one. The value that whispers on a white page
is invisible on a near-black one. Defaults are 0.06 in light mode and 0.10 in dark.

Scale is clamped to 0.5×–3×, rotation wraps into a single turn, and every value coerces
rather than throws — these settings arrive from REST, from imported JSON, and from rows
written by an older schema.

## Typography

`FontLibrary` ships **22 self-hosted families**, every one SIL Open Font Licence 1.1 with a
matching row in `CREDITS.md` — enforced by a test, not by memory. Three of them cover Arabic,
Bengali and Devanagari.

Families are self-hosted WOFF2 by default. The Google Fonts CDN is an opt-in toggle, off by
default: a default-on CDN sends every visitor's IP to a third party and costs a connection to
a second origin.

`FontPairingLibrary` ships **10 pairings**. A pairing sets heading and body; the other four
roles (display, interface, quote, code) inherit from one of those two.

### Overrides are stored as absence

`TypographySettings` holds sparse per-role overrides. A role with no entry follows the
pairing, so changing the pairing later propagates into every role the user never touched.
`withoutRoleOverride()` restores inheritance by removing the key, never by copying the current
value into it.

`familiesInUse()` returns only the families a role actually resolves to. Enqueueing the whole
bundled library is how a 100 KB font budget turns into half a megabyte.

### The scale

`TypeScale` emits fluid `clamp()` values in rem, never px, so raising the browser base size
enlarges the site. The narrow-viewport end uses a **damped ratio**: a 1.5 ratio that reads as
confident on a desktop headline is a wall of text on a 360px phone. Below the base step the
wide-viewport size is the smaller of the two, so the bounds are ordered explicitly — an
inverted `clamp()` silently pins to one end.

## Sections and inheritance

`SectionOverride` is a sparse map keyed by `SectionOverrideKey`. An absent key means inherit —
inheritance is stored as **absence**, never as a copy of the global value. That is what makes a
later global change propagate into untouched sections, and what lets the admin show an accurate
modified dot with a per-key revert. `divergedKeys()` reports exactly what the user changed.

`SectionResolver` folds the global settings with an override into a `ResolvedSection` where
nothing is nullable. An override that sets nothing resolves identically to global, field for
field — the property everything else relies on.

## The compiled stylesheet

`TokenCompiler` is the only place a design decision becomes a value. Nothing downstream — theme
stylesheet, block, or template — may contain a colour, font or spacing literal; it reads a
token instead.

`CompileStylesheet` emits, in this order:

1. `:root` with the light-mode tokens
2. `[data-theme="dark"]` re-declaring **only** the tokens that actually differ
3. one scoped block per diverging section, in both modes
4. the pattern layer, drawn on a pseudo-element so opacity and blend apply to the pattern and
   not to the content above it
5. `@media (prefers-reduced-motion: reduce)` — **last**

The reduced-motion block is last on purpose. Source order decides the winner in CSS, and a
visitor who asked their operating system for less motion must beat every stored setting,
however specific.

`CompiledStylesheet` carries a content hash derived from the CSS itself, not from a timestamp
or a settings blob, so the filename changes when and only when the output changes. That is what
lets the file be served with a far-future cache header and still update the moment a setting
moves.

## Why a misspelt token is worse than a missing one

`composer audit:tokens` (`bin/token-audit.php`) fails the build when theme CSS reads a custom
property that neither the compiler emits nor the theme declares. It exists because of a bug
that survived every other gate in this pipeline.

The theme read `var(--edulume-surface-sunken, #f1f2f4)` in the footer and the utility bar. It
looked right, it passed the literal gate — a fallback is allowed — and in light mode it *was*
right, because the fallback is a light grey and so is the light-mode surface. Nothing emits
`--edulume-surface-sunken`. The compiler emits `--edulume-surface-subtle`.

In dark mode the ink token flipped to near-white, as designed, and the background stayed the
light grey literal, because a fallback does not change with the mode. The result was white on
white at **1.07:1** against a 4.5:1 requirement, on every template, for as long as the typo
had existed. The failure was invisible in the mode people develop in.

So the gate does not check against a list of valid token names. A hand-maintained list would
have been written by the same person who made the typo, and would have contained it. It
instantiates `TokenCompiler`, compiles the default settings in **both** modes, and asks what
came out — a token emitted in only one mode is precisely the asymmetry worth catching.

The corollary for anyone adding CSS: a `var()` fallback is a degradation path for when the
plugin is deactivated, never a value. If something must respond to the accent or the mode, it
has to be a `var()` of a token that does. Tokens the compiler does not own — the spacing steps,
the pill radius, the shadow — are derived in the theme's own `:root` in `base.css` from tokens
it does, which is why widening the gutter moves the header padding and the footer rhythm with
it.
