<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Licence;

/**
 * What a licence is currently worth.
 *
 * The critical property, encoded here rather than left to each caller to remember: **no status
 * disables the product**. A lapsed licence stops updates and support. Crippling a site somebody
 * paid for, months after they paid, is a refund and a public complaint, and it punishes the
 * person least able to fix it — the client, not the agency that stopped renewing.
 */
enum LicenceStatus: string
{
    case Unlicensed = 'unlicensed';
    case Active = 'active';
    case Grace = 'grace';
    case Expired = 'expired';
    case SiteLimitReached = 'site-limit-reached';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Unlicensed => 'No licence key',
            self::Active => 'Active',
            self::Grace => 'Expired — in the grace period',
            self::Expired => 'Expired',
            self::SiteLimitReached => 'Site limit reached',
            self::Revoked => 'Revoked',
        };
    }

    /** Whether automatic updates are delivered. */
    public function allowsUpdates(): bool
    {
        return $this === self::Active || $this === self::Grace;
    }

    /** Whether support is available. */
    public function allowsSupport(): bool
    {
        return $this === self::Active || $this === self::Grace;
    }

    /**
     * Whether the product works.
     *
     * Always. This method exists to make that a stated rule with a test behind it, rather than
     * an omission that a future change could quietly reverse.
     */
    public function allowsUse(): bool
    {
        return true;
    }

    /** Whether the site owner should be told something needs attention. */
    public function needsAttention(): bool
    {
        return $this !== self::Active;
    }

    /** How loudly. A nag on every screen for an unlicensed site is how people learn to ignore notices. */
    public function noticeUrgency(): string
    {
        return match ($this) {
            self::Active => 'none',
            self::Unlicensed, self::Grace => 'info',
            self::Expired, self::SiteLimitReached => 'warning',
            self::Revoked => 'error',
        };
    }
}
