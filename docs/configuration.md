# Configuration

## The settings aggregate

`ThemeSettings` is the whole theme configuration as one object: the accent (curated slug or a
custom hex), the light/dark preference, and four sub-aggregates — `TypographySettings`,
`PatternSettings`, `MotionSettings` and `LayoutSettings`. Each knows how to serialise itself,
so no single class knows the shape of everything.

## Every path coerces, none throws

`ThemeSettings::fromArray()` never throws. The blob it is handed may come from the REST API,
from a JSON file a client exported eighteen months ago, or from a row written by a version
that did not have half these keys.

`Domain\Support\Guard` is the one place untrusted input is normalised — strings, ints,
floats, bools, enums, enum lists and maps. Corrupt any key in a stored blob and the site loads
with a valid default for that key and nothing else disturbed. A site owner who pasted a broken
export deserves their defaults back, not a stack trace.

One case worth knowing: a backed enum's `tryFrom()` **throws** rather than returning null when
handed the wrong scalar type, so `Guard::toEnum()` checks the backing type first.

## Schema versioning and migrations

`ThemeSettings::CURRENT_SCHEMA_VERSION` is stamped into every blob written.

`SettingsMigrator` brings a stored blob forward one version at a time, running on the raw
array before `fromArray()` sees it, so an old key is translated rather than silently coerced
away. Each step is small and independent: a future v3 adds one method and one match arm.

The v1 → v2 step is the shape of all of them:

- v1 stored a single pattern `opacity`. v2 splits it per mode, lifting the dark value because
  the same opacity that whispers on white is invisible on near-black.
- v1 stored `patternRotation` at the top level. v2 moves it inside the pattern block.

Nothing else in the blob is touched, and a blob already at the current version is returned
unchanged.
