<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Port;

/**
 * The finders' read side.
 *
 * Separate from `ContentReader`, which exists for the CSV export and speaks in mapped rows. The
 * finder wants display data — title, URL, thumbnail — and giving the two the same port would
 * force one of them to translate on every row for the benefit of the other.
 */
interface FinderRepository
{
    /**
     * @param array<string, mixed> $filters
     *
     * @return list<array<string, mixed>>
     */
    public function find(string $postTypeKey, array $filters): array;

    /**
     * The total matching the filters, ignoring pagination — what the pager needs.
     *
     * @param array<string, mixed> $filters
     */
    public function count(string $postTypeKey, array $filters): int;
}
