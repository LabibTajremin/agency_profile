# Demo media licences

Every image the product ships as demo content is listed here, with where it came from and what
licence it carries. The rule this file exists to enforce is simple: **nothing in a distributed
theme may be somebody else's asset under a licence nobody re-checked.**

## What is bundled

| Set                | Location                       | Count | Format | Source                     | Licence |
| ------------------ | ------------------------------ | ----- | ------ | -------------------------- | ------- |
| Hero backdrop      | `boutique/media/hero-*`        | 1     | SVG    | Generated for this product | CC0     |
| Destination scenes | `boutique/media/destination-*` | 8     | SVG    | Generated for this product | CC0     |
| University crests  | `boutique/media/crest-*`       | 24    | SVG    | Generated for this product | CC0     |
| Portraits          | `boutique/media/portrait-*`    | 6     | SVG    | Generated for this product | CC0     |
| Scenes             | `boutique/media/scene-*`       | 12    | SVG    | Generated for this product | CC0     |
| Subject artwork    | `boutique/media/subject-*`     | 10    | SVG    | Generated for this product | CC0     |
| Partner marks      | `boutique/media/partner-*`     | 8     | SVG    | Generated for this product | CC0     |

The machine-readable version, one entry per file, is `boutique/media-credits.json`. That file is
the authority; this table is the summary.

## The seeded videos

The front page's video row ships with four sample links so the section is visible before the
owner has pasted anything. They are **not** bundled files — they are links, and every one of
them points at something we are licensed to point at:

| Item               | Source                                      | Licence   |
| ------------------ | ------------------------------------------- | --------- |
| Big Buck Bunny     | Blender Foundation, official YouTube upload | CC BY 3.0 |
| Three sample clips | Google's public `gtv-videos-bucket`         | CC BY 3.0 |

Big Buck Bunny is © Blender Foundation, `peach.blender.org`. The credit is carried in the item's
own title, which is what the CC BY attribution requirement actually asks for — a licence noted
only in a file nobody opens is not attribution.

**No Facebook, Instagram or TikTok link is seeded, and that is deliberate.** There is no such
video on any of those platforms that belongs to nobody: every one is a real account's real post.
Shipping one in a theme that gets sold means redistributing another company's marketing, which
is the same mistake as bundling unlicensed stock photography, in a form that is easier to
notice. What ships instead is the _format_ of each — in the Videos screen's help, and beside the
address field itself — which is the half an owner actually needs.

Replace all four on day one. They are there so the section is not an empty frame, not because
anybody should launch with them.

## Why generated vector artwork rather than photography

Three reasons, in order of how much they cost when ignored.

1. **Redistribution.** A theme sold to hundreds of sites redistributes every asset in it. Most
   stock licences — including several that read as permissive — do not allow that. Generated
   originals have no such question attached.
2. **Weight.** The whole `boutique/media/` set is under 400KB. An equivalent set of WebP
   photographs at the sizes these are displayed at would be several megabytes, which is a real
   cost on the shared hosting these sites run on and on the phones most of their visitors use.
3. **Scaling.** These are drawn, not sampled, so they are correct at any size and in any
   density. There is no second file for a retina screen and no blur on a wide hero.

## University crests

The crest artworks are **initial-letter monograms on a solid tile**. They are named after real
universities so the demo reads as a working site, but no real institutional mark, logo, crest or
wordmark is reproduced anywhere in this product.

A site owner adding their own partner logos is licensing those from the institutions themselves;
that is their agreement to hold, not ours to ship.

## Adding to this set

Anything added under `demos/*/media/` must have a row in that pack's `media-credits.json` giving
the file, the licence and the source, and must be one of:

- artwork generated for this product,
- an asset the product's owner holds the rights to, or
- a CC0 / Unsplash / Pexels asset, with the source URL recorded in the credits file.

Nothing else. If provenance cannot be stated in one line, it does not ship.
