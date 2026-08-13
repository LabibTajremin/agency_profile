<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Lead;

use Edulume\Core\Domain\Lead\Lead;
use Edulume\Core\Domain\Lead\LeadQuery;

/**
 * One page of the inbox, with enough context to render pagination without a second query.
 */
final class LeadPage
{
    /**
     * @param list<Lead> $leads
     */
    private function __construct(
        public readonly array $leads,
        public readonly int $totalCount,
        public readonly LeadQuery $query,
    ) {
    }

    /**
     * @param list<Lead> $leads
     */
    public static function of(array $leads, int $totalCount, LeadQuery $query): self
    {
        return new self($leads, max(0, $totalCount), $query);
    }

    public function pageCount(): int
    {
        return (int) max(1, ceil($this->totalCount / $this->query->perPage));
    }

    public function hasNextPage(): bool
    {
        return $this->query->page < $this->pageCount();
    }

    public function isEmpty(): bool
    {
        return $this->leads === [];
    }
}
