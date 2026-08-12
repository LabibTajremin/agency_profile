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
