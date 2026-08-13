<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * How the font picker is grouped, and what a family is broadly for.
 */
enum FontCategory: string
{
    case SansSerif = 'sans-serif';
    case Serif = 'serif';
    case Slab = 'slab';
    case Display = 'display';
    case Monospace = 'monospace';

    /**
     * The generic family appended to every CSS stack, so text still renders if the
     * self-hosted file never arrives.
     */
    public function cssFallback(): string
    {
        return match ($this) {
            self::SansSerif, self::Display => 'sans-serif',
            self::Serif, self::Slab => 'serif',
            self::Monospace => 'monospace',
        };
    }
}
