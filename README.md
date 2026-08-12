# Edulume

A premium WordPress theme and companion plugin for study-abroad and overseas-education consultancies.

The product ships as two packages:

| Package         | Path                    | Responsibility                                                      |
| --------------- | ----------------------- | ------------------------------------------------------------------- |
| `edulume-core`  | `plugins/edulume-core/` | All data, logic, settings, leads and REST. Survives a theme switch. |
| `edulume-theme` | `themes/edulume-theme/` | Presentation only. Renders; never persists.                         |

## Repository layout

```
plugins/edulume-core/src/Domain/         entities, value objects, pure business rules
plugins/edulume-core/src/Application/    use cases and port interfaces
plugins/edulume-core/src/Infrastructure/ WordPress hooks, repositories, REST
plugins/edulume-core/src/Admin/          settings app bootstrap and admin screens
themes/edulume-theme/                    the theme
themes/edulume-child/                    the shipped child theme
apps/admin/                              React + TypeScript configurator source
docs/                                    architecture, theming, testing, deployment
bin/coverage-gate.php                    the offline coverage gate used by CI
```

Dependencies point inward only: `Domain` knows about nothing, `Application` knows about
`Domain`, `Infrastructure` knows about both. That is what makes the domain fully testable
without WordPress.

## Requirements

- PHP 8.1 or newer (tested through 8.3)
- WordPress 6.5 or newer
- Composer 2, Node 20

## Getting started

```bash
composer install
npm install
composer check
```

## The single quality command

```bash
composer check
```

It runs lint → static analysis → unit tests → integration tests and exits non-zero if any
step fails. The same steps run in GitHub Actions on every pull request; a red check is not
mergeable.

Coverage is enforced offline by `bin/coverage-gate.php`, which reads the Clover report and
fails below the floor for the `Domain` and `Application` layers.

## Documentation

See [`docs/`](docs/): [architecture](docs/architecture.md), [theming](docs/theming.md),
[configuration](docs/configuration.md), [testing](docs/testing.md),
[CSV import](docs/csv-import.md), [child theme](docs/child-theme.md),
[deployment](docs/deployment.md) and [handover](docs/handover.md).

Third-party assets and their licences are recorded in [`CREDITS.md`](CREDITS.md).
