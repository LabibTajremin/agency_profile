<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Demo;

/**
 * What to do when the site already has content.
 *
 * There is deliberately no silent default. Overwriting someone's real courses because they
 * clicked a demo thumbnail is unrecoverable, and "we warned you in the changelog" is not a
 * recovery path.
 */
enum ExistingContentChoice: string
{
    /** Keep what is there and add the demo alongside it. */
    case Merge = 'merge';

    /** Remove previously imported demo content first, then import. Never touches authored content. */
    case Fresh = 'fresh';

    public function label(): string
    {
        return match ($this) {
            self::Merge => 'Add alongside my content',
            self::Fresh => 'Replace the previous demo',
        };
    }

    /**
     * Whether this choice removes anything.
     *
     * Only ever previously imported demo items — those carry an import marker. Authored content
     * has none and is never in scope for removal.
     */
    public function removesPreviousDemo(): bool
    {
        return $this === self::Fresh;
    }
}
