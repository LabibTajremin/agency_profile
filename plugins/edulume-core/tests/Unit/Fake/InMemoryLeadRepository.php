<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Fake;

use Edulume\Core\Application\Port\LeadRepository;
use Edulume\Core\Domain\Lead\Lead;
use Edulume\Core\Domain\Lead\LeadQuery;

/** Lead storage in an array, honouring the same filters and pagination as the real one. */
final class InMemoryLeadRepository implements LeadRepository
{
    /** @var array<int, Lead> */
    private array $leads = [];

    private int $nextId = 1;

    public function save(Lead $lead, string $savedAt): int
    {
        $id = $lead->id > 0 ? $lead->id : $this->nextId++;

        $stored = $lead->toArray();
        $stored['id'] = $id;

        $this->leads[$id] = Lead::fromArray($stored);

        return $id;
    }

    public function find(int $id): ?Lead
    {
        return $this->leads[$id] ?? null;
    }

    public function search(LeadQuery $query): array
    {
        return array_slice($this->matching($query), $query->offset(), $query->perPage);
    }

    public function count(LeadQuery $query): int
    {
        return count($this->matching($query));
    }

    /**
     * Counting must ignore pagination, exactly as a SQL COUNT(*) does; a fake that counts one
     * page is a fake that hides a broken pager.
     *
     * @return list<Lead>
     */
    private function matching(LeadQuery $query): array
    {
        $matching = [];

        foreach ($this->leads as $lead) {
            if ($query->status !== null && $lead->status !== $query->status) {
                continue;
            }

            if ($query->assignedToId !== null && $lead->assignedToId !== $query->assignedToId) {
                continue;
            }

            if ($query->branchId !== null && $lead->branchId !== $query->branchId) {
                continue;
            }

            if ($query->searchTerm !== '' && !str_contains($lead->name . $lead->email, $query->searchTerm)) {
                continue;
            }

            $matching[] = $lead;
        }

        return $matching;
    }

    public function delete(int $id): void
    {
        unset($this->leads[$id]);
    }

    /**
     * @return list<Lead>
     */
    public function all(): array
    {
        return array_values($this->leads);
    }
}
