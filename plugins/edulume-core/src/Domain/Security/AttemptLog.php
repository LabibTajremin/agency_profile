<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Security;

/**
 * A bounded record of failed logins.
 *
 * Hard-capped and trimmed on every write. An unbounded log stored in an option is a row that
 * grows forever and, if it is autoloaded, is read on every request the site ever serves — a
 * slow degradation nobody connects back to the security module that caused it. Two hundred
 * entries is enough to answer "is something happening right now", which is the only question
 * this log is for.
 */
final class AttemptLog
{
    public const MAXIMUM_ENTRIES = 200;

    /**
     * @param list<array{ip: string, username: string, at: string}> $entries
     */
    private function __construct(public readonly array $entries)
    {
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        $entries = [];

        foreach ($stored as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $entries[] = [
                'ip' => is_string($candidate['ip'] ?? null) ? $candidate['ip'] : '',
                'username' => is_string($candidate['username'] ?? null) ? $candidate['username'] : '',
                'at' => is_string($candidate['at'] ?? null) ? $candidate['at'] : '',
            ];
        }

        return new self(self::trim($entries));
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function with(string $ip, string $username, string $at): self
    {
        $entries = $this->entries;
        $entries[] = ['ip' => $ip, 'username' => $username, 'at' => $at];

        return new self(self::trim($entries));
    }

    public function count(): int
    {
        return count($this->entries);
    }

    /**
     * How many of the recorded failures came from one address.
     */
    public function failuresFrom(string $ip): int
    {
        return count(array_filter(
            $this->entries,
            static fn (array $entry): bool => $entry['ip'] === $ip
        ));
    }

    /**
     * @return list<array{ip: string, username: string, at: string}>
     */
    public function toArray(): array
    {
        return $this->entries;
    }

    /**
     * @param list<array{ip: string, username: string, at: string}> $entries
     * @return list<array{ip: string, username: string, at: string}>
     */
    private static function trim(array $entries): array
    {
        return array_values(array_slice($entries, -self::MAXIMUM_ENTRIES));
    }
}
