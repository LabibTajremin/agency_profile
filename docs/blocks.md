# Block reference

Thirty-eight blocks in five inserter groups, all under **Edulume**. Each ships two to four style
variations, and every one reads the compiled tokens rather than carrying its own colours — so a
change of accent moves the whole library.

Blocks are registered by the **plugin**. Content built with them survives a theme switch.

## Layout

`Section`, `Container`, `Split columns`, `Divider`, `Sticky aside`, `Tabs`, `Accordion`

`Section` is the one to reach for first: it carries the background tone, the pattern and the
spacing, and it is what the per-section overrides in the configurator target.

## Content

`Hero`, `Page header`, `Feature grid`, `Icon list`, `Steps`, `Quote`, `Statistics`, `Timeline`,
`Team grid`, `FAQ list`

`Steps` is worth knowing — a numbered process is most of what a consultancy sells, and it reads
better than a bulleted list of the same words.

`FAQ list` emits `FAQPage` structured data once per page, however many FAQ blocks are on it.

## Data

`Course grid`, `Course table`, `Institution grid`, `Destination grid`, `Scholarship list`,
`Event list`, `Comparison table`, `Ranking badge`, `Intake calendar`

These query real content and are always paginated. There is no "show all" option, by design:
an unbounded query is what takes a site down at three thousand courses.

`Event list` drops past events automatically. `Scholarship list` marks closed scholarships
rather than hiding them — someone researching next year still wants to see them.

## Media

`Gallery`, `Media and text`, `Video`, `Logo wall`, `Before and after`, `Testimonial slider`

`Video` loads its player only once someone asks for it, which is worth roughly half a megabyte
on a page with an embed above the fold.

## Conversion

`Call to action`, `Lead form`, `Eligibility check`, `Cost calculator`, `Book counselling`,
`Newsletter signup`

`Eligibility check` is the highest-converting block in the set: it produces matched courses and
captures the lead with the full profile attached, so the counsellor opens an enquiry already
knowing what the person is looking for.

`Book counselling` carries the page it was clicked from into the lead.

## Style variations

Each block's variations are in the block sidebar under **Styles**. A variation changes appearance
only — switching one never loses content. Add your own from a child theme; see
[the child-theme guide](child-theme.md).

## Scripts

A block that needs JavaScript declares it, and the page loads only the modules its blocks asked
for — including blocks nested inside reusable blocks and template parts. A page with no carousel
ships no carousel code.
