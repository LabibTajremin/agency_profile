<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Content;

use Edulume\Core\Application\Port\ExecutionBudget;

/**
 * A budget that stops after a fixed number of items, or when the request runs short of time,
 * whichever comes first.
 *
 * The item cap is what makes a progress bar mean anything: a request that imports "as much as
 * it can" reports a different fraction every time and cannot be paced. The wrapped time budget
 * is still consulted because ten items is quick on a decent host and not always quick on a
 * shared one, and running out of wall clock mid-write is the failure this whole cursor
 * machinery exists to avoid.
 */
final class BatchBudget implements ExecutionBudget
{
    private int $consumed = 0;

    public function __construct(
        private readonly ExecutionBudget $within,
        private readonly int $items = 10,
    ) {
    }

    public function hasTimeRemaining(): bool
    {
        return $this->consumed < max(1, $this->items) && $this->within->hasTimeRemaining();
    }

    public function consume(): void
    {
        $this->consumed++;
        $this->within->consume();
    }
}
