<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Security;

/**
 * Which address a request came from.
 *
 * `REMOTE_ADDR` unless the site owner has explicitly said there is a proxy in front. Everything
 * else — `X-Forwarded-For`, `CF-Connecting-IP`, `X-Real-IP` — is a header the client itself
 * sends, and trusting one by default breaks the lockout in both directions at once: an attacker
 * changes a header per attempt and is never counted, and can also name somebody else's address
 * and have them locked out instead.
 *
 * When a proxy is declared, the left-most entry of the forwarded chain is used. That is the hop
 * closest to the client; reading the right-most gives you the proxy's own address, which is the
 * same for every visitor and turns the counter into a site-wide fuse.
 */
final class ClientIp
{
    private const FORWARDED_HEADERS = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
    ];

    /**
     * @param array<string, mixed> $server
     */
    public static function resolve(array $server, bool $trustProxy = false): string
    {
        if ($trustProxy) {
            $forwarded = self::fromForwardedHeaders($server);

            if ($forwarded !== '') {
                return $forwarded;
            }
        }

        return self::validate($server['REMOTE_ADDR'] ?? null);
    }

    /**
     * @param array<string, mixed> $server
     */
    private static function fromForwardedHeaders(array $server): string
    {
        foreach (self::FORWARDED_HEADERS as $header) {
            $value = $server[$header] ?? null;

            if (!is_string($value)) {
                continue;
            }

            foreach (explode(',', $value) as $hop) {
                $address = self::validate(trim($hop));

                if ($address !== '') {
                    return $address;
                }
            }
        }

        return '';
    }

    private static function validate(mixed $candidate): string
    {
        if (!is_string($candidate)) {
            return '';
        }

        return filter_var($candidate, FILTER_VALIDATE_IP) === false ? '' : $candidate;
    }
}
