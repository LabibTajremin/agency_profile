<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Security;

/**
 * When a client is locked out, and for how long.
 *
 * Progressive rather than flat, because a flat window is a rate limit and not a deterrent: an
 * attacker who can afford to wait fifteen minutes can afford to wait fifteen minutes a thousand
 * times. Doubling per offence makes the tenth attempt cost days while the first mistake by a
 * real person still costs a quarter of an hour.
 *
 * Capped, because past a day a lockout is a ban, and a ban is a decision a person should make.
 */
final class LockoutPolicy
{
    public function __construct(private readonly ShieldSettings $settings)
    {
    }

    public function isLockedOut(int $failures, int $offences = 0): bool
    {
        if (!$this->settings->limitAttempts) {
            return false;
        }

        return $failures >= $this->settings->maxAttempts && $this->durationMinutes($offences) > 0;
    }

    /**
     * How long the next lockout lasts, in minutes.
     *
     * `$offences` counts lockouts already served by this client, so the first is the configured
     * window and each repeat doubles it.
     */
    public function durationMinutes(int $offences = 0): int
    {
        $base = $this->settings->lockoutMinutes;

        if (!$this->settings->progressiveLockout || $offences < 1) {
            return $base;
        }

        /*
         * Doubling is computed by halving the cap down instead of multiplying the base up. A
         * site configured with a long window and a client with thirty offences overflows an int
         * long before the `min()` would have clamped it, and an overflowed int is negative,
         * which reads as "not locked out at all".
         */
        $doublings = min($offences, 40);
        $ceiling = ShieldSettings::MAXIMUM_LOCKOUT_MINUTES;

        for ($i = 0; $i < $doublings; $i++) {
            if ($base >= $ceiling) {
                return $ceiling;
            }

            $base *= 2;
        }

        return min($base, $ceiling);
    }

    /**
     * How many failures remain before the next attempt locks the client out.
     */
    public function remainingAttempts(int $failures): int
    {
        if (!$this->settings->limitAttempts) {
            return $this->settings->maxAttempts;
        }

        return max(0, $this->settings->maxAttempts - $failures);
    }
}
