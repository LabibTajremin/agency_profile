# Deploying Edulume on cPanel shared hosting

Written against a Dianahost cPanel account running `sparkpath.com.bd`, but nothing here is
host-specific — any cPanel host with PHP 8.1+ works the same way.

## Before you start

Check **cPanel → Select PHP Version**. Edulume requires **PHP 8.1 or newer**; 8.2 or 8.3 is
better. If the account is on 8.0 or below the plugin will not activate, and no amount of
re-uploading will change that.

Confirm these extensions are enabled while you are on that screen: `mbstring`, `json`, `dom`,
`zip`, `curl`. All five are on by default in most cPanel builds.

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

## What is not included

**Images.** The demo imports text, not photography — there are no bundled images, and the
importer has no media step. Every section will be structurally complete and visually plain until
you add your own images through the media library.

**The other three demo packs.** `gulf-premium`, `test-prep` and `guide-site` are declared in the
library but carry no content files yet. Import `boutique`; the others create nothing.
