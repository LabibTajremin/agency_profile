<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

/**
 * When a lead stops being something the consultancy is allowed to keep.
 *
 * Expressed as pure arithmetic over timestamps so the sweep that deletes expired leads can be
 * tested without waiting two years for one to expire.
 */
final class RetentionPolicy
{
    private const SECONDS_PER_DAY = 86400;

    public function __construct(private readonly int $retentionDays)
    {
    }

    public static function forForm(FormDefinition $form): self
    {
        return new self($form->retentionDays);
    }

    public function expiresAt(int $capturedAtTimestamp): int
    {
        return $capturedAtTimestamp + ($this->retentionDays * self::SECONDS_PER_DAY);
    }

    public function hasExpired(int $capturedAtTimestamp, int $nowTimestamp): bool
    {
        return $nowTimestamp >= $this->expiresAt($capturedAtTimestamp);
    }

    public function daysRemaining(int $capturedAtTimestamp, int $nowTimestamp): int
    {
        $remaining = $this->expiresAt($capturedAtTimestamp) - $nowTimestamp;

        return $remaining <= 0 ? 0 : (int) ceil($remaining / self::SECONDS_PER_DAY);
    }
}
