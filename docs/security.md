# Security

What the build enforces, and the checklist a person still has to work through before a release.

## Enforced on every pull request

`composer audit:security` (`bin/security-audit.php`) fails the build on four things. Each was
chosen because breaking it is **silent** — the site keeps working, and nobody finds out until
someone is looking for a way in.

| Rule                                                                                                         | Why                                                                                            |
| ------------------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------- |
| No `eval`, `create_function`, `assert` on a string, executing a `base64_decode`, or fetching code at runtime | Legitimate WordPress products need none of these; compromised ones are found doing all of them |
| Every `$wpdb` query goes through `prepare()`                                                                 | Interpolating a variable into SQL is the bug that keeps coming back                            |
| Every `register_rest_route()` declares a `permission_callback`                                               | WordPress registers a route happily without one — a public endpoint nobody meant to publish    |
| No `$_GET`/`$_POST`/`$_REQUEST`/`$_COOKIE` read without a sanitiser on the same line                         | Unsanitised input is the entry point for most of the rest                                      |

The gate is verified against a deliberately vulnerable file: each of the four rules has been
observed catching a real violation and passing the corrected form.

The `wordpress-standards` job additionally runs **PHPCS with the WordPress security ruleset**
(`phpcs-security.xml.dist`) and **Plugin Check**. Both are kept in their own job with their own
dependency install rather than folded into `composer lint`, because WPCS cannot be installed in
every environment this repository is developed in — and a gate folded into a lint that cannot
run there would be a gate that silently passes.

## Enforced in code

- **REST.** Routes are declared as data in `Domain\Rest\RouteCatalogue` with a required
  capability on every one, and the registrar derives the permission callback from the
  definition. A controller cannot forget a check it never had the chance to write.
- **Roles.** Four roles, stated as denials as much as grants. The counsellor's lead scope is
  applied to the query, not to the rendered list — a scope applied at render time is a scope
  that leaks through the next endpoint someone adds.
- **Uploads.** `Domain\Security\UploadPolicy` is an allowlist. Extension _and_ claimed MIME type
  must agree: an extension is what the uploader claims and a MIME type can be spoofed, so
  requiring both is what makes `payload.php.jpg` a rejection rather than a stored file. 10 MB
  ceiling.
- **SVG.** `Domain\Security\SvgSanitiser` is allowlist-based and strips scripts, event handlers,
  external references and embedded foreign objects. A malicious SVG is neutralised in test, not
  in principle.
- **Custom CSS and JS.** Administrators only, via `edit_themes` — `unfiltered_html` is not
  enough, because a Site Manager who can paste a `<script>` into a settings field escalates to
  Administrator the next time one views the page. Stored CSS is refused if it contains
  `expression()`, `@import`, a `javascript:` URL, a `behavior` property, `-moz-binding`, or
  anything that would close the `<style>` element.
- **Rate limiting.** Per-address, on every form submission, with a time trap and a honeypot that
  work with no captcha configured.
- **Webhooks.** Outbound payloads are HMAC-signed; retries are capped with exponential backoff.

## Release penetration checklist

Work through this on a staging copy with real content before each release. Everything here needs
a person; a build cannot do it.

### Authentication and authorisation

- [ ] Log in as each of the four roles. Confirm every admin screen and REST route the role
      should not reach returns 403, not an empty page.
- [ ] As a Counsellor, request another counsellor's lead by ID directly through the REST route.
      Confirm 403.
- [ ] As a Content Editor, attempt to change a theme setting through the REST route. Confirm 403.
- [ ] Confirm a logged-out request to every privileged route returns 401.

### Injection

- [ ] Submit `' OR 1=1 --` into every lead-form field, the finder search box and the CSV import.
      Confirm no query error and no altered result set.
- [ ] Submit `<script>alert(1)</script>` and `"><img src=x onerror=alert(1)>` into every field
      that is later displayed in the admin. Confirm they render as text in the lead inbox.
- [ ] Import a CSV whose first cell begins with `=`, `+`, `-` or `@`. Confirm the export escapes
      it so a spreadsheet does not execute it.

### Uploads

- [ ] Upload the malicious SVG in `plugins/edulume-core/tests` fixtures through the media
      library. Confirm the stored file contains no `<script>` and no event handler.
- [ ] Rename a PHP file to `.jpg` and upload it. Confirm rejection on the MIME check.
- [ ] Upload a 15 MB PDF. Confirm rejection with the size message.
- [ ] Request an uploaded file's directory listing. Confirm no index.

### CSRF and session

- [ ] Replay every admin POST from a different origin with a stale nonce. Confirm rejection.
- [ ] Confirm the lead form's nonce is per-session and that a submission with none is rejected.

### Data protection

- [ ] Run an erasure request for a lead with an uploaded file. Confirm both the row and the file
      are gone.
- [ ] Confirm the retention period actually deletes; check the scheduled event exists.
- [ ] Confirm consent categories gate every tracking script: with analytics consent withheld, no
      analytics request leaves the page.

### Configuration

- [ ] Confirm `WP_DEBUG_DISPLAY` off produces no stack traces on a forced error.
- [ ] Confirm the plugin's uninstall routine removes its options and tables only when the
      "delete data on uninstall" setting is on.
- [ ] Confirm no secret — licence key, webhook secret, CRM token — is printed in any REST
      response or admin page source.
