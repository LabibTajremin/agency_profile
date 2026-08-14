# Release checklist

Every box needs **evidence** — a CI run, a link, a screenshot — not an assertion. A checklist
signed off from memory is a checklist that certifies nothing.

## 1. The build says yes

- [ ] `composer check` green locally
- [ ] All CI jobs green on the release branch: `php-quality`, `php-unit` (8.1 / 8.2 / 8.3),
      `php-integration`, `js-quality`, `site-audits`, `wordpress-standards`
- [ ] Coverage gate: 100% line on `Domain` + `Application`, ≥90% branch
- [ ] `composer make:pot` produces no diff — the shipped template is current
- [ ] `php bin/package.php --verify` builds all three ZIPs with nothing missing and no test,
      vendor or source-map file inside

## 2. Visual regression matrix

24 accents × 2 modes × 6 templates = **288 renders**. Reviewed, not merely generated — a
regression suite whose diffs nobody looks at is a slow way of storing PNGs.

Templates: home, course archive, single course, institution archive, contact, 404.

- [ ] Matrix generated for the release candidate
- [ ] Every diff against the previous release triaged: intended, or a bug with an issue number
- [ ] No accent produces a contrast failure in either mode (the automated check covers this;
      confirm it ran)

## 3. Cross-browser

Real devices or a device cloud, not only emulation. Home, course finder, eligibility quiz and
one lead form on each.

- [ ] Chrome, latest, Windows
- [ ] Firefox, latest, Windows
- [ ] Safari, latest, macOS
- [ ] Safari, iOS, current and current − 1
- [ ] Edge, latest
- [ ] Samsung Internet, current — the largest single browser in several of this product's
      markets, and the one most likely to be skipped

## 4. Accessibility

- [ ] axe sweep green in CI (every template, both modes)
- [ ] NVDA / Firefox pass recorded — see [accessibility](accessibility.md)
- [ ] VoiceOver / Safari pass recorded, macOS and iOS
- [ ] 200% zoom and 400% reflow checked at four breakpoints
- [ ] `prefers-reduced-motion` verified to override every stored motion setting

## 5. Performance

- [ ] Lighthouse CI within budget on all six URLs — see [performance](performance.md)
- [ ] Verified on the **shared-hosting** deployment below, not only in Docker. Docker on a CI
      runner is faster than the hosting most of these sites run on, and a budget met only there
      is a budget met nowhere that matters.

## 6. Internationalisation

- [ ] `composer audit:i18n` green
- [ ] RTL checked with **real Arabic content** at four breakpoints — lorem ipsum in an RTL
      layout hides exactly the problems this is looking for
- [ ] Bengali and Devanagari render without line-height collisions
- [ ] English, Arabic and Bengali translations complete; `.pot` current

## 7. Security

- [ ] `composer audit:security` green
- [ ] WPCS security ruleset clean
- [ ] Plugin Check clean
- [ ] Penetration checklist worked through on staging — see [security](security.md)

## 8. A real deployment

On a **real shared-hosting account**, not Docker:

- [ ] Fresh WordPress, fresh database
- [ ] Both ZIPs installed through the dashboard uploader
- [ ] Setup wizard completed end to end, including creating the additional user
- [ ] Each of the four demos imported on a clean install, each in under three minutes
- [ ] Rollback removes imported content completely
- [ ] Importing over existing content never destroys it silently
- [ ] A 3,000-row CSV imported to completion through the resume path
- [ ] A lead submitted from the front end arrives in the inbox and by email

## 9. Licensing

- [ ] Activation, deactivation and site-limit enforcement against the live endpoint
- [ ] An expired licence blocks updates and **nothing else**
- [ ] The product functions fully with no licence key at all

## 10. Documentation and packaging

- [ ] Someone who has never seen the product installs it, rebrands it and imports a demo using
      only `docs/` — and the places they got stuck are fixed before release, not noted
- [ ] `CHANGELOG.md` complete for this version
- [ ] `CREDITS.md` re-verified: every bundled asset's licence checked this release, not
      inherited from last time
- [ ] Support policy current

## 11. Tag

- [ ] Version bumped in `edulume-core.php`, `style.css`, `package.json`, `readme.txt`
- [ ] `CHANGELOG.md` entry dated
- [ ] Tag pushed
- [ ] Release ZIPs attached to the tag

## Beta feedback

- [ ] Beta reports triaged; each either fixed, scheduled with an issue number, or answered with
      a reason. "Won't fix" is a legitimate outcome; silence is not.
