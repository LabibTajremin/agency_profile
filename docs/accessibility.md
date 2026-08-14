# Accessibility

The parts a machine can check are checked on every pull request. The parts it cannot are listed
here as work a person has to do, because a checklist that claims a build can verify a screen
reader pass is worse than one that admits it cannot.

## Enforced in CI

The `site-audits` job runs axe-core against **every template in both light and dark mode** — the
list is in `tools/axe/run.mjs`. Auditing one mode is how a dark theme ships with an invisible
focus ring: the light run passed and nothing looked at the other half.

Critical and serious violations fail the build. Moderate and minor ones are printed but do not.
A gate that fails on every advisory is a gate people start skipping, and a skipped gate catches
nothing.

Lighthouse's accessibility category is asserted at 1.0 in `lighthouserc.json`.

## Enforced in code

`Edulume\Core\Domain\Accessibility\AccessibilityRules` holds the numbers, so the same thresholds
apply in the admin's contrast checks and in the theme:

| Rule                | Value                             | Where it bites                                                            |
| ------------------- | --------------------------------- | ------------------------------------------------------------------------- |
| Touch target        | 44×44 px, both axes               | Buttons, nav links, the floating cluster, the dismiss control             |
| Focus ring contrast | 3:1 against the surface behind it | Checked per mode, against the actual section surface                      |
| Body text contrast  | 4.5:1                             | Every accent-derived ink pair is proven at build time                     |
| Large text contrast | 3:1                               | Headings at 24px and above                                                |
| Heading order       | One `h1`, no level skipped        | A jump from `h2` to `h4` tells a screen-reader user a section was omitted |
| Landmarks           | `banner`, `main`, `contentinfo`   | Present in `header.php` and `footer.php`                                  |

`prefers-reduced-motion` is declared last in `base.css` and overrides **every** motion setting
the configurator can store. That is deliberate and is not configurable — a visitor with a
vestibular disorder should not need the site owner's permission.

## Keyboard

- A skip link is the first focusable element on every page.
- The mobile drawer traps focus while open, closes on `Esc`, and returns focus to the control
  that opened it rather than to the top of the document.
- The finder is a real `<form>`: it filters with the keyboard alone, with or without JavaScript.
- Focus is `:focus-visible`, so a mouse click leaves no ring — which is the reason people used
  to remove outlines entirely.

## What still needs a person

These cannot be automated and have to be repeated before each release:

- [ ] **NVDA on Windows / Firefox.** Walk the home page, the course finder, the eligibility quiz
      and a lead form. Confirm each form error is announced, not just displayed.
- [ ] **VoiceOver on macOS / Safari and on iOS.** The same four flows. Confirm the rotor lists
      the landmarks and that headings read as an outline.
- [ ] **Alt text review.** The build enforces that a decorative image has an empty `alt` and a
      meaningful one does not. Whether the text actually describes the image is a judgement.
- [ ] **200% zoom and 400% reflow.** No horizontal scrolling at 320 px wide equivalent.
- [ ] **Windows High Contrast Mode.** Confirm borders survive; several tokens become
      `currentColor` there.
