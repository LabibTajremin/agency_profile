<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use Edulume\Core\Domain\Support\Guard;

/**
 * The last ten snapshots, newest first.
 *
 * Bounded on purpose. An unbounded history of full settings blobs is a row in wp_options that
 * grows until an autoloaded query starts timing out, and nobody restores from a snapshot taken
 * four hundred saves ago anyway.
 */
final class SnapshotHistory
{
    public const MAXIMUM_SNAPSHOTS = 10;

    /**
     * @param list<SettingsSnapshot> $snapshots
     */
    private function __construct(private readonly array $snapshots)
    {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        $snapshots = [];

        foreach ($stored as $entry) {
            $snapshots[] = SettingsSnapshot::fromArray(Guard::toArray($entry));
        }

        return new self(array_slice($snapshots, 0, self::MAXIMUM_SNAPSHOTS));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(static fn (SettingsSnapshot $snapshot): array => $snapshot->toArray(), $this->snapshots);
    }

    public function with(SettingsSnapshot $snapshot): self
    {
        return new self(array_slice([$snapshot, ...$this->snapshots], 0, self::MAXIMUM_SNAPSHOTS));
    }

    /**
     * @return list<SettingsSnapshot>
     */
    public function all(): array
    {
        return $this->snapshots;
    }

    public function count(): int
    {
        return count($this->snapshots);
    }

    public function isEmpty(): bool
    {
        return $this->snapshots === [];
    }

    public function mostRecent(): ?SettingsSnapshot
    {
        return $this->snapshots[0] ?? null;
    }

    public function at(int $index): ?SettingsSnapshot
    {
        return $this->snapshots[$index] ?? null;
    }
}
