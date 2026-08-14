<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

/**
 * How much of the inbox a role is allowed to see.
 *
 * Applied to the query rather than to the rendered list. Filtering after fetching means the
 * rows still crossed the boundary, and the count at the top of the screen still tells a
 * counsellor how many leads exist that they are not allowed to read.
 */
enum LeadScope: string
{
    case Everything = 'everything';
    case OwnBranch = 'own-branch';
    case OwnAssignments = 'own-assignments';
    case Nothing = 'nothing';

    public function apply(LeadQuery $query, int $userId, int $branchId): LeadQuery
    {
        return match ($this) {
            self::Everything => $query,
            self::OwnAssignments => $query->scopedToCounsellor($userId),
            self::OwnBranch => LeadQuery::of(
                $query->status,
                $query->assignedToId,
                $branchId,
                $query->searchTerm,
                $query->page,
                $query->perPage,
                $query->sortColumn,
                $query->isDescending,
            ),
            self::Nothing => $query->scopedToCounsellor(0),
        };
    }

    public function seesAnything(): bool
    {
        return $this !== self::Nothing;
    }
}
