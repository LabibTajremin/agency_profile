<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * The site's default appearance. "Auto" follows the visitor's operating system and resolves
 * to a concrete ThemeMode before first paint.
 */
enum ThemePreference: string
{
    case Light = 'light';
    case Dark = 'dark';
    case Auto = 'auto';

    public function resolve(ThemeMode $systemMode): ThemeMode
    {
        return match ($this) {
            self::Light => ThemeMode::Light,
            self::Dark => ThemeMode::Dark,
            self::Auto => $systemMode,
        };
    }
}
