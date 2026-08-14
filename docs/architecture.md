# Architecture

Two packages, four layers, one direction of dependency.

## Packages

| Package         | Path                    | Responsibility                                                           |
| --------------- | ----------------------- | ------------------------------------------------------------------------ |
| `edulume-core`  | `plugins/edulume-core/` | Owns all data, logic, settings, leads and REST. Survives a theme switch. |
| `edulume-theme` | `themes/edulume-theme/` | Presentation only. Zero database writes.                                 |

The theme talks to the plugin only through documented PHP functions, hooks and REST. It never
reads a plugin option key directly.

## Layers inside the plugin

| Layer            | Path                  | Rule                                                                              |
| ---------------- | --------------------- | --------------------------------------------------------------------------------- |
| `Domain`         | `src/Domain/`         | Entities, value objects, pure business rules. No `wp_*` calls, no I/O.            |
| `Application`    | `src/Application/`    | Use cases orchestrating the domain. Depends on `Domain` and port interfaces only. |
| `Infrastructure` | `src/Infrastructure/` | WordPress hooks, repositories, database, REST controllers, file writing.          |
| `Admin`          | `src/Admin/`          | Settings app bootstrap, REST schema, admin screens.                               |

Dependencies point inward only. Data access happens through repository interfaces declared in
`src/Application/Port/`, implemented in `src/Infrastructure/`.

## Why

The domain layer carries the parts of this product that are genuinely hard — colour maths,
contrast compliance, palette generation, token compilation, lead pipeline rules. Keeping it free
of WordPress is what makes those parts testable in milliseconds and provable at 100% coverage.

## The plugin bootstrap

`plugins/edulume-core/edulume-core.php` holds a plugin header and one call. Everything that
happens, happens in `Infrastructure\Wp\Plugin`, so the entry file stays something a
maintainer reads in ten seconds and the behaviour stays something they can test.

`Infrastructure\Wp\Container` is an explicit list of factories rather than a reflection-driven
auto-wiring container. With this few services, explicit factories are shorter to read,
impossible to misconfigure at runtime, and show the whole dependency graph on one screen.

## Lifecycle

| Step         | What it does                              | What it deliberately does not do                                                                                                                                |
| ------------ | ----------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Activation   | Grants capabilities, records the version  | Write default settings — absent settings already load as defaults, and writing them would overwrite a configuration that survived a deactivate/reactivate cycle |
| Deactivation | Flushes rewrite rules                     | Delete anything; a deactivation is usually a diagnosis step                                                                                                     |
| Uninstall    | Removes options and published stylesheets | Anything at all, unless the site owner switched on "delete data on uninstall" first                                                                             |

## Where settings live

`OptionSettingsRepository` stores the settings blob and the section overrides in two
non-autoloaded options. Migration happens **on read**, not on upgrade: a site can be restored
from a database dump older than the plugin, or have its plugin folder replaced over FTP,
without any upgrade hook ever firing.

## Where the stylesheet lives

`UploadsStylesheetWriter` writes into `wp-content/uploads/edulume/` — not the plugin directory,
which is often read-only on managed hosting and is replaced wholesale on update. The filename
carries the content hash, so an unchanged stylesheet is never rewritten and its URL can be
cached indefinitely. Compilation happens on save; rendering only enqueues a stored URL.
