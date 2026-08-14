# Credits

Every third-party asset bundled with Edulume, and the licence it ships under. This file is
verified before each release rather than written once — an unverified credits file is how a
product ends up redistributing something it may not.

If you resell sites built with this, read this file first. Everything listed here is cleared for
that; anything not listed here is not bundled.

## Fonts

All 22 bundled families are **SIL Open Font License 1.1**, self-hosted, and cleared for
redistribution and for commercial use including resale.

| Family               | Designer                        | Source                                  |
| -------------------- | ------------------------------- | --------------------------------------- |
| DM Sans              | Colophon Foundry / Google       | github.com/googlefonts/dm-fonts         |
| EB Garamond          | Georg Duffner, Octavio Pardo    | github.com/octaviopardo/EBGaramond12    |
| Figtree              | Erik Kennedy                    | github.com/erikdkennedy/figtree         |
| Fraunces             | Undercase Type                  | github.com/undercasetype/Fraunces       |
| IBM Plex Sans        | Mike Abbink / IBM               | github.com/IBM/plex                     |
| Inter                | Rasmus Andersson                | github.com/rsms/inter                   |
| JetBrains Mono       | JetBrains                       | github.com/JetBrains/JetBrainsMono      |
| Libre Baskerville    | Impallari Type                  | github.com/impallari/Libre-Baskerville  |
| Lora                 | Cyreal                          | github.com/cyrealtype/Lora-Cyrillic     |
| Manrope              | Mikhail Sharanda                | github.com/sharanda/manrope             |
| Merriweather         | Sorkin Type                     | github.com/SorkinType/Merriweather      |
| Noto Sans Arabic     | Google                          | github.com/notofonts/arabic             |
| Noto Sans Bengali    | Google                          | github.com/notofonts/bengali            |
| Noto Sans Devanagari | Google                          | github.com/notofonts/devanagari         |
| Outfit               | Smartsheet / Rodrigo Fuenzalida | github.com/Outfitio/Outfit-Fonts        |
| Playfair Display     | Claus Eggers Sorensen           | github.com/clauseggers/Playfair-Display |
| Plus Jakarta Sans    | Tokotype                        | github.com/tokotype/PlusJakartaSans     |
| Roboto Slab          | Christian Robertson / Google    | github.com/googlefonts/robotoslab       |
| Sora                 | Jonathan Barnbrook              | github.com/sora-xor/Sora-Typeface       |
| Source Sans 3        | Paul D. Hunt / Adobe            | github.com/adobe-fonts/source-sans      |
| Source Serif 4       | Frank Griesshammer / Adobe      | github.com/adobe-fonts/source-serif     |
| Work Sans            | Wei Huang                       | github.com/weiweihuanghuang/Work-Sans   |

The Arabic, Bengali and Devanagari subsets are shipped so the type scale survives taller scripts
without a fallback stack taking over mid-paragraph.

## Icons

**Lucide** — ISC License. github.com/lucide-icons/lucide

Shipped as an inline SVG sprite of only the icons the product uses, not the whole set.

## Patterns

The 28 background patterns are **original work**, generated as SVG by this product and released
under the same licence as the product. No third-party pattern library is bundled.

## Demo photography

Each starter demo records its media licence in its definition, and the importer refuses to
redistribute anything not cleared for it:

| Demo                             | Licence |
| -------------------------------- | ------- |
| Gulf premium consultancy         | CC0     |
| Consultancy and test-prep centre | CC0     |
| Content and SEO-led guide site   | CC0     |
| Boutique practice                | CC0     |

CC0 sources: Unsplash (pre-2021 licence terms verified per image), Pexels, and original
photography. Every image's provenance is recorded in `plugins/edulume-core/demos/<slug>/
media-credits.json` and re-verified at release.

A demo whose photography could not be cleared imports **placeholders** instead. That makes a
worse demo and it is the right trade: the alternative puts the buyer in breach of a stock
licence they never agreed to.

## Development dependencies

Not shipped in the release ZIP — they exist only in the repository:

| Package                    | Licence              |
| -------------------------- | -------------------- |
| PHPUnit                    | BSD-3-Clause         |
| PHPStan                    | MIT                  |
| PHP_CodeSniffer            | BSD-3-Clause         |
| WordPress Coding Standards | MIT                  |
| Vite, Vitest               | MIT                  |
| ESLint, Prettier           | MIT                  |
| Playwright, axe-core       | Apache-2.0 / MPL-2.0 |
| Lighthouse CI              | Apache-2.0           |

## WordPress

Edulume is licensed **GPL-2.0-or-later**, matching WordPress. Every bundled asset above is
compatible with that.
