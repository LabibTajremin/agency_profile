<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Licence;

use DateTimeImmutable;

/**
 * A licence, as the site knows it.
 *
 * Stored locally and re-validated periodically rather than checked on every page load: a licence
 * check in the request path means the site goes down when the licence server does, which is a
 * failure mode nobody buying a theme signed up for.
 */
final class Licence
{
    /** Fourteen days after expiry, updates still arrive. Long enough to survive a holiday. */
    public const GRACE_DAYS = 14;

    public function __construct(
        public readonly string $key,
        public readonly ?DateTimeImmutable $expiresAt = null,
        public readonly int $siteLimit = 1,
        public readonly int $activationCount = 0,
        public readonly bool $isRevoked = false,
        public readonly bool $isActivatedHere = false,
        public readonly ?DateTimeImmutable $lastCheckedAt = null,
    ) {
    }

    public static function none(): self
    {
        return new self('');
    }

    public function hasKey(): bool
    {
        return trim($this->key) !== '';
    }

    public function statusAt(DateTimeImmutable $now): LicenceStatus
    {
        if (!$this->hasKey()) {
            return LicenceStatus::Unlicensed;
        }

        if ($this->isRevoked) {
            return LicenceStatus::Revoked;
        }

        // Checked before expiry: a licence that is both over its site limit and expired should
        // tell the owner the thing they can actually fix by deactivating another site.
        if (!$this->isActivatedHere && $this->activationCount >= $this->siteLimit) {
            return LicenceStatus::SiteLimitReached;
        }

        if ($this->expiresAt === null) {
            return LicenceStatus::Active;
        }

        if ($now <= $this->expiresAt) {
            return LicenceStatus::Active;
        }

        return $now <= $this->expiresAt->modify(sprintf('+%d days', self::GRACE_DAYS))
            ? LicenceStatus::Grace
            : LicenceStatus::Expired;
    }

    /** Whether another site can still be activated against this key. */
    public function hasSeatAvailable(): bool
    {
        return $this->isActivatedHere || $this->activationCount < $this->siteLimit;
    }

    public function seatsRemaining(): int
    {
        return max(0, $this->siteLimit - $this->activationCount);
    }

    /**
     * Whether the licence should be re-checked against the server.
     *
     * Daily. More often wastes a request on every site that has one; less often means a revoked
     * licence keeps receiving updates for a week.
     */
    public function isDueForCheck(DateTimeImmutable $now): bool
    {
        return $this->lastCheckedAt === null || $this->lastCheckedAt->modify('+1 day') <= $now;
    }

    public function activatedHere(DateTimeImmutable $now): self
    {
        return new self(
            $this->key,
            $this->expiresAt,
            $this->siteLimit,
            $this->isActivatedHere ? $this->activationCount : $this->activationCount + 1,
            $this->isRevoked,
            true,
            $now,
        );
    }

    public function deactivatedHere(DateTimeImmutable $now): self
    {
        return new self(
            $this->key,
            $this->expiresAt,
            $this->siteLimit,
            $this->isActivatedHere ? max(0, $this->activationCount - 1) : $this->activationCount,
            $this->isRevoked,
            false,
            $now,
        );
    }

    public function checkedAt(DateTimeImmutable $now): self
    {
        return new self(
            $this->key,
            $this->expiresAt,
            $this->siteLimit,
            $this->activationCount,
            $this->isRevoked,
            $this->isActivatedHere,
            $now,
        );
    }

    /**
     * A masked form for display.
     *
     * The full key appears in support tickets, screenshots and screen shares. Showing the last
     * four is enough to confirm which key is in place without publishing it.
     */
    public function maskedKey(): string
    {
        $trimmed = trim($this->key);

        if ($trimmed === '') {
            return '';
        }

        return strlen($trimmed) <= 4
            ? str_repeat('•', strlen($trimmed))
            : str_repeat('•', strlen($trimmed) - 4) . substr($trimmed, -4);
    }
}
