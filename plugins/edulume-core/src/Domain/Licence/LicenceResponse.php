<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Licence;

use DateTimeImmutable;

/**
 * What the licence server said.
 *
 * `$reachable` is separate from `$isValid` on purpose. "The server said no" and "we could not
 * ask" are different facts, and collapsing them is how an outage at the vendor turns into every
 * customer's site reporting an invalid licence.
 */
final class LicenceResponse
{
    public function __construct(
        public readonly bool $reachable,
        public readonly bool $isValid = false,
        public readonly ?DateTimeImmutable $expiresAt = null,
        public readonly int $siteLimit = 1,
        public readonly int $activationCount = 0,
        public readonly bool $isRevoked = false,
        public readonly string $message = '',
    ) {
    }

    public static function unreachable(string $message = 'Could not reach the licence server.'): self
    {
        return new self(false, message: $message);
    }

    public static function invalid(string $message): self
    {
        return new self(true, false, message: $message);
    }

    /**
     * Whether this response should overwrite what the site already believes.
     *
     * Only a reachable one. An unreachable server leaves the stored licence exactly as it was,
     * which is what keeps updates flowing through a vendor outage.
     */
    public function shouldReplaceStoredLicence(): bool
    {
        return $this->reachable;
    }
}
