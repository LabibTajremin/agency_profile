<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

/**
 * A filtered, paginated request for leads.
 *
 * Pagination is not optional: `perPage` is clamped rather than nullable, so there is no way
 * to express "give me every lead" and no way for a ten-thousand-row inbox to try.
 */
final class LeadQuery
{
    public const MAXIMUM_PER_PAGE = 200;
    public const DEFAULT_PER_PAGE = 25;

    private function __construct(
        public readonly ?LeadStatus $status,
        public readonly ?int $assignedToId,
        public readonly ?int $branchId,
        public readonly string $searchTerm,
        public readonly int $page,
        public readonly int $perPage,
        public readonly string $sortColumn,
        public readonly bool $isDescending,
    ) {
    }

    public static function all(): self
    {
        return new self(null, null, null, '', 1, self::DEFAULT_PER_PAGE, 'created_at', true);
    }

    public static function of(
        ?LeadStatus $status = null,
        ?int $assignedToId = null,
        ?int $branchId = null,
        string $searchTerm = '',
        int $page = 1,
        int $perPage = self::DEFAULT_PER_PAGE,
        string $sortColumn = 'created_at',
        bool $isDescending = true
    ): self {
        return new self(
            $status,
            $assignedToId === null ? null : max(0, $assignedToId),
            $branchId === null ? null : max(0, $branchId),
            trim($searchTerm),
            max(1, $page),
            min(self::MAXIMUM_PER_PAGE, max(1, $perPage)),
            $sortColumn,
            $isDescending,
        );
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function forPage(int $page): self
    {
        return new self(
            $this->status,
            $this->assignedToId,
            $this->branchId,
            $this->searchTerm,
            max(1, $page),
            $this->perPage,
            $this->sortColumn,
            $this->isDescending,
        );
    }

    /**
     * A counsellor sees only their own leads, whatever the request asked for.
     */
    public function scopedToCounsellor(int $counsellorId): self
    {
        return new self(
            $this->status,
            max(0, $counsellorId),
            $this->branchId,
            $this->searchTerm,
            $this->page,
            $this->perPage,
            $this->sortColumn,
            $this->isDescending,
        );
    }
}
