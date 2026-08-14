<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Fake;

use Edulume\Core\Application\Port\CaptchaVerifier;

/** A captcha that is either absent, or present and predetermined. */
final class StubCaptchaVerifier implements CaptchaVerifier
{
    private function __construct(
        private readonly bool $isConfigured,
        private readonly bool $verdict,
    ) {
    }

    public static function notConfigured(): self
    {
        return new self(false, false);
    }

    public static function configured(bool $verdict): self
    {
        return new self(true, $verdict);
    }

    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    public function verify(string $token, string $ipAddress): bool
    {
        return $this->verdict;
    }
}
