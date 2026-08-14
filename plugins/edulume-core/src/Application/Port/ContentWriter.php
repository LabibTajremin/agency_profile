<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Port;

use Edulume\Core\Domain\Content\CsvMappedRow;

/**
 * Writes an imported row into storage, without saying where storage is.
 */
interface ContentWriter
{
    /**
     * Creates or updates the item this row describes, and returns its identifier.
     *
     * Matching an existing item by slug or title is the implementation's business; what the
     * use case guarantees is that importing the same file twice does not double the catalogue.
     */
    public function upsert(string $postTypeKey, CsvMappedRow $row): int;
}
