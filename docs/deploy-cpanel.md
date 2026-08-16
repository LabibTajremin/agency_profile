# Deploying Edulume on cPanel shared hosting

Written against a Dianahost cPanel account running `sparkpath.com.bd`, but nothing here is
host-specific — any cPanel host with PHP 8.1+ works the same way.

## Before you start

### Getting into the account

1. Sign in at `https://clients.dianahost.com.bd/clientarea.php`
2. **Services → My Services →** click the active hosting product
3. **Login to cPanel** — the green button on the service page

From there you have File Manager, MySQL Databases, phpMyAdmin, MultiPHP, SSL/TLS Status and
Cron Jobs. Terminal is available on some plans and not others.

### PHP

**cPanel → MultiPHP Manager →** set the domain to **PHP 8.2**. Edulume requires **8.1 or
newer**; on 8.0 or below the plugin will not activate and no amount of re-uploading changes
that.

**cPanel → Select PHP Version → Extensions** — confirm: `mysqli`, `curl`, `gd` or `imagick`,
`mbstring`, `zip`, `intl`, `json`, `dom`, `openssl`, `exif`.

### PHP limits

**cPanel → MultiPHP INI Editor → Editor mode:**

```ini
memory_limit = 256M
max_execution_time = 180
max_input_vars = 5000
upload_max_filesize = 64M
post_max_size = 64M
```

`max_input_vars` is the one that bites. The Home sections screen posts two fields per section
and the video repeater posts five per row; the default of 1000 silently truncates a large save,
which presents as "my settings won't stick" and looks nothing like a limits problem.

### Do the whole thing on staging first

Create `staging.sparkpath.com.bd` as a subdomain in cPanel, install and verify there, then
repeat on the live domain. Debugging on a client's live URL is a choice you get to make once.

## Do I need to run a migration?

**No.** The plugin migrates itself, and it does so on two separate triggers so that no
deployment route can miss it:

1. **On activation** — `register_activation_hook` creates the lead tables, grants capabilities
   to the administrator role, and flushes rewrite rules.
2. **On upgrade** — every admin page load compares the version recorded in the database against
   the version in the files. When they differ, the same routine runs again.

The second trigger is the one that matters for how you will actually deploy. Activation hooks
fire once, on activation, and never again — so overwriting the plugin folder by FTP or File
Manager, which is the normal way to update on shared hosting, would otherwise leave new code
running against an old schema. The version check closes that: upload the files, open wp-admin,
and the database is brought up to date on the first page you load.

It is safe to repeat. `dbDelta()` is declarative, and capabilities and options are set to a
known value rather than appended to.

**You never need to run SQL by hand, and there is no migration command to remember.**

If you ever want to force it, deactivating and reactivating the plugin runs the same routine.

## Step 1 — Upload and activate the plugin

The plugin is the part that matters. Without it there are no post types, no design tokens, no
admin screens and no demo importer — the theme renders, but there is nothing to render.

**Via wp-admin (easiest):**

1. **Plugins → Add New → Upload Plugin**
2. Choose `edulume-core.zip`
3. **Install Now**, then **Activate**

**Via cPanel File Manager**, if the upload limit blocks the zip:

1. **File Manager → `public_html/wp-content/plugins`**
2. **Upload** `edulume-core.zip`, then right-click it → **Extract**
3. Delete the zip
4. **Plugins** in wp-admin → **Activate** Edulume Core

If the upload fails on size, raise `upload_max_filesize` and `post_max_size` in
**cPanel → MultiPHP INI Editor**. The plugin zip is around 250 KB, so this is rarely needed.

## Step 2 — Upload and activate the theme

1. **Appearance → Themes → Add New → Upload Theme**
2. Choose `edulume-theme.zip` → **Install Now** → **Activate**

## Step 3 — The child theme (optional)

**You do not need it.** `edulume-child.zip` contains a stylesheet and a `functions.php` and no
templates, so activating it changes nothing you can see.

Install it when you start customising. Anything you put in a child theme survives a parent
update; anything you edit in the parent is overwritten by one. If you do activate the child,
leave the parent installed — WordPress needs the parent present to render.

## Step 4 — Import the demo content

This creates 174 items across all 16 content types, so every section of the site has something
in it.

**From wp-admin:** **Edulume → Demos**, choose **Boutique practice**, import.

**From WP-CLI**, if your host provides terminal access:

```bash
cd ~/public_html
wp plugin activate edulume-core
wp theme activate edulume-theme
wp edulume demo import boutique --mode=full
```

Options worth knowing:

| Flag         | Values                                  | Meaning                                                          |
| ------------ | --------------------------------------- | ---------------------------------------------------------------- |
| `--mode`     | `full`, `content-only`, `settings-only` | `full` imports content **and** applies the demo's theme settings |
| `--existing` | `merge`, `fresh`                        | `merge` is the default and never deletes anything                |

Every imported row is tagged with `_edulume_demo`, so removing the demo later removes exactly
what it created and nothing that merely looks similar.

## Step 5 — Permalinks

**Settings → Permalinks → Post name → Save Changes.**

Do this once after activating. Custom post types register their own URL structures, and shared
hosting frequently serves a stale `.htaccess` until the rules are flushed. Skipping this is the
usual cause of "the homepage works but every course URL is a 404".

## Step 6 — Set the homepage

**Settings → Reading → Your homepage displays → A static page**, and pick the page you want.

## Changing the admin username and password

**Password:** **Users → Profile → Set New Password**. Standard WordPress, nothing
Edulume-specific.

**Username:** WordPress does not allow it, by design, and no plugin changes that safely. The
supported route is:

1. **Users → Add New**, create a new Administrator with the username you want and a different
   email address
2. Log out, log in as the new user
3. **Users**, delete the old account, and when prompted **attribute its content to the new user**

Step 3 matters — deleting without attributing discards the old user's posts.

Edulume's setup wizard separately offers to create an **additional** user with one of its own
roles (Site Manager, Content Editor, Counsellor), with a password strength meter and generator.
That is user creation, not credential editing.

## Step 7 — The production block in wp-config.php

Insert **above** the `/* That's all, stop editing! */` line:

```php
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'DISALLOW_FILE_EDIT', true );
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );
define( 'FORCE_SSL_ADMIN', true );
define( 'WP_POST_REVISIONS', 5 );
define( 'EMPTY_TRASH_DAYS', 14 );
define( 'AUTOSAVE_INTERVAL', 120 );
define( 'DISABLE_WP_CRON', true );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );

// Login shield rescue hatch — uncomment ONLY if you are locked out.
// define( 'EDULUME_SHIELD_DISABLE', true );
```

`DISALLOW_FILE_MODS` is deliberately **not** set: it would block plugin and theme updates from
wp-admin, which is how the site owner is expected to update this product.

Then set permissions: directories `755`, files `644`, and `wp-config.php` to **`600`**.

`DISABLE_WP_CRON` above turns off WordPress's own pseudo-cron, so it has to be replaced —
**cPanel → Cron Jobs**, every five minutes:

```
*/5 * * * * cd /home/CPUSER/public_html && /usr/local/bin/php -q wp-cron.php > /dev/null 2>&1
```

Confirm the binary path in **Select PHP Version**; on CloudLinux it is often
`/opt/alt/php82/usr/bin/php`. Without this, scheduled posts and the enquiry retry queue stop.

## Step 8 — HTTPS

**cPanel → SSL/TLS Status →** select the domain → **Run AutoSSL**, and wait for issue.

Then **Settings → General** in wp-admin, and set both **WordPress Address** and **Site Address**
to `https://sparkpath.com.bd`.

Force it at the top of `public_html/.htaccess`, **above** the `# BEGIN WordPress` block:

```apache
RewriteEngine On
RewriteCond %{HTTPS} !=on
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## Step 9 — Caching

Install **LiteSpeed Cache**. It is the right choice here specifically because the server is
LiteSpeed: LSCache is server-level, and no PHP-based cache can match it on this stack.

- **Cache** → enable, TTL 604800
- **Cache → Excludes** → add your login-shield path
- **Page Optimization** → CSS/JS minify **on**, combine **off**. Combining breaks more than it
  fixes over HTTP/2.
- **Page Optimization → Lazy Load images: OFF.** The theme already sets `loading="lazy"` on
  every image below the fold and `fetchpriority="high"` on the one above it. Two lazy-load
  systems fighting is a real bug and a confusing one.
- **Media → WebP replacement: on**
- **Object Cache** → only if the plan exposes Redis or Memcached. Check; do not assume.
- Purge all after every settings change.

## Step 10 — Hardening

Append to `public_html/.htaccess`:

```apache
<Files wp-config.php>
  Require all denied
</Files>
<Files xmlrpc.php>
  Require all denied
</Files>
Options -Indexes
```

And create `wp-content/uploads/.htaccess`:

```apache
<FilesMatch "\.(php|php\d|phtml|phar)$">
  Require all denied
</FilesMatch>
```

That last one matters more than it looks: an upload directory that will execute PHP turns any
file-upload bug anywhere on the site into remote code execution.

Now move the sign-in page — Step 11, and not before.

## Step 11 — Moving the sign-in page

**Edulume → Safety** carries the login shield. It is **off** when the plugin is installed, and
it belongs here, second to last, because everything above it has to be working before you move
the door.

What it does when you switch it on:

- `wp-login.php` and `/wp-admin` return a genuine **404** to anyone not signed in — not a
  redirect, because a redirect tells whoever is knocking that the door exists
- Sign-in moves to a path you choose, `secure-access` by default
- Repeated failures are locked out, and each repeat offence doubles the wait, capped at a day
- Every failed sign-in gets the same message, so guessing tells nobody which half they got right
- A hidden field catches automated attempts
- XML-RPC is switched off

`admin-ajax.php`, `wp-cron.php`, the REST API, `robots.txt` and the sitemaps are **never**
blocked, whatever the settings say. Blocking `admin-ajax.php` is the classic version of this
mistake and it takes the front end down with it. On a multisite network the module does not run
at all.

### Before you press save

1. Read the address on the page and tick the box confirming you have saved it. The form will not
   submit without that when you are switching the shield on or moving the path.
2. Check the email. The new address is sent to the site's admin address the moment it changes.
3. **Open the new address in a private window and sign in there — before you close the session
   you are already in.** This is the step that separates a five-second fix from a support
   ticket.

### If you are locked out

Open `wp-config.php` through **cPanel → File Manager**, and add this line above the
`/* That's all, stop editing! */` comment:

```php
define( 'EDULUME_SHIELD_DISABLE', true );
```

The shield stops entirely — `wp-login.php` works again immediately. Sign in, fix the setting,
then remove the line.

## Step 12 — Email

PHP's `mail()` on shared hosting lands in spam often enough that you should assume it will,
which silently breaks every enquiry the site receives — the form says "thank you" and nobody is
ever told.

Install **WP Mail SMTP** and authenticate against a real mailbox: either a cPanel account
(`mail.sparkpath.com.bd`, port 465, SSL) or an external provider. Then send a test to a Gmail
address and confirm it reached the **inbox**, not merely that it was sent.

## Step 13 — Backups

- **cPanel → Backup Wizard** → download a full backup immediately after launch.
- If the plan includes **JetBackup**, confirm the schedule is actually running.
- Install UpdraftPlus with an off-server destination — daily files, daily database. Host-level
  backups alone are not a backup strategy; they live on the machine you are backing up.

## If Imunify360 or ModSecurity gets in the way

Symptoms: random 403s on `admin-ajax.php`, settings saves that do nothing, a blocked upload.
On a shared host this is usually a false positive.

**cPanel → ModSecurity** → disable for the domain temporarily to confirm the cause, then open a
ticket at `clients.dianahost.com.bd` asking them to whitelist the specific rule ID, which
appears in the Imunify360 log. Re-enable it once whitelisted. Leaving it off is not a fix.

## Launch checklist

- [ ] `https://sparkpath.com.bd` loads over HTTPS, padlock valid, no mixed-content warnings
- [ ] Every front-page section renders and is populated
- [ ] The theme toggle works and survives a reload
- [ ] Video rail: no iframe until scrolled to, muted autoplay fires, unmute works
- [ ] Every destination page shows an intake video
- [ ] Founders page renders both founders, photos, timelines and counters
- [ ] Login shield: `wp-login.php` 404s and the custom path works — **checked in a private
      window before closing your session**
- [ ] Enquiry form submits and the email reaches an inbox, not spam
- [ ] Permalinks work on destinations, universities and team
- [ ] The 404 page and the search results page are both styled
- [ ] 320px and 375px: no horizontal scrolling anywhere
- [ ] Lighthouse mobile performance ≥ 85 on the live URL
- [ ] `robots.txt` and the XML sitemap are reachable; Search Console verified
- [ ] A backup has been taken and downloaded off-server
- [ ] Credentials handed over securely — **not by email and not over WhatsApp**

## Updating later

1. **Plugins → Installed Plugins**, deactivate Edulume Core — or don't; the version check
   handles it either way
2. Upload the new `edulume-core.zip` over the old one, or extract it over the folder
3. Open any wp-admin page

The version check runs the migration on that first page load. Nothing else is required.

## If something goes wrong

**White screen after activating the plugin.** Almost always PHP version. Check
**Select PHP Version** is 8.1+. To recover, rename `wp-content/plugins/edulume-core` in File
Manager — WordPress deactivates a plugin whose folder has vanished and wp-admin comes back.

**Course and destination URLs 404.** Re-save permalinks (Step 5).

**"Imported 0 items."** You are running a build from before this was fixed — the importer
resolved its payload path one directory too high and silently found nothing. Upload the current
`edulume-core.zip`.

**Sections still look empty after importing.** Check the plugin is _activated_, not merely
installed. The theme alone registers no content types.

**Styling looks unstyled or colours are wrong.** The compiled stylesheet is written to
`wp-content/uploads/edulume/`. Confirm that directory is writable — cPanel occasionally sets
`uploads` to 755 with the wrong owner after a migration.

## About the demo imagery

The import brings 69 images into the media library and sets them as featured images, so cards,
archives and the logo wall are illustrated rather than blank.

They are **generated vector artwork**, not photography: layered gradients in the site's own
palette with a subject-appropriate motif — skylines for destinations, monograms for team members
and universities, figure groups for event scenes. That is a deliberate choice rather than a
shortcut. Nothing available to bundle here is licensed for redistribution, and shipping
unlicensed stock is exactly what the licence check in the importer exists to prevent.

Replace them with your own photography when you have it. Everything the import creates is
tagged, so removing the demo takes its images with it — including the files on disk, not just
the library records.

## What is not included

**The other three demo packs.** `gulf-premium`, `test-prep` and `guide-site` are declared in the
library but carry no content files yet. Import `boutique`; the others create nothing.
