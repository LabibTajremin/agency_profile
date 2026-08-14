<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Tracking;

/**
 * The cookie categories a visitor decides about.
 *
 * Necessary is always on and is the only one that is: everything else starts off and stays off
 * until the visitor says otherwise. A banner whose toggles do not actually gate anything is
 * worse than no banner, because it documents an intention the site then ignores.
 */
enum ConsentCategory: string
{
    case Necessary = 'necessary';
    case Analytics = 'analytics';
    case Marketing = 'marketing';
    case Preferences = 'preferences';

    public function isAlwaysAllowed(): bool
    {
        return $this === self::Necessary;
    }

    public function label(): string
    {
        return match ($this) {
            self::Necessary => 'Necessary',
            self::Analytics => 'Analytics',
            self::Marketing => 'Marketing',
            self::Preferences => 'Preferences',
        };
    }

    /**
     * What agreeing to this category actually permits, in the visitor's terms.
     *
     * Written out because "we value your privacy" tells a person nothing, and a banner that
     * explains nothing is a banner that gets clicked through without a decision being made.
     */
    public function description(): string
    {
        return match ($this) {
            self::Necessary => 'Needed to run the site. Cannot be switched off.',
            self::Analytics => 'Lets us see which pages are used, in aggregate.',
            self::Marketing => 'Lets us measure adverts and show you relevant ones elsewhere.',
            self::Preferences => 'Remembers choices like your language and light or dark mode.',
        };
    }
}
