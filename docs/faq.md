# FAQ

**Does my content survive switching themes?**
Yes. Content types and blocks are registered by the plugin, not the theme. Switch away and the
posts, the relationships and the block content are all still there.

**Can I use this with my existing theme?**
The plugin works on its own — content types, leads, forms, finders and the REST API do not need
the Edulume theme. You lose the design layer, which is most of what the theme is.

**Do I need a page builder?**
No. The block library covers the layouts; the configurator covers the design. Page builders work
alongside it if you already use one.

**Does anything call a paid API?**
No. Currency conversion uses admin-editable static rates. Captchas are optional and the spam
protection works with none configured.

**What happens when my licence expires?**
Updates and support stop. Nothing else. The site keeps working exactly as it did.

**How many sites does one licence cover?**
Whatever your plan says — the count is enforced by the licence server. Deactivating a site
releases its seat immediately, even if our server is unreachable at the time.

**Can I resell sites built with this?**
Yes. Bundled fonts are OFL and demo photography is CC0 or licensed for redistribution; see
[CREDITS.md](../CREDITS.md). Check that file before reselling, not after.

**Can I remove your branding?**
There is none to remove. The footer credit line is an ordinary setting you can edit or empty,
and nothing is gated behind a licence check.

**Will it work on shared hosting?**
Yes. Every long-running operation — CSV import, demo import — is chunked against the execution
limit rather than assuming it can run to completion.

**Is it accessible?**
Every template is checked with axe in both light and dark mode on every pull request, and
critical or serious violations fail the build. See [accessibility](accessibility.md) for what
the build enforces and what still needs a person.

**Does it support RTL?**
Yes, throughout — the stylesheets use logical properties, and a build gate fails on any physical
one. English, Arabic and Bengali ship translated; everything else is translation-ready with a
generated `.pot`.

**How do I get support?**
See [the support policy](support.md).
