<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Port;

/**
 * Per-address submission throttling.
 */
interface RateLimiter
{
    public function isAllowed(string $key): bool;

    public function record(string $key): void;
}
