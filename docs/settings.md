# The configurator

**Edulume → Design** opens the configurator: nine panels beside a live preview. Press
<kbd>Cmd</kbd>/<kbd>Ctrl</kbd> + <kbd>K</kbd> anywhere in it to search every setting by name,
including the ones behind Advanced.

Three things are true of every panel:

- **It opens with a handful of controls and hides the rest behind Advanced.** The defaults are
  chosen to be shippable.
- **It leads with presets.** You should be able to get a good-looking site without touching a
  slider.
- **A changed control shows a dot and a revert button.** Revert takes that one control back —
  to the shipped default, or to _inheriting_ if the control is a per-section override.

Nothing reaches the public site until you press **Save**. Leaving with unsaved changes prompts
and names the panels that would lose them.

## Presets

Ten complete looks. Choosing one **previews** it; it is not applied until you confirm, and a
snapshot is taken first, so undo restores exactly what you had.

You can export your current configuration as a preset file and import it on another site.

## Colors

One accent drives the whole palette. Every derived colour — text, links, borders, focus ring,
the admin scheme — is generated through the contrast engine and checked against WCAG at build
time, for all 24 accents in both modes.

Entering a **custom hex** gets you a per-mode review: a colour that clears 4.5:1 on a near-white
background will not clear it on a near-black one, so the review reports each mode separately and
suggests the nearest compliant colour for whichever mode falls short.

## Typography

Ten curated pairings, 22 self-hosted families. Setting a single role — heading, body, mono —
breaks the pairing for that role only and leaves the rest inherited. The type scale is fluid
`clamp()` with a damped mobile ratio, so an H1 that reads well on a desktop does not overflow a
phone.

Add Arabic or Bengali under **Character subsets** only if you publish in those scripts; Latin
alone keeps the font budget under 100 KB.

## Patterns

Twenty-eight tileable patterns, tinted with your accent and emitted as a data URI — no extra
request. Light and dark opacity are set separately, because a pattern that reads well on white
is usually invisible on near-black.

## Layout, Motion, Header, Footer

Width, density and radius do most of the work of a redesign. Motion ships five presets and
seventeen individually toggleable effects; turning one off removes its JavaScript entirely.
Header and footer variants change arrangement only — your menu, logo and widgets carry across,
so switching one never loses configuration.

**`prefers-reduced-motion` overrides every motion setting here.** That is deliberate and cannot
be switched off.

## Sections

Re-theme one section of the home page without touching the rest. Anything you do not set is
inherited and shows an **Inherit** badge; reverting a section control returns it to inheriting
rather than to a default.

## Safety

**Edulume → Safety** holds export, import with a full before/after diff, granular and full
reset behind a typed confirmation, the last ten snapshots, and session undo/redo.

If a configuration ever renders the site unusable, add `?edulume_safe_mode=1` to any URL. That
renders default tokens without touching anything stored, so you can get back into the
configurator and fix it — no database access needed.
