<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Support;

/**
 * Reading and merging nested configuration.
 *
 * Two operations, both of which look trivial and are not.
 *
 * `merge()` is recursive for maps and wholesale for lists. Recursing into a list is the bug
 * this class exists to prevent: a site owner who trims their six services down to four gets
 * `array_replace_recursive` semantics back — four edited entries and the last two survivors of
 * the defaults, which reads as content reappearing after they deleted it. A list is a single
 * value the owner has authored; a map is a set of independent fields.
 *
 * `get()` walks a dot path and stops the moment the path leaves the data, so a caller can ask
 * for `hero.badges.2.label` against an empty array and get its fallback rather than a notice.
 */
final class NestedArray
{
    /**
     * Stored values laid over defaults.
     *
     * @param array<array-key, mixed> $defaults
     * @param array<array-key, mixed> $stored
     * @return array<array-key, mixed>
     */
    public static function merge(array $defaults, array $stored): array
    {
        $merged = $defaults;

        foreach ($stored as $key => $value) {
            $existing = $merged[$key] ?? null;

            $merged[$key] = is_array($value) && is_array($existing) && !self::isList($value) && !self::isList($existing)
                ? self::merge($existing, $value)
                : $value;
        }

        return $merged;
    }

    /**
     * The value at a dot path, or the fallback.
     *
     * @param array<array-key, mixed> $data
     */
    public static function get(array $data, string $path, mixed $fallback = null): mixed
    {
        $cursor = $data;

        foreach (self::segments($path) as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return $fallback;
            }

            $cursor = $cursor[$segment];
        }

        return $cursor;
    }

    /**
     * @return list<string>
     */
    private static function segments(string $path): array
    {
        return array_values(array_filter(
            explode('.', $path),
            static fn (string $segment): bool => $segment !== ''
        ));
    }

    /**
     * @param array<array-key, mixed> $value
     */
    private static function isList(array $value): bool
    {
        return $value === [] || array_is_list($value);
    }
}
