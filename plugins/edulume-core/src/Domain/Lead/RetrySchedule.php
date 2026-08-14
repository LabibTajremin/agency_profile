<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

/**
 * How long to wait before trying a failed delivery again.
 *
 * Exponential, capped, and finite. A webhook that retries forever against a receiver that is
 * gone becomes a cron job nobody remembers scheduling.
 */
final class RetrySchedule
{
    public const MAXIMUM_ATTEMPTS = 5;

    private const BASE_DELAY_SECONDS = 60;
    private const MAXIMUM_DELAY_SECONDS = 3600;

    public function shouldRetryAfter(int $attempt): bool
    {
        return $attempt < self::MAXIMUM_ATTEMPTS;
    }

    public function delayAfter(int $attempt): int
    {
        if (!$this->shouldRetryAfter($attempt)) {
            return 0;
        }

        return min(self::MAXIMUM_DELAY_SECONDS, self::BASE_DELAY_SECONDS * (2 ** max(0, $attempt - 1)));
    }
}
