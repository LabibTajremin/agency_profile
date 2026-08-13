<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Fake;

use Edulume\Core\Application\Port\RateLimiter;

/** Counts submissions per key, so the limit is a number a test can reach deliberately. */
final class WindowedRateLimiter implements RateLimiter
{
    /** @var array<string, int> */
    private array $counts = [];

    public function __construct(private readonly int $allowance)
    {
    }

    public function isAllowed(string $key): bool
    {
        return ($this->counts[$key] ?? 0) < $this->allowance;
    }

    public function record(string $key): void
    {
        $this->counts[$key] = ($this->counts[$key] ?? 0) + 1;
    }
}
