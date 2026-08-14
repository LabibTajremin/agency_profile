# Performance budget

The numbers are in `Edulume\Core\Domain\Performance\PerformanceBudget` and asserted by the
`site-audits` CI job through `lighthouserc.json`. A budget nobody measures is a wish.

| Metric                        | Budget   |
| ----------------------------- | -------- |
| Mobile Lighthouse performance | ≥ 90     |
| Largest Contentful Paint      | ≤ 2.0 s  |
| Cumulative Layout Shift       | ≤ 0.05   |
| Interaction to Next Paint     | ≤ 200 ms |
| JavaScript transferred        | ≤ 120 KB |
| CSS transferred               | ≤ 60 KB  |
| Fonts transferred             | ≤ 100 KB |
| Requests                      | ≤ 45     |

Measured under 4× CPU throttling on a Slow 4G profile, three runs per URL. Measuring on a fast
desktop connection is how a site ships at four seconds on a phone with a green badge in CI.

A metric the run did not produce counts as a **breach**, not a pass. Silent omission is exactly
how a budget stops catching anything.

## How the budget is kept

- **Critical CSS is inlined** (`assets/css/critical.css`) and deliberately short. Inlining is
  only a win while it stays smaller than the request it replaces.
- **Scripts load conditionally.** Blocks declare the module they need as they render
  (`edulume_block_requires_feature`), and the footer enqueues only those. A page with no
  carousel ships no carousel code — including for blocks nested inside reusable blocks and
  template parts, which scanning post content would miss.
- **The LCP image gets `fetchpriority="high"` and `loading="eager"`**; every other image gets
  `loading="lazy"` and `decoding="async"`. Only the first in-content image on a singular view
  is promoted — `fetchpriority` on the wrong image is worse than not using it.
- **Every image carries explicit dimensions**, and cards reserve their aspect ratio, which is
  what holds CLS at zero while images decode.
- **No unbounded queries.** `composer audit:queries` fails the build on `posts_per_page => -1`,
  `nopaging`, and a `LIMIT`-less `get_results`. Finder page sizes are clamped server-side, so
  nothing a visitor types can widen a query.

## Caching plugins

The compiled stylesheet is written to `wp-content/uploads` under a content-hash filename, so it
is safe to cache for a year and never needs purging — a settings change produces a different
file name rather than a different file.

Page caches are compatible without configuration. The two things to exclude from full-page
caching are the lead form endpoints and the finder's fetch responses; both are already
`POST`/no-store, which every major plugin honours by default.
