<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Lead;

use Edulume\Core\Application\Port\RateLimiter;

/**
 * Rate limiting on transients.
 *
 * Transients rather than a custom table because the data is worthless after its window: an
 * object cache evicting it early costs one extra allowed submission, while a table costs a row
 * per address forever and a cleanup job nobody writes.
 *
 * The key is hashed, so a rate-limit entry never stores a visitor's IP address in plain form —
 * it is personal data under GDPR and it has no business sitting in the options table.
 */
final class TransientRateLimiter implements RateLimiter
{
    private const PREFIX = 'edulume_rl_';

    public function __construct(
        private readonly int $maximumAttempts = 5,
        private readonly int $windowSeconds = 600,
    ) {
    }

    public function isAllowed(string $key): bool
    {
        return $this->countFor($key) < $this->maximumAttempts;
    }

    public function record(string $key): void
    {
        $transient = $this->transientKey($key);
        $count = $this->countFor($key);

        // The window starts at the first attempt and is not extended by later ones: a sliding
        // window that resets on every attempt can be held open indefinitely by a script, which
        // is the opposite of what a rate limit is for.
        set_transient($transient, $count + 1, $this->windowSeconds);
    }

    private function countFor(string $key): int
    {
        $stored = get_transient($this->transientKey($key));

        return is_numeric($stored) ? (int) $stored : 0;
    }

    private function transientKey(string $key): string
    {
        return self::PREFIX . substr(hash('sha256', $key), 0, 32);
    }
}
