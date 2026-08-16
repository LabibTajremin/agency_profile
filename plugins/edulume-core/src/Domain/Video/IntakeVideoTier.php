<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Video;

/**
 * Where a destination's intake video came from.
 *
 * Surfaced in the admin's destination list so the owner can see coverage at a glance. "Every
 * country has a video" is only reassuring if you can also tell which countries are relying on
 * the fallback — otherwise the guarantee hides the work still to do.
 */
enum IntakeVideoTier: string
{
    case Own = 'own';
    case Global = 'global';
    case Demo = 'demo';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Own => 'Own video',
            self::Global => 'Site-wide video',
            self::Demo => 'Sample video',
            self::None => 'None',
        };
    }

    public function isFallback(): bool
    {
        return $this === self::Global || $this === self::Demo;
    }
}
