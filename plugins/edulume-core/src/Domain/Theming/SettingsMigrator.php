<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use Edulume\Core\Domain\Support\Guard;

/**
 * Brings a stored settings blob forward to the current schema, one version at a time.
 *
 * Migrations run on the raw array, before `ThemeSettings::fromArray()` sees it, so a v1 key
 * is translated rather than silently coerced away. Each step is small and independent; a
 * future v3 adds one more method and one more case, and nothing else moves.
 */
final class SettingsMigrator
{
    private const FIRST_SCHEMA_VERSION = 1;

    /**
     * How much heavier a pattern needs to be in dark mode to read the same. v1 stored a single
     * opacity, so the dark value has to be derived rather than read.
     */
    private const DARK_MODE_OPACITY_LIFT = 1.6;

    /**
     * @param array<array-key, mixed> $stored
     *
     * @return array<array-key, mixed>
     */
    public function migrate(array $stored): array
    {
        $version = Guard::toInt(
            $stored['schemaVersion'] ?? self::FIRST_SCHEMA_VERSION,
            self::FIRST_SCHEMA_VERSION,
            self::FIRST_SCHEMA_VERSION,
            ThemeSettings::CURRENT_SCHEMA_VERSION,
        );

        while ($version < ThemeSettings::CURRENT_SCHEMA_VERSION) {
            $stored = match ($version) {
                1 => $this->migrateVersionOneToTwo($stored),
                default => $stored,
            };

            $version++;
        }

        $stored['schemaVersion'] = ThemeSettings::CURRENT_SCHEMA_VERSION;

        return $stored;
    }

    public function needsMigration(mixed $stored): bool
    {
        $version = Guard::toInt(Guard::toArray($stored)['schemaVersion'] ?? null, self::FIRST_SCHEMA_VERSION);

        return $version < ThemeSettings::CURRENT_SCHEMA_VERSION;
    }

    /**
     * v1 carried one pattern opacity for both modes and one `patternRotation` at the top
     * level. v2 splits the opacity per mode and moves rotation inside the pattern block.
     *
     * @param array<array-key, mixed> $stored
     *
     * @return array<array-key, mixed>
     */
    private function migrateVersionOneToTwo(array $stored): array
    {
        $pattern = Guard::toArray($stored['pattern'] ?? null);

        if (array_key_exists('opacity', $pattern)) {
            $opacity = Guard::toFloat($pattern['opacity'], 0.06, 0.0, 1.0);

            $pattern['lightModeOpacity'] = $opacity;
            $pattern['darkModeOpacity'] = min(1.0, $opacity * self::DARK_MODE_OPACITY_LIFT);

            unset($pattern['opacity']);
        }

        if (array_key_exists('patternRotation', $stored)) {
            $pattern['rotation'] = Guard::toInt($stored['patternRotation']);

            unset($stored['patternRotation']);
        }

        if ($pattern !== []) {
            $stored['pattern'] = $pattern;
        }

        return $stored;
    }
}
