<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Security;

/**
 * How the login shield is configured.
 *
 * Off by default, and that is not timidity. This module can lock the owner out of their own
 * site, so the state a plugin arrives in has to be the state that cannot do that; turning it on
 * is a deliberate act with a confirmation and a rescue email attached.
 *
 * Every value is coerced on the way in. A settings row can be edited by hand, restored from an
 * older dump, or written by another plugin, and a shield that reads `maxAttempts` as the string
 * "unlimited" and compares it numerically is a shield that locks nobody out.
 */
final class ShieldSettings
{
    public const DEFAULT_SLUG = 'secure-access';

    /** A day. Beyond this a lockout is indistinguishable from a ban, and bans need a person. */
    public const MAXIMUM_LOCKOUT_MINUTES = 1440;

    /**
     * @param list<string> $allowlistIps
     */
    private function __construct(
        public readonly bool $enabled,
        public readonly string $slug,
        public readonly bool $limitAttempts,
        public readonly int $maxAttempts,
        public readonly int $lockoutMinutes,
        public readonly bool $progressiveLockout,
        public readonly bool $honeypot,
        public readonly bool $disableXmlrpc,
        public readonly bool $hideLoginErrors,
        public readonly bool $emailAlerts,
        public readonly array $allowlistIps,
        public readonly bool $trustedProxy,
    ) {
    }

    public static function defaults(): self
    {
        return new self(
            false,
            self::DEFAULT_SLUG,
            true,
            5,
            15,
            true,
            true,
            true,
            true,
            true,
            [],
            false,
        );
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        $defaults = self::defaults();

        return new self(
            self::boolean($stored, 'enabled', $defaults->enabled),
            self::slug($stored, $defaults->slug),
            self::boolean($stored, 'limitAttempts', $defaults->limitAttempts),
            self::bounded($stored, 'maxAttempts', $defaults->maxAttempts, 1, 100),
            self::bounded($stored, 'lockoutMinutes', $defaults->lockoutMinutes, 1, self::MAXIMUM_LOCKOUT_MINUTES),
            self::boolean($stored, 'progressiveLockout', $defaults->progressiveLockout),
            self::boolean($stored, 'honeypot', $defaults->honeypot),
            self::boolean($stored, 'disableXmlrpc', $defaults->disableXmlrpc),
            self::boolean($stored, 'hideLoginErrors', $defaults->hideLoginErrors),
            self::boolean($stored, 'emailAlerts', $defaults->emailAlerts),
            self::addresses($stored['allowlistIps'] ?? null),
            self::boolean($stored, 'trustedProxy', $defaults->trustedProxy),
        );
    }

    /**
     * @return array<string, bool|int|list<string>|string>
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'slug' => $this->slug,
            'limitAttempts' => $this->limitAttempts,
            'maxAttempts' => $this->maxAttempts,
            'lockoutMinutes' => $this->lockoutMinutes,
            'progressiveLockout' => $this->progressiveLockout,
            'honeypot' => $this->honeypot,
            'disableXmlrpc' => $this->disableXmlrpc,
            'hideLoginErrors' => $this->hideLoginErrors,
            'emailAlerts' => $this->emailAlerts,
            'allowlistIps' => $this->allowlistIps,
            'trustedProxy' => $this->trustedProxy,
        ];
    }

    public function with(string $key, mixed $value): self
    {
        $values = $this->toArray();
        $values[$key] = $value;

        return self::fromArray($values);
    }

    public function allows(string $ip): bool
    {
        return in_array($ip, $this->allowlistIps, true);
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    private static function boolean(array $stored, string $key, bool $fallback): bool
    {
        return array_key_exists($key, $stored) ? (bool) $stored[$key] : $fallback;
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    private static function bounded(array $stored, string $key, int $fallback, int $minimum, int $maximum): int
    {
        $value = $stored[$key] ?? null;

        if (!is_numeric($value)) {
            return $fallback;
        }

        return max($minimum, min($maximum, (int) $value));
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    private static function slug(array $stored, string $fallback): string
    {
        $value = $stored['slug'] ?? null;

        return is_string($value) && $value !== '' ? $value : $fallback;
    }

    /**
     * @return list<string>
     */
    private static function addresses(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $addresses = [];

        foreach ($value as $candidate) {
            if (is_string($candidate) && filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
                $addresses[] = $candidate;
            }
        }

        return array_values(array_unique($addresses));
    }
}
