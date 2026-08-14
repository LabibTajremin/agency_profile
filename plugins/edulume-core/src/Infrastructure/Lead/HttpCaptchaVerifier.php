<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Lead;

use Edulume\Core\Application\Port\CaptchaVerifier;

/**
 * reCAPTCHA, hCaptcha and Turnstile, which share a verification shape closely enough that three
 * classes would be three copies of the same twenty lines.
 *
 * A verification that cannot reach the provider **passes**. That is deliberate and is the
 * opposite of the usual instinct: the honeypot, the time trap and the rate limiter all still
 * apply, so failing open costs a little spam, while failing closed means an outage at Cloudflare
 * silently stops every enquiry on every site — and nobody finds out until they wonder why the
 * phone stopped ringing.
 */
final class HttpCaptchaVerifier implements CaptchaVerifier
{
    private const ENDPOINTS = [
        'recaptcha' => 'https://www.google.com/recaptcha/api/siteverify',
        'hcaptcha' => 'https://api.hcaptcha.com/siteverify',
        'turnstile' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    ];

    public function __construct(
        private readonly string $provider,
        private readonly string $secret,
    ) {
    }

    public function isConfigured(): bool
    {
        return trim($this->secret) !== '' && array_key_exists($this->provider, self::ENDPOINTS);
    }

    public function verify(string $token, string $ipAddress): bool
    {
        if (!$this->isConfigured()) {
            return true;
        }

        if (trim($token) === '') {
            return false;
        }

        $response = wp_remote_post(self::ENDPOINTS[$this->provider], [
            'timeout' => 5,
            'body' => [
                'secret' => $this->secret,
                'response' => $token,
                'remoteip' => $ipAddress,
            ],
        ]);

        if (is_wp_error($response)) {
            return true;
        }

        $decoded = json_decode((string) wp_remote_retrieve_body($response), true);

        return is_array($decoded) && ($decoded['success'] ?? false) === true;
    }
}
