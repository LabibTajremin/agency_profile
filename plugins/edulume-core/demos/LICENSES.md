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
