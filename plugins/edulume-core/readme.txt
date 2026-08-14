=== Edulume Core ===
Contributors: edulume
Tags: education, study abroad, consultancy, leads, courses
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Content types, leads, finders and the design system behind the Edulume theme.

== Description ==

Edulume Core carries everything that has to outlive a theme switch: sixteen content types with
real post-to-post relationships, the lead pipeline and form builder, the course, institution and
scholarship finders, the block library, and the token compiler the theme reads.

Registering all of it in the plugin rather than the theme is deliberate. Content registered by a
theme disappears the moment somebody switches themes, and taking a client's course catalogue
down with a redesign is not a recoverable mistake.

Nothing here calls a paid API. Currency conversion uses admin-editable static rates, captchas
are optional, and the spam protection works with none configured.

== Installation ==

1. Upload the plugin and activate it.
2. Install the Edulume theme.
3. Follow the setup wizard, or skip it — it never blocks wp-admin.

Set Settings → Permalinks to Post name so the archive bases take effect.

== Frequently Asked Questions ==

= Does my content survive switching themes? =

Yes. Content types and blocks are registered here, not in the theme.

= What happens when a licence expires? =

Updates and support stop. Nothing else. The product keeps working.

= Does it work on shared hosting? =

Yes. Every long-running operation is chunked against the execution limit rather than assuming
it can run to completion.

== Changelog ==

See CHANGELOG.md in the repository for the full history.
