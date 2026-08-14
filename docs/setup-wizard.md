# Setup wizard

Eight steps, all optional, and it never blocks `wp-admin`. Leave it at any point; **Edulume →
Setup** reopens it, and it remembers what you already did.

1. **Welcome** — what the wizard sets up, and your licence key if you have one. Skipping means
   no updates, not a limited product.
2. **Site identity** — name, tagline, logo and favicon. The favicon generates the whole icon set
   and `site.webmanifest` from one square image; upload at least 512×512 or it is refused rather
   than scaled up.
3. **Contact and branches** — phone, WhatsApp, email, office hours and your first branch. These
   feed the utility bar, the floating buttons and the footer from one place.
4. **Style** — accent, typography and density beside a live preview.
5. **Content** — import a starter demo, import your own CSV, or start empty. If the site already
   has content you are asked whether to add alongside it or replace a previous demo; nothing is
   ever overwritten silently.
6. **Add a user** — optional, and it creates an _additional_ user, not the administrator.
   WordPress creates the administrator during core installation, long before any plugin exists
   to ask, so the useful version of this step is a second account with one of the product roles:
   Site Manager, Content Editor or Counsellor. Username and email uniqueness are checked as you
   type, not on submit.
7. **Essentials** — permalinks, time zone, the privacy page and which forms to switch on.
8. **Done** — a checklist of what is set up and what you skipped, each linking to where to
   finish it.

## Re-running it

Safe at any time. It does not undo anything; it walks the same steps with your current values
filled in, and the closing checklist shows what is still empty.
