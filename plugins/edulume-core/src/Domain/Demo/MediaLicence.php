<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Demo;

/**
 * The licence the demo's photography ships under.
 *
 * Recorded per demo rather than assumed, because "we'll swap those images before launch" is a
 * sentence that has shipped a great many themes with unlicensed stock in them.
 */
enum MediaLicence: string
{
    case Cc0 = 'cc0';
    case RedistributableLicence = 'redistributable';

    /** Licensed for the demo but not for redistribution: imports placeholders instead. */
    case PlaceholderOnly = 'placeholder-only';

    public function isRedistributable(): bool
    {
        return $this !== self::PlaceholderOnly;
    }

    public function label(): string
    {
        return match ($this) {
            self::Cc0 => 'CC0 / public domain',
            self::RedistributableLicence => 'Licensed for redistribution',
            self::PlaceholderOnly => 'Placeholders on import',
        };
    }
}
