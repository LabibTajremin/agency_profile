<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Port;

/**
 * An optional third-party captcha.
 *
 * `isConfigured()` exists so the use case can tell "no captcha set up" apart from "captcha
 * said no". Treating the first as a failure would lock every visitor out of a site whose owner
 * never signed up for one.
 */
interface CaptchaVerifier
{
    public function isConfigured(): bool;

    public function verify(string $token, string $ipAddress): bool;
}
