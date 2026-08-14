<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * The way back from a configuration that broke the site.
 *
 * Appending `?edulume-safe-mode=1` renders the defaults and drops any custom CSS, without
 * touching what is stored. That matters: the recovery path must not require database access,
 * because the person who needs it is usually the one who does not have any.
 */
final class SafeMode
{
    public const QUERY_PARAMETER = 'edulume-safe-mode';

    private const ENABLED_VALUES = ['1', 'true', 'yes', 'on'];

    /**
     * @param array<string, mixed> $queryParameters
     */
    public static function isRequestedBy(array $queryParameters): bool
    {
        $value = $queryParameters[self::QUERY_PARAMETER] ?? null;

        if (!is_string($value) && !is_int($value)) {
            return false;
        }

        return in_array(strtolower(trim((string) $value)), self::ENABLED_VALUES, true);
    }

    /**
     * Stored settings are left exactly where they are; only what renders changes.
     */
    public static function settingsFor(ThemeSettings $stored, bool $isActive): ThemeSettings
    {
        return $isActive ? ThemeSettings::defaults() : $stored;
    }

    public static function allowsCustomCss(bool $isActive): bool
    {
        return !$isActive;
    }
}
