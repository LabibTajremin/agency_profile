<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Port;

use Edulume\Core\Domain\Lead\Lead;
use Edulume\Core\Domain\Lead\LeadQuery;

/**
 * Where leads live.
 *
 * `search()` takes a query object rather than loose arguments, which is what keeps every
 * caller paginated: there is no signature here that can express "all of them".
 */
interface LeadRepository
{
    public function save(Lead $lead, string $savedAt): int;

    public function find(int $id): ?Lead;

    /**
     * @return list<Lead>
     */
    public function search(LeadQuery $query): array;

    public function count(LeadQuery $query): int;

    public function delete(int $id): void;
}
