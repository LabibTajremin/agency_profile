<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * How tightly the vertical rhythm is packed. Multiplies the stored spacing rather than
 * replacing it, so a site owner's own spacing choice survives a density change.
 */
enum LayoutDensity: string
{
    case Compact = 'compact';
    case Comfortable = 'comfortable';
    case Spacious = 'spacious';

    public function spacingMultiplier(): float
    {
        return match ($this) {
            self::Compact => 0.75,
            self::Comfortable => 1.0,
            self::Spacious => 1.35,
        };
    }
}
