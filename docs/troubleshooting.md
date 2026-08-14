# Troubleshooting

## The site looks plain and there is an admin notice

The theme is active but **Edulume Core** is not. Activate the plugin. Your content is untouched;
the theme degrades rather than breaking.

## Archive URLs 404

Go to **Settings → Permalinks** and press Save. WordPress only rebuilds rewrite rules when that
page is saved, and the content types register their bases on activation — before you had a
chance to set permalinks to Post name.

## My configurator changes are not showing on the site

In order of likelihood:

1. **You did not press Save.** Nothing reaches the public site until you do.
2. **A caching plugin is serving an old page.** The stylesheet itself is named by a content
   hash and never needs purging, but the HTML that links it does. Purge the page cache.
3. **A section override is winning.** Check **Design → Sections** for the section in question —
   an override beats the global value by design, and a control showing no Inherit badge is set.

## The site is unusable after a configuration change

Add `?edulume_safe_mode=1` to any URL. That renders default tokens without touching anything
stored, so you can reach the configurator and fix or reset. Then **Edulume → Safety →
Snapshots** restores the last known-good configuration exactly.

You never need database access for this.

## The CSV import stops part way

It has not failed — it ran out of execution time and saved its place. Press **Resume**. On
hosting with a 30-second limit a large catalogue takes several passes, and each one picks up
exactly where the last stopped.

If a run reports failed rows, the per-row list says which and why. Fix those rows and re-import
just them; rows whose `slug` already exists are updated, not duplicated.

## Leads are not arriving by email

WordPress's `wp_mail()` goes through your host's PHP mail by default, which most inbox providers
now reject. Install an SMTP plugin and send through a real mail service. The lead is still
recorded in **Edulume → Leads** regardless of whether the email got through — check there first
to tell a delivery problem from a capture problem.

## The eligibility quiz returns nothing

It reports near misses rather than hiding them, so an empty result means no course in the
catalogue is close. Check that courses have `study_level` and an English score set; a course
with neither cannot match any profile.

## A colour I chose is flagged

The review is per mode. No single colour clears 4.5:1 against both a near-white and a near-black
surface, so a hex that passes in light mode can fail in dark. Use the suggested nearest
compliant colour for the failing mode, or set a different accent for that mode.

## Updates are not appearing

Check **Edulume → Licence**. An expired licence stops updates and support and nothing else — the
product keeps working. A licence in its fourteen-day grace period still receives updates. If the
status says the site limit is reached, deactivate the licence on a site you no longer use.

## Something else

`Edulume → System` prints the versions, active plugins, PHP extensions and file permissions we
ask for first in a support request. Copy that panel into your message.
