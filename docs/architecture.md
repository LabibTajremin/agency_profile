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
