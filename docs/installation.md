# Installation

## What you need

- WordPress 6.5 or newer
- PHP 8.1 or newer
- MySQL 5.7 / MariaDB 10.4 or newer

Shared hosting is fine. Nothing here needs a VPS, a cron daemon you control, or a paid API.

## Install

1. Upload **Edulume Core** (`edulume-core.zip`) under **Plugins → Add New → Upload Plugin** and
   activate it.
2. Upload the **Edulume** theme (`edulume-theme.zip`) under **Appearance → Themes → Add New →
   Upload Theme** and activate it.

Install the plugin first. The theme reads tokens the plugin compiles; activating it alone gives
you a plain but working site and an admin notice explaining why.

If you plan to edit templates, also upload **Edulume Child** (`edulume-child.zip`) and activate
that instead of the parent. See [the child-theme guide](child-theme.md).

## First run

Activating the plugin creates:

- Sixteen content types and eleven taxonomies
- Four roles — Site Manager, Content Editor, Counsellor, Branch Manager
- The lead tables
- A compiled stylesheet in `wp-content/uploads/edulume/`

Then the [setup wizard](setup-wizard.md) opens. It is entirely optional and never blocks
`wp-admin`; you can leave it and come back.

## Permalinks

Set **Settings → Permalinks** to **Post name**. The content types register their own bases
(`/courses/`, `/destinations/`, …) and those only take effect once permalinks are not plain.
The wizard's Essentials step does this for you.

## Upgrading

With a licence key entered, updates arrive through **Dashboard → Updates** like any other
plugin. Without one, download the new ZIP and upload it over the old version — nothing is
disabled, only the automatic delivery.

A snapshot of your settings is taken automatically before every update, so
**Edulume → Safety → Snapshots** can put them back.

## Uninstalling

Deactivating changes nothing. Deleting the plugin removes its options and lead tables **only if**
you switch on **Edulume → Advanced → Delete data on uninstall** first. Your posts are ordinary
WordPress posts and are never removed.
