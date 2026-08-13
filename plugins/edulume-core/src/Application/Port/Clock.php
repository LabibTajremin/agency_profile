<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Port;

/**
 * The current time, injected rather than read.
 *
 * A use case that calls `time()` cannot be tested deterministically, and "it only fails around
 * midnight UTC" is a bug report nobody enjoys.
 */
interface Clock
{
    public function now(): string;

    public function timestamp(): int;
}
