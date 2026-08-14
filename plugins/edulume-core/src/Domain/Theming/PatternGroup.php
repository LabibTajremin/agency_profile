<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * How the pattern picker is grouped in the admin. Groups exist so that twenty-eight tiles
 * can be scanned in a few seconds rather than scrolled through.
 */
enum PatternGroup: string
{
    case Dots = 'dots';
    case Lines = 'lines';
    case Geometric = 'geometric';
    case Waves = 'waves';
    case Texture = 'texture';
    case Editorial = 'editorial';
    case Travel = 'travel';

    public function label(): string
    {
        return match ($this) {
            self::Dots => 'Dots',
            self::Lines => 'Lines',
            self::Geometric => 'Geometric',
            self::Waves => 'Waves',
            self::Texture => 'Texture',
            self::Editorial => 'Editorial',
            self::Travel => 'Travel',
        };
    }
}
