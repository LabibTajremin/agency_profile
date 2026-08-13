<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Fake;

use Edulume\Core\Application\Port\Clock;

/** A clock that never moves, so "when did this happen" is a fact the test states. */
final class FixedClock implements Clock
{
    public function __construct(
        private readonly string $now,
        private readonly int $timestamp,
    ) {
    }

    public function now(): string
    {
        return $this->now;
    }

    public function timestamp(): int
    {
        return $this->timestamp;
    }
}
