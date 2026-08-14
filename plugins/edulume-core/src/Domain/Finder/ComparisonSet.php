<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Finder;

/**
 * The two-to-four items a visitor is comparing.
 *
 * Bounded at four because a comparison table wider than four columns stops being readable on
 * anything smaller than a laptop, and this audience is overwhelmingly on a phone.
 */
final class ComparisonSet
{
    public const MINIMUM = 2;
    public const MAXIMUM = 4;

    /**
     * @param list<int> $itemIds
     */
    private function __construct(public readonly array $itemIds)
    {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @param list<int> $itemIds
     */
    public static function of(array $itemIds): self
    {
        $unique = [];

        foreach ($itemIds as $id) {
            if ($id > 0 && !in_array($id, $unique, true)) {
                $unique[] = $id;
            }
        }

        return new self(array_slice($unique, 0, self::MAXIMUM));
    }

    /**
     * Adding beyond the cap is a no-op rather than an error, and beyond it the UI disables the
     * control — silently dropping the oldest would lose an item the visitor deliberately chose.
     */
    public function add(int $itemId): self
    {
        if ($itemId <= 0 || $this->isFull() || $this->contains($itemId)) {
            return $this;
        }

        return new self([...$this->itemIds, $itemId]);
    }

    public function remove(int $itemId): self
    {
        return new self(array_values(array_filter(
            $this->itemIds,
            static fn (int $id): bool => $id !== $itemId,
        )));
    }

    public function contains(int $itemId): bool
    {
        return in_array($itemId, $this->itemIds, true);
    }

    public function isFull(): bool
    {
        return count($this->itemIds) >= self::MAXIMUM;
    }

    /** Whether there is enough here to render a comparison at all. */
    public function isComparable(): bool
    {
        return count($this->itemIds) >= self::MINIMUM;
    }

    public function count(): int
    {
        return count($this->itemIds);
    }

    /** The shareable form: a stable, comma-separated list. */
    public function toParameter(): string
    {
        return implode(',', $this->itemIds);
    }

    public static function fromParameter(string $parameter): self
    {
        $ids = [];

        foreach (explode(',', $parameter) as $candidate) {
            $trimmed = trim($candidate);

            if (ctype_digit($trimmed)) {
                $ids[] = (int) $trimmed;
            }
        }

        return self::of($ids);
    }
}
