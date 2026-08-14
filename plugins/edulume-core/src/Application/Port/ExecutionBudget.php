<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Port;

/**
 * How much of the request's execution time is left.
 *
 * Injected rather than read from `max_execution_time` directly, because an importer that
 * consults the wall clock cannot be tested: the whole point is proving it stops in time, and
 * a test that waits 30 seconds to find out is a test nobody runs.
 */
interface ExecutionBudget
{
    public function hasTimeRemaining(): bool;

    public function consume(): void;
}
