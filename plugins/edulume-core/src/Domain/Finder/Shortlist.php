<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Finder;

/**
 * A visitor's saved items.
 *
 * Held in `localStorage`, with no login. Requiring an account to save a course is the single
 * fastest way to lose the visitor who was about to become a lead — and it means the shortlist
 * costs nothing to store and nothing to erase under a data request.
 *
 * This class is the shared contract for the shape: the browser writes it, the server reads it
 * when a lead is submitted so the enquiry arrives with what the person was actually looking at.
 */
final class Shortlist
{
    /** Bounded so a script cannot fill a visitor's storage quota through the site. */
    public const MAXIMUM = 50;

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
     * Toggling rather than separate add and remove: the UI is one heart icon, and two methods
     * would let the two drift into disagreeing about what the icon means.
     */
    public function toggle(int $itemId): self
    {
        if ($itemId <= 0) {
            return $this;
        }

        return $this->contains($itemId)
            ? new self(array_values(array_filter($this->itemIds, static fn (int $id): bool => $id !== $itemId)))
            : self::of([...$this->itemIds, $itemId]);
    }

    public function contains(int $itemId): bool
    {
        return in_array($itemId, $this->itemIds, true);
    }

    public function count(): int
    {
        return count($this->itemIds);
    }

    public function isEmpty(): bool
    {
        return $this->itemIds === [];
    }

    public function toJson(): string
    {
        return (string) json_encode($this->itemIds);
    }

    /**
     * Reads what the browser stored, treating anything unrecognisable as an empty shortlist.
     *
     * Storage is written by a previous version of the site, by another tab, and occasionally by
     * a browser extension — so this parses defensively rather than trusting its own format.
     */
    public static function fromJson(string $json): self
    {
        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return self::empty();
        }

        $ids = [];

        foreach ($decoded as $candidate) {
            if (is_int($candidate) || (is_string($candidate) && ctype_digit($candidate))) {
                $ids[] = (int) $candidate;
            }
        }

        return self::of($ids);
    }
}
