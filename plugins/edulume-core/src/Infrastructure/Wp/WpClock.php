<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Wp;

use Edulume\Core\Application\Port\Clock;

/**
 * The site's clock.
 *
 * `now()` is UTC in MySQL's format, because that is what goes into the lead tables and mixing
 * site-local and UTC timestamps in one column is a class of bug that only shows up when someone
 * changes the site's time zone months later.
 */
final class WpClock implements Clock
{
    public function now(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    public function timestamp(): int
    {
        return time();
    }
}
