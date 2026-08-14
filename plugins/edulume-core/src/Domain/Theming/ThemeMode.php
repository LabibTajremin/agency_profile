<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * The two rendering modes every visual decision is resolved against.
 *
 * "Auto" is a visitor preference, not a mode: it resolves to one of these before paint.
 */
enum ThemeMode: string
{
    case Light = 'light';
    case Dark = 'dark';
}
