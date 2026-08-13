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
}
