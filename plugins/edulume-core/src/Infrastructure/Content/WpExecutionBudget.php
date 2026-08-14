<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Content;

use Edulume\Core\Application\Port\ExecutionBudget;

/**
 * The real execution budget, read from PHP's limit.
 *
 * Stops well short of the limit rather than at it. Finishing the last row is not the goal —
 * having enough time left afterwards to save the cursor and render a response is, because a
 * request killed mid-write leaves an import that cannot say where it got to.
 */
final class WpExecutionBudget implements ExecutionBudget
{
    /** Seconds held back for saving the cursor and rendering the response. */
    private const RESERVE_SECONDS = 5.0;

    /** Used when `max_execution_time` is 0, which means "no limit" and usually means CLI. */
    private const ASSUMED_LIMIT_SECONDS = 30.0;

    private readonly float $startedAt;

    private readonly float $allowance;

    public function __construct(?float $limitSeconds = null)
    {
        $this->startedAt = microtime(true);

        $configured = $limitSeconds ?? (float) ini_get('max_execution_time');
        $limit = $configured > 0.0 ? $configured : self::ASSUMED_LIMIT_SECONDS;

        $this->allowance = max(1.0, $limit - self::RESERVE_SECONDS);
    }

    public function hasTimeRemaining(): bool
    {
        return (microtime(true) - $this->startedAt) < $this->allowance;
    }

    public function consume(): void
    {
        // Nothing to do: this budget measures elapsed time, so the work itself is the
        // consumption. The method exists because a row-counting budget needs it.
    }
}
