<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Fake;

use Edulume\Core\Application\Port\ExecutionBudget;

/**
 * A budget measured in rows rather than seconds, so "the request ran out of time" is a fact a
 * test can state rather than something it has to wait for.
 */
final class CountingExecutionBudget implements ExecutionBudget
{
    private int $consumed = 0;

    public function __construct(private readonly int $allowance)
    {
    }

    public function hasTimeRemaining(): bool
    {
        return $this->consumed < $this->allowance;
    }

    public function consume(): void
    {
        $this->consumed++;
    }
}
